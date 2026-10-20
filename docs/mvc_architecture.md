# MVC Architecture --- Sistem Informasi Pengadaan Barang Sekolah

## Laravel 12 · Livewire v3 · AlpineJS · Mary UI · Eloquent ORM

### Fat Model, Skinny Controller --- Arsitektur Terpadu per Modul

**Versi:** 1.0 · **Acuan:** PRD Clean v1.1 --- Per Modul

> Dokumen ini menggabungkan pendekatan Livewire dan Traditional
> Controller dalam satu file. Penanda pada tiap modul menunjukkan
> pendekatan antarmuka yang digunakan. Business rule tetap berada di
> domain/model atau service yang sesuai, bukan hanya di UI.

## 1. Dasar Bisnis dan Prinsip Arsitektur

### Model bisnis

-   Satu CV mengoperasikan aplikasi dan menjadi penyedia yang
    bertransaksi dengan sekolah.
-   Superadmin dan Admin adalah staf dari CV yang sama. Superadmin
    memiliki seluruh fungsi Admin dan menjadi satu-satunya role yang
    boleh mengelola master data.
-   Sekolah adalah pengguna eksternal dan hanya dapat mengakses
    data/transaksi miliknya.
-   CV dapat mengelola dan berkoordinasi dengan pemasok untuk pemenuhan
    barang. Pemasok bukan role pengguna utama dalam ruang lingkup inti.
-   Penerimaan dapat bertahap. Transaksi selesai setelah barang
    diterima, administrasi wajib lengkap, dan pembayaran dikonfirmasi.
-   Sistem mencatat status pembayaran dan bukti pembayaran secara
    sederhana. Invoice bukan bukti pembayaran; kuitansi hanya
    diterbitkan setelah pembayaran dikonfirmasi.
-   Pajak tidak boleh ditentukan berdasarkan asumsi atau tarif
    hard-coded yang belum divalidasi.

### Prinsip utama

1.  Controller/Livewire Component tipis: otorisasi, validasi input,
    panggil domain/service, kembalikan respons.
2.  Business rule dan transisi status terpusat pada Model/domain method
    atau Action Service.
3.  Otorisasi dilakukan di server pada setiap action, route, query
    sensitif, download, dan export.
4.  Perubahan harga final, status, penerimaan, dokumen, pembayaran,
    akun, dan master data penting memiliki audit trail.
5.  Dokumen final memakai snapshot data saat diterbitkan agar perubahan
    profil/master data tidak mengubah dokumen historis.
6.  Integrasi eksternal berjalan melalui Service/Job, bukan langsung
    dari template UI.
7.  Nama model/komponen di dokumen ini adalah rancangan; cocokkan dengan
    repository dan skema aktual sebelum implementasi.
8.  PRD tidak otomatis mengesahkan perubahan database. Lakukan analisis
    dampak sebelum menambah/mengubah tabel atau kolom.

## 2. Alur Arsitektur Terpadu

### Livewire

``` text
User action → Livewire Component → authorize + validate
            → Model/Domain method/Action Service
            → DB transaction (untuk proses kritis)
            → Event/Observer/Queue Job bila diperlukan
            → Re-render + feedback
```

### Traditional Controller

``` text
HTTP request → Route/Middleware → Controller
             → authorize + validate → Model/Service
             → Database/File storage → HTTP response/download
```

  -----------------------------------------------------------------------
  Layer                   Tanggung jawab          Batasan
  ----------------------- ----------------------- -----------------------
  Model/Domain            Relasi, scopes, aturan  Tidak mengirim email/WA
                          bisnis, kalkulasi,      langsung atau mengatur
                          validasi transisi dan   tampilan
                          invariants              

  Livewire Component      State UI, form reaktif, Tidak menjadi
                          filter, modal, upload,  satu-satunya tempat
                          memanggil domain method business rule/keamanan

  Controller              HTTP request-response,  Tidak memuat workflow
                          authorization, validasi bisnis panjang
                          request, delegasi,      
                          stream file             

  Service/Action          Generasi dokumen,       Tidak menggandakan
                          penomoran, integrasi,   aturan domain
                          orkestrasi lintas model 

  Observer/Event          Audit dan reaksi atas   Bukan satu-satunya
                          peristiwa model         pengaman transisi
                                                  status

  Queue Job               Email/WhatsApp dan      Tidak mengubah status
                          pekerjaan lambat secara transaksi tanpa
                          asynchronous            validasi domain

  Blade/Mary UI/Alpine    Presentasi dan          Tidak menetapkan harga
                          interaksi klien         final, pajak final,
                                                  role, atau status resmi
  -----------------------------------------------------------------------

