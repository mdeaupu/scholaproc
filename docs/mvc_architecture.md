# MVC Architecture — Sistem Informasi Pengadaan Barang Sekolah
### Pendekatan Fat Model, Skinny Controller (Revisi — disesuaikan database final 19 tabel)

> Perubahan dari versi sebelumnya: ditambahkan Model untuk master data
> (PackageCategory, FundingSource, BudgetYear, ItemUnit), Model
> ProcurementNegotiation (M9), Model ProcurementVerificationFile (M11), dan
> Model/Service DocumentNumberSequence (M8/M10) menggantikan penomoran manual.

## 1. Arsitektur Umum

Prinsip utama:
- Controller hanya menerima request
- Controller melakukan authorization
- Controller memanggil method Model/Service
- Business Rule berada di Model
- State Transition berada di Model
- Query kompleks menggunakan Scope
- Event penting menggunakan Observer
- Integrasi eksternal menggunakan Service

Struktur:
```
Request
  ↓
Controller
  ↓
Model (Business Rule)
  ↓
Service (External Integration)
  ↓
Database
```

---

## M1. Authentication & Authorization

**Model: User**

Relationships
- `school()`
- `histories()`
- `negotiations()` *(baru — putaran tawaran yang pernah diajukan user ini)*

Authorization Methods
- `isSuperadmin()`
- `isAdmin()`
- `isSchool()`
- `canManageSchools()`
- `canManageSuppliers()`
- `canProcessProcurement()`
- **`canDecideNegotiation(ProcurementRequest $request)`** — hanya `true` untuk school pemilik transaksi (keputusan terima/tolak negosiasi selalu di sekolah)
- **`canVerifyReceipt(ProcurementRequest $request)`** — hanya `true` untuk school pemilik transaksi

Query Scopes
- `scopeSuperadmin()`
- `scopeAdmin()`
- `scopeSchool()`

**Controller: AuthController**
- `login()`
- `logout()`
- `resetPassword()`

> Catatan: login memakai `username` (bukan NIK). Tidak ada kolom/field NIK
> di modul ini.

---

## M2. Dashboard

**Model: School**
- `activeRequestsCount()`
- `completedRequestsCount()`

**Model: ProcurementRequest**

Scopes
- `scopeSubmitted()`
- `scopeVerified()`
- `scopeCompleted()`
- `scopeRejected()`
- **`scopeAwaitingNegotiation()`** — item masih `negotiation_status = 'negotiating'`

Statistics
- `getTotalEstimatedAmount()`
- `getTotalOfficialAmount()` — mengambil dari `grand_total` jika `totals_locked_at` sudah terisi, atau live-calculate jika belum

**Controller: DashboardController**
- `ownerDashboard()`
- `adminCvDashboard()`
- `adminSchoolDashboard()`

---

## M3. Manajemen Sekolah

**Model: School**

Relationships
- `setting()`
- `users()`
- `procurementRequests()`
- **`documentNumberSequences()`** *(baru)*

Business Methods
- `activate()`
- `suspend()`
- `isActive()`
- `totalRequests()`
- `totalProcurementValue()`

**Model: SchoolSetting**

Relationships
- `school()`

Business Methods
- `getLetterHead()` — menggabungkan: `kop_pusat`, `kop_provinsi`, `kop_sub_wilayah`

**Controller: SchoolController**
- `index()`, `store()`, `show()`, `update()`, `destroy()`, `activate()`, `suspend()`

**Controller: SchoolUserController** (mengelola akun School)
- `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
- `activate()`, `deactivate()`, `resetPassword()`

---

## M4. Manajemen Supplier

**Model: Supplier**

Relationships
- `legalDocuments()`
- `procurementRequests()`

Business Methods
- `activeBusinessPermit()`
- `hasCompleteLegalDocuments()`
- `totalProjects()`
- `totalProjectValue()`
- **`ownershipSplit()`** *(baru — mengembalikan `director_share_percentage` + `commissioner_share_percentage`, dipakai untuk cetak dokumen "Data Penyedia")*

**Model: SupplierLegalDocument**

Business Methods
- `isExpired()`
- `isBusinessPermit()`
- `isDeed()`

> Catatan: tidak ada method upload/attach file di model ini. Dokumen legal
> diisi manual (nomor, tanggal, notaris/instansi) — tidak ada scan/file yang
> disimpan.

**Controller: SupplierController**
- `index()`, `store()`, `show()`, `update()`, `destroy()`

---

## M5. Manajemen Admin

**Model: User**

Business Methods
- `activate()`
- `deactivate()`
- `isActive()`
- `resetPassword()`
- `changePassword()`

Scopes
- `scopeAdmin()`
- `scopeActive()`
- `scopeInactive()`

**Controller: AdminController**
- `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
- `activate()`, `deactivate()`, `resetPassword()`

