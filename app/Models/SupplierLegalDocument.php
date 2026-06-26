<?php

namespace App\Models;

use App\Enums\DocumentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLegalDocument extends Model
{
    use HasFactory;
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
            'valid_until' => 'date',
            'document_type' => DocumentType::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isExpired(): bool
    {
        if (is_null($this->valid_until)) {
            return false;
        }

        return Carbon::parse($this->valid_until)->isPast();
    }

    public function isBusinessPermit(): bool
    {
        return $this->document_type === DocumentType::BUSINESS_PERMIT;
    }

    public function isDeed(): bool
    {
        return $this->document_type === DocumentType::DEED;
    }
}
