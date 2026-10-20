<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ProcurementNegotiation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const OFFERED_BY_CV = 'cv';

    public const OFFERED_BY_SCHOOL = 'school';

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

    public function procurementRequest(): HasOneThrough
    {
        return $this->hasOneThrough(
            ProcurementRequest::class,
            ProcurementRequestItem::class,
            'id',
            'id',
            'procurement_request_item_id',
            'procurement_request_id'
        );
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfferedBy(Builder $query, string $party): Builder
    {
        return $query->where('offered_by', $party);
    }
}