Business Rules: Hanya Superadmin yang dapat mengelola akun Admin. Data menggunakan tabel `users` dengan `role=admin`.

---

## M6. Manajemen Master Data (BARU)

**Model: PackageCategory**
- Relationships: `procurementRequests()`
- Business: `activate()`, `deactivate()`, `isActive()`
- Scope: `scopeActive()`

**Model: FundingSource**
- Relationships: `procurementRequests()`
- Business: `activate()`, `deactivate()`, `isActive()`
- Scope: `scopeActive()`

**Model: BudgetYear**
- Relationships: `procurementRequests()`, `documentNumberSequences()`
- Business: `activate()` *(set `is_active=true`, otomatis nonaktifkan tahun anggaran lain)*, `isCurrentlyActive()`
- Validation: `overlapsWith(BudgetYear $other)` — dicek di level aplikasi sebelum simpan, karena tidak bisa dijamin oleh constraint database
- Scope: `scopeActive()`

**Model: ItemUnit**
- Relationships: `procurementRequestItems()`
- Business: `activate()`, `deactivate()`, `isActive()`
- Scope: `scopeActive()`

**Controller: MasterDataController** *(atau dipecah per entitas: `PackageCategoryController`, `FundingSourceController`, `BudgetYearController`, `ItemUnitController`)*
- `index()`, `store()`, `update()`, `destroy()`, `activate()`, `deactivate()`

Business Rules: Model-model ini murni lookup/reference data — tidak ada state
machine atau kalkulasi kompleks. Hanya Superadmin/Admin yang bisa mengelola.

---

## M7. Pengajuan Pengadaan

**Model: ProcurementRequest**

Relationships
- `school()`
- `supplier()`
- `packageCategory()` *(baru — FK)*
- `budgetYear()` *(baru — FK)*
- `fundingSource()` *(baru — FK)*
- `items()`
- `documents()`
- `signatories()`
- `histories()`
- `generatedDocuments()`
- `notifications()`
- **`verificationFiles()`** *(baru)*
- **`verifiedBy()`** *(baru — belongsTo User)*

Workflow State Machine
```
draft → submitted → verified → supplier_assigned → items_prepared → completed
draft → submitted → rejected
```

State Transition Methods
- `submit(User $user)` — Validasi: minimal 1 item, status draft
- `verify(User $user)`
- `reject(User $user, string $reason)`
- `assignSupplier(Supplier $supplier, User $user)`
- `markItemsPrepared(User $user)`
- `complete(User $user)`
- **`markVerified(User $user)`** *(baru — set `verified_at`/`verified_by`; validasi: minimal 1 `procurement_verification_files` sudah diunggah)*

State Validation
- `canSubmit()`
- `canVerify()`
- `canAssignSupplier()`
- `canPrepareItems()`
- `canComplete()`
- **`canMarkVerified()`** *(baru)*

Audit Trail
- `recordHistory(User $user, string $status, ?string $notes)` → menulis ke `procurement_request_histories`

Financial Calculation
- `estimatedSubtotal()`
- `officialSubtotal()`
- `totalPpn()`
- `totalPph22()`
- `totalPph23()`
- `grandTotal()` — live-calculate jika `totals_locked_at` masih NULL
- `netTotal()`
- **`lockTotals()`** *(baru — menghitung & mengisi `subtotal`/`tax_amount`/`grand_total`/`totals_locked_at`; validasi: semua item harus `negotiation_status IN ('accepted','rejected')` dulu)*
- **`isTotalsLocked()`** *(baru)*
- **`reopenTotals(User $user, string $reason)`** *(baru — reset `totals_locked_at` ke NULL untuk alur revisi resmi, dicatat ke history)*

