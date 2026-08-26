<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProcurementNegotiation extends Model
{
    use HasFactory;
    public const STATUS_PENDING = 'pending';
    public const STATUS_COUNTERED = 'countered';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const OFFERED_BY_ADMIN = 'admin_cv';
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

    // ─── Relationships ──────────────────────────────────────────────

    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('procurement_request_item_id', $itemId);
    }

    public function scopeLatestPerItem(Builder $query): Builder
    {
        return $query->whereRaw('id IN (
            SELECT MAX(id) FROM procurement_negotiations
            GROUP BY procurement_request_item_id
        )');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ─── Business Methods ───────────────────────────────────────────

    /**
     * Create a new negotiation offer (round).
     * Auto-counters the previous pending round if one exists.
     */
    public static function offer(
        ProcurementRequestItem $item,
        User $user,
        string $offeredBy,
        float $price,
        ?string $notes = null
    ): self {
        if (!in_array($offeredBy, [self::OFFERED_BY_ADMIN, self::OFFERED_BY_SCHOOL])) {
            throw new Exception('Pihak penawar tidak valid.');
        }

        if ($item->isNegotiationSettled()) {
            throw new Exception('Negosiasi untuk item ini sudah selesai (diterima/ditolak).');
        }

        $procurementRequest = $item->procurementRequest;

        if (!$procurementRequest->supplier_id) {
            throw new Exception('Supplier belum ditentukan. Tentukan supplier terlebih dahulu.');
        }

        return DB::transaction(function () use ($item, $user, $offeredBy, $price, $notes) {
            // Counter the previous pending round if exists
            $previousPending = $item->negotiations()
                ->where('status', self::STATUS_PENDING)
                ->latest('round_number')
                ->first();

            if ($previousPending) {
                $previousPending->update(['status' => self::STATUS_COUNTERED]);
            }

            // Determine round number
            $lastRound = $item->negotiations()->max('round_number') ?? 0;
            $newRound = $lastRound + 1;

            // Update item negotiation status
            $item->update(['negotiation_status' => 'negotiating']);

            return self::create([
                'procurement_request_item_id' => $item->id,
                'round_number' => $newRound,
                'offered_by' => $offeredBy,
                'user_id' => $user->id,
                'offered_price' => $price,
                'status' => self::STATUS_PENDING,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Accept this offer. Only school can decide.
     * Copies offered_price to item's official_price.
     */
    public function accept(User $user): void
    {
        if (!$user->canDecideNegotiation($this->procurementRequestItem->procurementRequest)) {
            throw new Exception('Hanya pihak sekolah yang dapat menerima/menolak tawaran harga.');
        }

        if ($this->status !== self::STATUS_PENDING) {
            throw new Exception('Hanya tawaran berstatus pending yang dapat diterima.');
        }

        DB::transaction(function () use ($user) {
            $this->update(['status' => self::STATUS_ACCEPTED]);

            $this->procurementRequestItem->update([
                'official_price' => $this->offered_price,
                'negotiation_status' => 'accepted',
            ]);

            $this->procurementRequestItem->procurementRequest->recordHistory(
                $user,
                $this->procurementRequestItem->procurementRequest->status,
                "Negosiasi diterima: {$this->procurementRequestItem->item_name} Rp " . number_format($this->offered_price, 0, ',', '.')
            );
        });
    }

    /**
     * Reject this offer. Only school can decide.
     */
    public function reject(User $user, ?string $reason = null): void
    {
        if (!$user->canDecideNegotiation($this->procurementRequestItem->procurementRequest)) {
            throw new Exception('Hanya pihak sekolah yang dapat menerima/menolak tawaran harga.');
        }

        if ($this->status !== self::STATUS_PENDING) {
            throw new Exception('Hanya tawaran berstatus pending yang dapat ditolak.');
        }

        DB::transaction(function () use ($user, $reason) {
            $this->update(['status' => self::STATUS_REJECTED]);

            $this->procurementRequestItem->update([
                'negotiation_status' => 'rejected',
            ]);

            $this->procurementRequestItem->procurementRequest->recordHistory(
                $user,
                $this->procurementRequestItem->procurementRequest->status,
                "Negosiasi ditolak: {$this->procurementRequestItem->item_name}" . ($reason ? " — {$reason}" : '')
            );
        });
    }

    public function isLatestRound(): bool
    {
        $latestRound = $this->procurementRequestItem->negotiations()
            ->latest('round_number')
            ->first();

        return $latestRound && $latestRound->id === $this->id;
    }

    public function isFromSchool(): bool
    {
        return $this->offered_by === self::OFFERED_BY_SCHOOL;
    }

    public function isFromSupplierRepresentative(): bool
    {
        return $this->offered_by === self::OFFERED_BY_ADMIN;
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REJECTED]);
    }
}
