<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequestItem extends Model
{
    use HasFactory;

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

    protected $guarded = ['id'];

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

    public function estimatedAmount(): float
    {
        return (float) $this->estimated_price * $this->quantity;
    }

    public function officialAmount(): float
    {
        return (float) ($this->official_price ?? 0) * $this->quantity;
    }

    public function pphAmount(): float
    {
        if (!$this->is_pph) {
            return 0;
        }

        $this->loadMissing('procurementRequest');

        $baseAmount = $this->officialAmount() > 0 ? $this->officialAmount() : $this->estimatedAmount();

        $rate = ($this->procurementRequest->pph_22_rate + $this->procurementRequest->pph_23_rate) / 100;

        return $baseAmount * $rate;
    }

    protected function estimatedAmountTotal(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->estimatedAmount(),
        );
    }

    protected function officialAmountTotal(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->officialAmount(),
        );
    }
}
