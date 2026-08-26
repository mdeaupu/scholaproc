<?php

namespace App\Observers;

use App\Models\ProcurementNegotiation;

class ProcurementNegotiationObserver
{
    public function created(ProcurementNegotiation $negotiation): void
    {
        \Log::info('Negotiation offer created', [
            'item_id' => $negotiation->procurement_request_item_id,
            'round' => $negotiation->round_number,
            'offered_by' => $negotiation->offered_by,
            'price' => $negotiation->offered_price,
            'user_id' => $negotiation->user_id,
        ]);
    }

    public function updated(ProcurementNegotiation $negotiation): void
    {
        if ($negotiation->isDirty('status') && $negotiation->isSettled()) {
            \Log::info('Negotiation settled', [
                'item_id' => $negotiation->procurement_request_item_id,
                'round' => $negotiation->round_number,
                'status' => $negotiation->status,
                'price' => $negotiation->offered_price,
            ]);
        }
    }
}
