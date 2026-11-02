<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcurementInvoice extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'tax_snapshot',
        'price_snapshot',
        'subsidiary_snapshot',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'processed_at' => 'datetime',
            'tax_snapshot' => 'array',
            'price_snapshot' => 'array',
            'subsidiary_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(ProcurementPayment::class);
    }
}
