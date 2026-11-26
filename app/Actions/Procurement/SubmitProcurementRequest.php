<?php

namespace App\Actions\Procurement;

use App\Models\ProcurementRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubmitProcurementRequest
{
    public function handle(ProcurementRequest $request, User $user): ProcurementRequest
    {
        abort_unless(
            $user->isSchool() && $user->school_id === $request->school_id,
            403
        );

        return DB::transaction(function () use ($request, $user): ProcurementRequest {
            $request = ProcurementRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            abort_unless($user->school_id === $request->school_id, 403);

            if (! in_array($request->status, [
                ProcurementRequest::STATUS_DRAFT,
                ProcurementRequest::STATUS_NEEDS_REVISION,
            ], true)) {
                throw new Exception('Hanya draf atau pengajuan yang perlu revisi yang dapat dikirim.');
            }

            $items = $request->items()
                ->get(['item_name', 'specification', 'unit_id', 'quantity', 'estimated_price'])
                ->map(fn ($item): array => [
                    'item_name' => trim((string) $item->item_name),
                    'specification' => $item->specification,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'estimated_price' => $item->estimated_price,
                ])
                ->all();

            Validator::make([
                'package_category_id' => $request->package_category_id,
                'budget_year_id' => $request->budget_year_id,
                'funding_source_id' => $request->funding_source_id,
                'items' => $items,
            ], [
                'package_category_id' => [
                    'required',
                    'integer',
                    Rule::exists('package_categories', 'id')->where('is_active', true),
                ],
                'budget_year_id' => [
                    'required',
                    'integer',
                    Rule::exists('budget_years', 'id')->where('is_active', true),
                ],
                'funding_source_id' => [
                    'required',
                    'integer',
                    Rule::exists('funding_sources', 'id')->where('is_active', true),
                    Rule::exists('funding_source_school', 'funding_source_id')
                        ->where('school_id', $request->school_id),
                ],
                'items' => [
                    'required',
                    'array',
                    'min:'.ProcurementRequest::MINIMUM_ITEMS,
                ],
                'items.*.item_name' => ['required', 'string', 'max:255'],
                'items.*.specification' => ['required', 'string', 'max:1000', 'not_in:-'],
                'items.*.unit_id' => [
                    'required',
                    'integer',
                    Rule::exists('item_units', 'id')->where('is_active', true),
                ],
                'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
                'items.*.estimated_price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:9999999999999.99',
                ],
            ])->validate();

            $request->submit($user);

            return $request->refresh();
        });
    }
}
