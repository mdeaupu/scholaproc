<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

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

    public function subsidiary(): BelongsTo
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

    public function activate(int $actorId): void
    {
        if ($this->status === self::STATUS_ACTIVE) {
            return;
        }

        $actor = User::query()->findOrFail($actorId);

        if (! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Admin Pusat yang dapat mengaktifkan aturan pajak.');
        }
        if (trim((string) $this->legal_basis) === ''
            || $this->rate === null
            || (float) $this->rate < 0
            || (float) $this->rate > 100
            || $this->calculation_method !== 'percentage') {
            throw new \LogicException('Aturan pajak belum memiliki dasar hukum dan konfigurasi persentase yang valid.');
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'approved_by' => $actorId,
            'approved_at' => now(),
            'verified_by' => $actorId,
            'verified_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($actor)
            ->withProperties(['tax_type' => $this->tax_type, 'version' => $this->version])
            ->log('Aturan pajak diaktifkan');
    }

    public function deactivate(): void
    {
        if ($this->status === self::STATUS_INACTIVE) {
            return;
        }

        $this->update(['status' => self::STATUS_INACTIVE]);
    }
}
