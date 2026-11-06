<?php

namespace App\Services;

use App\Models\ProcurementRequest;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TaxEvaluationService
{
    /**
     * @return array{status:string, rules:array<string,?int>, rates:array<string,?float>, basis:string, context:array<string,mixed>}
     */
    public function evaluate(ProcurementRequest $request, array $context = []): array
    {
        $context = array_merge(
            $this->defaultContext($request),
            array_intersect_key($context, array_flip(['buyer_type', 'pkp_status', 'transaction_type', 'item_type', 'funding_source']))
        );
        $context['subsidiary_id'] = $request->subsidiary_id;

        $matchedRules = [];
        $rates = [];
        $missing = [];
        $matchedIds = [];
        $conflicts = [];
        $invalid = [];

        if (! $request->hasSubsidiary() || ! $request->subsidiary?->isActive()) {
            return [
                'status' => 'NEEDS_REVIEW',
                'rules' => array_fill_keys([TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23], null),
                'rates' => array_fill_keys([TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23], null),
                'basis' => 'Anak Perusahaan aktif belum ditetapkan; konfigurasi pajak tidak dapat dievaluasi.',
                'context' => $context,
            ];
        }

        foreach ([TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23] as $taxType) {
            $matches = $this->resolveMatchingRules($taxType, $context);
            if (count($matches) === 0) {
                $matchedRules[$taxType] = null;
                $rates[$taxType] = null;
                $missing[] = $taxType;
            } elseif (count($matches) > 1) {
                $matchedRules[$taxType] = null;
                $rates[$taxType] = null;
                $conflicts[] = $taxType.' ('.count($matches).' aturan aktif cocok)';
            } else {
                $rule = $matches->first();
                if (! $this->isEligibleRule($rule)) {
                    $matchedRules[$taxType] = null;
                    $rates[$taxType] = null;
                    $invalid[] = $taxType.' (aturan aktif tidak memiliki persetujuan, verifikasi, dasar hukum, atau tarif valid)';
                } else {
                    $matchedRules[$taxType] = $rule->id;
                    $rates[$taxType] = (float) $rule->rate;
                    $matchedIds[] = $rule->id;
                }
            }
        }

        if ($missing !== [] || $conflicts !== [] || $invalid !== []) {
            $reasons = [];
            if ($missing !== []) {
                $reasons[] = 'Tidak ditemukan aturan pajak aktif yang cocok untuk: '.implode(', ', $missing).'.';
            }
            if ($conflicts !== []) {
                $reasons[] = 'Konflik aturan pajak aktif untuk: '.implode(', ', $conflicts).'.';
            }
            if ($invalid !== []) {
                $reasons[] = 'Aturan pajak belum memenuhi validasi untuk: '.implode(', ', $invalid).'.';
            }

            return [
                'status' => 'NEEDS_REVIEW',
                'rules' => $matchedRules,
                'rates' => $rates,
                'basis' => implode(' ', $reasons).' Status NEEDS_REVIEW.',
                'context' => $context,
            ];
        }

        return [
            'status' => 'RESOLVED',
            'rules' => $matchedRules,
            'rates' => $rates,
            'basis' => 'Evaluasi otomatis berdasarkan aturan pajak aktif: '.implode(', ', $matchedIds).'.',
            'context' => $context,
        ];
    }

    public function evaluateAndApply(ProcurementRequest $request, User $actor, array $context = []): array
    {
        if (! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Admin Pusat yang dapat mengevaluasi konfigurasi pajak.');
        }

        $result = DB::transaction(function () use ($request, $actor, $context): array {
            $lockedRequest = ProcurementRequest::query()->lockForUpdate()->findOrFail($request->id);
            TaxRule::query()
                ->active()
                ->whereIn('tax_type', [TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23])
                ->lockForUpdate()
                ->get();

            $evaluation = $this->evaluate($lockedRequest, $context);
            $lockedRequest->applyTaxEvaluation($this, $actor, $evaluation);

            return $evaluation;
        });

        $request->refresh();

        return $result;
    }

    public function isCurrentEvaluation(ProcurementRequest $request, array $evaluation): bool
    {
        $context = $evaluation['context'] ?? [];
        $current = $this->evaluate($request, $context);

        return $current['status'] === ($evaluation['status'] ?? null)
            && $current['rules'] === ($evaluation['rules'] ?? null)
            && $current['rates'] === ($evaluation['rates'] ?? null)
            && $current['basis'] === ($evaluation['basis'] ?? null);
    }

    private function resolveMatchingRules(string $taxType, array $context): \Illuminate\Support\Collection
    {
        return TaxRule::query()
            ->active()
            ->forTaxType($taxType)
            ->orderByDesc('version')
            ->get()
            ->filter(fn (TaxRule $rule): bool => $rule->matches($context));
    }

    private function isEligibleRule(TaxRule $rule): bool
    {
        return $rule->approved_by !== null
            && $rule->approved_at !== null
            && $rule->verified_by !== null
            && $rule->verified_at !== null
            && trim((string) $rule->legal_basis) !== ''
            && $rule->rate !== null
            && (float) $rule->rate >= 0
            && (float) $rule->rate <= 100
            && $rule->calculation_method === 'percentage';
    }

    private function defaultContext(ProcurementRequest $request): array
    {
        return [
            'subsidiary_id' => $request->subsidiary_id,
            'buyer_type' => null,
            'pkp_status' => null,
            'transaction_type' => $request->packageCategory?->name,
            'item_type' => null,
            'funding_source' => $request->fundingSource?->name,
        ];
    }
}
