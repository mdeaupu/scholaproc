<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProcurementDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'document_type',
        'document_number',
        'document_date',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function generateNumber(string $sequence): string
    {
        $year = date('Y');
        $code = match ($this->document_type) {
            'cover' => 'COV',
            'planning' => 'HPS',
            'negotiation' => 'BA-NEGO',
            'purchase_order' => 'SPK',
            'inspection' => 'BA-HP',
            'bast' => 'BAST',
            'invoice' => 'INV',
            'receipt' => 'KW',
            default => 'DOC'
        };

        $supplierName = $this->procurementRequest->supplier->company_name ?? 'SUPPLIER';
        $supplierCode = Str::slug($supplierName, '-');

        return "{$sequence}/{$supplierCode}/{$code}/{$year}";
    }

    public function isComplete(): bool
    {
        return !empty($this->document_number) && !empty($this->document_date);
    }
}
