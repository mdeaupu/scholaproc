<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementNegotiation extends Model
{
    protected $fillable = [
        'procurement_request_item_id',
        'round_number',
        'offered_by',
        'user_id',
        'offered_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
        'round_number' => 'integer',
    ];

    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
