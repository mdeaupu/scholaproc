<?php

namespace App\Services;

use App\Models\DocumentNumberSequence;
use App\Models\ProcurementRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementNumberGenerator
{
    public function generate(ProcurementRequest $procurement, string $documentType): string
    {
        return DB::transaction(function () use ($procurement, $documentType) {
            $sequence = DocumentNumberSequence::lockForUpdate()
                ->firstOrCreate([
                    'school_id' => $procurement->school_id,
                    'budget_year_id' => $procurement->budget_year_id,
                    'document_type' => $documentType,
                ]);

            $number = $sequence->nextNumber();
            $paddedNumber = str_pad($number, 3, '0', STR_PAD_LEFT);

            return $this->formatNumber($procurement, $documentType, $paddedNumber);
        });
    }

    private function formatNumber(ProcurementRequest $procurement, string $type, string $seq): string
    {
        $code = match ($type) {
            'cover' => 'COV',
            'planning' => 'HPS',
            'negotiation' => 'BA-NEGO',
            'purchase_order' => 'SPK',
            'inspection' => 'BA-HP',
            'bast' => 'BAST',
            'invoice' => 'INV',
            'receipt' => 'KW',
            'supplier_declaration' => 'IDENTITAS-PENYEDIA',
            default => 'DOC',
        };

        $supplierCode = Str::slug($procurement->supplier->company_name ?? 'SUPPLIER', '-');
        $year = date('Y');

        return "{$seq}/{$supplierCode}/{$code}/{$year}";
    }
}
