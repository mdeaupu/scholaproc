<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementReceiptItem extends Model
{
    public const FOLLOW_UP_NONE = 'none';
    public const FOLLOW_UP_OPEN = 'open';
    public const FOLLOW_UP_RESOLVED = 'resolved';

    protected $fillable = [
        'procurement_receipt_id',
        'procurement_request_item_id',
        'quantity',
        'condition',
        'issue_notes',
        'follow_up_status',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ProcurementReceipt::class, 'procurement_receipt_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItem::class, 'procurement_request_item_id');
    }
}
