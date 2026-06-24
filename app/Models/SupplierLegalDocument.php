<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLegalDocument extends Model
{
    protected $fillable = [
        'supplier_id',
        'document_type',
        'document_number',
        'document_date',
        'notary_name',
        'issuer',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