### Pemilihan pendekatan

  -----------------------------------------------------------------------
  Kebutuhan                           Pendekatan
  ----------------------------------- -----------------------------------
  CRUD, form dinamis, filter,         **Livewire**
  dashboard, modal                    

  Negosiasi harga dan riwayat tawaran **Livewire**; event/polling bila
                                      diperlukan

  Upload bukti penerimaan dengan      **Livewire**
  progress/preview                    

  Checklist kesiapan dokumen          **Livewire**

  Login/logout                        **Traditional / auth bawaan
                                      Laravel**

  Generate/download PDF dan export    **Traditional Controller** untuk
  Excel                               file response

  Daftar notifikasi dan tombol resend **Livewire**; pengiriman aktual
                                      lewat Job/Service

  Email/WhatsApp dan proses lambat    **Queue Job / Service**
  -----------------------------------------------------------------------

## 3. Model/Domain Inti

Daftar berikut adalah kandidat domain object, bukan instruksi otomatis
untuk membuat tabel baru.

  -----------------------------------------------------------------------
  Model/domain object                 Tanggung jawab
  ----------------------------------- -----------------------------------
  `User`                              Identitas, role, status akun,
                                      relasi sekolah bila relevan

  `School`, `SchoolSetting`           Profil sekolah, pengaturan
                                      identitas/kop dokumen

  `Supplier`, `SupplierLegalDocument` Data pemasok yang dikelola internal
                                      CV dan metadata legalitas bila
                                      diperlukan

  `PackageCategory`, `Item`,          Kategori/paket, katalog barang bila
  `ItemUnit`                          digunakan, dan satuan

  `FundingSource`, `BudgetYear`       Referensi sumber dana dan tahun
                                      anggaran

  `ProcurementRequest`,               Pengajuan/transaksi dan baris
  `ProcurementRequestItem`            barang, jumlah, spesifikasi, harga

  `ProcurementNegotiation`            Riwayat tawaran per barang

  `ProcurementRequestHistory`         Riwayat status dan catatan
                                      transaksi

  `ProcurementDocument` /             Metadata, nomor, versi, lokasi
  `GeneratedDocument`                 file, status dokumen

  `ProcurementSignatory`              Penandatangan bila diperlukan

  `Delivery` / `ProcurementDelivery`  Catatan tiap pengiriman bila
                                      dimodelkan terpisah

  `Receipt` / `ProcurementReceipt`    Penerimaan per pengiriman, jumlah
                                      dan kondisi

  `ProcurementVerificationFile`       Foto/scan/bukti penerimaan

  `Invoice`                           Status dan data invoice

  `PaymentRecord`                     Status, tanggal, nilai, bukti
                                      pembayaran

  `DocumentNumberSequence`            Nomor urut dokumen yang aman dari
                                      duplikasi

  `Notification`, `ActivityLog`       Status notifikasi dan audit
                                      aktivitas
  -----------------------------------------------------------------------

### Aturan domain penting

-   Harga final hanya terbentuk setelah persetujuan sekolah dan CV
    tercatat sesuai kewenangan.
-   Setiap putaran negosiasi menyimpan penawar, waktu, harga, catatan,
    dan hasil; riwayat lama tidak ditimpa.
-   Penerimaan dicatat per pengiriman. Sistem menghitung jumlah dipesan,
    kumulatif diterima, dan sisa.
-   Penerimaan sebagian tidak berarti semua barang sudah diterima.
-   Invoice, penerimaan, dan pembayaran adalah peristiwa/status yang
    berbeda.
-   `Completed` mensyaratkan barang diterima, dokumen administrasi wajib
    lengkap, dan pembayaran dikonfirmasi.
-   Master data yang sudah digunakan tidak dihapus permanen jika merusak
    riwayat; gunakan penonaktifan/koreksi terjejak.
