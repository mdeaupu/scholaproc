<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequestItem extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'line_number',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'estimated_price',
        'official_price',
        'is_pph',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'integer',
            'estimated_price' => 'decimal:2',
            'official_price' => 'decimal:2',
            'is_pph' => 'boolean',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }
}
