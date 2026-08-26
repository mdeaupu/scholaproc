# PRD — Sistem Informasi Pengadaan Barang Sekolah (Versi Final v7)

> Perubahan dari v6: disesuaikan dengan struktur database final (19 tabel) —
> ditambahkan modul Negosiasi Harga (M11) dan Verifikasi Penerimaan Barang
> (M12), master data (kategori paket, sumber dana, tahun anggaran, satuan),
> penomoran dokumen otomatis, snapshot total harga, dan penambahan data
> legal/kepemilikan supplier.

## 1. Gambaran Sistem

Sistem Informasi Pengadaan Barang Sekolah adalah aplikasi web yang menghubungkan sekolah sebagai pemohon pengadaan dengan CV/perusahaan sebagai penyedia barang dan jasa.

Sistem mendukung:
- Pengajuan kebutuhan barang oleh sekolah
- Verifikasi dan pengelolaan pengadaan oleh Admin
- Penentuan supplier
- **Negosiasi harga per item antara sekolah dan Admin (mewakili supplier)**
- Generate dokumen pengadaan otomatis, dengan **penomoran otomatis**
- **Verifikasi penerimaan barang** (upload foto/bukti fisik)
- Notifikasi WhatsApp dan Email
- Audit trail seluruh proses pengadaan

---

## 2. Role & Hak Akses

### Superadmin
- Dashboard statistik global
- Kelola Admin
- Kelola sekolah
- Kelola supplier
- Monitoring seluruh transaksi
- Laporan dan audit

### Admin
- Verifikasi pengajuan
- Menentukan supplier
- **Mengajukan/menanggapi tawaran harga (negosiasi) atas nama supplier**
- Menentukan harga resmi (hasil negosiasi yang diterima sekolah)
- Mengelola dokumen pengadaan
- Mengirim notifikasi

### School
- Membuat pengajuan
- Melihat progres pengajuan
- **Mengajukan/menanggapi tawaran harga (negosiasi)**
- **Menerima atau menolak harga hasil negosiasi (keputusan akhir selalu di sekolah)**
- **Mengunggah bukti fisik penerimaan barang (foto & dokumen bertanda tangan)**
- **Mengonfirmasi barang sudah diterima (verifikasi)**
- Mengunduh dokumen
- Menerima notifikasi

> Catatan: supplier tidak memiliki akun login. Semua interaksi atas nama
> supplier (termasuk penawaran harga) diwakilkan oleh Admin.

---

## 3. Workflow Utama

Draft
→ Submitted
→ Verified
→ Supplier Assigned
→ **(Negosiasi harga per item berlangsung di sini, hingga semua item disepakati)**
→ Items Prepared
→ **(Verifikasi penerimaan barang: upload bukti fisik)**
→ Completed

Atau:

Draft
→ Submitted
→ Rejected

---

## 4. Modul Sistem

### M1. Authentication & Authorization
- Login (menggunakan `username`, bukan NIK)
- Role Based Access Control
- Reset Password
- Session Management

### M2. Dashboard

#### Superadmin
- Total sekolah aktif
- Total supplier
- Total pengajuan
- Total dokumen
- Grafik pengadaan
- Aktivitas pengguna

#### Admin
- Pengajuan masuk
- Status pengajuan
- **Negosiasi yang menunggu tanggapan**
- Reminder proses

#### School
- Pengajuan aktif
- Status terakhir
- **Negosiasi yang menunggu keputusan sekolah**
- Notifikasi terbaru

### M3. Manajemen Sekolah

Fitur:
- CRUD sekolah
- Aktivasi dan suspend sekolah
- Kelola konfigurasi sekolah (kop surat)
- Kelola akun School

### M4. Manajemen Supplier

Fitur:
- CRUD supplier (termasuk alamat & persentase kepemilikan Direktur/Komisaris)
- Kelola legalitas supplier (nomor & tanggal dokumen — **diisi manual, tanpa upload scan**, karena data diolah sendiri oleh Admin)
- Riwayat proyek supplier

### M5. Manajemen Admin

Fitur:
- Menampilkan daftar Admin
- Menambah akun Admin
- Mengubah data Admin
- Mengaktifkan atau menonaktifkan akun
- Reset password Admin
- Menghapus akun (Soft Delete)

### M6. Manajemen Master Data (BARU)

