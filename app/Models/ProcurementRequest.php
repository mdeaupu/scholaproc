<?php

namespace App\Models;

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

    /**
     * Status yang masih perlu ditindaklanjuti (bukan draf, selesai, ditolak, atau dibatalkan).
     */
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
            if ($model->isTotalsLocked() && ! $model->allowSnapshotCorrection
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
                ])) {
                throw new LogicException('Snapshot transaksi terkunci; gunakan tindakan koreksi beralasan.');
            }

            if ($model->isDirty('subsidiary_id')) {
                if ($model->officialDocuments()->whereNotNull('issued_at')->exists()
                    && ! $model->allowSubsidiaryCorrection) {
                    throw new LogicException('Anak Perusahaan tidak dapat diganti setelah dokumen resmi diterbitkan tanpa koreksi berwenang.');
                }

                if ($model->subsidiary_id !== null
                    && (int) $model->subsidiary_id !== (int) $model->getOriginal('subsidiary_id')) {
                $subsidiary = Subsidiary::findOrFail($model->subsidiary_id);

                if (! $subsidiary->isActive()) {
                    throw new LogicException('Anak Perusahaan nonaktif tidak dapat ditetapkan pada transaksi baru.');
                }

                    if ($model->officialDocuments()->whereNotNull('issued_at')->exists()
                        && ! $model->allowSubsidiaryCorrection) {
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

        if (! $this->hasOfficialPrices()
            || $this->items()->where('negotiation_status', '!=', 'accepted')->exists()) {
            throw new LogicException('Harga final memerlukan persetujuan sekolah dan penyedia untuk semua barang.');
        }

        DB::transaction(function () use ($actor): void {
            $this->lockTotals();
            $this->update(['status' => self::STATUS_PRICE_AGREED]);
            $this->recordHistory($actor, self::STATUS_PRICE_AGREED, 'Persetujuan harga kedua pihak tercatat; snapshot harga dikunci.');
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

    public function canComplete(): bool
    {
        $payment = $this->payment()->whereNotNull('confirmed_at')->first();

        return $this->status === self::STATUS_PAYMENT_PENDING
            && $this->items()->exists()
            && $this->hasSubsidiary()
            && $this->isTaxConfigurationValidated()
            && $this->allQuantitiesReceived()
            && ! $this->hasOpenReceiptFollowUps()
            && $this->hasSignatories()
            && $this->hasOfficialPrices()
            && $this->allItemsNegotiationSettled()
            && $this->hasCompletedAdministrativeProcessing()
            && $payment !== null
            && (float) $payment->amount === (float) ($this->invoice?->total_amount ?? 0)
            && $this->hasIssuedDocument('purchase_order')
            && $this->hasIssuedDocument('bast')
            && $this->hasIssuedDocument('invoice')
            && $this->hasIssuedDocument('receipt');
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

        app(\App\Services\ProcurementProcessingService::class)->confirmPayment(
            $this->payment()->firstOrFail(),
            $user
        );
    }

    public function complete(User $user): void
    {
        $this->authorizeInternal($user);

        if (! $this->canComplete()) {
            throw new Exception('Pengadaan belum dapat diselesaikan: penerimaan penuh, administrasi, pembayaran terkonfirmasi, atau tindak lanjut masih belum terpenuhi.');
        }

        DB::transaction(function () use ($user): void {
            $this->update(['status' => self::STATUS_COMPLETED]);
            $this->recordHistory($user, self::STATUS_COMPLETED);
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

    public function reopenTotals(User $user, string $reason): void
    {
        $this->authorizeInternal($user);
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new LogicException('Alasan koreksi harga wajib diisi minimal 5 karakter.');
        }

        DB::transaction(function () use ($user, $reason): void {
            $before = [
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->tax_amount,
                'grand_total' => $this->grand_total,
                'price_snapshot' => $this->price_snapshot,
                'tax_snapshot' => $this->tax_snapshot,
            ];
            $this->allowSnapshotCorrection = true;

            try {
                $this->forceFill([
                    'totals_locked_at' => null,
                    'subtotal' => null,
                    'tax_amount' => null,
                    'grand_total' => null,
                    'price_snapshot' => null,
                    'tax_snapshot' => null,
                    'subsidiary_snapshot' => null,
                    'status' => self::STATUS_NEGOTIATION,
                ])->save();
            } finally {
                $this->allowSnapshotCorrection = false;
            }

            $this->recordHistory($user, self::STATUS_DISPUTED, 'Koreksi harga dibuka: '.$reason.'; snapshot sebelumnya: '.json_encode($before));
        });
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
        return app(\App\Services\TaxEvaluationService::class)->evaluateAndApply($this, $actor, $context);
    }

    public function applyTaxEvaluation(
        \App\Services\TaxEvaluationService $service,
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
                    'ppn_rate' => $resolved ? $rates[\App\Models\TaxRule::TAX_PPN] : null,
                    'pph_22_rate' => $resolved ? $rates[\App\Models\TaxRule::TAX_PPH22] : null,
                    'pph_23_rate' => $resolved ? $rates[\App\Models\TaxRule::TAX_PPH23] : null,
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

    public function hasSubsidiary(): bool
    {
        return ! empty($this->subsidiary_id);
    }

    public function allQuantitiesReceived(): bool
    {
        foreach ($this->items as $item) {
            $received = (int) $item->receiptItems()
                ->whereHas('receipt', fn (Builder $query) => $query->whereNotNull('verified_at'))
                ->sum('quantity');

            if ($received < (int) $item->quantity) {
                return false;
            }
        }

        return $this->items()->exists();
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
        return $this->hasSubsidiary() && $this->hasSignatories() && $this->hasOfficialPrices();
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