Query Scopes
- `scopeDraft()`, `scopeSubmitted()`, `scopeVerified()`, `scopeAssigned()`, `scopePrepared()`, `scopeCompleted()`, `scopeRejected()`
- `scopeBySchool()`, `scopeBySupplier()`, `scopeByYear()`

**Model: ProcurementRequestItem**

Relationships
- `unit()` *(baru — FK ke ItemUnit, menggantikan kolom teks `unit`)*
- **`negotiations()`** *(baru — hasMany ProcurementNegotiation)*

Calculation Methods
- `estimatedAmount()`
- `officialAmount()`
- `pphAmount()`

Accessors
- `getEstimatedAmountAttribute()`
- `getOfficialAmountAttribute()`

Business Methods *(baru)*
- `latestNegotiationRound()`
- `isNegotiationSettled()` — `true` jika `negotiation_status IN ('accepted','rejected')`

**Model: ProcurementRequestHistory**
- `createdBy()`

**Controller: ProcurementRequestController**
- `index()`, `show()`, `store()`, `update()`, `submit()`

**Controller: ProcurementProcessController**
- `verify()`, `reject()`, `assignSupplier()`, `markItemsPrepared()`, `complete()`

---

## M8. Pemrosesan Pengadaan

**Model: ProcurementDocument**
- `generateNumber()` — memanggil `ProcurementNumberGenerator` service (lihat M10)
- `isComplete()`

**Model: ProcurementSignatory**
- `formattedIdentity()`

**Model: ProcurementRequest** *(tambahan)*
- `generateOfficialDocuments()`
- `isReadyForDocumentGeneration()`
- `hasSupplier()`
- `hasSignatories()`
- `hasOfficialPrices()` — sekarang bergantung pada `isNegotiationSettled()` semua item, bukan sekadar `official_price IS NOT NULL`

**Controller: ProcurementProcessingController**
- `setTaxes()`
- `setSignatories()`
- `setDocumentNumbers()`

---

## M9. Negosiasi Harga (BARU)

**Model: ProcurementNegotiation**

Relationships
- `item()` — belongsTo ProcurementRequestItem
- `user()` — belongsTo User (siapa yang menawar)

Business Methods
- `offer(ProcurementRequestItem $item, User $user, string $offeredBy, float $price, ?string $notes)` — static/factory: membuat round baru, otomatis set round sebelumnya (kalau ada) jadi `status='countered'`
- `accept(User $user)` — validasi: hanya boleh dipanggil kalau `$user->canDecideNegotiation()`; menyalin `offered_price` ke `procurement_request_items.official_price`, set `negotiation_status='accepted'`
- `reject(User $user, ?string $reason)` — validasi sama; set `negotiation_status='rejected'`
- `isLatestRound()`
- `isFromSchool()` / `isFromSupplierRepresentative()`

Query Scopes
- `scopeForItem()`
- `scopeLatestPerItem()`
- `scopePending()`

**Controller: ProcurementNegotiationController**
- `index()` — riwayat negosiasi per item
- `offer()` — Admin atau School mengajukan harga
- `accept()` — **hanya School**
- `reject()` — **hanya School**

Business Rules: Siapa boleh input apa ditegakkan di Controller lewat
authorization (`$this->authorize(...)`), bukan di database. Round yang sudah
`accepted`/`rejected` tidak bisa dibuka round baru kecuali lewat
`reopenTotals()` di level `ProcurementRequest` (alur revisi resmi).

---

## M10. Generate Dokumen

**Model: GeneratedDocument**
- `generatePdf()`
- `regeneratePdf()`
- `downloadUrl()`

**Model: ProcurementRequest**
- `generateCover()`
- `generatePlanning()`
- `generateNegotiation()`
- `generatePurchaseOrder()`
- `generateInspection()`
- `generateBast()`
- `generateInvoice()`
- `generateReceipt()`
- **`generateSupplierDeclaration()`** *(baru — dokumen "Identitas Penyedia")*
- `generateAllDocuments()`