-   Perlakuan pajak harus dikonfigurasi dan divalidasi oleh pihak yang
    bertanggung jawab sebelum dianggap final.

## 4. Arsitektur per Modul

Penanda: **[LIVEWIRE](#livewire)** = antarmuka reaktif;
**\[TRADITIONAL\]** = Controller/auth biasa; **\[HYBRID\]** = UI
Livewire dan endpoint file/ekspor tradisional.

### M1. Authentication & Authorization --- \[TRADITIONAL\]

**Alasan:** login/logout/reset password mengikuti request-response
standar.

-   **Model/domain:** `User`, `School`, Policy/Gate untuk semua resource
    sensitif.
-   **Auth/controller:** login, logout, reset password, change password;
    middleware autentikasi dan status akun.
-   **Metode kandidat:** `isSuperadmin()`, `isAdmin()`, `isSchool()`,
    `isActive()`.
-   **Aturan:** Superadmin memiliki akses internal tertinggi; Admin
    tidak dapat mengelola master data; Sekolah hanya mengakses transaksi
    sekolahnya sendiri.
-   Otorisasi tetap dilakukan pada server, termasuk endpoint Livewire,
    unduhan, dan ekspor.

### M2. Dashboard --- [LIVEWIRE](#livewire)

**Komponen:** `SuperadminDashboard`, `AdminDashboard`,
`SchoolDashboard`.

-   **Model/domain:** `School`, `ProcurementRequest`, `Notification`,
    `PaymentRecord` atau domain pembayaran yang tersedia.
-   Superadmin melihat ringkasan sekolah, pengguna, pengajuan,
    transaksi, dokumen, aktivitas, dan pembayaran.
-   Admin melihat pengajuan untuk ditangani, negosiasi menunggu respons,
    pesanan berjalan, penerimaan, dan pembayaran yang perlu
    ditindaklanjuti.
-   Sekolah melihat pengajuan miliknya, status negosiasi,
    pengiriman/penerimaan, dokumen, dan pembayaran.
-   Filter periode, sekolah, status, dan tahun anggaran harus
    menghormati hak akses. Definisi status dashboard harus sama dengan
    laporan.

### M3. Manajemen Sekolah --- [LIVEWIRE](#livewire)

**Komponen:** `SchoolManager`, `SchoolSettingManager` bila dipisah,
`SchoolUserManager`.

-   **Model/domain:** `School`, `SchoolSetting`, `User`, relasi
    pembatasan sumber dana sekolah.
-   Superadmin membuat, memperbarui, mengaktifkan/menonaktifkan data
    sekolah serta mengelola akun sekolah.
-   Mengelola profil dan informasi yang digunakan pada dokumen serta
    sumber dana yang tersedia untuk setiap sekolah.
-   Penonaktifan sekolah tidak menghapus transaksi historis. Perubahan
    profil tidak mengubah dokumen final lama.

### M4. Manajemen Supplier --- [LIVEWIRE](#livewire)

**Komponen:** `SupplierManager`, `SupplierLegalDocumentManager` bila
diperlukan.

-   **Model/domain:** `Supplier`, `SupplierLegalDocument`.
-   Superadmin mengelola pemasok yang digunakan CV, kontak, informasi
    operasional, serta metadata legalitas bila diperlukan.
-   Supplier adalah mitra pemasok internal CV, bukan penyedia independen
    yang bertransaksi langsung dengan sekolah melalui marketplace.
-   Upload scan legalitas bukan kebutuhan inti PRD; tambahkan hanya bila
    kebutuhan operasional telah ditetapkan.

### M5. Manajemen Admin --- [LIVEWIRE](#livewire)

**Komponen:** `AdminManager`.

-   **Model/domain:** `User`.
-   Superadmin membuat, melihat, memperbarui, mengaktifkan/menonaktifkan
    akun Admin dan melakukan reset password.
-   Hanya Superadmin yang dapat mengelola akun Admin. Akun dengan
    histori aktivitas sebaiknya dinonaktifkan, bukan dihapus permanen.
-   Perubahan role/status akun harus tercatat.

### M6. Manajemen Master Data --- [LIVEWIRE](#livewire)

**Komponen:** `MasterDataManager` dengan tab, atau manager terpisah per
entitas.

-   **Model/domain:** `PackageCategory`, `Item` bila katalog terpusat
    dipakai, `ItemUnit`, `FundingSource`, `BudgetYear`.
-   Mengelola kategori/paket, barang, satuan, sumber dana, dan tahun
    anggaran.
-   **Hanya Superadmin** dapat menambah, mengubah, mengaktifkan, atau
    menonaktifkan master data. Admin tidak memiliki akses ini.
-   Sumber dana dapat dibatasi per sekolah. Tahun akademik tidak menjadi
    field terpisah.
-   Validasi rentang tahun anggaran dilakukan di server. Data referensi
    yang telah digunakan tidak dihapus permanen jika merusak riwayat.

### M7. Pengajuan Pengadaan --- [LIVEWIRE](#livewire)

**Komponen:** `ProcurementRequestForm`, `ProcurementRequestList`,
`ProcurementRequestDetail`, `ProcurementReviewPanel`.

-   **Model/domain:** `ProcurementRequest`, `ProcurementRequestItem`,
    `School`, `PackageCategory`, `FundingSource`, `BudgetYear`,
    `ItemUnit`, `ProcurementRequestHistory`.
-   Sekolah menyimpan draf dan mengirim pengajuan berisi kategori/paket,
    sumber dana, tahun anggaran, barang, spesifikasi, satuan, kuantitas,
    dan estimasi harga bila relevan.
-   Admin memeriksa kelengkapan, meminta revisi, menolak dengan alasan,
    atau meneruskan ke negosiasi.
-   **Status domain mengikuti PRD:** `Draft`, `Submitted`,
    `Needs Revision`, `Verified`, `Negotiation`, `Price Agreed`,
    `Processing`, `Partially Received`, `Received`, `Invoiced`,
    `Payment Pending`, `Completed`, `Disputed`, `Rejected`, `Cancelled`.
-   `submit()` memvalidasi data wajib dan item; `requestRevision()`,
    `verify()`, `reject()`, `cancel()` memeriksa status/aktor dan
    menyimpan riwayat.
-   Hindari workflow lama yang menganggap transaksi selesai setelah item
    disiapkan. Status selesai mengikuti kriteria penerimaan,
    administrasi, dan pembayaran pada PRD.

### M8. Pemrosesan Pengadaan --- \[HYBRID: LIVEWIRE + TRADITIONAL\]

**Komponen Livewire:** `ProcurementProcessingPanel`, `DeliveryManager`
bila pengiriman dipisahkan, `PaymentStatusPanel`, `InvoicePanel`.

**Controller tradisional:** endpoint generate/download dokumen dan
endpoint file bila memang diperlukan.

-   **Model/domain:** `ProcurementRequest`, `ProcurementRequestItem`,
    `Supplier`, domain pengiriman, `Invoice`, `PaymentRecord`,
    `DocumentNumberSequence` sesuai implementasi aktual.
-   Memproses pesanan setelah harga final disepakati; CV dapat
    mengoordinasikan pemasok untuk memenuhi barang.
-   Memperbarui progres pemrosesan/pengiriman dan mencatat status
    invoice serta pembayaran.
-   Invoice diproses setelah barang diterima dan dokumen penerimaan
    terkait diproses sesuai prosedur.
-   Pembayaran dilakukan setelah barang diterima; status pembayaran
    terpisah dari status penerimaan dan penerbitan invoice.
-   Kuitansi hanya setelah pembayaran dikonfirmasi. Pajak memakai
    konfigurasi tervalidasi, bukan asumsi atau tarif hard-coded.
-   Harga final dan kuantitas yang digunakan dokumen harus tersimpan
    sebagai snapshot.

### M9. Negosiasi Harga --- [LIVEWIRE](#livewire)

**Komponen:** `NegotiationPanel`, ditempatkan pada detail
pengajuan/transaksi.

-   **Model/domain:** `ProcurementNegotiation`,
    `ProcurementRequestItem`, `ProcurementRequest`, `User`.
-   Metode kandidat: `offer()`, `accept()`/`approveFinalPrice()`,
    `reject()`, `isLatestRound()`.
-   Admin CV dan sekolah dapat mengajukan/menanggapi penawaran sesuai
    alur yang diizinkan.
-   Setiap putaran menyimpan harga, waktu, pengguna, catatan, dan
    keputusan.
-   Harga final terbentuk setelah persetujuan sekolah dan CV tercatat;
    jangan membatasi keputusan final hanya kepada sekolah.
-   Setelah harga final disepakati, perubahan memerlukan alur
    koreksi/revisi berwenang dan jejak audit.
-   Livewire boleh menyembunyikan tombol sesuai role untuk UX, tetapi
    authorization tetap dilakukan di server.

### M10. Generate Dokumen --- \[HYBRID: LIVEWIRE + TRADITIONAL\]

**Komponen Livewire:** `DocumentCenter`, `DocumentReadinessPanel`.

**Controller tradisional:** `DocumentController@generate`, `download`,
dan `regenerate` bila regenerasi diizinkan.

-   **Model/domain:** `ProcurementDocument`/`GeneratedDocument`,
    `ProcurementSignatory`, `DocumentNumberSequence`,
    `ProcurementRequest`.
-   **Service:** `DocumentGenerationService`, `DocumentNumberGenerator`.
-   Dokumen PRD: ringkasan pengajuan, penawaran/hasil negosiasi, surat
    pesanan, dokumen pengiriman, dokumen pemeriksaan/penerimaan, BAST,
    invoice, kuitansi setelah pembayaran dikonfirmasi.
-   Dokumen identitas/pernyataan penyedia ditambahkan hanya bila
    diperlukan dan disahkan dalam proses operasional.
-   Nomor dokumen harus unik dan aman terhadap proses bersamaan. Dokumen
    menyimpan snapshot, versi, pembuat, waktu, dan status pengesahan.
-   Draf dibedakan dari dokumen yang sudah ditandatangani. Regenerasi
    tidak boleh diam-diam menimpa versi final historis.

### M11. Verifikasi Penerimaan Barang --- [LIVEWIRE](#livewire)

**Komponen:** `ReceiptVerificationPanel`, `ReceiptEntryForm`,
`ReceiptHistoryList`.

-   **Model/domain:** `Delivery`/`ProcurementDelivery`,
    `Receipt`/`ProcurementReceipt`, `ProcurementVerificationFile`,
    `ProcurementRequest`, `ProcurementRequestItem` sesuai skema aktual.
-   Mencatat setiap penerimaan: tanggal, barang, kuantitas diterima,
    kondisi, catatan, dan petugas pencatat.
-   Mendukung penerimaan sebagian/bertahap, perhitungan jumlah kumulatif
    dan sisa, serta tindak lanjut kekurangan/kerusakan.
-   Foto/scan dapat diunggah sebagai bukti; unggah file tidak otomatis
    menandai semua barang telah diterima.
-   Konfirmasi penerimaan adalah aksi eksplisit yang memvalidasi jumlah
    dan kondisi. Role yang boleh mengonfirmasi harus mengikuti prosedur
    operasional dan policy; jangan mengunci hanya ke sekolah tanpa
    validasi kebutuhan.
-   Invoice diproses setelah penerimaan dan dokumen terkait memenuhi
    prosedur.

### M12. Notifikasi --- \[LIVEWIRE + QUEUE/SERVICE\]

**Komponen:** `NotificationManager`, `NotificationList`.

-   **Model/domain:** `Notification`, `ProcurementRequest`.
-   **Service/Job:** `EmailService`, `WhatsAppService` bila integrasi
    tersedia, `SendNotificationJob`.
-   Pemicu: pengajuan baru, permintaan revisi, keputusan pemeriksaan,
    tawaran harga dan keputusan, pengiriman/penerimaan, dokumen
    tersedia, invoice/pembayaran, selesai/batal.
-   Simpan status pengiriman, waktu, dan kegagalan yang aman. Pengiriman
    ulang tidak boleh menggandakan perubahan transaksi.
-   Kegagalan email/WhatsApp tidak membatalkan transaksi tersimpan. Jika
    integrasi eksternal belum tersedia, notifikasi in-app tetap dapat
    digunakan.

### M13. Laporan --- \[HYBRID: LIVEWIRE + TRADITIONAL\]

**Komponen Livewire:** `ReportViewer` untuk filter dan preview.

**Controller tradisional:** `ReportController@exportPdf`, `exportExcel`.

-   **Model/domain:** `ProcurementRequest`, `ProcurementRequestItem`,
    `PaymentRecord`, scopes/query object sesuai implementasi.
-   Filter laporan berdasarkan periode, sekolah, status, sumber dana,
    dan tahun anggaran sesuai hak akses.
-   Ringkasan dapat mencakup nilai transaksi, penerimaan, invoice,
    pembayaran, dan status penyelesaian sesuai data yang tersedia.
-   Endpoint ekspor memvalidasi filter dan authorization. Sekolah hanya
    melihat transaksi miliknya.
-   Angka laporan harus konsisten dengan dashboard dan data sumber; data
    yang belum tersedia tidak boleh ditampilkan sebagai angka nol yang
    menyesatkan.

## 5. Observer, Event, Job, dan Service

### Observer/Event

-   `ProcurementRequestObserver`: audit perubahan status dan aktivitas
    penting.
-   `ProcurementNegotiationObserver`: audit tawaran baru dan memicu
    notifikasi kepada pihak lain.
-   `DeliveryObserver` / `ReceiptObserver`: audit pengiriman dan
    penerimaan jika model terpisah digunakan.
-   `GeneratedDocumentObserver`: metadata pembuatan/versi dokumen.
-   `PaymentRecordObserver`: audit perubahan status pembayaran.
-   `NotificationObserver`: dispatch pengiriman ke queue.

Nama observer mengikuti model yang benar-benar tersedia. Jangan membuat
tabel baru hanya karena sebuah nama objek disebut di dokumen ini.

### Service/Action

-   `DocumentGenerationService`: menghasilkan file dari snapshot
    transaksi.
-   `DocumentNumberGenerator`: menghasilkan nomor unik.
-   `PaymentRecordingService` bila perlu mengorkestrasi pencatatan
    pembayaran dan audit.
-   `EmailService`, `WhatsAppService` bila integrasi tersedia.
-   `ReportExportService` bila logika ekspor kompleks.
-   `SendNotificationJob` untuk pengiriman asynchronous.

### Transaksi database

Gunakan `DB::transaction()` untuk proses kritis, misalnya menyetujui
harga final dan mengunci snapshot, mencatat penerimaan beserta rincian,
mengonfirmasi pembayaran dan status terkait, atau membuat nomor dokumen
beserta metadata. Simpan perubahan database terlebih dahulu, lalu
dispatch Job setelah commit; jangan menahan transaksi database selama
request eksternal yang lambat.

## 6. Keamanan, Audit, dan Konsistensi

1.  Gunakan Policy/Gate untuk sekolah, pengajuan, negosiasi, dokumen,
    penerimaan, pembayaran, supplier, laporan, dan master data.
2.  Batasi query sekolah berdasarkan sekolah yang terkait dengan akun
    login.
3.  Hanya Superadmin dapat mengelola master data.
4.  Validasi tipe/ukuran file dan kontrol akses file; hindari URL publik
    yang mudah ditebak.
5.  Catat aktor, waktu, dan alasan bila relevan untuk perubahan harga
    final, status, dokumen, penerimaan, pembayaran, akun, dan master
    data.
6.  Gunakan penonaktifan/soft delete untuk data referensi yang telah
    digunakan jika sesuai skema.
7.  Cegah duplikasi pada konfirmasi pembayaran dan pembuatan dokumen.
8.  Jangan menghitung pajak final memakai nilai hard-coded yang belum
    divalidasi.
9.  Simpan snapshot dokumen final: nilai, identitas tercetak, nomor,
    waktu penerbitan, dan versi.
10. Uji authorization pada request Livewire, route, download, dan
    endpoint ekspor, bukan hanya pada tampilan.
11. Siapkan backup dan prosedur pemulihan yang diuji.

## 7. Kriteria Pengujian Arsitektur

  -----------------------------------------------------------------------
  Area                                Kriteria minimum
  ----------------------------------- -----------------------------------
  Role                                Admin ditolak saat mengelola master
                                      data melalui action/URL langsung;
                                      sekolah tidak dapat mengakses
                                      transaksi sekolah lain

  Pengajuan                           Data wajib divalidasi;
                                      revisi/penolakan menyimpan alasan

  Negosiasi                           Riwayat tawaran tidak tertimpa;
                                      harga final memerlukan persetujuan
                                      sekolah dan CV

  Snapshot                            Perubahan profil/master data tidak
                                      mengubah dokumen final historis

  Penerimaan bertahap                 Jumlah dipesan, diterima, dan sisa
                                      ditampilkan dengan benar

  Kondisi barang                      Kekurangan/kerusakan dapat dicatat
                                      dan ditindaklanjuti

  Invoice/pembayaran                  Invoice bukan bukti pembayaran;
                                      kuitansi tidak tersedia sebelum
                                      pembayaran dikonfirmasi

  Penyelesaian                        Transaksi tidak dapat `Completed`
                                      jika penerimaan, administrasi
                                      wajib, atau pembayaran belum
                                      memenuhi kriteria

  Audit                               Perubahan penting dapat ditelusuri

  Dokumen                             Nomor tidak duplikat; regenerasi
                                      menjaga histori versi

  Notifikasi                          Kegagalan pengiriman eksternal
                                      tidak membatalkan transaksi
                                      tersimpan

  Laporan                             Angka konsisten dengan sumber data
                                      dan dibatasi sesuai hak akses
  -----------------------------------------------------------------------

## 8. Ringkasan Pendekatan per Modul

  --------------------------------------------------------------------------------------
  Modul PRD         Nama modul        Pendekatan        Komponen/endpoint utama
  ----------------- ----------------- ----------------- --------------------------------
  M1                Authentication &  **TRADITIONAL**   Auth routes/controller,
                    Authorization                       middleware, policies

  M2                Dashboard         **LIVEWIRE**      `SuperadminDashboard`,
                                                        `AdminDashboard`,
                                                        `SchoolDashboard`

  M3                Manajemen Sekolah **LIVEWIRE**      `SchoolManager`,
                                                        `SchoolSettingManager`,
                                                        `SchoolUserManager`

  M4                Manajemen         **LIVEWIRE**      `SupplierManager`,
                    Supplier                            `SupplierLegalDocumentManager`

  M5                Manajemen Admin   **LIVEWIRE**      `AdminManager`

  M6                Manajemen Master  **LIVEWIRE**      `MasterDataManager` atau manager
                    Data                                per entitas

  M7                Pengajuan         **LIVEWIRE**      `ProcurementRequestForm`,
                    Pengadaan                           `ProcurementRequestList`,
                                                        `ProcurementReviewPanel`

  M8                Pemrosesan        **HYBRID**        `ProcurementProcessingPanel`,
                    Pengadaan                           `PaymentStatusPanel`, Controller
                                                        file bila perlu

  M9                Negosiasi Harga   **LIVEWIRE**      `NegotiationPanel`

  M10               Generate Dokumen  **HYBRID**        `DocumentCenter` +
                                                        `DocumentController` untuk file

  M11               Verifikasi        **LIVEWIRE**      `ReceiptVerificationPanel`,
                    Penerimaan Barang                   `ReceiptEntryForm`,
                                                        `ReceiptHistoryList`

  M12               Notifikasi        **LIVEWIRE +      `NotificationManager`,
                                      QUEUE/SERVICE**   `SendNotificationJob`

  M13               Laporan           **HYBRID**        `ReportViewer` +
                                                        `ReportController` untuk ekspor
  --------------------------------------------------------------------------------------

## 9. Catatan Implementasi dan Pengendalian Perubahan

-   PRD Clean v1.1 adalah acuan kebutuhan fungsional; dokumen ini
    menerjemahkannya menjadi pembagian tanggung jawab teknis.
-   Inventarisasi kode, route, controller, Livewire component, model,
    migration, policy, service, job, dan view yang benar-benar ada
    sebelum implementasi.
-   Jangan mengubah skema database hanya karena sebuah domain object
    dicantumkan. Periksa skema aktual, data lama, relasi, laporan,
    integrasi, dan dampak migrasi.
-   Dokumentasikan gap antara aplikasi yang berjalan dan arsitektur
    target, lalu lakukan penyesuaian bertahap.
-   Jalankan pengujian regresi untuk login, hak akses sekolah,
    negosiasi, penerimaan bertahap, dokumen, pembayaran, laporan, dan
    data historis.

------------------------------------------------------------------------

*MVC Architecture --- Sistem Informasi Pengadaan Barang Sekolah ·
Selaras dengan PRD Clean v1.1 --- Per Modul*
