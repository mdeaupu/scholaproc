<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementRequest extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'uuid',
        'school_id',
        'supplier_id',
        'status',
        'package_category',
        'budget_year',
        'funding_source',
        'start_date',
        'end_date',
        'work_duration_text',
        'is_taxable',
        'ppn_rate',
        'pph_22_rate',
        'pph_23_rate',
        'cv_notes',
        'requested_at',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'ppn_rate' => 'decimal:2',
            'pph_22_rate' => 'decimal:2',
            'pph_23_rate' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_at' => 'datetime',
        ];
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_SUPPLIER_ASSIGNED = 'supplier_assigned';
    public const STATUS_ITEMS_PREPARED = 'items_prepared';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ProcurementSignatory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementDocument::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItem::class)->orderBy('line_number');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProcurementRequestHistory::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->exists();
    }

    public function canVerify(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canAssignSupplier(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function canPrepareItems(): bool
    {
        return $this->status === self::STATUS_SUPPLIER_ASSIGNED;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_ITEMS_PREPARED;
    }

    public function recordHistory(User $user, string $status, ?string $notes = null): void
    {
        $this->histories()->create([
            'user_id' => $user->id,
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    public function submit(User $user): void
    {
        if (!$this->canSubmit())
            throw new Exception("Pengajuan tidak dapat di-submit. Pastikan status draft dan memiliki minimal 1 item.");

        DB::transaction(function () use ($user) {
            $this->update(['status' => self::STATUS_SUBMITTED, 'requested_at' => now()]);
            $this->recordHistory($user, self::STATUS_SUBMITTED, 'Pengajuan di-submit.');
        });
    }

    public function verify(User $user): void
    {
        if (!$this->canVerify())
            throw new Exception("Hanya pengajuan berstatus submitted yang dapat diverifikasi.");

        DB::transaction(function () use ($user) {
            $this->update(['status' => self::STATUS_VERIFIED]);
            $this->recordHistory($user, self::STATUS_VERIFIED, 'Pengajuan diverifikasi oleh Admin CV.');
        });
    }

    public function reject(User $user, string $reason): void
    {
        if (!in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED])) {
            throw new Exception("Status saat ini tidak dapat di-reject.");
        }

        DB::transaction(function () use ($user, $reason) {
            $this->update(['status' => self::STATUS_REJECTED]);
            $this->recordHistory($user, self::STATUS_REJECTED, "Ditolak: {$reason}");
        });
    }

    public function assignSupplier(Supplier $supplier, User $user): void
    {
        if (!$this->canAssignSupplier())
            throw new Exception("Status harus verified untuk menunjuk supplier.");

        DB::transaction(function () use ($user, $supplier) {
            $this->update([
                'supplier_id' => $supplier->id,
                'status' => self::STATUS_SUPPLIER_ASSIGNED
            ]);
            $this->recordHistory($user, self::STATUS_SUPPLIER_ASSIGNED, "Supplier {$supplier->company_name} ditunjuk.");
        });
    }

    public function markItemsPrepared(User $user): void
    {
        if (!$this->canPrepareItems())
            throw new Exception("Status harus supplier_assigned untuk persiapan item.");

        DB::transaction(function () use ($user) {
            $this->update(['status' => self::STATUS_ITEMS_PREPARED]);
            $this->recordHistory($user, self::STATUS_ITEMS_PREPARED, 'Barang/Jasa sedang disiapkan.');
        });
    }

    public function complete(User $user): void
    {
        if (!$this->canComplete())
            throw new Exception("Pengajuan belum bisa diselesaikan.");

        DB::transaction(function () use ($user) {
            $this->update(['status' => self::STATUS_COMPLETED]);
            $this->recordHistory($user, self::STATUS_COMPLETED, 'Proses pengadaan selesai (BAST diterbitkan).');
        });
    }

    private function getBaseCalculationTotal(): float
    {
        $official = $this->officialSubtotal();
        return $official > 0 ? $official : $this->estimatedSubtotal();
    }

    public function estimatedSubtotal(): float
    {
        return $this->items->sum(fn($item) => $item->estimatedAmount());
    }

    public function officialSubtotal(): float
    {
        return $this->items->sum(fn($item) => $item->officialAmount());
    }

    public function totalPpn(): float
    {
        if (!$this->is_taxable)
            return 0;
        return $this->getBaseCalculationTotal() * ($this->ppn_rate / 100);
    }

    public function totalPph22(): float
    {
        $taxableItemsTotal = $this->items->where('is_pph', true)->sum(fn($item) => $item->officialAmount() > 0 ? $item->officialAmount() : $item->estimatedAmount());
        return $taxableItemsTotal * ($this->pph_22_rate / 100);
    }

    public function totalPph23(): float
    {
        $taxableItemsTotal = $this->items->where('is_pph', true)->sum(fn($item) => $item->officialAmount() > 0 ? $item->officialAmount() : $item->estimatedAmount());
        return $taxableItemsTotal * ($this->pph_23_rate / 100);
    }

    public function grandTotal(): float
    {
        return $this->getBaseCalculationTotal() + $this->totalPpn();
    }

    public function netTotal(): float
    {
        return $this->grandTotal() - $this->totalPph22() - $this->totalPph23();
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_SUPPLIER_ASSIGNED);
    }

    public function scopePrepared($query)
    {
        return $query->where('status', self::STATUS_ITEMS_PREPARED);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('budget_year', $year);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', 'submitted');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public static function getTotalEstimatedAmount(): float
    {
        return (float) DB::table('procurement_request_items')
            ->selectRaw('SUM(quantity * estimated_price) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalOfficialAmount(): float
    {
        return (float) DB::table('procurement_request_items')
            ->selectRaw('SUM(quantity * official_price) as total')
            ->value('total') ?? 0;
    }
}
