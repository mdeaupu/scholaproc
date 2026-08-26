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

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_SUPPLIER_ASSIGNED = 'supplier_assigned';
    public const STATUS_ITEMS_PREPARED = 'items_prepared';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

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
        return (float) DB::table('procurement_request_items')
            ->selectRaw('SUM(quantity * estimated_price) as total')
            ->value('total') ?? 0;
    }

    public static function getTotalOfficialAmount(): float
    {
        $lockedTotal = (float) self::whereNotNull('totals_locked_at')->sum('grand_total');

        $unlockedTotal = (float) DB::table('procurement_request_items')
            ->join('procurement_requests', 'procurement_request_items.procurement_request_id', '=', 'procurement_requests.id')
            ->whereNull('procurement_requests.totals_locked_at')
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

    public function hasSupplier(): bool
    {
        return !empty($this->supplier_id);
    }

    public function hasSignatories(): bool
    {
        $requiredRoles = ['headmaster', 'inspector', 'treasurer'];
        $existingRoles = $this->signatories()->pluck('role')->toArray();

        foreach ($requiredRoles as $role) {
            if (!in_array($role, $existingRoles)) {
                return false;
            }
        }
        return true;
    }

    public function hasOfficialPrices(): bool
    {
        return $this->items()->whereNull('official_price')
            ->orWhere('official_price', '<=', 0)
            ->count() === 0;
    }

    public function isReadyForDocumentGeneration(): bool
    {
        return $this->hasSupplier() && $this->hasSignatories() && $this->hasOfficialPrices();
    }

    public function generateOfficialDocuments(): void
    {
        if (!$this->isReadyForDocumentGeneration()) {
            throw new Exception("Gagal generate. Mohon lengkapi data supplier, penandatangan, dan harga resmi terlebih dahulu.");
        }

        $documentTypes = ['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt'];

        $sequenceNumber = str_pad($this->id, 3, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($documentTypes, $sequenceNumber) {
            foreach ($documentTypes as $type) {
                $doc = $this->documents()->firstOrNew(['document_type' => $type]);
                $doc->document_number = $doc->generateNumber($sequenceNumber);
                $doc->document_date = now()->toDateString();
                $doc->save();
            }
        });
    }
}