Fitur:
- CRUD Kategori Paket Pengadaan
- CRUD Sumber Dana Sekolah
- CRUD Tahun Anggaran (rentang tanggal mulai–selesai)
- CRUD Satuan Barang

> Tujuan: agar School tinggal memilih dari daftar yang sudah ada saat
> membuat pengajuan, tidak mengetik ulang teks yang sama berkali-kali.

### M7. Pengajuan Pengadaan

Data pengajuan:
- Kategori paket (pilih dari master)
- Sumber dana (pilih dari master)
- Tahun anggaran (pilih dari master)
- Daftar barang (nama, spesifikasi, satuan dari master, qty)
- Estimasi harga per item

### M8. Pemrosesan Pengadaan

Admin dapat:
- Verifikasi pengajuan
- Menentukan supplier
- Mengatur pajak (PPN, PPh 22, PPh 23)
- Mengatur pejabat penandatangan
- Mengatur nomor dokumen (**otomatis**, lihat M12b)

### M9. Negosiasi Harga (BARU)

Alur:
1. Admin mengajukan harga penawaran per item (atas nama supplier)
2. School menawar balik
3. Admin memberi counter-offer
4. ... berlanjut sampai salah satu pihak setuju
5. **School** memutuskan terima/tolak — keputusan akhir selalu di sekolah

Setiap putaran tawaran tercatat sebagai riwayat (tidak menimpa data lama).
Begitu diterima, harga otomatis menjadi `official_price` item terkait.

### M10. Generate Dokumen

Dokumen:
- Cover
- Dokumen Perencanaan
- Hasil Negosiasi
- Surat Pesanan
- Surat Hasil Pemeriksaan *(kolom jumlah diterima & kondisi barang dicetak kosong — diisi manual di kertas fisik, tidak dientri balik ke sistem)*
- BAST *(sama seperti di atas)*
- Faktur
- Kuitansi
- **Identitas Penyedia** *(surat pernyataan/pakta integritas Direktur CV — BARU)*

### M11. Verifikasi Penerimaan Barang (BARU)

Fitur:
- Upload foto barang fisik
- Upload scan dokumen yang sudah ditandatangani (BAST, kuitansi, dll)
- Konfirmasi status "barang sudah diterima" — tindakan terpisah dari upload,
  dilakukan oleh School setelah bukti diunggah

### M12. Notifikasi

Channel:
- WhatsApp (WAHA)
- Email

Event tambahan yang perlu dinotifikasi:
- Ada tawaran harga baru masuk (negosiasi)
- Tawaran harga diterima/ditolak
- Barang telah diverifikasi diterima

### M13. Laporan

Filter:
- Periode
- Sekolah
- Supplier
- Status

Export:
- PDF
- Excel

---

## 5. Struktur Database Final (19 Tabel)

### Master Data
- **schools** — identitas inti sekolah (npsn, nama, alamat, kontak, status)
- **school_settings** — kop surat (1:1 dengan schools)
- **package_categories** — master kategori paket pengadaan
- **funding_sources** — master sumber dana
- **budget_years** — master tahun anggaran (pakai `start_date`/`end_date`, bukan textfield)
- **item_units** — master satuan barang
- **document_number_sequences** — counter nomor dokumen per sekolah + tahun anggaran + jenis dokumen

### Pengguna & Supplier
- **users** — role: superadmin, admin, school; login pakai `username`
- **suppliers** — identitas + alamat & persentase kepemilikan Direktur/Komisaris
- **supplier_legal_documents** — riwayat izin usaha & akta (nomor, tanggal, notaris/instansi — tanpa file scan)

### Transaksi Pengadaan
- **procurement_requests** — header transaksi: FK ke package_categories, budget_years, funding_sources; konfigurasi pajak; snapshot total (`subtotal`, `tax_amount`, `grand_total`, `totals_locked_at`); status verifikasi (`verified_at`, `verified_by`)
- **procurement_signatories** — pejabat penandatangan (headmaster, inspector, treasurer, finance, other)
- **procurement_documents** — nomor & tanggal dokumen resmi (termasuk `supplier_declaration`)
- **procurement_request_items** — detail barang; `unit_id` FK ke item_units; `negotiation_status`
- **procurement_negotiations** — riwayat multi-putaran tawar-menawar harga per item
- **procurement_verification_files** — bukti foto/scan penerimaan barang
- **procurement_request_histories** — timeline perubahan status
- **generated_documents** — metadata PDF hasil generate

