<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRule extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (self $rule): void {
            activity()->performedOn($rule)->causedBy(auth()->user())->log('Aturan pajak dibuat');
        });

        static::updated(function (self $rule): void {
            activity()->performedOn($rule)->causedBy(auth()->user())->log('Aturan pajak diubah');
        });

        static::deleted(function (self $rule): void {
            activity()->performedOn($rule)->causedBy(auth()->user())->log('Aturan pajak dihapus');
        });
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const TAX_PPN = 'ppn';
    public const TAX_PPH22 = 'pph22';
    public const TAX_PPH23 = 'pph23';

    protected $fillable = [
        'tax_type',
        'name',
        'version',
        'status',
        'buyer_type',
        'pkp_status',
        'transaction_type',
        'item_type',
        'funding_source',
        'subsidiary_id',
        'dpp_method',
        'calculation_method',
        'rate',
        'collector',
        'exemption',
        'legal_basis',
        'effective_from',
        'effective_to',
        'created_by',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'change_notes',
    ];

    protected $casts = [
        'version' => 'integer',
        'rate' => 'decimal:4',
        'exemption' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function subsidiary(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            });
    }

    public function scopeForTaxType(Builder $query, string $taxType): Builder
    {
        return $query->where('tax_type', $taxType);
    }

    public function matches(array $context): bool
    {
        foreach (['buyer_type', 'pkp_status', 'transaction_type', 'item_type', 'funding_source'] as $key) {
            $ruleValue = $this->{$key};
            $contextValue = $context[$key] ?? null;
            if ($ruleValue !== null && $ruleValue !== 'all' && $ruleValue !== $contextValue) {
                return false;
            }
        }

        if ($this->subsidiary_id !== null && (int) $this->subsidiary_id !== (int) ($context['subsidiary_id'] ?? 0)) {
            return false;
        }

        return true;
    }

    public function submitForApproval(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \LogicException('Hanya draft yang dapat diajukan.');
        }
        $this->update(['status' => self::STATUS_PENDING]);
    }

    public function approve(int $approverId): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \LogicException('Hanya aturan pending yang dapat disetujui.');
        }
        if ($this->created_by === $approverId) {
            throw new \LogicException('Persetujuan harus dilakukan pengguna berbeda dari pembuat.');
        }
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    public function activate(int $actorId): void
    {
        $actor = User::query()->findOrFail($actorId);

        if (! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Admin Pusat yang dapat mengaktifkan aturan pajak.');
        }
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \LogicException('Hanya aturan yang disetujui dapat diaktifkan.');
        }
        if ($this->approved_by === null || $this->approved_at === null) {
            throw new \LogicException('Persetujuan aturan wajib tercatat sebelum aktivasi.');
        }
        if ($actorId === (int) $this->approved_by) {
            throw new \LogicException('Verifikator aktivasi harus berbeda dari pemberi persetujuan.');
        }
        if (trim((string) $this->legal_basis) === ''
            || $this->rate === null
            || (float) $this->rate < 0
            || (float) $this->rate > 100
            || $this->calculation_method !== 'percentage') {
            throw new \LogicException('Aturan pajak belum memiliki dasar hukum dan konfigurasi persentase yang valid.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($actorId): void {
            $this->update([
                'status' => self::STATUS_ACTIVE,
                'verified_by' => $actorId,
                'verified_at' => now(),
            ]);
        });

        activity()
            ->performedOn($this)
            ->causedBy($actor)
            ->withProperties(['tax_type' => $this->tax_type, 'version' => $this->version])
            ->log('Aturan pajak diaktifkan tanpa menonaktifkan aturan lain');
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }
}
