<?php

namespace App\Services;

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class DocumentGenerationService
{
    private const TYPES = [
        'cover',
        'planning',
        'negotiation',
        'purchase_order',
        'delivery',
        'inspection',
        'bast',
        'invoice',
        'receipt',
        'supplier_declaration',
    ];

    public function issue(
        ProcurementRequest $request,
        string $type,
        User $actor,
        array $stageData = []
    ): ProcurementDocument {
        if (! in_array($type, self::TYPES, true)) {
            throw new LogicException('Jenis dokumen tidak dikenal.');
        }

        return DB::transaction(function () use ($request, $type, $actor, $stageData): ProcurementDocument {
            $request = ProcurementRequest::query()
                ->with(['school', 'packageCategory', 'budgetYear', 'fundingSource', 'items.unit', 'subsidiary', 'signatories'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->assertStage($request, $type, $stageData);

            $previous = $request->documents()
                ->where('document_type', $type)
                ->orderByDesc('version')
                ->first();
            if ($previous?->issued_at !== null) {
                if (! in_array($type, ['bast', 'delivery', 'inspection'], true)) {
                    throw new LogicException('Dokumen final sudah terbit dan tidak boleh ditimpa.');
                }

                $stageKey = $type === 'delivery' ? 'delivery_id' : 'receipt_id';
                $priorStageId = data_get($previous->document_snapshot, 'stage.'.$stageKey);
                if (($stageData[$stageKey] ?? null) === $priorStageId) {
                    throw new LogicException('Dokumen untuk tahap ini sudah diterbitkan.');
                }
            }

            $version = ((int) ($previous?->version ?? 0)) + 1;
            $identity = $request->subsidiary_snapshot
                ?? $request->subsidiary?->documentIdentitySnapshot();
            if (! $identity) {
                throw new LogicException('Snapshot identitas Anak Perusahaan tidak tersedia.');
            }
            if (in_array($type, ['purchase_order', 'bast', 'invoice'], true)) {
                $this->assertLetterheadExists($identity);
            }
            if ($type === 'bast') {
                $purchaseOrder = $request->documents()
                    ->where('document_type', 'purchase_order')
                    ->whereNotNull('issued_at')
                    ->orderByDesc('version')
                    ->first();

                if (! $purchaseOrder) {
                    throw new LogicException('BAST memerlukan snapshot surat pesanan yang telah diterbitkan.');
                }

                $stageData['purchase_order'] = [
                    'number' => $purchaseOrder->document_number,
                    'date' => $purchaseOrder->document_date?->toDateString(),
                ];
                $stageData['signatories'] = $request->signatories
                    ->map(fn ($signatory): array => [
                        'role' => $signatory->role,
                        'name' => $signatory->name,
                        'nip' => $signatory->nip,
                        'title' => $signatory->title,
                    ])
                    ->values()
                    ->all();
            }

            $snapshot = [
                'document_type' => $type,
                'version' => $version,
                'generated_at' => now()->toIso8601String(),
                'school' => [
                    'name' => $request->school?->name,
                    'npsn' => $request->school?->npsn,
                    'address' => $request->school?->address,
                ],
                'subsidiary' => $identity,
                'transaction' => [
                    'uuid' => $request->uuid,
                    'status' => $request->status,
                    'budget_year' => $request->budgetYear?->name,
                    'package_category' => $request->packageCategory?->name,
                    'funding_source' => $request->fundingSource?->name,
                    'items' => $request->price_snapshot['items'] ?? [],
                    'subtotal' => $request->subtotal,
                    'tax' => $request->tax_snapshot,
                    'tax_amount' => $request->tax_amount,
                    'grand_total' => $request->grand_total,
                ],
                'stage' => $stageData,
            ];

            $document = $request->documents()->create([
                'document_type' => $type,
                'document_number' => $this->number($request, $type, $version),
                'document_date' => now()->toDateString(),
                'version' => $version,
                'issued_by' => $actor->id,
                'document_snapshot' => $snapshot,
            ]);
            $document->issue();

            return $document->refresh();
        });
    }

    public function viewFor(ProcurementDocument $document): ?string
    {
        return match ($document->document_type) {
            'purchase_order' => 'documents.surat-pesanan',
            'bast' => 'documents.bast',
            'invoice' => 'documents.invoice',
            default => null,
        };
    }

    /**
     * @return array{document:ProcurementDocument, snapshot:array, subsidiary:array, letterhead:array}
     */
    public function viewData(ProcurementDocument $document): array
    {
        $snapshot = $document->document_snapshot ?? [];
        $identity = $document->subsidiary_snapshot
            ?? data_get($snapshot, 'subsidiary');

        if (! is_array($identity)) {
            throw new LogicException('Snapshot identitas Anak Perusahaan tidak tersedia pada dokumen.');
        }

        $this->assertLetterheadExists($identity);
        $path = $identity['letterhead_path'];
        $disk = Storage::disk('public');
        $mimeType = $disk->mimeType($path);

        if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new LogicException('Berkas kop surat pada snapshot bukan PNG atau JPEG yang didukung.');
        }

        $letterhead = [
            'path' => $path,
            'url' => $identity['letterhead_url'] ?? $disk->url($path),
            'src' => 'data:'.$mimeType.';base64,'.base64_encode($disk->get($path)),
        ];

        return [
            'document' => $document,
            'snapshot' => $snapshot,
            'subsidiary' => $identity,
            'letterhead' => $letterhead,
        ];
    }

    private function assertStage(ProcurementRequest $request, string $type, array $stageData): void
    {
        if (! $request->hasSubsidiary()) {
            throw new LogicException('Dokumen resmi memerlukan Anak Perusahaan yang ditetapkan.');
        }

        $allowed = match ($type) {
            'purchase_order' => $request->isTotalsLocked()
                && in_array($request->status, [
                    ProcurementRequest::STATUS_PRICE_AGREED,
                    ProcurementRequest::STATUS_PROCESSING,
                ], true),
            'delivery' => in_array($request->status, [
                ProcurementRequest::STATUS_PROCESSING,
                ProcurementRequest::STATUS_PARTIALLY_RECEIVED,
                ProcurementRequest::STATUS_DISPUTED,
            ], true)
                && isset($stageData['delivery_id']),
            'negotiation' => in_array($request->status, [
                ProcurementRequest::STATUS_NEGOTIATION,
                ProcurementRequest::STATUS_PRICE_AGREED,
            ], true),
            'planning' => in_array($request->status, [
                ProcurementRequest::STATUS_VERIFIED,
                ProcurementRequest::STATUS_NEGOTIATION,
                ProcurementRequest::STATUS_PRICE_AGREED,
                ProcurementRequest::STATUS_PROCESSING,
            ], true),
            'bast', 'inspection' => isset($stageData['receipt_id'])
                && $request->receipts()->whereKey($stageData['receipt_id'])->whereNotNull('verified_at')->exists(),
            'invoice' => $request->invoice()->exists()
                && $request->receipts()->whereNotNull('verified_at')->exists(),
            'receipt' => $request->payment()->whereNotNull('confirmed_at')->exists(),
            default => true,
        };

        if (! $allowed) {
            throw new LogicException("Dokumen {$type} belum memenuhi tahap penerbitan yang dipersyaratkan.");
        }

        if ($type === 'invoice' && ! $request->isTaxConfigurationValidated()) {
            throw new LogicException('Invoice tidak dapat diterbitkan sebelum konfigurasi pajak tervalidasi.');
        }
    }

    private function assertLetterheadExists(array $identity): void
    {
        $path = $identity['letterhead_path'] ?? null;
        $disk = Storage::disk('public');

        if (! is_string($identity['name'] ?? null)
            || trim($identity['name']) === ''
            || ! is_string($identity['npwp'] ?? null)
            || trim($identity['npwp']) === ''
            || ! is_string($identity['director_name'] ?? null)
            || trim($identity['director_name']) === '') {
            throw new LogicException('Snapshot Anak Perusahaan tidak memiliki nama, NPWP, dan direktur yang lengkap.');
        }

        if (! is_string($path) || $path === '' || ! $disk->exists($path)) {
            throw new LogicException(
                'Berkas kop surat tidak tersedia pada snapshot Anak Perusahaan; unggah dan tetapkan ulang identitas sebelum menerbitkan dokumen.'
            );
        }

        if (! in_array($disk->mimeType($path), ['image/jpeg', 'image/png'], true)) {
            throw new LogicException('Berkas kop surat pada snapshot bukan PNG atau JPEG yang didukung.');
        }
    }

    private function number(ProcurementRequest $request, string $type, int $version): string
    {
        $code = strtoupper(match ($type) {
            'purchase_order' => 'SP',
            'delivery' => 'KIRIM',
            'bast' => 'BAST',
            'inspection' => 'PEMERIKSAAN',
            'invoice' => 'INV',
            'receipt' => 'KW',
            default => str_replace('_', '-', $type),
        });
        $year = now()->format('Y');
        $versionSuffix = $version > 1 ? '-V'.$version : '';

        return "{$code}/{$request->id}/{$year}{$versionSuffix}";
    }
}
