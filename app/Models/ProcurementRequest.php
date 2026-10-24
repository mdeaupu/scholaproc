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

    /**
     * Kosakata status mengikuti PRD bagian 6 (Status Transaksi).
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_SUPPLIER_ASSIGNED = 'supplier_assigned';

    public const STATUS_ITEMS_PREPARED = 'items_prepared';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NEGOTIATION = 'negotiation';

    public const STATUS_PRICE_AGREED = 'price_agreed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_INVOICED = 'invoiced';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Menunggu Verifikasi',
        self::STATUS_SUPPLIER_ASSIGNED => 'Supplier Ditentukan',
        self::STATUS_ITEMS_PREPARED => 'Barang Disiapkan',
        self::STATUS_NEEDS_REVISION => 'Perlu Revisi',
        self::STATUS_VERIFIED => 'Terverifikasi',
        self::STATUS_NEGOTIATION => 'Negosiasi Harga',
        self::STATUS_PRICE_AGREED => 'Harga Disepakati',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_PARTIALLY_RECEIVED => 'Diterima Sebagian',
        self::STATUS_RECEIVED => 'Diterima',
        self::STATUS_INVOICED => 'Ditagih',
        self::STATUS_PAYMENT_PENDING => 'Menunggu Pembayaran',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_DISPUTED => 'Bermasalah',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const STATUS_PILL_CLASSES = [
        self::STATUS_DRAFT => 'bg-gray-100 text-gray-700',
        self::STATUS_SUBMITTED => 'bg-yellow-100 text-yellow-800',
        self::STATUS_SUPPLIER_ASSIGNED => 'bg-indigo-100 text-indigo-800',
        self::STATUS_ITEMS_PREPARED => 'bg-indigo-100 text-indigo-800',
        self::STATUS_NEEDS_REVISION => 'bg-orange-100 text-orange-800',
        self::STATUS_VERIFIED => 'bg-blue-100 text-blue-800',
        self::STATUS_NEGOTIATION => 'bg-yellow-100 text-yellow-800',
        self::STATUS_PRICE_AGREED => 'bg-teal-100 text-teal-800',
        self::STATUS_PROCESSING => 'bg-indigo-100 text-indigo-800',
        self::STATUS_PARTIALLY_RECEIVED => 'bg-indigo-100 text-indigo-800',
        self::STATUS_RECEIVED => 'bg-cyan-100 text-cyan-800',
        self::STATUS_INVOICED => 'bg-blue-100 text-blue-800',
        self::STATUS_PAYMENT_PENDING => 'bg-amber-100 text-amber-800',
        self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
        self::STATUS_DISPUTED => 'bg-red-100 text-red-800',
        self::STATUS_REJECTED => 'bg-red-100 text-red-800',
        self::STATUS_CANCELLED => 'bg-gray-100 text-gray-500',
    ];

    /**
     * Status yang masih perlu ditindaklanjuti (bukan draf, selesai, ditolak, atau dibatalkan).
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_SUPPLIER_ASSIGNED,
        self::STATUS_ITEMS_PREPARED,
        self::STATUS_NEEDS_REVISION,
        self::STATUS_VERIFIED,
        self::STATUS_NEGOTIATION,
        self::STATUS_PRICE_AGREED,
        self::STATUS_PROCESSING,
        self::STATUS_PARTIALLY_RECEIVED,
        self::STATUS_RECEIVED,
        self::STATUS_INVOICED,
        self::STATUS_PAYMENT_PENDING,
        self::STATUS_DISPUTED,
    ];

    /**
     * Status yang tidak dihitung sebagai nilai transaksi berjalan.
     */
    public const EXCLUDED_FINANCIAL_STATUSES = [
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'uuid',
        'school_id',
        'supplier_id',
        'status',
        'package_category_id',
        'budget_year_id',
        'funding_source_id',
        'start_date',
        'end_date',
        'work_duration_text',
        'is_taxable',
        'ppn_rate',
        'pph_22_rate',
        'pph_23_rate',
        'subtotal',
        'tax_amount',
        'grand_total',
        'totals_locked_at',
        'cv_notes',
        'requested_at',
        'verified_at',
        'verified_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'ppn_rate' => 'decimal:2',
            'pph_22_rate' => 'decimal:2',
            'pph_23_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_at' => 'datetime',
            'totals_locked_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budgetYear(): BelongsTo
    {
        return $this->belongsTo(BudgetYear::class, 'budget_year_id');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id');
    }

    public function packageCategory(): BelongsTo
    {
        return $this->belongsTo(PackageCategory::class, 'package_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function verificationFiles(): HasMany
    {
        return $this->hasMany(ProcurementVerificationFile::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUPPLIER_ASSIGNED);
    }

    public function scopePrepared(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ITEMS_PREPARED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeBySchool(Builder $query, $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeBySupplier(Builder $query, $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByYear(Builder $query, $budgetYearId): Builder
    {
        return $query->where('budget_year_id', $budgetYearId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeInPeriod(Builder $query, $from = null, $to = null): Builder
    {
        if ($from !== null && $from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== null && $to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
        return $query;
    }

    public function scopeAwaitingNegotiation(Builder $query): Builder
    {
        return $query->whereHas('items', function ($q) {
            $q->where('negotiation_status', 'negotiating');
        });
    }

    // ─── State Machine ──────────────────────────────────────────────

    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->count() >= 1;
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

    public function submit(User $user): void
    {
        if (!$this->canSubmit()) {
            throw new Exception('Pengajuan tidak dapat di-submit. Pastikan status draft dan memiliki minimal 1 item.');
        }

        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'requested_at' => now(),
        ]);

        $this->recordHistory($user, self::STATUS_SUBMITTED);
    }

    public function verify(User $user): void
    {
        if (!$this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat diverifikasi.');
        }

        $this->update(['status' => self::STATUS_VERIFIED]);
        $this->recordHistory($user, self::STATUS_VERIFIED);
    }

    public function reject(User $user, string $reason): void
    {
        if (!$this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat ditolak.');
        }

        $this->update(['status' => self::STATUS_REJECTED]);
        $this->recordHistory($user, self::STATUS_REJECTED, 'Ditolak: ' . $reason);
    }

    public function assignSupplier(Supplier $supplier, User $user): void
    {
        if (!$this->canAssignSupplier()) {
            throw new Exception('Hanya pengajuan berstatus verified yang dapat ditentukan suppliernya.');
        }

        $this->update([
            'status' => self::STATUS_SUPPLIER_ASSIGNED,
            'supplier_id' => $supplier->id,
        ]);

        $this->recordHistory($user, self::STATUS_SUPPLIER_ASSIGNED);
    }

    public function markItemsPrepared(User $user): void
    {
        if (!$this->canPrepareItems()) {
            throw new Exception('Hanya pengajuan berstatus supplier_assigned yang dapat ditandai siap.');
        }

        $this->update(['status' => self::STATUS_ITEMS_PREPARED]);
        $this->recordHistory($user, self::STATUS_ITEMS_PREPARED);
    }

    public function complete(User $user): void
    {
        if (!$this->canComplete()) {
            throw new Exception('Hanya pengajuan berstatus items_prepared yang dapat diselesaikan.');
        }

        $this->update(['status' => self::STATUS_COMPLETED]);
        $this->recordHistory($user, self::STATUS_COMPLETED);
    }

    // ─── Audit Trail ────────────────────────────────────────────────

    public function recordHistory(User $user, string $status, ?string $notes = null): void
    {
        $this->histories()->create([
            'user_id' => $user->id,
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    // ─── Financial Calculations ─────────────────────────────────────

    public function estimatedSubtotal(): float
    {
        return (float) $this->items()->sum(DB::raw('quantity * estimated_price'));
    }

    public function officialSubtotal(): float
    {
        return (float) $this->items()->sum(DB::raw(
            'quantity * COALESCE(official_price, estimated_price)'
        ));
    }

    public function baseSubtotal(): float
    {
        $officialTotal = $this->officialSubtotal();

        if ($officialTotal > 0 && $this->items()->whereNotNull('official_price')->count() > 0) {
            return $officialTotal;
        }

        return $this->estimatedSubtotal();
    }

    public function totalPpn(): float
    {
        if (!$this->is_taxable) {
            return 0;
        }

        return round($this->baseSubtotal() * ($this->ppn_rate / 100), 2);
    }

    public function totalPph22(): float
    {
        $pphItems = $this->items()->where('is_pph', true);

        if ($pphItems->count() === 0) {
            return 0;
        }

        $pphBase = (float) $pphItems->sum(DB::raw('quantity * COALESCE(official_price, estimated_price)'));

        return round($pphBase * ($this->pph_22_rate / 100), 2);
    }

    public function totalPph23(): float
    {
        $pphItems = $this->items()->where('is_pph', true);

        if ($pphItems->count() === 0) {
            return 0;
        }

        $pphBase = (float) $pphItems->sum(DB::raw('quantity * COALESCE(official_price, estimated_price)'));

        return round($pphBase * ($this->pph_23_rate / 100), 2);
    }

    public function grandTotal(): float
    {
        return $this->baseSubtotal() + $this->totalPpn();
    }

    public function netTotal(): float
    {
        return $this->baseSubtotal() + $this->totalPpn() - $this->totalPph22() - $this->totalPph23();
    }

    // ─── Snapshot Lock ──────────────────────────────────────────────

    public function isTotalsLocked(): bool
    {
        return $this->totals_locked_at !== null;
    }

    public function lockTotals(): void
    {
        $this->update([
            'subtotal' => $this->baseSubtotal(),
            'tax_amount' => $this->totalPpn() - $this->totalPph22() - $this->totalPph23(),
            'grand_total' => $this->grandTotal(),
            'totals_locked_at' => now(),
        ]);
    }

    public function reopenTotals(User $user, string $reason): void
    {
        $this->update([
            'totals_locked_at' => null,
            'subtotal' => null,
            'tax_amount' => null,
            'grand_total' => null,
        ]);

        $this->recordHistory($user, $this->status, 'Revisi: ' . $reason);
    }

    // ─── Dashboard Statistics ───────────────────────────────────────

    public static function getTotalEstimatedAmount(): float
    {
        return self::sumEstimatedAmount(self::query());
    }

    public static function sumEstimatedAmount(Builder $query): float
    {
        return (float) (clone $query)
            ->join('procurement_request_items', 'procurement_request_items.procurement_request_id', '=', 'procurement_requests.id')
            ->whereNotIn('procurement_requests.status', self::EXCLUDED_FINANCIAL_STATUSES)
            ->selectRaw('SUM(procurement_request_items.quantity * procurement_request_items.estimated_price) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalOfficialAmount(): float
    {
        return self::sumOfficialAmount(self::query());
    }

    public static function sumOfficialAmount(Builder $query): float
    {
        $lockedTotal = (float) (clone $query)
            ->whereNotNull('totals_locked_at')
            ->whereNotIn('status', self::EXCLUDED_FINANCIAL_STATUSES)
            ->sum('grand_total');

        $unlockedTotal = (float) (clone $query)
            ->join('procurement_request_items', 'procurement_request_items.procurement_request_id', '=', 'procurement_requests.id')
            ->whereNull('procurement_requests.totals_locked_at')
            ->whereNotIn('procurement_requests.status', self::EXCLUDED_FINANCIAL_STATUSES)
            ->selectRaw('SUM(procurement_request_items.quantity * procurement_request_items.official_price) as total')
            ->value('total') ?? 0;

        return $lockedTotal + $unlockedTotal;
    }

    public function getOfficialAmount(): float
    {
        if ($this->totals_locked_at !== null) {
            return (float) $this->grand_total;
        }

        return (float) $this->items()->sum(DB::raw('quantity * official_price'));
    }

    public function getTotalEstimatedAmountAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn ($item) => $item->quantity * $item->estimated_price);
        }

        return (float) $this->items()->sum(DB::raw('quantity * estimated_price'));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusPillClassAttribute(): string
    {
        return self::STATUS_PILL_CLASSES[$this->status] ?? 'bg-gray-100 text-gray-700';
    }
}
