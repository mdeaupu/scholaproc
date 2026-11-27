<?php

namespace App\Models;

use App\Services\DocumentGenerationService;
use App\Services\NotificationService;
use App\Services\ProcurementProcessingService;
use App\Services\TaxEvaluationService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class ProcurementRequest extends Model
{
    use HasFactory, SoftDeletes;

    private bool $allowSnapshotCorrection = false;

    private bool $allowSubsidiaryCorrection = false;

    /**
     * Kosakata status mengikuti PRD bagian 6 (Status Transaksi).
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

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

    /**
     * Ekspresi nilai transaksi untuk laporan. Dipisah dari alias SELECT karena
     * SQL tidak mengizinkan memakai alias yang sama di dalam SUM() pada daftar
     * SELECT yang sama — karena itu reportSummary() memakai ekspresi ini
     * langsung, bukan lewat nama alias.
     *
     * `REPORT_EFFECTIVE_TOTAL_SQL` mengikuti basis nilai yang sama dengan
     * {@see getOfficialAmount()}: bila snapshot sudah dikunci
     * (`totals_locked_at` terisi) angka itu yang dipakai, bila belum dihitung
     * live dari harga item resmi + PPN — persis seperti
     * baseSubtotal()/totalPpn()/grandTotal().
     */
    private const REPORT_ESTIMATED_SQL = 'COALESCE(item_totals.estimated_subtotal, 0)';

    private const REPORT_BASE_SQL = 'CASE WHEN COALESCE(item_totals.official_item_count, 0) > 0
            AND COALESCE(item_totals.official_subtotal, 0) > 0
            THEN COALESCE(item_totals.official_subtotal, 0)
            ELSE COALESCE(item_totals.estimated_subtotal, 0) END';

    private const REPORT_EFFECTIVE_TOTAL_SQL = 'CASE
            WHEN procurement_requests.totals_locked_at IS NOT NULL
                THEN procurement_requests.grand_total
            ELSE ('.self::REPORT_BASE_SQL.' + CASE
                    WHEN procurement_requests.is_taxable
                        THEN ROUND(('.self::REPORT_BASE_SQL.') * procurement_requests.ppn_rate / 100, 2)
                    ELSE 0
                END)
        END';

    private const REPORT_QUANTITY_SQL = 'COALESCE(item_totals.total_quantity, 0)';

    private const REPORT_ITEM_TYPES_SQL = 'COALESCE(item_totals.total_item_types, 0)';

    public const STATUS_CANCELLED = 'cancelled';

    public const MINIMUM_ITEMS = 1;

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Menunggu Verifikasi',
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

    public const ACTIVE_STATUSES = [
        self::STATUS_SUBMITTED,
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

    public const EXCLUDED_FINANCIAL_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'uuid',
        'school_id',
        'status',
        'package_category_id',
        'budget_year_id',
        'funding_source_id',
        'start_date',
        'end_date',
        'work_duration_text',
        'subtotal',
        'totals_locked_at',
        'cv_notes',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
        'verified_at',
        'verified_by',
        'payment_confirmed_at',
        'price_snapshot',
        'subsidiary_snapshot',
        'payment_confirmed_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if ($model->subsidiary_id !== null) {
                $subsidiary = Subsidiary::findOrFail($model->subsidiary_id);

                if (! $subsidiary->isActive()) {
                    throw new LogicException('Anak Perusahaan nonaktif tidak dapat ditetapkan pada transaksi baru.');
                }
            }
        });

        static::updating(function (self $model): void {
            $wasAlreadyLocked = $model->getOriginal('totals_locked_at') !== null;

            if (
                $wasAlreadyLocked && ! $model->allowSnapshotCorrection
                && $model->isDirty([
                    'is_taxable',
                    'ppn_rate',
                    'pph_22_rate',
                    'pph_23_rate',
                    'subtotal',
                    'tax_amount',
                    'grand_total',
                    'price_snapshot',
                    'tax_snapshot',
                    'subsidiary_snapshot',
                ])
            ) {
                throw new LogicException('Snapshot transaksi terkunci; gunakan tindakan koreksi beralasan.');
            }

            if ($model->isDirty('subsidiary_id')) {
                if (
                    $model->officialDocuments()->whereNotNull('issued_at')->exists()
                    && ! $model->allowSubsidiaryCorrection
                ) {
                    throw new LogicException('Anak Perusahaan tidak dapat diganti setelah dokumen resmi diterbitkan tanpa koreksi berwenang.');
                }

                if (
                    $model->subsidiary_id !== null
                    && (int) $model->subsidiary_id !== (int) $model->getOriginal('subsidiary_id')
                ) {
                    $subsidiary = Subsidiary::findOrFail($model->subsidiary_id);

                    if (! $subsidiary->isActive()) {
                        throw new LogicException('Anak Perusahaan nonaktif tidak dapat ditetapkan pada transaksi baru.');
                    }

                    if (
                        $model->officialDocuments()->whereNotNull('issued_at')->exists()
                        && ! $model->allowSubsidiaryCorrection
                    ) {
                        throw new LogicException('Anak Perusahaan tidak dapat diganti setelah dokumen resmi diterbitkan.');
                    }
                }
            }
        });
    }

    public function finalizePriceAgreement(User $actor): void
    {
        if (! $actor->isAdmin() && ! $actor->isSuperAdmin() && ! $actor->isSchool()) {
            throw new AuthorizationException('Pengguna tidak berwenang mengesahkan harga.');
        }

        if ($this->status !== self::STATUS_NEGOTIATION || ! $this->hasSubsidiary()) {
            throw new LogicException('Harga final hanya dapat disahkan dalam negosiasi dengan Anak Perusahaan yang telah ditetapkan.');
        }

        $hasUnapprovedFinalOffer = $this->items()
            ->whereDoesntHave('negotiations', function (Builder $query): void {
                $query->where('status', ProcurementNegotiation::STATUS_ACCEPTED)
                    ->whereNotNull('school_approved_at')
                    ->whereNotNull('provider_approved_at');
            })
            ->exists();

        if (
            ! $this->hasOfficialPrices()
            || $this->items()->where('negotiation_status', '!=', 'accepted')->exists()
            || $hasUnapprovedFinalOffer
        ) {
            throw new LogicException('Harga final memerlukan persetujuan sekolah dan penyedia untuk semua barang.');
        }

        DB::transaction(function () use ($actor): void {
            $this->lockTotals();
            $this->update(['status' => self::STATUS_PRICE_AGREED]);
            $this->recordHistory($actor, self::STATUS_PRICE_AGREED, 'Persetujuan harga kedua pihak tercatat; snapshot harga dikunci.');
            $this->notifyPriceAgreed();
            app(DocumentGenerationService::class)->issue($this, 'negotiation', $actor);
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
            'reviewed_at' => 'datetime',
            'totals_locked_at' => 'datetime',
            'verified_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'tax_configuration_validated' => 'boolean',
            'tax_configuration_validated_at' => 'datetime',
            'price_snapshot' => 'array',
            'tax_snapshot' => 'array',
            'subsidiary_snapshot' => 'array',
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

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function subsidiaryAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subsidiary_assigned_by');
    }

    public function assignSubsidiary(Subsidiary $subsidiary, User $actor): void
    {
        if (! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Admin Pusat yang dapat menetapkan Anak Perusahaan.');
        }

        if (! $subsidiary->isActive()) {
            throw new LogicException('Anak Perusahaan nonaktif tidak dapat ditetapkan pada transaksi baru.');
        }

        if ($this->status !== self::STATUS_VERIFIED) {
            throw new LogicException('Anak Perusahaan hanya dapat ditetapkan setelah pengajuan diverifikasi.');
        }

        if ($this->officialDocuments()->whereNotNull('issued_at')->exists()) {
            throw new LogicException('Anak Perusahaan tidak dapat diganti setelah dokumen resmi diterbitkan.');
        }

        $oldSubsidiaryId = $this->subsidiary_id;
        $assignedAt = now();

        DB::transaction(function () use ($subsidiary, $actor, $oldSubsidiaryId, $assignedAt): void {
            $this->subsidiary_id = $subsidiary->id;
            $this->subsidiary_assigned_by = $actor->id;
            $this->subsidiary_assigned_at = $assignedAt;
            $this->subsidiary_snapshot = $subsidiary->documentIdentitySnapshot();
            $this->save();

            activity()
                ->performedOn($this)
                ->causedBy($actor)
                ->withProperties([
                    'old_subsidiary_id' => $oldSubsidiaryId,
                    'new_subsidiary_id' => $subsidiary->id,
                    'subsidiary_assigned_at' => $assignedAt->toIso8601String(),
                ])
                ->log('Anak Perusahaan transaksi ditetapkan atau diganti');

            if ($this->status === self::STATUS_VERIFIED) {
                $this->issuePlanningDocumentIfReady($actor);
            }
        });

        DB::afterCommit(function () use ($actor): void {
            try {
                $this->notifySupplierAssigned($actor);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::report($e);
            }
        });
    }

    public function correctSubsidiary(Subsidiary $subsidiary, User $actor, string $reason): void
    {
        $this->authorizeInternal($actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new LogicException('Alasan koreksi Anak Perusahaan wajib diisi minimal 5 karakter.');
        }

        if (! $subsidiary->isActive()) {
            throw new LogicException('Anak Perusahaan nonaktif tidak dapat ditetapkan.');
        }

        DB::transaction(function () use ($subsidiary, $actor, $reason): void {
            $oldId = $this->subsidiary_id;
            $oldSnapshot = $this->subsidiary_snapshot;
            $oldTax = $this->taxSnapshot();
            $this->allowSubsidiaryCorrection = true;
            $this->allowSnapshotCorrection = true;

            try {
                $this->forceFill([
                    'subsidiary_id' => $subsidiary->id,
                    'subsidiary_assigned_by' => $actor->id,
                    'subsidiary_assigned_at' => now(),
                    'subsidiary_snapshot' => $subsidiary->documentIdentitySnapshot(),
                    'is_taxable' => null,
                    'ppn_rate' => null,
                    'pph_22_rate' => null,
                    'pph_23_rate' => null,
                    'tax_configuration_validated' => false,
                    'tax_configuration_basis' => null,
                    'tax_configuration_validated_by' => null,
                    'tax_configuration_validated_at' => null,
                    'tax_amount' => null,
                    'grand_total' => null,
                    'tax_snapshot' => null,
                ])->save();
            } finally {
                $this->allowSubsidiaryCorrection = false;
                $this->allowSnapshotCorrection = false;
            }

            activity()
                ->performedOn($this)
                ->causedBy($actor)
                ->withProperties([
                    'old_subsidiary_id' => $oldId,
                    'new_subsidiary_id' => $subsidiary->id,
                    'old_snapshot' => $oldSnapshot,
                    'new_snapshot' => $this->subsidiary_snapshot,
                    'invalidated_tax_configuration' => $oldTax,
                    'reason' => $reason,
                ])
                ->log('Koreksi Anak Perusahaan transaksi');
            $this->recordHistory($actor, $this->status, 'Koreksi Anak Perusahaan: '.$reason);
        });

        DB::afterCommit(function () use ($actor): void {
            try {
                $this->notifySupplierAssigned($actor);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::report($e);
            }
        });
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function paymentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ProcurementSignatory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementDocument::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function officialDocuments(): HasMany
    {
        return $this->documents();
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

    public function deliveries(): HasMany
    {
        return $this->hasMany(ProcurementDelivery::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ProcurementReceipt::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(ProcurementInvoice::class);
    }

    public function payment(): HasOneThrough
    {
        return $this->hasOneThrough(
            ProcurementPayment::class,
            ProcurementInvoice::class,
            'procurement_request_id',
            'procurement_invoice_id'
        );
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

    public function scopeBySubsidiary(Builder $query, int $subsidiaryId): Builder
    {
        return $query->where('subsidiary_id', $subsidiaryId);
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
    }

    public function scopeAwaitingNegotiation(Builder $query): Builder
    {
        return $query->whereHas('items', function ($q) {
            $q->where('negotiation_status', 'negotiating');
        });
    }

    // ─── Report Scopes ──────────────────────────────────────────────

    /**
     * Subquery agregat item per transaksi, dipakai bersama oleh
     * scopeWithReportTotals() dan scopeWithGroupedReportTotals().
     */
    protected static function itemTotalsSubquery(): QueryBuilder
    {
        return DB::table('procurement_request_items')
            ->select('procurement_request_id')
            ->selectRaw('COALESCE(SUM(quantity * estimated_price), 0) AS estimated_subtotal')
            ->selectRaw('COALESCE(SUM(quantity * COALESCE(official_price, estimated_price)), 0) AS official_subtotal')
            ->selectRaw('SUM(CASE WHEN official_price IS NOT NULL THEN 1 ELSE 0 END) AS official_item_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS total_quantity')
            ->selectRaw('COUNT(*) AS total_item_types')
            ->groupBy('procurement_request_id');
    }

    public function scopePeriod(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        $column = 'COALESCE(procurement_requests.requested_at, procurement_requests.created_at)';
        $reportTimezone = config('report.timezone', 'Asia/Jakarta');
        $storageTimezone = config('app.timezone', 'UTC');

        if ($from) {
            $start = Carbon::parse($from, $reportTimezone)->startOfDay()->setTimezone($storageTimezone);
            $query->whereRaw("{$column} >= ?", [$start->toDateTimeString()]);
        }

        if ($to) {
            $end = Carbon::parse($to, $reportTimezone)->endOfDay()->setTimezone($storageTimezone);
            $query->whereRaw("{$column} <= ?", [$end->toDateTimeString()]);
        }

        return $query;
    }

    /**
     * Catatan penamaan: `scopeSchool()` dan `scopeSupplier()` dari dokumen
     * arsitektur tidak dapat dibuat — nama tersebut sudah dipakai relasi
     * `school()` dan `supplier()`, dan Eloquent selalu mendahulukan method yang
     * benar-benar ada di model sebelum scope-nya, sehingga panggilan
     * `ProcurementRequest::school($id)` akan mengarah ke relasi, bukan filter.
     * Kontrak yang sama sudah dipenuhi oleh scopeBySchool() dan
     * scopeBySupplier() yang dipakai di seluruh laporan.
     */
    public function scopeStatus(Builder $query, $status): Builder
    {
        $status = is_array($status) ? array_filter($status) : [$status];
        $status = array_values($status);

        if ($status === []) {
            return $query;
        }

        return $query->whereIn('procurement_requests.status', $status);
    }

    /**
     * Titik masuk tunggal untuk seluruh filter laporan, dipakai bersama oleh
     * Livewire ReportViewer dan ReportController agar hasil preview di layar
     * selalu identik dengan isi file export.
     */
    public function scopeForReport(Builder $query, array $filters = []): Builder
    {
        return $query
            ->period($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->when(! empty($filters['school_id']), fn ($q) => $q->bySchool($filters['school_id']))
            ->when(! empty($filters['subsidiary_id']), fn ($q) => $q->bySubsidiary($filters['subsidiary_id']))
            ->when(! empty($filters['funding_source_id']), fn ($q) => $q->where('funding_source_id', $filters['funding_source_id']))
            ->when(! empty($filters['budget_year_id']), fn ($q) => $q->where('budget_year_id', $filters['budget_year_id']))
            ->when(! empty($filters['status']), fn ($q) => $q->status($filters['status']));
    }

    /**
     * Menyisipkan agregat nilai per transaksi (subtotal estimasi, subtotal
     * dasar, total resmi setelah pajak, jumlah & jenis item) lewat LEFT JOIN ke
     * subquery agregat item — menghindari N+1 saat laporan mengagregasi banyak
     * baris.
     */
    public function scopeWithReportTotals(Builder $query): Builder
    {
        return self::joinReportItemTotals($query)
            ->select('procurement_requests.*')
            ->selectRaw(self::REPORT_ESTIMATED_SQL.' AS report_estimated_subtotal')
            ->selectRaw(self::REPORT_BASE_SQL.' AS report_base_subtotal')
            ->selectRaw(self::REPORT_EFFECTIVE_TOTAL_SQL.' AS report_effective_total')
            ->selectRaw(self::REPORT_QUANTITY_SQL.' AS report_total_quantity')
            ->selectRaw(self::REPORT_ITEM_TYPES_SQL.' AS report_total_item_types');
    }

    /**
     * Versi agregat dari {@see scopeWithReportTotals()} untuk query yang memakai
     * GROUP BY. Karena mode ONLY_FULL_GROUP_BY aktif di MySQL, hanya kolom
     * SCHOOL/SUPPLIER/STATUS yang diagregasi beserta ekspresi agregat di bawah
     * yang boleh dipilih — bukan `procurement_requests.*`.
     */
    public function scopeWithGroupedReportTotals(Builder $query): Builder
    {
        return self::joinReportItemTotals($query);
    }

    protected static function joinReportItemTotals(Builder $query): Builder
    {
        return $query->leftJoinSub(
            self::itemTotalsSubquery(),
            'item_totals',
            'item_totals.procurement_request_id',
            '=',
            'procurement_requests.id'
        );
    }

    /**
     * Literal status untuk dipakai di dalam SQL agregat. Nilai yang di-*splice*
     * ke query selalu berasal dari konstanta kelas (bukan input pengguna),
     * sehingga aman tanpa binding parameter.
     */
    protected static function sqlStatus(string $status): string
    {
        return "'".str_replace("'", "''", $status)."'";
    }

    // ─── State Machine ──────────────────────────────────────────────

    public function canSubmit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_NEEDS_REVISION], true)
            && $this->items()->count() >= self::MINIMUM_ITEMS;
    }

    public function canVerify(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canStartNegotiation(): bool
    {
        return $this->status === self::STATUS_VERIFIED && $this->hasSubsidiary();
    }

    public function canConfirmPayment(): bool
    {
        return $this->status === self::STATUS_PAYMENT_PENDING
            && $this->payment_confirmed_at === null;
    }

    public function completionEligibility(): array
    {
        $payment = $this->payment()->whereNotNull('confirmed_at')->first();

        return [
            'status_payment_pending' => $this->status === self::STATUS_PAYMENT_PENDING,
            'has_items' => $this->items()->exists(),
            'has_subsidiary' => $this->hasSubsidiary(),
            'tax_configuration_valid' => $this->isTaxConfigurationValidated(),
            'all_quantities_received' => $this->allQuantitiesReceived(),
            'no_open_follow_ups' => ! $this->hasOpenReceiptFollowUps(),
            'has_signatories' => $this->hasSignatories(),
            'has_official_prices' => $this->hasOfficialPrices(),
            'all_items_negotiation_settled' => $this->allItemsNegotiationSettled(),
            'administrative_processing_completed' => $this->hasCompletedAdministrativeProcessing(),
            'payment_confirmed' => $payment !== null
                && (float) $payment->amount === (float) ($this->invoice?->total_amount ?? 0),
            'purchase_order_issued' => $this->hasIssuedDocument('purchase_order'),
            'bast_issued' => $this->hasIssuedDocument('bast'),
            'invoice_issued' => $this->hasIssuedDocument('invoice'),
        ];
    }

    public function canComplete(): bool
    {
        return ! in_array(false, $this->completionEligibility(), true);
    }

    public function invoiceEligibility(): array
    {
        return [
            'status_received' => $this->status === self::STATUS_RECEIVED,
            'all_quantities_received' => $this->allQuantitiesReceived(),
            'no_open_follow_ups' => ! $this->hasOpenReceiptFollowUps(),
            'has_signatories' => $this->hasSignatories(),
            'tax_configuration_valid' => $this->isTaxConfigurationValidated(),
            'totals_locked' => $this->isTotalsLocked(),
            'bast_issued' => $this->hasIssuedDocument('bast'),
            'not_yet_invoiced' => ! $this->invoice()->exists(),
        ];
    }

    public function canProcessInvoice(): bool
    {
        return ! in_array(false, $this->invoiceEligibility(), true);
    }

    private function issuePlanningDocumentIfReady(User $actor): void
    {
        if (
            $this->hasSubsidiary()
            && is_string(data_get($this->subsidiary_snapshot, 'letterhead_path'))
            && data_get($this->subsidiary_snapshot, 'letterhead_path') !== ''
        ) {
            app(DocumentGenerationService::class)->issue($this, 'planning', $actor);
        }
    }

    public function canCancel(User $user): bool
    {
        if ($user->isSchool()) {
            return $user->school_id === $this->school_id
                && in_array($this->status, [
                    self::STATUS_DRAFT,
                    self::STATUS_SUBMITTED,
                    self::STATUS_NEEDS_REVISION,
                    self::STATUS_VERIFIED,
                    self::STATUS_NEGOTIATION,
                    self::STATUS_PRICE_AGREED,
                ], true);
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return ! in_array($this->status, [
                self::STATUS_COMPLETED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ], true);
        }

        return false;
    }

    public function canMarkVerified(): bool
    {
        return in_array($this->status, [
            self::STATUS_PROCESSING,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_COMPLETED,
        ], true)
            && $this->verificationFiles()->count() > 0
            && $this->verified_at === null;
    }

    public function canConfirmReceipt(): bool
    {
        return $this->canMarkVerified() && $this->hasVerificationEvidence();
    }

    public function canShowNegotiation(): bool
    {
        return in_array($this->status, [
            self::STATUS_NEGOTIATION,
            self::STATUS_PRICE_AGREED,
            self::STATUS_PROCESSING,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_RECEIVED,
            self::STATUS_INVOICED,
            self::STATUS_PAYMENT_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_DISPUTED,
        ], true) && $this->hasSubsidiary();
    }

    public function canShowProcessing(): bool
    {
        return in_array($this->status, [
            self::STATUS_VERIFIED,
            self::STATUS_NEGOTIATION,
            self::STATUS_PRICE_AGREED,
            self::STATUS_PROCESSING,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_RECEIVED,
            self::STATUS_INVOICED,
            self::STATUS_PAYMENT_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_DISPUTED,
        ], true);
    }

    public function canShowReceiptVerification(): bool
    {
        return $this->verified_at !== null
            || in_array($this->status, [
                self::STATUS_PROCESSING,
                self::STATUS_PARTIALLY_RECEIVED,
                self::STATUS_RECEIVED,
                self::STATUS_DISPUTED,
                self::STATUS_COMPLETED,
            ], true);
    }

    public function hasVerificationEvidence(): bool
    {
        return $this->verificationFiles()
            ->where('file_type', ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT)
            ->count() > 0;
    }

    public function submit(User $user): void
    {
        $this->authorizeSchoolOwner($user);

        if (! $this->canSubmit()) {
            throw new Exception('Pengajuan tidak dapat di-submit. Pastikan status draft atau perlu revisi dan memiliki minimal 1 item.');
        }

        $hasInvalidSpecification = $this->items()
            ->where(function (Builder $query): void {
                $query->whereNull('specification')
                    ->orWhere('specification', '')
                    ->orWhere('specification', '-');
            })
            ->exists();

        if ($hasInvalidSpecification) {
            throw new Exception('Spesifikasi barang wajib diisi dan tidak boleh menggunakan placeholder "-".');
        }

        DB::transaction(function () use ($user): void {
            $this->update([
                'status' => self::STATUS_SUBMITTED,
                'requested_at' => now(),
            ]);
            $this->recordHistory($user, self::STATUS_SUBMITTED);
        });
    }

    public function verify(User $user): void
    {
        $this->authorizeInternal($user);
        if (! $this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat diverifikasi.');
        }

        DB::transaction(function () use ($user): void {
            $this->update([
                'status' => self::STATUS_VERIFIED,
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
            ]);
            $this->recordHistory($user, self::STATUS_VERIFIED);
            $this->notifyVerified();
            if ($this->hasSubsidiary()) {
                $this->issuePlanningDocumentIfReady($user);
            }
        });
    }

    public function requestRevision(User $user, string $reason): void
    {
        $this->authorizeInternal($user);

        if (! $this->canVerify()) {
            throw new Exception('Revisi hanya dapat diminta untuk pengajuan berstatus submitted.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new Exception('Alasan revisi wajib diisi minimal 5 karakter.');
        }

        DB::transaction(function () use ($user, $reason): void {
            $this->update(['status' => self::STATUS_NEEDS_REVISION]);
            $this->recordHistory($user, self::STATUS_NEEDS_REVISION, 'Revisi diminta: '.$reason);
        });

        DB::afterCommit(function () use ($reason): void {
            try {
                $this->notifyNeedsRevision($reason);
            } catch (\Throwable $e) {
                \Log::warning('Gagal mengirim notifikasi revisi', [
                    'request_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function reject(User $user, string $reason): void
    {
        $this->authorizeInternal($user);
        if (! $this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat ditolak.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new Exception('Alasan penolakan wajib diisi minimal 5 karakter.');
        }

        DB::transaction(function () use ($user, $reason): void {
            $this->update(['status' => self::STATUS_REJECTED]);
            $this->recordHistory($user, self::STATUS_REJECTED, 'Ditolak: '.$reason);
        });

        DB::afterCommit(function () use ($reason): void {
            try {
                $this->notifyRejected($reason);
            } catch (\Throwable $e) {
                \Log::warning('Gagal mengirim notifikasi penolakan', [
                    'request_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function startNegotiation(User $user): void
    {
        $this->authorizeInternal($user);

        if (! $this->hasSubsidiary()) {
            throw new LogicException('Tetapkan Anak Perusahaan aktif sebelum memulai negosiasi.');
        }

        if (! $this->canStartNegotiation()) {
            throw new Exception('Hanya pengajuan terverifikasi yang dapat diteruskan ke negosiasi.');
        }

        DB::transaction(function () use ($user): void {
            $this->update(['status' => self::STATUS_NEGOTIATION]);
            $this->recordHistory($user, self::STATUS_NEGOTIATION);
        });
    }

    public function confirmPayment(User $user): void
    {
        $this->authorizeInternal($user);

        if (! $this->canConfirmPayment()) {
            throw new Exception('Pembayaran hanya dapat dikonfirmasi satu kali pada status menunggu pembayaran.');
        }

        app(ProcurementProcessingService::class)->confirmPayment(
            $this->payment()->firstOrFail(),
            $user
        );
    }

    public function complete(User $user): void
    {
        $this->authorizeInternal($user);

        $eligibility = $this->completionEligibility();
        if (in_array(false, $eligibility, true)) {
            $labels = [
                'status_payment_pending' => 'status transaksi belum menunggu pembayaran',
                'has_items' => 'daftar barang kosong',
                'has_subsidiary' => 'Anak Perusahaan belum ditetapkan',
                'tax_configuration_valid' => 'konfigurasi pajak belum tervalidasi',
                'all_quantities_received' => 'penerimaan barang belum penuh',
                'no_open_follow_ups' => 'tindak lanjut penerimaan masih terbuka',
                'has_signatories' => 'penandatangan sekolah (Kepala Sekolah, Pemeriksa, Bendahara) belum lengkap',
                'has_official_prices' => 'harga resmi barang belum lengkap',
                'all_items_negotiation_settled' => 'negosiasi barang belum selesai',
                'administrative_processing_completed' => 'verifikasi penerimaan atau faktur belum tercatat',
                'payment_confirmed' => 'pembayaran terkonfirmasi belum sesuai total faktur',
                'purchase_order_issued' => 'Surat Pesanan belum diterbitkan',
                'bast_issued' => 'BAST belum diterbitkan',
                'invoice_issued' => 'Faktur belum diterbitkan',
            ];

            $missing = collect($eligibility)
                ->filter(fn (bool $passed): bool => ! $passed)
                ->keys()
                ->map(fn (string $key): string => $labels[$key] ?? $key)
                ->implode(', ');

            throw new Exception("Pengadaan belum dapat diselesaikan: {$missing}.");
        }

        DB::transaction(function () use ($user): void {
            $this->update(['status' => self::STATUS_COMPLETED]);
            $this->recordHistory($user, self::STATUS_COMPLETED);
            app(DocumentGenerationService::class)->issue($this, 'receipt', $user);
            app(DocumentGenerationService::class)->issue($this, 'cover', $user);
        });
    }

    public function cancel(User $user, string $reason): void
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new Exception('Alasan pembatalan wajib diisi minimal 5 karakter.');
        }

        if (mb_strlen($reason) > 1000) {
            throw new Exception('Alasan pembatalan maksimal 1000 karakter.');
        }

        if (! $this->canCancel($user)) {
            throw new Exception('Pengajuan tidak dapat dibatalkan oleh pengguna ini pada status saat ini.');
        }

        DB::transaction(function () use ($user, $reason): void {
            $this->update(['status' => self::STATUS_CANCELLED]);
            $this->recordHistory($user, self::STATUS_CANCELLED, 'Dibatalkan: '.$reason);
        });

        DB::afterCommit(function () use ($user, $reason): void {
            try {
                $this->notifyCancelled($reason, $user);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::report($e);
            }
        });
    }

    private function authorizeSchoolOwner(User $user): void
    {
        if (! $user->isSchool() || $user->school_id !== $this->school_id) {
            throw new AuthorizationException('Hanya sekolah pemilik pengajuan yang dapat melakukan tindakan ini.');
        }
    }

    private function authorizeInternal(User $user): void
    {
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Admin CV atau Superadmin yang dapat melakukan tindakan ini.');
        }
    }

    public function markVerified(User $user): void
    {
        if (! $this->canMarkVerified()) {
            throw new Exception('Verifikasi penerimaan tidak dapat dilakukan. Pastikan minimal 1 bukti sudah diunggah dan status pengadaan sedang diproses, diterima sebagian, atau selesai.');
        }

        $this->update([
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        $this->recordHistory($user, $this->status, 'Barang telah diverifikasi diterima oleh sekolah.');
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
        if (! $this->isTaxConfigurationValidated()) {
            throw new LogicException('Pajak belum tervalidasi; total pajak final tidak tersedia.');
        }
        if (! $this->is_taxable) {
            return 0;
        }

        return round($this->baseSubtotal() * ($this->ppn_rate / 100), 2);
    }

    public function totalPph22(): float
    {
        if (! $this->isTaxConfigurationValidated()) {
            throw new LogicException('Pajak belum tervalidasi; total pajak final tidak tersedia.');
        }

        $pphItems = $this->items()->where('is_pph', true);

        if ($pphItems->count() === 0) {
            return 0;
        }

        $pphBase = (float) $pphItems->sum(DB::raw('quantity * COALESCE(official_price, estimated_price)'));

        return round($pphBase * ($this->pph_22_rate / 100), 2);
    }

    public function totalPph23(): float
    {
        if (! $this->isTaxConfigurationValidated()) {
            throw new LogicException('Pajak belum tervalidasi; total pajak final tidak tersedia.');
        }

        $pphItems = $this->items()->where('is_pph', true);

        if ($pphItems->count() === 0) {
            return 0;
        }

        $pphBase = (float) $pphItems->sum(DB::raw('quantity * COALESCE(official_price, estimated_price)'));

        return round($pphBase * ($this->pph_23_rate / 100), 2);
    }

    public function grandTotal(): float
    {
        if (! $this->isTaxConfigurationValidated()) {
            throw new LogicException('Pajak belum tervalidasi; total transaksi final tidak tersedia.');
        }

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
        if (! $this->hasSubsidiary() || ! $this->hasOfficialPrices()) {
            throw new LogicException('Snapshot harga memerlukan Anak Perusahaan dan harga final untuk setiap barang.');
        }

        if ($this->isTotalsLocked()) {
            return;
        }

        $lines = $this->items()->with('unit')->get()->map(fn (ProcurementRequestItem $item): array => [
            'item_id' => $item->id,
            'item_name' => $item->item_name,
            'specification' => $item->specification,
            'unit' => $item->unit?->name,
            'quantity' => $item->quantity,
            'official_price' => (string) $item->official_price,
            'amount' => number_format((float) $item->quantity * (float) $item->official_price, 2, '.', ''),
        ])->all();
        $subtotal = round(array_sum(array_map(fn (array $line): float => (float) $line['amount'], $lines)), 2);
        $taxAmount = $this->isTaxConfigurationValidated()
            ? $this->totalPpn()
            : null;

        $this->allowSnapshotCorrection = true;

        try {
            $this->forceFill([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $taxAmount === null ? null : $subtotal + $taxAmount,
                'totals_locked_at' => now(),
                'price_snapshot' => ['items' => $lines, 'subtotal' => number_format($subtotal, 2, '.', '')],
                'tax_snapshot' => $this->taxSnapshot(),
                'subsidiary_snapshot' => $this->subsidiary()->firstOrFail()->documentIdentitySnapshot(),
            ])->save();
        } finally {
            $this->allowSnapshotCorrection = false;
        }
    }

    public function isTaxConfigurationValidated(): bool
    {
        return $this->tax_configuration_validated
            && $this->tax_configuration_validated_at !== null
            && $this->tax_configuration_validated_by !== null
            && filled($this->tax_configuration_basis)
            && $this->is_taxable !== null
            && (! $this->is_taxable || $this->ppn_rate !== null)
            && (! $this->items()->where('is_pph', true)->exists()
                || ($this->pph_22_rate !== null && $this->pph_23_rate !== null));
    }

    public function evaluateTaxConfiguration(User $actor, array $context = []): array
    {
        return app(TaxEvaluationService::class)->evaluateAndApply($this, $actor, $context);
    }

    public function applyTaxEvaluation(
        TaxEvaluationService $service,
        User $actor,
        array $evaluation
    ): void {
        $this->authorizeInternal($actor);

        DB::transaction(function () use ($service, $actor, $evaluation): void {
            $locked = static::query()->lockForUpdate()->findOrFail($this->id);

            if (! $service->isCurrentEvaluation($locked, $evaluation)) {
                throw new LogicException('Hasil evaluasi pajak tidak valid atau sudah berubah; evaluasi ulang sebelum diterapkan.');
            }

            if ($locked->invoice()->exists()) {
                throw new LogicException('Konfigurasi pajak tidak dapat diubah setelah invoice diproses.');
            }

            $locked->allowSnapshotCorrection = true;

            try {
                $resolved = $evaluation['status'] === 'RESOLVED';
                $rates = $evaluation['rates'];
                $locked->forceFill([
                    'is_taxable' => $resolved ? true : null,
                    'ppn_rate' => $resolved ? $rates[TaxRule::TAX_PPN] : null,
                    'pph_22_rate' => $resolved ? $rates[TaxRule::TAX_PPH22] : null,
                    'pph_23_rate' => $resolved ? $rates[TaxRule::TAX_PPH23] : null,
                    'tax_configuration_validated' => $resolved,
                    'tax_configuration_basis' => $evaluation['basis'],
                    'tax_configuration_validated_by' => $resolved ? $actor->id : null,
                    'tax_configuration_validated_at' => $resolved ? now() : null,
                ])->save();

                if ($locked->isTotalsLocked()) {
                    $locked->forceFill([
                        'tax_amount' => $resolved ? $locked->totalPpn() : null,
                        'grand_total' => $resolved ? $locked->baseSubtotal() + $locked->totalPpn() : null,
                        'tax_snapshot' => $resolved ? $locked->taxSnapshot() : null,
                    ])->save();
                }
            } finally {
                $locked->allowSnapshotCorrection = false;
            }

            $locked->recordHistory(
                $actor,
                $locked->status,
                $resolved
                ? 'Konfigurasi pajak diterapkan dari aturan tervalidasi.'
                : 'Konfigurasi pajak belum tervalidasi: '.$evaluation['basis']
            );
            activity()
                ->performedOn($locked)
                ->causedBy($actor)
                ->withProperties([
                    'status' => $evaluation['status'],
                    'rules' => $evaluation['rules'],
                    'rates' => $resolved ? $evaluation['rates'] : null,
                ])
                ->log('Evaluasi aturan pajak transaksi');
        });

        $this->refresh();
    }

    private function taxSnapshot(): array
    {
        $validated = $this->isTaxConfigurationValidated();

        return [
            'validated' => $validated,
            'basis' => $this->tax_configuration_basis,
            'validated_at' => $this->tax_configuration_validated_at?->toIso8601String(),
            'validated_by' => $this->tax_configuration_validated_by,
            'is_taxable' => $this->is_taxable,
            'ppn_rate' => $this->ppn_rate,
            'pph_22_rate' => $this->pph_22_rate,
            'pph_23_rate' => $this->pph_23_rate,
            'ppn_amount' => $validated ? $this->totalPpn() : null,
            'pph_22_amount' => $validated ? $this->totalPph22() : null,
            'pph_23_amount' => $validated ? $this->totalPph23() : null,
        ];
    }

    // ─── Report ─────────────────────────────────────────────────────

    public static function statusLabels(): array
    {
        return self::STATUS_LABELS;
    }

    public static function statusLabel(string $status): string
    {
        return self::statusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function reportStatuses(): array
    {
        return array_keys(self::statusLabels());
    }

    /**
     * Ringkasan angka untuk laporan. Semua agregasi terjadi di database —
     * transaksi yang tidak cocok filter sudah tersaring oleh scopeForReport()
     * sebelum method ini dipanggil.
     */
    public static function reportSummary(Builder $query): array
    {
        $aggregate = $query
            ->withGroupedReportTotals()
            ->selectRaw('COUNT(procurement_requests.id) AS total_transactions')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ESTIMATED_SQL.'), 0) AS total_estimated')
            ->selectRaw('COALESCE(SUM('.self::REPORT_EFFECTIVE_TOTAL_SQL.'), 0) AS total_official')
            ->selectRaw('COALESCE(SUM('.self::REPORT_QUANTITY_SQL.'), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ITEM_TYPES_SQL.'), 0) AS total_item_types')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_COMPLETED).' THEN 1 ELSE 0 END), 0) AS completed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_REJECTED).' THEN 1 ELSE 0 END), 0) AS rejected_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.totals_locked_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS locked_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.subsidiary_id IS NULL THEN 1 ELSE 0 END), 0) AS unsubsidiaried_count')
            ->first();

        $totalTransactions = (int) ($aggregate->total_transactions ?? 0);
        $totalOfficial = (float) ($aggregate->total_official ?? 0);

        return [
            'total_transactions' => $totalTransactions,
            'total_quantity' => (int) ($aggregate->total_quantity ?? 0),
            'total_item_types' => (int) ($aggregate->total_item_types ?? 0),
            'total_estimated' => round((float) ($aggregate->total_estimated ?? 0), 2),
            'total_official' => round($totalOfficial, 2),
            'average_value' => $totalTransactions > 0 ? round($totalOfficial / $totalTransactions, 2) : 0.0,
            'saving_amount' => round((float) ($aggregate->total_estimated ?? 0) - $totalOfficial, 2),
            'completed_count' => (int) ($aggregate->completed_count ?? 0),
            'rejected_count' => (int) ($aggregate->rejected_count ?? 0),
            'locked_count' => (int) ($aggregate->locked_count ?? 0),
            'unsubsidiaried_count' => (int) ($aggregate->unsubsidiaried_count ?? 0),
        ];
    }

    /**
     * Rekap nilai pengadaan per sekolah. Hanya sekolah yang memiliki transaksi
     * pada rentang filter yang muncul, diurutkan dari nilai terbesar.
     */
    public static function reportBySchool(Builder $query): Collection
    {
        return $query
            ->withGroupedReportTotals()
            ->join('schools', 'schools.id', '=', 'procurement_requests.school_id')
            ->groupBy('schools.id', 'schools.name', 'schools.npsn')
            ->orderByDesc('total_official')
            ->orderBy('schools.name')
            ->selectRaw('schools.id AS school_id, schools.name AS school_name, schools.npsn AS school_npsn')
            ->selectRaw('COUNT(procurement_requests.id) AS total_transactions')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ESTIMATED_SQL.'), 0) AS total_estimated')
            ->selectRaw('COALESCE(SUM('.self::REPORT_EFFECTIVE_TOTAL_SQL.'), 0) AS total_official')
            ->selectRaw('COALESCE(SUM('.self::REPORT_QUANTITY_SQL.'), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_COMPLETED).' THEN 1 ELSE 0 END), 0) AS completed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_REJECTED).' THEN 1 ELSE 0 END), 0) AS rejected_count')
            ->get()
            ->map(fn ($row) => [
                'school_id' => (int) $row->school_id,
                'school_name' => $row->school_name,
                'school_npsn' => $row->school_npsn,
                'total_transactions' => (int) $row->total_transactions,
                'total_quantity' => (int) $row->total_quantity,
                'total_estimated' => round((float) $row->total_estimated, 2),
                'total_official' => round((float) $row->total_official, 2),
                'completed_count' => (int) $row->completed_count,
                'rejected_count' => (int) $row->rejected_count,
            ])
            ->values();
    }

    /**
     * Rekap nilai pengadaan per Anak Perusahaan. Transaksi yang Anak
     * Perusahaan-nya belum ditentukan dikelompokkan ke baris
     * "Belum Ditetapkan Anak Perusahaan" sehingga tidak hilang dari total grup.
     */
    public static function reportBySubsidiary(Builder $query): Collection
    {
        return $query
            ->withGroupedReportTotals()
            ->leftJoin('subsidiaries', 'subsidiaries.id', '=', 'procurement_requests.subsidiary_id')
            ->groupBy('subsidiaries.id', 'subsidiaries.name')
            ->orderByDesc('total_official')
            ->orderBy('subsidiaries.name')
            ->selectRaw('subsidiaries.id AS subsidiary_id, COALESCE(subsidiaries.name, \'Belum Ditetapkan Anak Perusahaan\') AS subsidiary_name')
            ->selectRaw('COUNT(procurement_requests.id) AS total_transactions')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ESTIMATED_SQL.'), 0) AS total_estimated')
            ->selectRaw('COALESCE(SUM('.self::REPORT_EFFECTIVE_TOTAL_SQL.'), 0) AS total_official')
            ->selectRaw('COALESCE(SUM('.self::REPORT_QUANTITY_SQL.'), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_COMPLETED).' THEN 1 ELSE 0 END), 0) AS completed_count')
            ->get()
            ->map(fn ($row) => [
                'subsidiary_id' => $row->subsidiary_id === null ? null : (int) $row->subsidiary_id,
                'subsidiary_name' => $row->subsidiary_name,
                'total_transactions' => (int) $row->total_transactions,
                'total_quantity' => (int) $row->total_quantity,
                'total_estimated' => round((float) $row->total_estimated, 2),
                'total_official' => round((float) $row->total_official, 2),
                'completed_count' => (int) $row->completed_count,
            ])
            ->values();
    }

    /**
     * Rekap per status. Semua status selalu ditampilkan (zero-filled) supaya
     * pembaca laporan bisa langsung melihat alur transaksi yang belum selesai
     * tanpa harus menebak angka nol.
     */
    public static function reportByStatus(Builder $query): Collection
    {
        $aggregated = $query
            ->withGroupedReportTotals()
            ->groupBy('procurement_requests.status')
            ->selectRaw('procurement_requests.status AS status')
            ->selectRaw('COUNT(procurement_requests.id) AS total_transactions')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ESTIMATED_SQL.'), 0) AS total_estimated')
            ->selectRaw('COALESCE(SUM('.self::REPORT_EFFECTIVE_TOTAL_SQL.'), 0) AS total_official')
            ->selectRaw('COALESCE(SUM('.self::REPORT_QUANTITY_SQL.'), 0) AS total_quantity')
            ->get()
            ->keyBy('status');

        return collect(self::statusLabels())
            ->map(function (string $label, string $status) use ($aggregated) {
                $row = $aggregated->get($status);

                return [
                    'status' => $status,
                    'label' => $label,
                    'total_transactions' => (int) ($row->total_transactions ?? 0),
                    'total_quantity' => (int) ($row->total_quantity ?? 0),
                    'total_estimated' => round((float) ($row->total_estimated ?? 0), 2),
                    'total_official' => round((float) ($row->total_official ?? 0), 2),
                ];
            })
            ->values();
    }

    /**
     * Daftar transaksi untuk tabel detail & sheet "Transaksi". Nilai uang
     * diambil dari agregat report_effective_total agar konsisten dengan
     * ringkasan dan rekap lain.
     */
    public static function reportTransactions(Builder $query, ?int $limit = null, int $offset = 0): Collection
    {
        return $query
            ->withReportTotals()
            ->with([
                'school' => fn ($q) => $q->withTrashed()->select('id', 'name', 'npsn'),
                'subsidiary' => fn ($q) => $q->select('id', 'name', 'status'),
                'fundingSource:id,name',
                'budgetYear:id,name',
                'packageCategory:id,name',
            ])
            ->orderByDesc('procurement_requests.created_at')
            ->orderByDesc('procurement_requests.id')
            ->when($limit, fn ($q) => $q->offset($offset)->limit($limit))
            ->get()
            ->map(fn (self $request) => self::mapReportTransaction($request))
            ->values();
    }

    public static function mapReportTransaction(self $request): array
    {
        return [
            'id' => $request->id,
            'uuid' => $request->uuid,
            'school_name' => $request->school?->name ?? '-',
            'school_npsn' => $request->school?->npsn ?? '-',
            'subsidiary_name' => $request->subsidiary?->name ?? 'Belum Ditetapkan',
            'funding_source_name' => $request->fundingSource?->name ?? '-',
            'budget_year_name' => $request->budgetYear?->name ?? '-',
            'category_name' => $request->packageCategory?->name ?? '-',
            'status' => $request->status,
            'status_label' => self::statusLabel($request->status),
            'requested_at' => $request->requested_at,
            'created_at' => $request->created_at,
            'total_quantity' => (int) $request->report_total_quantity,
            'total_item_types' => (int) $request->report_total_item_types,
            'total_estimated' => round((float) $request->report_estimated_subtotal, 2),
            'total_official' => round((float) $request->report_effective_total, 2),
        ];
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
            ->sum(DB::raw('COALESCE(grand_total, subtotal)'));

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

    public function hasSupplier(): bool
    {
        return ! empty($this->supplier_id);
    }

    public function hasSubsidiary(): bool
    {
        return ! empty($this->subsidiary_id);
    }

    public function allQuantitiesReceived(): bool
    {
        if (! $this->items()->exists()) {
            return false;
        }

        return $this->items->every(
            fn (ProcurementRequestItem $item): bool => $item->remainingToReceive() <= 0
        );
    }

    public function hasOpenReceiptFollowUps(): bool
    {
        return ProcurementReceiptItem::query()
            ->whereHas('receipt', fn (Builder $query) => $query->where('procurement_request_id', $this->id))
            ->where('follow_up_status', ProcurementReceiptItem::FOLLOW_UP_OPEN)
            ->exists();
    }

    public function hasIssuedDocument(string $type): bool
    {
        return $this->documents()->where('document_type', $type)->whereNotNull('issued_at')->exists();
    }

    public function hasCompletedAdministrativeProcessing(): bool
    {
        return $this->receipts()->whereNotNull('verified_at')->exists()
            && $this->invoice()->exists();
    }

    public function hasSignatories(): bool
    {
        $requiredRoles = ['headmaster', 'inspector', 'treasurer'];
        $existingRoles = $this->signatories()->pluck('role')->toArray();

        foreach ($requiredRoles as $role) {
            if (! in_array($role, $existingRoles, true)) {
                return false;
            }
        }

        return true;
    }

    public function hasOfficialPrices(): bool
    {
        return $this->items()
            ->where(function (Builder $query): void {
                $query->whereNull('official_price')
                    ->orWhere('official_price', '<=', 0);
            })
            ->where('negotiation_status', '!=', 'rejected')
            ->doesntExist();
    }

    public function allItemsNegotiationSettled(): bool
    {
        return $this->items()
            ->whereNotIn('negotiation_status', ['accepted', 'rejected'])
            ->count() === 0;
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function packageCategoryNameAttribute(): string
    {
        return $this->packageCategory?->name ?? '-';
    }

    protected function budgetYearNameAttribute(): string
    {
        return $this->budgetYear?->name ?? '-';
    }

    protected function fundingSourceNameAttribute(): string
    {
        return $this->fundingSource?->name ?? '-';
    }

    public function negotiationProgress(): array
    {
        $total = $this->items()->count();
        $accepted = $this->items()->where('negotiation_status', 'accepted')->count();
        $rejected = $this->items()->where('negotiation_status', 'rejected')->count();
        $settled = $accepted + $rejected;
        $pending = $total - $settled;

        return [
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'pending' => $pending,
            'settled' => $settled,
            'percentage' => $total > 0 ? round(($settled / $total) * 100) : 0,
        ];
    }

    public function isReadyForDocumentGeneration(): bool
    {
        return $this->hasSupplier()
            && $this->hasSubsidiary()
            && $this->hasSignatories()
            && $this->hasOfficialPrices();
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

    // ─── Notification Methods ─────────────────────────────────────

    private function getNotificationService(): NotificationService
    {
        return app(NotificationService::class);
    }

    public function notifySubmitted(): void
    {
        $this->getNotificationService()->notify($this, 'submitted', [], auth()->user());
    }

    public function notifyVerified(): void
    {
        $this->getNotificationService()->notify($this, 'verified', [], auth()->user());
    }

    public function notifyRejected(string $reason): void
    {
        $this->getNotificationService()->notify($this, 'rejected', ['reason' => $reason], auth()->user());
    }

    public function notifyNeedsRevision(string $reason): void
    {
        $this->getNotificationService()->notify($this, 'needs_revision', ['reason' => $reason], auth()->user());
    }

    public function notifySupplierAssigned(?User $actor = null): void
    {
        $this->getNotificationService()->notify($this, 'supplier_assigned', [], $actor ?? auth()->user());
    }

    public function notifyCancelled(string $reason, ?User $actor = null): void
    {
        $this->getNotificationService()->notify($this, 'cancelled', ['reason' => $reason], $actor ?? auth()->user());
    }

    public function notifyNegotiationOffer(ProcurementRequestItem $item, ProcurementNegotiation $negotiation, array $extraContext = []): void
    {
        $this->getNotificationService()->notify($this, 'negotiation_offer', array_merge([
            'item' => $item,
            'negotiation' => $negotiation,
            'offered_by' => $negotiation->offered_by,
        ], $extraContext), auth()->user());
    }

    public function notifyNegotiationDecided(ProcurementRequestItem $item, ProcurementNegotiation $negotiation, string $action, array $extraContext = []): void
    {
        $event = $action === 'accepted' ? 'negotiation_accepted' : 'negotiation_rejected';
        $decidedBy = $extraContext['decided_by'] ?? (auth()->user()?->isSchool() ? 'school' : 'admin');
        $this->getNotificationService()->notify($this, $event, array_merge([
            'item' => $item,
            'negotiation' => $negotiation,
            'decided_by' => $decidedBy,
        ], $extraContext), auth()->user());
    }

    public function notifyItemsPrepared(): void
    {
        $this->getNotificationService()->notify($this, 'items_prepared', [], auth()->user());
    }

    public function notifyReceiptVerified(): void
    {
        $this->getNotificationService()->notify($this, 'receipt_verified', [], auth()->user());
    }

    public function notifyCompleted(): void
    {
        $this->getNotificationService()->notify($this, 'completed', [], auth()->user());
    }

    public function notifyDocumentGenerated(array $context = []): void
    {
        $this->getNotificationService()->notify($this, 'document_generated', $context, auth()->user());
    }

    public function notifyPaymentRecorded(array $context): void
    {
        $this->getNotificationService()->notify($this, 'record_payment', $context, auth()->user());
    }

    public function notifyPaymentConfirmed(array $context): void
    {
        $this->getNotificationService()->notify($this, 'confirm_payment', $context, auth()->user());
    }

    public function notifyPriceAgreed(): void
    {
        $this->getNotificationService()->notify($this, 'price_agreed', [], auth()->user());
    }
}
