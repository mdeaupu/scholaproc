# MVC Architecture — Sistem Informasi Pengadaan Barang Sekolah
### Laravel 12 · Livewire v3 · AlpineJS · Mary UI · Fat Model, Skinny Controller
### (Revisi — disesuaikan database final 19 tabel)

> Perubahan dari versi sebelumnya: ditambahkan M6 Manajemen Master Data
> (Livewire), M9 Negosiasi Harga (Livewire — real-time tawar-menawar), M11
> Verifikasi Penerimaan Barang (Livewire — upload bukti). Modul lama
> digeser nomornya, lihat Ringkasan Pendekatan Per Modul di bagian akhir.

■ **LIVEWIRE COMPONENT** — Interaksi dinamis, real-time, tanpa full page reload
■ **TRADITIONAL CONTROLLER** — Halaman statis atau operasi satu kali (generate, download)

---

## 1. Prinsip Arsitektur

| Prinsip Utama | Livewire v3 Rule | Traditional Controller Rule |
|---|---|---|
| Request Handling | Livewire Component menerima action dari `wire:click` / `wire:submit` | Controller menerima HTTP request dari route biasa |
| Authorization | `authorize()` di dalam action method Component | `authorize()` di Controller method |
| Business Rule | Tetap di Model — Component hanya memanggil method Model | Tetap di Model — Controller hanya memanggil method Model |
| State / Reactivity | `$wire` properties, reactive props, Livewire events | Session / Flash data untuk feedback satu kali |
| View | Blade component dengan `wire:model`, `wire:click`, AlpineJS, Mary UI | Blade view biasa, tidak ada binding dua arah |
| Query Kompleks | Scope di Model, dipanggil dari Livewire computed property | Scope di Model, dipanggil dari Controller |
| External Integration | Dispatch job/event dari Component, eksekusi di Service | Panggil Service langsung dari Controller |

```
LIVEWIRE FLOW                          TRADITIONAL FLOW
User Action (wire:click/submit)        HTTP Request → Route
        ↓                                     ↓
Livewire Component (action method)     Controller Method
        ↓                                     ↓
Model (Business Rule)                  Model (Business Rule)
        ↓                                     ↓
Service (External Integration)         Service (External Integration)
        ↓                                     ↓
Re-render Component / Blade             Database → Response / Download
```

---

## 2. Kapan Livewire vs Traditional Controller?

| Kondisi / Fitur | ■ Livewire | ■ Controller |
|---|---|---|
| Real-time update data | ✓ Ya | ✗ Tidak |
| Form multi-step / wizard | ✓ Ya | ✗ Tidak |
| Live search / filter tabel | ✓ Ya | ✗ Tidak |
| **Tawar-menawar harga real-time (negosiasi)** | **✓ Ya** | ✗ Tidak |
| **Upload bukti foto/dokumen verifikasi** | **✓ Ya** | ✗ Tidak |
| Generate & download dokumen PDF | ✗ Tidak | ✓ Ya |
| Export Excel | ✗ Tidak | ✓ Ya |
| Halaman laporan summary (baca saja) | ■ Opsional | ✓ Ya |
| Notifikasi / resend | ✓ Ya (list + resend) | ✗ Tidak |
| Manajemen CRUD entitas (termasuk master data) | ✓ Ya (modal, inline edit) | ✗ Tidak |
| Workflow state transition | ✓ Ya (konfirmasi modal) | ✗ Tidak |
| QR Scanner (kamera real-time) | ✓ Ya (wire + Alpine) | ✗ Tidak |
| Auth login / logout | ✗ Tidak | ✓ Ya (Breeze default) |

---

## 3. Arsitektur Per Modul

### M1. Authentication & Authorization ■ TRADITIONAL CTRL

Mengapa Traditional Controller?
- Laravel Breeze sudah menyediakan `AuthController` berbasis request-response
- Login/logout adalah operasi satu kali, tidak butuh reaktivitas
- Middleware `auth` & Spatie Permissions bekerja optimal di HTTP lifecycle biasa

Model: User
- Relationships: `school()`, `histories()`
- Authorization: `isSuperadmin()`, `isAdmin()`, `isSchool()`, `canManageSchools()`, `canManageSuppliers()`, `canProcessProcurement()`, `canDecideNegotiation()`, `canVerifyReceipt()`
- Scopes: `scopeSuperadmin()`, `scopeAdmin()`, `scopeSchool()`

