<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementReceipt extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'received_at',
        'recorded_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementReceiptItem::class);
    }
}
