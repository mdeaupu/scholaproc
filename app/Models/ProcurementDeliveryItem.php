<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementDeliveryItem extends Model
{
    protected $fillable = ['procurement_delivery_id', 'procurement_request_item_id', 'quantity'];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ProcurementDelivery::class, 'procurement_delivery_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItem::class, 'procurement_request_item_id');
    }
}