**Model: DocumentNumberSequence** *(baru)*
- `nextNumber(): int` — increment `last_number` dalam `DB::transaction()` dengan `lockForUpdate()`, mengembalikan nomor urut baru
- `currentNumber(): int`

**Controller: DocumentController**
- `generate()`, `download()`, `regenerate()`

---

## M11. Verifikasi Penerimaan Barang (BARU)

**Model: ProcurementVerificationFile**

Relationships
- `procurementRequest()`
- `uploadedBy()` — belongsTo User

Business Methods
- `isPhoto()` / `isSignedDocument()`

**Model: ProcurementRequest** *(tambahan)*
- `markVerified(User $user)` — lihat M7
- `hasVerificationEvidence()` — minimal 1 `signed_document` sudah diunggah

**Controller: ProcurementVerificationController**
- `index()` — daftar file bukti per transaksi
- `upload()` — upload foto/scan
- `destroy()` — hapus file (hanya sebelum `verified_at` terisi)
- `markVerified()` — konfirmasi status verified

---

## M12. Notifikasi

**Model: Notification**
- `send()`, `sendWhatsapp()`, `sendEmail()`, `markSent()`, `markFailed()`

**Model: ProcurementRequest**
- `notifySubmitted()`
- `notifyVerified()`
- `notifySupplierAssigned()`
- **`notifyNegotiationOffer()`** *(baru)*
- **`notifyNegotiationDecided()`** *(baru)*
- `notifyItemsPrepared()`
- **`notifyReceiptVerified()`** *(baru)*
- `notifyCompleted()`

**Controller: NotificationController**
- `index()`, `resend()`

---

## M13. Laporan

**Model: ProcurementRequest**

Report Scopes
- `scopePeriod()`, `scopeSchool()`, `scopeSupplier()`, `scopeStatus()`

Report Methods
- `reportSummary()`, `reportBySchool()`, `reportBySupplier()`, `reportByStatus()`

**Controller: ReportController**
- `summary()`, `exportPdf()`, `exportExcel()`

---

## Observer Layer

**ProcurementRequestObserver**
- Triggered: `created`, `updated`
- Actions: `createHistory()`, `writeActivityLog()`

**ProcurementNegotiationObserver** *(baru)*
- Triggered: `created`, `updated`
- Actions: `notifyCounterparty()` — beri tahu pihak lain ada tawaran baru; `writeActivityLog()`

**ProcurementVerificationFileObserver** *(baru)*
- Triggered: `created`
- Actions: `writeActivityLog()`

**GeneratedDocumentObserver**
- Triggered: `created`
- Actions: `storeMetadata()`

**NotificationObserver**
- Triggered: `created`
- Actions: `dispatchQueue()`

---

## Service Layer

Walaupun menggunakan Fat Model, beberapa proses tetap dipisahkan agar Model tidak berisi integrasi eksternal.

**DocumentGenerationService**
- `generateCover()`, `generatePlanning()`, `generateNegotiation()`, `generatePurchaseOrder()`
- `generateInspection()`, `generateBast()`, `generateInvoice()`, `generateReceipt()`
- **`generateSupplierDeclaration()`** *(baru)*

**WhatsAppService**
- `send()`

**EmailService**
- `send()`

**ProcurementNumberGenerator**
- `generatePlanningNumber()`, `generateNegotiationNumber()`, `generatePurchaseOrderNumber()`
- `generateInspectionNumber()`, `generateBastNumber()`, `generateInvoiceNumber()`, `generateReceiptNumber()`
- **`generateSupplierDeclarationNumber()`** *(baru)*
- Semua method di atas sekarang delegasi ke `DocumentNumberSequence::nextNumber()`, bukan menghitung manual

---

## Target Komposisi Kode

| Layer | Persentase |
|---|---|
| Model | 78% |
| Service | 16% |
| Controller | 6% |

> Sedikit bergeser dari versi sebelumnya (80/15/5) karena modul Negosiasi dan
> Verifikasi menambah business logic di Model, dan penomoran dokumen otomatis
> menambah sedikit porsi Service.

Pendekatan ini memastikan seluruh business rule pengadaan tersentralisasi pada domain model sehingga controller tetap tipis, mudah diuji, dan mudah dipelihara.
