<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use LogicException;

class ProcurementNegotiation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const OFFERED_BY_CV = 'cv';

    public const OFFERED_BY_SCHOOL = 'school';

    protected $fillable = [
        'procurement_request_item_id',
        'round_number',
        'offered_by',
        'user_id',
        'offered_price',
        'status',
        'notes',
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
        User $actor,
        float $price,
        ?string $notes = null
    ): self {
        $request = $item->procurementRequest;
        $isProvider = $actor->isAdmin() || $actor->isSuperAdmin();

        if ((! $isProvider && ! $actor->isSchool())
            || ($actor->isSchool() && $actor->school_id !== $request->school_id)) {
            throw new AuthorizationException('Pengguna tidak berwenang mengajukan tawaran untuk transaksi ini.');
        }

        if ($request->status !== ProcurementRequest::STATUS_NEGOTIATION || ! $request->hasSubsidiary()) {
            throw new LogicException('Penawaran hanya dapat diajukan saat negosiasi dan setelah Anak Perusahaan ditetapkan.');
        }

        if ($price <= 0 || $request->isTotalsLocked()) {
            throw new LogicException('Harga penawaran harus positif dan snapshot belum boleh terkunci.');
        }

        return DB::transaction(function () use ($item, $actor, $price, $notes, $isProvider): self {
            $round = ((int) $item->negotiations()->max('round_number')) + 1;
            $negotiation = $item->negotiations()->create([
                'round_number' => $round,
                'offered_by' => $isProvider ? self::OFFERED_BY_CV : self::OFFERED_BY_SCHOOL,
                'user_id' => $actor->id,
                'offered_price' => $price,
                'status' => self::STATUS_PENDING,
                'notes' => $notes,
            ]);

            $item->update(['negotiation_status' => 'negotiating']);

            return $negotiation;
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
                || ! $request->hasSubsidiary()) {
                throw new LogicException('Tawaran ini tidak lagi dapat disetujui.');
            }

            if ($actor->isSchool() && $actor->school_id === $request->school_id) {
                $negotiation->school_approved_by = $actor->id;
                $negotiation->school_approved_at = now();
            } elseif ($actor->isAdmin() || $actor->isSuperAdmin()) {
                $negotiation->provider_approved_by = $actor->id;
                $negotiation->provider_approved_at = now();
            } else {
                throw new AuthorizationException('Hanya sekolah pemilik atau pihak penyedia yang dapat menyetujui tawaran.');
            }

            $negotiation->save();

            if ($negotiation->school_approved_at !== null && $negotiation->provider_approved_at !== null) {
                $negotiation->status = 'accepted';
                $negotiation->save();
                $item->update([
                    'official_price' => $negotiation->offered_price,
                    'negotiation_status' => 'accepted',
                ]);
            }

            if (! $request->items()->where('negotiation_status', '!=', 'accepted')->exists()) {
                $request->finalizePriceAgreement($actor);
            }
        });
    }
}
