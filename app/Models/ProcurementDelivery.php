<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementDelivery extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'delivered_at',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return ['delivered_at' => 'date'];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementDeliveryItem::class);
    }
}