Controller: AuthController
- `login()`, `logout()`, `resetPassword()`

Catatan Stack: Laravel Breeze + Spatie Permissions · Middleware `auth` · Session-based · login via `username` (tanpa NIK)

---

### M2. Dashboard ■ LIVEWIRE

Mengapa Livewire?
- Statistik pengadaan perlu update real-time tanpa reload halaman
- Filter periode / sekolah cukup dengan `wire:model`
- Mary UI menyediakan komponen card & stat yang reaktif

Livewire Components
- `SuperadminDashboard` — statistik semua sekolah, chart nilai pengadaan
- `AdminDashboard` — monitoring pengajuan, filter status, **daftar negosiasi menunggu tanggapan**
- `SchoolDashboard` — rekapitulasi pengajuan sekolah sendiri, **daftar negosiasi menunggu keputusan**

Model: School
- `activeRequestsCount()`, `completedRequestsCount()`

Model: ProcurementRequest
- Scopes: `scopeSubmitted()`, `scopeVerified()`, `scopeCompleted()`, `scopeRejected()`, `scopeAwaitingNegotiation()`
- Statistics: `getTotalEstimatedAmount()`, `getTotalOfficialAmount()`

Wire props: `periode`, `school_id`, `status` — computed property untuk aggregate

---

### M3. Manajemen Sekolah ■ LW

Mengapa Livewire?
- CRUD sekolah + setting dalam satu form reaktif
- Toggle aktif/suspend dengan konfirmasi modal
- Manajemen School terintegrasi di modul yang sama

Livewire Components
- `SchoolManager`: `index`, `store`, `show`, `update`, `destroy`, `activate`, `suspend`
- `SchoolUserManager`: `index`, `create`, `store`, `edit`, `update`, `destroy`, `activate`, `deactivate`, `resetPassword`

Model: School
- `activate()`, `suspend()`, `isActive()`, `totalRequests()`, `totalProcurementValue()`

Model: SchoolSetting
- `getLetterHead()` — gabungkan `kop_pusat` + `kop_provinsi` + `kop_sub_wilayah`

---

### M4. Manajemen Supplier ■ LIVEWIRE

Mengapa Livewire?
- CRUD supplier + dokumen legal dalam satu halaman reaktif
- Indikator kelengkapan dokumen ditampilkan real-time

Livewire Component: `SupplierManager`
- `index()`, `store()`, `show()`, `update()`, `destroy()`
- `addLegalDocument()`, `updateLegalDocument()` — **input teks manual (nomor, tanggal, notaris/instansi), tanpa upload file/scan**

Model: Supplier
- Relationships: `legalDocuments()`, `procurementRequests()`
- Business: `activeBusinessPermit()`, `hasCompleteLegalDocuments()`, `totalProjects()`, `totalProjectValue()`, `ownershipSplit()`

Model: SupplierLegalDocument
- `isExpired()`, `isBusinessPermit()`, `isDeed()`

---

### M5. Manajemen Admin ■ LW

Mengapa Livewire?
- CRUD dengan modal konfirmasi inline
- Toggle aktif/nonaktif langsung tanpa redirect
- Live search nama admin

