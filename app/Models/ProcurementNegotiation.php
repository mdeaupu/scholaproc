<?php

namespace App\Models;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcurementNegotiation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COUNTERED = 'countered';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const OFFERED_BY_ADMIN = 'admin_cv';

    public const OFFERED_BY_CV = self::OFFERED_BY_ADMIN;

    public const OFFERED_BY_SCHOOL = 'school';

    protected $fillable = [
        'procurement_request_item_id',
        'round_number',
        'offered_by',
        'user_id',
        'offered_price',
        'status',
        'notes',
        'response_note',
        'school_approved_by',
        'school_approved_at',
        'provider_approved_by',
        'provider_approved_at',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
        'round_number' => 'integer',
        'school_approved_at' => 'datetime',
        'provider_approved_at' => 'datetime',
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

    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('procurement_request_item_id', $itemId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfferedBy(Builder $query, string $party): Builder
    {
        return $query->where('offered_by', $party);
    }

    public static function offer(
        ProcurementRequestItem $item,
        User $user,
        string $offeredBy,
        float $price,
        ?string $notes = null
    ): self {
        if (! in_array($offeredBy, [self::OFFERED_BY_ADMIN, self::OFFERED_BY_SCHOOL], true)) {
            throw new Exception('Pihak penawar tidak valid.');
        }

        $request = $item->procurementRequest;
        $isProvider = $user->isAdmin() || $user->isSuperAdmin();
        $expectedParty = $isProvider ? self::OFFERED_BY_ADMIN : self::OFFERED_BY_SCHOOL;

        if ((! $isProvider && ! $user->isSchool())
            || ($user->isSchool() && $user->school_id !== $request->school_id)
            || $offeredBy !== $expectedParty) {
            throw new AuthorizationException('Pengguna tidak berwenang mengajukan tawaran untuk transaksi ini.');
        }

        if ($item->isNegotiationSettled()) {
            throw new Exception('Negosiasi untuk item ini sudah selesai (diterima/ditolak).');
        }

        if ($request->status !== ProcurementRequest::STATUS_NEGOTIATION || ! $request->hasSubsidiary()) {
            throw new LogicException('Penawaran hanya dapat diajukan saat negosiasi dan setelah Anak Perusahaan ditetapkan.');
        }

        if ($price <= 0 || $request->isTotalsLocked()) {
            throw new LogicException('Harga penawaran harus positif dan snapshot belum boleh terkunci.');
        }

        return DB::transaction(function () use ($item, $user, $offeredBy, $price, $notes, $isProvider): self {
            $lockedItem = ProcurementRequestItem::query()->lockForUpdate()->findOrFail($item->id);
            $lockedRequest = $lockedItem->procurementRequest;

            if ($lockedRequest->status !== ProcurementRequest::STATUS_NEGOTIATION
                || ! $lockedRequest->hasSubsidiary()
                || $lockedRequest->isTotalsLocked()
                || $lockedItem->isNegotiationSettled()) {
                throw new LogicException('Penawaran ini tidak lagi dapat diajukan.');
            }

            $expectedParty = $lockedItem->nextOfferBy();
            if ($offeredBy !== $expectedParty) {
                $expectedPartyLabel = $expectedParty === self::OFFERED_BY_ADMIN
                    ? 'Admin Pusat'
                    : 'Sekolah';
                throw new LogicException("Giliran penawaran saat ini adalah {$expectedPartyLabel}.");
            }

            $previousPending = $lockedItem->negotiations()
                ->where('status', self::STATUS_PENDING)
                ->latest('round_number')
                ->first();

            if ($previousPending) {
                $previousPending->update(['status' => self::STATUS_COUNTERED]);
            }

            $now = now();
            $negotiation = $lockedItem->negotiations()->create([
                'round_number' => ((int) $lockedItem->negotiations()->max('round_number')) + 1,
                'offered_by' => $offeredBy,
                'user_id' => $user->id,
                'offered_price' => $price,
                'status' => self::STATUS_PENDING,
                'notes' => $notes,
                'school_approved_by' => $user->isSchool() ? $user->id : null,
                'school_approved_at' => $user->isSchool() ? $now : null,
                'provider_approved_by' => $isProvider ? $user->id : null,
                'provider_approved_at' => $isProvider ? $now : null,
            ]);

            $lockedItem->update(['negotiation_status' => 'negotiating']);

            return $negotiation;
        });
    }

    public function accept(User $user): void
    {
        $request = $this->procurementRequestItem->procurementRequest;
        $isSchool = $user->canDecideNegotiation($request);
        $isProvider = $user->isAdmin() || $user->isSuperAdmin();
        $isOfferedBySchool = $this->isFromSchool();

        if ((! $isSchool && ! $isProvider)
            || ($isSchool && $isOfferedBySchool)
            || ($isProvider && ! $isOfferedBySchool)) {
            throw new AuthorizationException('Hanya pihak lawan yang dapat menerima tawaran ini.');
        }

        if ($this->status !== self::STATUS_PENDING) {
            throw new Exception('Hanya tawaran berstatus pending yang dapat diterima.');
        }

        $this->approve($user);
    }

    public function reject(User $user, ?string $reason = null): void
    {
        if (! $user->canDecideNegotiation($this->procurementRequestItem->procurementRequest)) {
            throw new Exception('Hanya pihak sekolah yang dapat menerima/menolak tawaran harga.');
        }

        if ($this->status !== self::STATUS_PENDING) {
            throw new Exception('Hanya tawaran berstatus pending yang dapat ditolak.');
        }

        DB::transaction(function () use ($user, $reason): void {
            $negotiation = self::query()->lockForUpdate()->findOrFail($this->id);
            $item = $negotiation->procurementRequestItem()->lockForUpdate()->firstOrFail();
            $request = $item->procurementRequest;

            if ($negotiation->status !== self::STATUS_PENDING || $request->isTotalsLocked()) {
                throw new LogicException('Tawaran ini tidak lagi dapat ditolak.');
            }

            $negotiation->update([
                'status' => self::STATUS_REJECTED,
                'response_note' => $reason,
            ]);
            $item->update(['negotiation_status' => 'rejected']);
            $request->recordHistory(
                $user,
                $request->status,
                "Negosiasi ditolak: {$item->item_name}".($reason ? " — {$reason}" : '')
            );
        });
    }

    public function approve(User $actor): void
    {
        DB::transaction(function () use ($actor): void {
            $negotiation = self::query()->lockForUpdate()->findOrFail($this->id);
            $item = $negotiation->procurementRequestItem()->lockForUpdate()->firstOrFail();
            $request = $item->procurementRequest;

            if ($negotiation->status !== self::STATUS_PENDING
                || $request->status !== ProcurementRequest::STATUS_NEGOTIATION
                || ! $request->hasSubsidiary()
                || $request->isTotalsLocked()) {
                throw new LogicException('Tawaran ini tidak lagi dapat disetujui.');
            }

            if ($actor->isSchool() && $actor->school_id === $request->school_id) {
                if ($negotiation->school_approved_at !== null) {
                    throw new LogicException('Persetujuan sekolah untuk tawaran ini sudah tercatat.');
                }

                $negotiation->school_approved_by = $actor->id;
                $negotiation->school_approved_at = now();
            } elseif ($actor->isAdmin() || $actor->isSuperAdmin()) {
                if ($negotiation->provider_approved_at !== null) {
                    throw new LogicException('Persetujuan penyedia untuk tawaran ini sudah tercatat.');
                }

                $negotiation->provider_approved_by = $actor->id;
                $negotiation->provider_approved_at = now();
            } else {
                throw new AuthorizationException('Hanya sekolah pemilik atau pihak penyedia yang dapat menyetujui tawaran.');
            }

            $negotiation->save();

            if ($negotiation->school_approved_at !== null && $negotiation->provider_approved_at !== null) {
                $negotiation->update(['status' => self::STATUS_ACCEPTED]);
                $item->update([
                    'official_price' => $negotiation->offered_price,
                    'negotiation_status' => 'accepted',
                ]);
                $request->recordHistory(
                    $actor,
                    $request->status,
                    "Negosiasi disepakati: {$item->item_name} Rp ".number_format((float) $negotiation->offered_price, 0, ',', '.')
                );
            }

            if (! $request->items()->where('negotiation_status', '!=', 'accepted')->exists()) {
                $request->finalizePriceAgreement($actor);
            }
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
        return in_array($this->offered_by, [self::OFFERED_BY_ADMIN, 'cv'], true);
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REJECTED], true);
    }
}
