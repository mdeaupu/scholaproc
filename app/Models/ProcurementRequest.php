<?php

namespace App\Models;

use App\Services\DocumentGenerationService;
use App\Services\NotificationService;
use App\Services\ProcurementNumberGenerator;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_SUPPLIER_ASSIGNED = 'supplier_assigned';

    public const STATUS_ITEMS_PREPARED = 'items_prepared';

    public const STATUS_COMPLETED = 'completed';

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
            ->when(! empty($filters['supplier_id']), fn ($q) => $q->bySupplier($filters['supplier_id']))
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
        return $this->status === self::STATUS_ITEMS_PREPARED
            && $this->hasSignatories()
            && $this->hasOfficialPrices()
            && $this->allItemsNegotiationSettled();
    }

    public function canStartNegotiation(): bool
    {
        return $this->status === self::STATUS_SUPPLIER_ASSIGNED
            && $this->supplier_id !== null;
    }

    public function canMarkVerified(): bool
    {
        return in_array($this->status, [self::STATUS_ITEMS_PREPARED, self::STATUS_COMPLETED])
            && $this->verificationFiles()->count() > 0
            && $this->verified_at === null;
    }

    public function hasVerificationEvidence(): bool
    {
        return $this->verificationFiles()
            ->where('file_type', ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT)
            ->count() > 0;
    }

    public function submit(User $user): void
    {
        if (! $this->canSubmit()) {
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
        if (! $this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat diverifikasi.');
        }

        $this->update(['status' => self::STATUS_VERIFIED]);
        $this->recordHistory($user, self::STATUS_VERIFIED);
    }

    public function reject(User $user, string $reason): void
    {
        if (! $this->canVerify()) {
            throw new Exception('Hanya pengajuan berstatus submitted yang dapat ditolak.');
        }

        $this->update(['status' => self::STATUS_REJECTED]);
        $this->recordHistory($user, self::STATUS_REJECTED, 'Ditolak: '.$reason);
    }

    public function assignSupplier(Supplier $supplier, User $user): void
    {
        if (! $this->canAssignSupplier()) {
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
        if (! $this->canPrepareItems()) {
            throw new Exception('Hanya pengajuan berstatus supplier_assigned yang dapat ditandai siap.');
        }

        $this->update(['status' => self::STATUS_ITEMS_PREPARED]);
        $this->recordHistory($user, self::STATUS_ITEMS_PREPARED);
    }

    public function complete(User $user): void
    {
        if (! $this->canComplete()) {
            throw new Exception('Hanya pengajuan berstatus items_prepared yang dapat diselesaikan. Pastikan pejabat penandatangan dan harga resmi sudah dilengkapi.');
        }

        $this->lockTotals();
        $this->update(['status' => self::STATUS_COMPLETED]);
        $this->recordHistory($user, self::STATUS_COMPLETED);
    }

    public function markVerified(User $user): void
    {
        if (! $this->canMarkVerified()) {
            throw new Exception('Verifikasi penerimaan tidak dapat dilakukan. Pastikan minimal 1 bukti dokumen bertanda tangan sudah diunggah dan status pengadaan adalah barang siap atau selesai.');
        }

        $this->update([
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        $this->recordHistory($user, 'verified_receipt', 'Barang telah diverifikasi diterima oleh sekolah.');
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
        if (! $this->is_taxable) {
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

        $this->recordHistory($user, $this->status, 'Revisi: '.$reason);
    }

    // ─── Report ─────────────────────────────────────────────────────

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_VERIFIED => 'Diverifikasi',
            self::STATUS_SUPPLIER_ASSIGNED => 'Supplier Ditentukan',
            self::STATUS_ITEMS_PREPARED => 'Barang Siap',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_REJECTED => 'Ditolak',
        ];
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
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.supplier_id IS NULL THEN 1 ELSE 0 END), 0) AS unsuppliered_count')
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
            'unsuppliered_count' => (int) ($aggregate->unsuppliered_count ?? 0),
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
     * Rekap nilai pengadaan per supplier. Transaksi yang supplier-nya belum
     * ditentukan sengaja tidak dimasukkan — kolom supplier tidak akan pernah
     * terisi pada rekap ini.
     */
    public static function reportBySupplier(Builder $query): Collection
    {
        return $query
            ->withGroupedReportTotals()
            ->whereNotNull('procurement_requests.supplier_id')
            ->join('suppliers', 'suppliers.id', '=', 'procurement_requests.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->orderByDesc('total_official')
            ->orderBy('suppliers.company_name')
            ->selectRaw('suppliers.id AS supplier_id, suppliers.company_name AS company_name')
            ->selectRaw('COUNT(procurement_requests.id) AS total_transactions')
            ->selectRaw('COALESCE(SUM('.self::REPORT_ESTIMATED_SQL.'), 0) AS total_estimated')
            ->selectRaw('COALESCE(SUM('.self::REPORT_EFFECTIVE_TOTAL_SQL.'), 0) AS total_official')
            ->selectRaw('COALESCE(SUM('.self::REPORT_QUANTITY_SQL.'), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM(CASE WHEN procurement_requests.status = '.self::sqlStatus(self::STATUS_COMPLETED).' THEN 1 ELSE 0 END), 0) AS completed_count')
            ->get()
            ->map(fn ($row) => [
                'supplier_id' => (int) $row->supplier_id,
                'company_name' => $row->company_name,
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
                'supplier' => fn ($q) => $q->withTrashed()->select('id', 'company_name'),
                'packageCategory:id,name',
                'budgetYear:id,name',
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
            'supplier_name' => $request->supplier?->company_name ?? null,
            'category_name' => $request->packageCategory?->name ?? '-',
            'budget_year_name' => $request->budgetYear?->name ?? '-',
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
        return ! empty($this->supplier_id);
    }

    public function hasSignatories(): bool
    {
        $requiredRoles = ['headmaster', 'inspector', 'treasurer'];
        $existingRoles = $this->signatories()->pluck('role')->toArray();

        foreach ($requiredRoles as $role) {
            if (! in_array($role, $existingRoles)) {
                return false;
            }
        }

        return true;
    }

    public function hasOfficialPrices(): bool
    {
        return $this->items()
            ->where(function ($q) {
                $q->whereNull('official_price')
                    ->orWhere('official_price', '<=', 0);
            })
            ->where('negotiation_status', '!=', 'rejected')
            ->count() === 0;
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
        return $this->hasSupplier() && $this->hasSignatories() && $this->hasOfficialPrices();
    }

    public function generateOfficialDocuments(): void
    {
        if (! $this->isReadyForDocumentGeneration()) {
            throw new Exception('Gagal generate. Mohon lengkapi data supplier, penandatangan, dan harga resmi terlebih dahulu.');
        }

        $documentTypes = ['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt', 'supplier_declaration'];

        DB::transaction(function () use ($documentTypes) {
            foreach ($documentTypes as $type) {
                $doc = $this->documents()->firstOrNew(['document_type' => $type]);
                $doc->document_number = app(ProcurementNumberGenerator::class)->generate($this, $type);
                $doc->document_date = now()->toDateString();
                $doc->save();
            }
        });
    }

    public function generateCover(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateCover($this);
    }

    public function generatePlanning(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generatePlanning($this);
    }

    public function generateNegotiation(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateNegotiation($this);
    }

    public function generatePurchaseOrder(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generatePurchaseOrder($this);
    }

    public function generateInspection(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateInspection($this);
    }

    public function generateBast(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateBast($this);
    }

    public function generateInvoice(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateInvoice($this);
    }

    public function generateReceipt(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateReceipt($this);
    }

    public function generateSupplierDeclaration(): GeneratedDocument
    {
        return app(DocumentGenerationService::class)->generateSupplierDeclaration($this);
    }

    public function generateAllDocuments(): void
    {
        $this->generateCover();
        $this->generatePlanning();
        $this->generateNegotiation();
        $this->generatePurchaseOrder();
        $this->generateInspection();
        $this->generateBast();
        $this->generateInvoice();
        $this->generateReceipt();
        $this->generateSupplierDeclaration();
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

    public function notifySupplierAssigned(): void
    {
        $this->getNotificationService()->notify($this, 'supplier_assigned', [], auth()->user());
    }

    public function notifyNegotiationOffer(ProcurementRequestItem $item, ProcurementNegotiation $negotiation): void
    {
        $this->getNotificationService()->notify($this, 'negotiation_offer', [
            'item' => $item,
            'negotiation' => $negotiation,
        ], auth()->user());
    }

    public function notifyNegotiationDecided(ProcurementRequestItem $item, ProcurementNegotiation $negotiation, string $action): void
    {
        $event = $action === 'accepted' ? 'negotiation_accepted' : 'negotiation_rejected';
        $this->getNotificationService()->notify($this, $event, [
            'item' => $item,
            'negotiation' => $negotiation,
        ], auth()->user());
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

    public function notifyDocumentGenerated(): void
    {
        $this->getNotificationService()->notify($this, 'document_generated', [], auth()->user());
    }
}
