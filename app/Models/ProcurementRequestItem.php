<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'line_number',
        'item_name',
        'specification',
        'unit_id',
        'quantity',
        'estimated_price',
        'official_price',
        'is_pph',
        'negotiation_status',
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

    // ─── Relationships ──────────────────────────────────────────────

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'unit_id');
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(ProcurementNegotiation::class, 'procurement_request_item_id');
    }

    // ─── Calculations ───────────────────────────────────────────────

    public function estimatedAmount(): float
    {
        return (float) $this->quantity * (float) $this->estimated_price;
    }

    public function officialAmount(): float
    {
        $price = $this->official_price ?? $this->estimated_price;

        return (float) $this->quantity * (float) $price;
    }

    public function pphAmount(): float
    {
        if (!$this->is_pph) {
            return 0;
        }

        return (float) $this->official_price * (float) $this->quantity;
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function estimatedAmountAttribute(): float
    {
        return $this->estimatedAmount();
    }

    protected function officialAmountAttribute(): float
    {
        return $this->officialAmount();
    }

    // ─── Business Methods ───────────────────────────────────────────

    public function latestNegotiationRound()
    {
        return $this->negotiations()->latest('round_number')->first();
    }

    public function isNegotiationSettled(): bool
    {
        return in_array($this->negotiation_status, ['accepted', 'rejected']);
    }
}