### Sistem
- **notifications** — log pengiriman WhatsApp & Email

> Catatan: seluruh kolom yang sebelumnya `ENUM` sudah diganti `VARCHAR` —
> nilai valid (status, role, document_type, dll) di-hardcode di level
> aplikasi, bukan di level database.

---

## 6. Relasi Antar Tabel

```
schools
├── school_settings
├── users
├── document_number_sequences
└── procurement_requests

suppliers
├── supplier_legal_documents
└── procurement_requests

package_categories ── procurement_requests
funding_sources    ── procurement_requests
budget_years       ── procurement_requests, document_number_sequences
item_units         ── procurement_request_items

procurement_requests
├── procurement_signatories
├── procurement_documents
├── procurement_request_items
│     └── procurement_negotiations
├── procurement_verification_files
├── procurement_request_histories
├── generated_documents
└── notifications

users
├── procurement_request_histories (siapa yang mengubah status)
├── procurement_negotiations (siapa yang menawar)
└── procurement_requests (verified_by)
```

---

## 7. Dokumen yang Digenerate

| Tipe | Generate Saat |
|--------|--------|
| Cover | Barang siap |
| Planning | Barang siap |
| Negotiation | Setelah semua item selesai dinegosiasikan |
| Purchase Order | Supplier ditentukan |
| Inspection | Barang siap |
| BAST | Barang siap |
| Invoice | Barang siap |
| Receipt | Barang siap |
| **Supplier Declaration (Identitas Penyedia)** | Supplier ditentukan |

---

## 8. Notifikasi Sistem

WhatsApp:
- Pengajuan baru
- Supplier ditentukan
- **Ada tawaran harga baru / tawaran diterima-ditolak**
- Barang siap
- **Barang telah diverifikasi diterima**
- Dokumen tersedia

Email:
- Seluruh event penting
- Link dokumen

---

## 9. Prioritas Pengembangan

1. Authentication
2. Manajemen Sekolah
3. Manajemen Master Data
4. Pengajuan Pengadaan
5. Pemrosesan Pengadaan
6. Negosiasi Harga
7. Generate Dokumen
8. Verifikasi Penerimaan Barang
9. Dashboard
10. Supplier
11. Notifikasi
12. Laporan

---

## 10. Catatan Teknis

### Penyimpanan Nomor Dokumen

Nomor dokumen tidak disimpan pada file PDF, melainkan pada tabel:

- procurement_documents (nomor final yang dipakai)
- **document_number_sequences (counter penghasil nomor urut, per sekolah + tahun anggaran + jenis dokumen)**

Nomor **digenerate otomatis** oleh sistem (bukan diketik manual admin), dengan
row-locking (`lockForUpdate()`) saat increment counter untuk mencegah dua
transaksi mendapat nomor yang sama. Perubahan format nomor dokumen tidak
memengaruhi struktur file.

### Snapshot Total Harga

`procurement_requests.subtotal`, `tax_amount`, `grand_total` dihitung dan
**dikunci** (`totals_locked_at` diisi) begitu semua item selesai dinegosiasikan
dan status masuk fase finalisasi. Setelah terkunci, perubahan harga item tidak
lagi memengaruhi dokumen yang sudah digenerate — mencegah dokumen legal
"berubah" retroaktif.

### Audit Trail

Seluruh perubahan status disimpan pada:

- procurement_request_histories

Perubahan detail level field (harga, supplier, dll) ditangani oleh **Spatie
Activity Log** (lihat bagian Activity Log).

### Soft Delete

Digunakan pada:
- schools
- users
- suppliers
- procurement_requests

### Queue

Digunakan untuk:
- WhatsApp
- Email
- Generate dokumen massal

### PDF Engine

- Laravel DOMPDF

### Activity Log

Menggunakan:
- Spatie Activitylog

### Keputusan yang Sengaja Diambil (bukan gap)

- Login memakai `username`, tidak ada kolom `nik` terpisah di `users`.
- `supplier_legal_documents` tidak menyimpan file scan — data legal diisi
  manual oleh Admin yang mengolah datanya sendiri.
- Hasil pemeriksaan barang per item (jumlah diterima, kondisi) tidak masuk
  database — proses ini manual di kertas fisik, sistem hanya mencetak nama
  barang & jumlah pesanan sebagai kolom kosong untuk diisi tangan.