Livewire Component: `AdminManager`
- `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- `activate()`, `deactivate()`, `resetPassword()`

Model: User
- Business: `activate()`, `deactivate()`, `isActive()`, `resetPassword()`, `changePassword()`
- Scopes: `scopeAdmin()`, `scopeActive()`, `scopeInactive()`

Role: `admin` · Hanya Superadmin yang bisa akses

---

### M6. Manajemen Master Data ■ LIVEWIRE (BARU)

Mengapa Livewire?
- CRUD sederhana (kategori paket, sumber dana, tahun anggaran, satuan) dengan modal, tanpa perlu halaman terpisah per entitas
- Toggle aktif/nonaktif langsung tanpa redirect

Livewire Component: `MasterDataManager` *(tab per entitas dalam satu halaman, atau dipecah: `PackageCategoryManager`, `FundingSourceManager`, `BudgetYearManager`, `ItemUnitManager`)*
- `index()`, `store()`, `update()`, `destroy()`, `activate()`, `deactivate()`

Model: PackageCategory / FundingSource / ItemUnit
- `activate()`, `deactivate()`, `isActive()`
- Scope: `scopeActive()`

Model: BudgetYear
- `activate()` — otomatis nonaktifkan tahun anggaran lain
- `overlapsWith(BudgetYear $other)` — validasi non-overlap dicek saat submit form (tidak bisa dijamin database)

Role: Superadmin / Admin

---

### M7. Pengajuan Pengadaan ■ LIVEWIRE

Mengapa Livewire?
- Penambahan item pengadaan secara dinamis (add/remove row tanpa reload)
- Dropdown kategori/sumber dana/tahun anggaran/satuan diambil dari master data (live search kalau daftar panjang)
- Kalkulasi estimasi subtotal real-time saat harga/qty diubah
- State machine transition (submit, verify, reject) via konfirmasi modal

Livewire Components
- `ProcurementRequestForm` — create/edit pengajuan, manajemen item dinamis, pilih master data
- `ProcurementRequestList` — daftar dengan filter & live search
- `ProcurementProcessPanel` — verify, reject, assignSupplier, markItemsPrepared, complete

Model: ProcurementRequest — Workflow State Machine
```
draft → submitted → verified → supplier_assigned → items_prepared → completed
draft → submitted → rejected
```
- State Transitions: `submit(User)`, `verify(User)`, `reject(User, reason)`, `assignSupplier(Supplier, User)`, `markItemsPrepared(User)`, `complete(User)`
- State Validation: `canSubmit()`, `canVerify()`, `canAssignSupplier()`, `canPrepareItems()`, `canComplete()`
- Audit: `recordHistory(User, status, notes)` → `procurement_request_histories`
- Financial: `estimatedSubtotal()`, `officialSubtotal()`, `totalPpn()`, `totalPph22()`, `totalPph23()`, `grandTotal()`, `netTotal()`, `lockTotals()`, `isTotalsLocked()`
- Scopes: `scopeDraft/Submitted/Verified/Assigned/Prepared/Completed/Rejected/BySchool/BySupplier/ByYear()`

Model: ProcurementRequestItem
- `estimatedAmount()`, `officialAmount()`, `pphAmount()`
- Accessors: `getEstimatedAmountAttribute()`, `getOfficialAmountAttribute()`
- `unit()` — belongsTo ItemUnit (dropdown master data)

Observer: ProcurementRequestObserver
- `created`/`updated` → `createHistory()`, `writeActivityLog()` (Spatie Activity Log)

---

### M8. Pemrosesan Pengadaan ■ LIVEWIRE

Mengapa Livewire?
- Set pajak, signatory, dan nomor dokumen dalam form multi-step yang reaktif
- Validasi `isReadyForDocumentGeneration()` ditampilkan real-time sebagai checklist
- AlpineJS untuk accordion setiap tahap pemrosesan

Livewire Component: `ProcurementProcessingPanel`
- `setTaxes()` — input PPN, PPh 22, PPh 23 dengan preview kalkulasi otomatis
- `setSignatories()` — form penandatangan dinamis
- `setDocumentNumbers()` — generate nomor via `ProcurementNumberGenerator` (dibackend `DocumentNumberSequence`)

Model: ProcurementDocument
- `generateNumber()`, `isComplete()`

Model: ProcurementSignatory
- `formattedIdentity()`

Model: ProcurementRequest *(tambahan)*
- `generateOfficialDocuments()`, `isReadyForDocumentGeneration()`, `hasSupplier()`, `hasSignatories()`, `hasOfficialPrices()`

Service: ProcurementNumberGenerator
- `generatePlanningNumber()`, `generateNegotiationNumber()`, `generatePurchaseOrderNumber()`, `generateInspectionNumber()`, `generateBastNumber()`, `generateInvoiceNumber()`, `generateReceiptNumber()`, `generateSupplierDeclarationNumber()`
- Delegasi ke `DocumentNumberSequence::nextNumber()` dengan `lockForUpdate()`

---

### M9. Negosiasi Harga ■ LIVEWIRE (BARU)

Mengapa Livewire?
- Tawar-menawar harga per item butuh update real-time — begitu satu pihak menawar, pihak lain harus langsung melihat tawaran baru tanpa reload
- Tombol terima/tolak butuh konfirmasi modal, hanya aktif untuk School
- Livewire polling/event untuk notifikasi "ada tawaran baru" saat pihak lain sedang membuka halaman yang sama

Livewire Component: `NegotiationPanel`
- Ditampilkan per item di dalam `ProcurementRequestForm` atau halaman detail transaksi
- `offer(itemId, price, notes)` — Admin atau School mengajukan harga
- `accept(negotiationId)` — **hanya tersedia untuk School** (`$this->authorize('decide', $request)`)
- `reject(negotiationId, reason)` — **hanya tersedia untuk School**
- Computed property: riwayat putaran per item (real-time re-render setelah action)

Model: ProcurementNegotiation
- `offer()`, `accept()`, `reject()`, `isLatestRound()`, `isFromSchool()`, `isFromSupplierRepresentative()`
- Scopes: `scopeForItem()`, `scopeLatestPerItem()`, `scopePending()`

Model: ProcurementRequestItem *(tambahan)*
- `negotiations()`, `latestNegotiationRound()`, `isNegotiationSettled()`

Observer: ProcurementNegotiationObserver
- `created`/`updated` → `notifyCounterparty()` (dispatch notifikasi WhatsApp/Email ke pihak lain), `writeActivityLog()`

Business Rules: Round yang sudah `accepted`/`rejected` tidak bisa dibuka round
baru dari Livewire component (tombol offer disembunyikan) kecuali lewat alur
`reopenTotals()` di `ProcurementRequest` (butuh authorization terpisah).

---

### M10. Generate Dokumen PDF ■ TRADITIONAL CTRL

Mengapa Traditional Controller?
- Generate + stream/download file adalah operasi satu arah, tidak butuh reaktivitas
- Barryvdh Laravel-DOMPDF bekerja optimal di Controller biasa dengan `response()->streamDownload()`
- Regenerate membutuhkan direct file response, bukan Livewire render

Controller: DocumentController
- `generate(ProcurementRequest)` — memanggil `DocumentGenerationService`, return stream PDF
- `download(GeneratedDocument)` — stream file dari Spatie Media Library
- `regenerate(GeneratedDocument)` — hapus lama, generate ulang

Service: DocumentGenerationService
- `generateCover()`, `generatePlanning()`, `generateNegotiation()`, `generatePurchaseOrder()`
- `generateInspection()`, `generateBast()`, `generateInvoice()`, `generateReceipt()`
- **`generateSupplierDeclaration()`** *(baru — dokumen "Identitas Penyedia")*

Model: GeneratedDocument
- `generatePdf()`, `regeneratePdf()`, `downloadUrl()`

Model: ProcurementRequest *(dokumen)*
- `generateCover/Planning/Negotiation/PurchaseOrder/Inspection/Bast/Invoice/Receipt/SupplierDeclaration/AllDocuments()`

Observer: GeneratedDocumentObserver
- `created` → `storeMetadata()` via Spatie Media Library

Trigger aksi: tombol di Livewire component memanggil route `DocumentController` via link/redirect

---

### M11. Verifikasi Penerimaan Barang ■ LIVEWIRE (BARU)

Mengapa Livewire?
- Upload foto/dokumen pakai Livewire file upload (progress bar real-time)
- Preview file sebelum konfirmasi pakai AlpineJS
- Tombol "Tandai Terverifikasi" butuh feedback langsung (checklist berapa file sudah diunggah)

Livewire Component: `VerificationPanel`
- `uploadFile(file, type)` — upload foto/scan dokumen bertanda tangan (via Spatie Media Library)
- `deleteFile(fileId)` — hanya sebelum `verified_at` terisi
- `markVerified()` — **hanya tersedia untuk School**, tombol non-aktif kalau belum ada `signed_document` yang diunggah

Model: ProcurementVerificationFile
- `isPhoto()`, `isSignedDocument()`

Model: ProcurementRequest *(tambahan)*
- `markVerified(User $user)`, `hasVerificationEvidence()`, `canMarkVerified()`

Observer: ProcurementVerificationFileObserver
- `created` → `writeActivityLog()`

---

### M12. Notifikasi ■ LIVEWIRE

Mengapa Livewire?
- Daftar notifikasi dengan status (terkirim/gagal) perlu live update
- Tombol resend menggunakan `wire:click` dengan feedback langsung (spinner, toast)
- Dispatch ke Queue lewat `NotificationObserver` agar tidak blocking

Livewire Component: `NotificationManager`
- `index()` — daftar dengan filter status + live polling
- `resend(Notification)` — trigger ulang pengiriman via WhatsApp/Email

Model: Notification
- `send()`, `sendWhatsapp()`, `sendEmail()`, `markSent()`, `markFailed()`

Model: ProcurementRequest *(notif methods)*
- `notifySubmitted()`, `notifyVerified()`, `notifySupplierAssigned()`, `notifyNegotiationOffer()`, `notifyNegotiationDecided()`, `notifyItemsPrepared()`, `notifyReceiptVerified()`, `notifyCompleted()`

Service Layer
- `WhatsAppService.send()` — integrasi WAHA (Docker)
- `EmailService.send()` — Laravel Mail

Observer: NotificationObserver
- `created` → `dispatchQueue()` — Laravel Queue agar tidak blocking request

---

### M13a. Laporan — Filter & View ■ LW

Mengapa Livewire?
- Filter periode, sekolah, supplier, status secara interaktif
- Preview ringkasan laporan real-time sebelum export

Livewire Component: `ReportViewer`
- Bind `wire:model` ke filter: `period`, `school_id`, `supplier_id`, `status`
- Computed property untuk aggregate `summary()`
- Tombol Export PDF/Excel memanggil route `ReportController`

Model: ProcurementRequest — Scopes
- `scopePeriod()`, `scopeSchool()`, `scopeSupplier()`, `scopeStatus()`
- Report: `reportSummary()`, `reportBySchool()`, `reportBySupplier()`, `reportByStatus()`

### M13b. Laporan — Export ■ CTRL

Mengapa Traditional Controller?
- Export PDF/Excel adalah operasi download satu arah
- Rap2hpoutre Fast-Excel + DOMPDF bekerja di HTTP response biasa

Controller: ReportController
- `summary()` — opsional, redirect jika sudah ada Livewire viewer
- `exportPdf(Request)` — terima filter dari query string, return stream PDF via DOMPDF
- `exportExcel(Request)` — terima filter, return download Excel via Fast-Excel

Livewire component meneruskan filter aktif sebagai query param saat user klik Export.

---

## 4. Target Komposisi Kode

| Layer | Target % | Tanggung Jawab | Stack Utama |
|---|---|---|---|
| Model | 76% | Business rule, state machine, kalkulasi finansial, negosiasi, verifikasi, scopes, observer | Eloquent, Spatie Activity Log, Observer |
| Service | 12% | External integration, generate dokumen, penomoran dokumen, kirim notifikasi | DOMPDF, Fast-Excel, WhatsApp HTTP API, Laravel Mail |
| Livewire Component | 10% | Reactive UI, form multi-step, CRUD dengan modal, live filter, negosiasi real-time, upload verifikasi | Livewire v3, AlpineJS, Mary UI, Blade Icons |
| Traditional Controller | 2% | Auth, generate/download file, export laporan | Laravel Breeze, DOMPDF, Fast-Excel |

---

## 5. Ringkasan Pendekatan Per Modul

| Modul | Pendekatan | Alasan Utama |
|---|---|---|
| M1. Auth & Authorization | ■ Traditional Controller | Operasi satu kali, Breeze default, middleware HTTP |
| M2. Dashboard | ■ Livewire | Statistik real-time, filter interaktif |
| M3. Manajemen Sekolah | ■ Livewire | CRUD + setting + admin sekolah dalam satu panel |
| M4. Manajemen Supplier | ■ Livewire | Legalitas input manual real-time, indikator kelengkapan |
| M5. Manajemen Admin | ■ Livewire | CRUD modal, toggle aktif/nonaktif inline |
| M6. Manajemen Master Data | ■ Livewire | CRUD sederhana, toggle aktif/nonaktif |
| M7. Pengajuan Pengadaan | ■ Livewire | Item dinamis, kalkulasi live, state machine modal |
| M8. Pemrosesan Pengadaan | ■ Livewire | Form multi-step pajak/signatory/nomor yang reaktif |
| M9. Negosiasi Harga | ■ Livewire | Tawar-menawar real-time, keputusan modal, notifikasi instan |
| M10. Generate Dokumen PDF | ■ Traditional Controller | Download file stream, DOMPDF, tidak perlu reaktivitas |
| M11. Verifikasi Penerimaan Barang | ■ Livewire | Upload file real-time, checklist bukti sebelum verified |
| M12. Notifikasi | ■ Livewire | Status real-time, resend dengan feedback langsung |
| M13a. Laporan — Filter & View | ■ Livewire | Filter interaktif, preview ringkasan live |
| M13b. Laporan — Export PDF/Excel | ■ Traditional Controller | File download, Fast-Excel + DOMPDF |

---

*MVC Architecture · Sistem Informasi Pengadaan Barang Sekolah · Laravel 12 + Livewire v3 (Revisi — 19 tabel final)*
