<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementRequestItem extends Model
{
    use HasFactory, SoftDeletes;

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

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->exists && $item->procurementRequest()->value('totals_locked_at') !== null
                && $item->isDirty([
                    'item_name',
                    'specification',
                    'unit_id',
                    'quantity',
                    'estimated_price',
                    'official_price',
                    'is_pph',
                ])) {
                throw new \LogicException('Rincian barang terkunci dalam snapshot final; gunakan alur koreksi berwenang.');
            }

            $specification = trim((string) $item->specification);
            $item->specification = $specification === '' ? null : $specification;
        });

        static::deleting(function (self $item): void {
            if ($item->procurementRequest()->value('totals_locked_at') !== null) {
                throw new \LogicException('Barang dalam snapshot final tidak dapat dihapus; gunakan alur koreksi berwenang.');
            }
        });
    }

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

    public function receiptItems(): HasMany
    {
        return $this->hasMany(ProcurementReceiptItem::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(ProcurementDeliveryItem::class);
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
        if (! $this->is_pph) {
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

    public function nextOfferBy(): ?string
    {
        if ($this->isNegotiationSettled()) {
            return null;
        }

        $latestRound = $this->latestNegotiationRound();

        if ($latestRound === null) {
            return ProcurementNegotiation::OFFERED_BY_ADMIN;
        }

        if (! in_array($latestRound->status, [
            ProcurementNegotiation::STATUS_PENDING,
            ProcurementNegotiation::STATUS_COUNTERED,
        ], true)) {
            return null;
        }

        return $latestRound->isFromSchool()
            ? ProcurementNegotiation::OFFERED_BY_ADMIN
            : ProcurementNegotiation::OFFERED_BY_SCHOOL;
    }

    public function isNegotiationSettled(): bool
    {
        return in_array($this->negotiation_status, ['accepted', 'rejected']);
    }
}
