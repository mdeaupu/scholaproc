<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementSignatory extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'role',
        'name',
        'nip',
        'title',
    ];

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

}
