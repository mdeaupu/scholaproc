# Struktur Database Pengadaan Barang Sekolah (Revisi)

Perubahan dari versi awal:
- Semua kolom `ENUM` diganti `VARCHAR` (nilai valid di-hardcode di aplikasi, bukan di level DB).
- Ditambahkan 4 tabel master baru: `package_categories`, `funding_sources`, `budget_years`, `item_units`.
- `procurement_requests.package_category`, `.funding_source`, `.budget_year` diganti jadi Foreign Key ke tabel master.
- `procurement_request_items.unit` diganti jadi `unit_id` (FK ke `item_units`).
- Ditambahkan snapshot total (`subtotal`, `tax_amount`, `grand_total`, `totals_locked_at`) di `procurement_requests` agar nilai transaksi terkunci begitu dokumen resmi digenerate/status diverifikasi.
- Ditambahkan fitur **negosiasi harga per item**: tabel baru `procurement_negotiations` (riwayat multi-putaran tawar-menawar antara sekolah dan admin_cv yang mewakili supplier) + kolom `negotiation_status` di `procurement_request_items`.
- Ditambahkan deskripsi tabel dan catatan kegunaan tiap kolom di seluruh tabel.
- Hasil review terhadap format dokumen output (Dokumen Perencanaan, Hasil Nego, Surat Pesanan, Surat Hasil Pemeriksaan, BAST, Faktur, Kuitansi, Cover, Identitas Penyedia):
  - Tambah `director_address`, `commissioner_address`, `director_share_percentage`, `commissioner_share_percentage` di `suppliers`.
  - Tambah `supplier_declaration` ke daftar `document_type` untuk dokumen "Identitas Penyedia".
  - Kolom "Jumlah Diterima" & "Kondisi Barang" di Surat Hasil Pemeriksaan/BAST **sengaja tidak dibuatkan tabel** — data itu diisi manual di kertas fisik saat serah terima, bukan lewat web, jadi cukup dicetak sebagai kolom kosong di dokumen (nama barang + jumlah pesanan saja yang perlu ditarik dari `procurement_request_items`).
- Ditambahkan fitur **verifikasi penerimaan barang**: tabel baru `procurement_verification_files` (upload foto barang & scan dokumen bertanda tangan, one-to-many per transaksi) + kolom `verified_at`/`verified_by` di `procurement_requests` (konfirmasi status verified, terpisah dari proses upload bukti).
- Ditambahkan fitur **penomoran dokumen otomatis**: tabel baru `document_number_sequences` (counter per sekolah + tahun anggaran + jenis dokumen) agar nomor surat resmi tidak diketik manual — mencegah nomor bentrok/loncat antar transaksi.
- Keputusan final (bukan gap, sudah dipertimbangkan lalu sengaja tidak diambil):
  - Login tetap pakai `username` di `users`, tidak perlu kolom `nik` terpisah.
  - `supplier_legal_documents` cukup diisi manual (nomor, tanggal, notaris/instansi) tanpa upload scan/file — karena pengisian data dilakukan sendiri oleh admin CV, bukan mengandalkan verifikasi dokumen fisik dari pihak luar.

--------------------------------------------------------------------------------
1. Table: schools
--------------------------------------------------------------------------------
Deskripsi: Data induk identitas sekolah. Satu baris = satu sekolah yang
           terdaftar di sistem. Komponen kop surat dipisah ke `school_settings`
           supaya tabel ini tetap ringkas untuk data identitas inti.

Columns:
  - id (BIGINT, PK, AI) -> ID unik sekolah, dipakai sebagai referensi di tabel lain
  - npsn (VARCHAR(8), Unique, NOT NULL) -> Nomor Pokok Sekolah Nasional, identitas resmi sekolah dari Kemendikbud
  - name (VARCHAR(255), NOT NULL) -> nama resmi sekolah
  - address (TEXT, NOT NULL) -> alamat lengkap sekolah, dipakai juga untuk cetak dokumen
  - postal_code (VARCHAR(10), Nullable) -> kode pos, opsional kalau tidak selalu diisi user
  - phone_number (VARCHAR(20), NOT NULL) -> nomor telepon/HP sekolah untuk kontak & notifikasi
  - email (VARCHAR(255), Nullable) -> email sekolah, opsional untuk notifikasi via email
  - status (VARCHAR(20), NOT NULL, DEFAULT 'active') -> hardcode: active, suspended.
      Menentukan apakah sekolah masih boleh login/bertransaksi di sistem
  - created_at (TIMESTAMP, Nullable) -> waktu data sekolah pertama kali dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir data sekolah diubah
  - deleted_at (TIMESTAMP, Nullable) -> soft delete; kalau terisi, sekolah dianggap terhapus tanpa menghapus baris fisik

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (npsn)
  - INDEX (status)

--------------------------------------------------------------------------------
2. Table: school_settings (1-to-1 with schools)
--------------------------------------------------------------------------------
Deskripsi: Menyimpan komponen kop surat dinamis dan konfigurasi khusus per
           sekolah, dipisah dari `schools` supaya profil inti tidak berantakan
           kalau ada tambahan konfigurasi lain di masa depan.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris setting
  - school_id (BIGINT, FK, Unique, NOT NULL) -> References schools(id). Menghubungkan setting ke satu sekolah spesifik (1-to-1)
  - kop_pusat (VARCHAR(255), NOT NULL) -> baris teratas kop surat, misal "PEMERINTAH DAERAH PROVINSI JAWA BARAT"
  - kop_provinsi (VARCHAR(255), NOT NULL) -> baris kedua kop surat, misal "DINAS PENDIDIKAN"
  - kop_sub_wilayah (VARCHAR(255), Nullable) -> baris ketiga kop surat kalau ada, misal "CABANG DINAS PENDIDIKAN WILAYAH VI"
  - created_at (TIMESTAMP, Nullable) -> waktu setting dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu setting terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (school_id)
  - FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE

--------------------------------------------------------------------------------
3. Table: users
--------------------------------------------------------------------------------
Deskripsi: Akun pengguna sistem. Tiga peran: owner (superadmin CV),
           admin_cv (admin CV/penyedia), dan admin_school (operator sekolah).
           Login diautentikasi lewat username (representasi NIK untuk sekolah
           atau username biasa untuk admin CV).

Columns:
  - id (BIGINT, PK, AI) -> ID unik user
  - school_id (BIGINT, FK, Nullable) -> References schools(id). Hanya terisi kalau role='admin_school'; NULL untuk owner/admin_cv
  - username (VARCHAR(50), Unique, NOT NULL) -> username untuk login
  - name (VARCHAR(255), NOT NULL) -> nama lengkap pengguna
  - email (VARCHAR(255), Unique, Nullable) -> email untuk notifikasi/reset password
  - password (VARCHAR(255), NOT NULL) -> password ter-hash, JANGAN pernah disimpan plain text
  - status (VARCHAR(20), NOT NULL, DEFAULT 'active') -> hardcode: active, suspended. Menentukan apakah user masih boleh login
  - role (VARCHAR(20), NOT NULL) -> hardcode: owner, admin_cv, admin_school. Menentukan hak akses & menu yang tampil
  - created_at (TIMESTAMP, Nullable) -> waktu akun dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir akun diubah
  - deleted_at (TIMESTAMP, Nullable) -> soft delete akun

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (username)
  - FOREIGN KEY (school_id) REFERENCES schools(id)
  - INDEX (role)
  - INDEX (status)

--------------------------------------------------------------------------------
4. Table: suppliers
--------------------------------------------------------------------------------
Deskripsi: Identitas dasar penyedia/CV. Dokumen legal (izin, akta) dipisah ke
           `supplier_legal_documents` supaya histori perubahan dokumen (misal
           akta perubahan) bisa dicatat tanpa menambah kolom baru di sini.
           Supplier TIDAK punya akun login — semua transaksi diwakilkan admin_cv.

Columns:
  - id (BIGINT, PK, AI) -> ID unik supplier
  - company_name (VARCHAR(255), NOT NULL) -> nama resmi perusahaan/CV
  - pic_name (VARCHAR(255), NOT NULL) -> nama Person In Charge, kontak utama yang dihubungi
  - email (VARCHAR(255), NOT NULL) -> email perusahaan
  - phone (VARCHAR(20), NOT NULL) -> nomor telepon perusahaan
  - address (TEXT, NOT NULL) -> alamat perusahaan, dipakai untuk cetak dokumen resmi
  - npwp (VARCHAR(30), NOT NULL) -> Nomor Pokok Wajib Pajak perusahaan
  - nib (VARCHAR(50), NOT NULL) -> Nomor Induk Berusaha
  - director_name (VARCHAR(255), NOT NULL) -> nama direktur aktif saat ini
  - director_nik (VARCHAR(16), NOT NULL) -> NIK direktur, dipakai untuk keperluan dokumen legal
  - director_npwp (VARCHAR(30), Nullable) -> NPWP pribadi direktur
  - director_phone (VARCHAR(20), Nullable) -> nomor kontak langsung direktur
  - director_address (TEXT, Nullable) -> alamat pribadi direktur (beda dengan alamat perusahaan), tercetak di dokumen "Data Penyedia" / Identitas Penyedia
  - director_share_percentage (DECIMAL(5,2), Nullable) -> persentase kepemilikan/bagian keuntungan direktur dalam CV, tercetak di dokumen "Data Penyedia"
  - commissioner_name (VARCHAR(255), Nullable) -> nama komisaris (kalau ada strukturnya)
  - commissioner_nik (VARCHAR(16), Nullable) -> NIK komisaris
  - commissioner_address (TEXT, Nullable) -> alamat pribadi komisaris, tercetak di dokumen "Data Penyedia"
  - commissioner_share_percentage (DECIMAL(5,2), Nullable) -> persentase kepemilikan/bagian keuntungan komisaris dalam CV
  - created_at (TIMESTAMP, Nullable) -> waktu supplier didaftarkan
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir data supplier diubah
  - deleted_at (TIMESTAMP, Nullable) -> soft delete supplier

  Indexes:
  - PRIMARY KEY (id)

--------------------------------------------------------------------------------
5. Table: supplier_legal_documents (One-to-Many)
--------------------------------------------------------------------------------
Deskripsi: Riwayat dokumen legal supplier (izin usaha seperti SIUP/IUJK, akta
           pendirian/perubahan). Berbentuk one-to-many supaya perubahan akta
           atau perpanjangan izin tercatat sebagai baris baru, bukan menimpa
           data lama — jadi histori tetap terjaga.

Columns:
  - id (BIGINT, PK, AI) -> ID unik dokumen
  - supplier_id (BIGINT, FK, NOT NULL) -> References suppliers(id). Dokumen ini milik supplier mana
  - document_type (VARCHAR(50)) -> jenis dokumen, misal "SIUP", "Akta Pendirian", "Akta Perubahan"
  - document_number (VARCHAR(100), Nullable) -> nomor dokumen resmi
  - document_date (DATE, Nullable) -> tanggal terbit dokumen
  - notary_name (VARCHAR(255), Nullable) -> nama notaris, relevan khusus untuk akta
  - issuer (VARCHAR(255), Nullable) -> pihak penerbit izin, misal "Pemerintah Daerah", relevan untuk dokumen perizinan
  - valid_until (VARCHAR(100), Nullable) -> masa berlaku, bisa teks bebas seperti "Seumur Hidup" atau tanggal spesifik
  - created_at (TIMESTAMP, Nullable) -> waktu baris dokumen dicatat di sistem
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir baris dokumen diubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE

--------------------------------------------------------------------------------
6. Table: package_categories (Master Data)
--------------------------------------------------------------------------------
Deskripsi: Master kategori paket pengadaan, agar admin tinggal pilih dari
           daftar yang sudah ada tanpa mengetik ulang teks yang sama berulang kali.

Columns:
  - id (BIGINT, PK, AI) -> ID unik kategori
  - name (VARCHAR(255), Unique, NOT NULL) -> nama kategori, misal "Pengadaan ATK", "Pengadaan Meubelair"
  - is_active (BOOLEAN, NOT NULL, DEFAULT TRUE) -> kalau FALSE, kategori disembunyikan dari pilihan baru tapi data lama yang sudah memakainya tetap aman
  - created_at (TIMESTAMP, Nullable) -> waktu kategori dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir kategori diubah

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (name)

--------------------------------------------------------------------------------
7. Table: funding_sources (Master Data)
--------------------------------------------------------------------------------
Deskripsi: Master sumber dana sekolah untuk pengadaan, agar konsisten
           penamaannya di semua transaksi (hindari variasi ketik seperti
           "BOS" vs "Bos" vs "B.O.S").

Columns:
  - id (BIGINT, PK, AI) -> ID unik sumber dana
  - name (VARCHAR(100), Unique, NOT NULL) -> nama sumber dana, misal "BOSP Reguler", "BOSP Kinerja", "Komite Sekolah"
  - is_active (BOOLEAN, NOT NULL, DEFAULT TRUE) -> kalau FALSE, tidak muncul di pilihan baru tapi data lama tetap valid
  - created_at (TIMESTAMP, Nullable) -> waktu data dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (name)

--------------------------------------------------------------------------------
8. Table: budget_years (Master Data)
--------------------------------------------------------------------------------
Deskripsi: Master tahun anggaran. Menggunakan DATE (bukan textfield/YEAR
           biasa) agar rentang periode anggaran bisa dipakai untuk query/filter
           tanggal, misal "tampilkan semua pengadaan yang start_date-nya masuk
           tahun anggaran ini".

Columns:
  - id (BIGINT, PK, AI) -> ID unik tahun anggaran
  - name (VARCHAR(20), Unique, NOT NULL) -> label tampilan di dropdown, misal "2025/2026"
  - start_date (DATE, NOT NULL) -> tanggal mulai periode anggaran
  - end_date (DATE, NOT NULL) -> tanggal berakhir periode anggaran
  - is_active (BOOLEAN, NOT NULL, DEFAULT FALSE) -> menandai tahun anggaran yang sedang berjalan saat ini, untuk default pilihan di form baru
  - created_at (TIMESTAMP, Nullable) -> waktu data dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (name)
  - UNIQUE (start_date, end_date)
  - INDEX (is_active)

--------------------------------------------------------------------------------
9. Table: item_units (Master Data)
--------------------------------------------------------------------------------
Deskripsi: Master satuan barang pengadaan (agar tidak input ulang teks satuan
           setiap kali menambah item, dan penamaan satuan konsisten di semua
           dokumen cetak).

Columns:
  - id (BIGINT, PK, AI) -> ID unik satuan
  - name (VARCHAR(50), Unique, NOT NULL) -> nama satuan, misal "Pcs", "Unit", "Rim", "Box", "Buah"
  - is_active (BOOLEAN, NOT NULL, DEFAULT TRUE) -> kalau FALSE, tidak muncul di pilihan baru tapi data lama tetap valid
  - created_at (TIMESTAMP, Nullable) -> waktu data dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (name)

--------------------------------------------------------------------------------
10. Table: document_number_sequences (NEW - Master/Support)
--------------------------------------------------------------------------------
Deskripsi: Penghasil nomor urut dokumen resmi secara otomatis, agar tidak
           mengandalkan input manual admin yang rawan bentrok (dua transaksi
           dapat nomor sama) atau loncat (nomor tidak berurutan). Counter
           dipisah per sekolah + tahun anggaran + jenis dokumen, karena
           masing-masing kombinasi biasanya punya urutan nomor sendiri-sendiri
           di administrasi pemerintahan.

           PENTING (level aplikasi): saat mengambil nomor baru, baris ini
           WAJIB dikunci lebih dulu (misal `SELECT ... FOR UPDATE` dalam
           transaction) sebelum increment `last_number` — supaya dua request
           yang datang bersamaan (dua admin submit di waktu hampir sama)
           tidak mendapat nomor yang sama.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris counter
  - school_id (BIGINT, FK, NOT NULL) -> References schools(id). Counter nomor ini milik sekolah mana
  - budget_year_id (BIGINT, FK, NOT NULL) -> References budget_years(id). Counter nomor di-reset ulang per tahun anggaran
  - document_type (VARCHAR(30), NOT NULL) -> hardcode sama seperti document_type lain (cover, planning, negotiation, purchase_order, inspection, bast, invoice, receipt, supplier_declaration). Jenis dokumen mana yang di-counter
  - last_number (INT, NOT NULL, DEFAULT 0) -> nomor urut terakhir yang sudah dipakai, increment tiap kali ada dokumen baru dari kombinasi sekolah+tahun+jenis yang sama
  - created_at (TIMESTAMP, Nullable) -> waktu counter pertama kali dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir counter berubah (nomor terakhir dipakai)

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (school_id) REFERENCES schools(id)
  - FOREIGN KEY (budget_year_id) REFERENCES budget_years(id)
  - UNIQUE (school_id, budget_year_id, document_type)

--------------------------------------------------------------------------------
11. Table: procurement_requests (HEADER)
--------------------------------------------------------------------------------
Deskripsi: Header transaksi pengadaan — satu baris mewakili satu paket
           pengadaan dari draft sampai selesai. Nomor dokumen dinamis dan
           penandatangan dipisah ke tabel anak (`procurement_documents`,
           `procurement_signatories`) supaya header ini fokus ke data inti
           transaksi, tanggal, dan pajak.

Columns:
  - id (BIGINT, PK, AI) -> ID unik internal, dipakai untuk relasi antar tabel
  - uuid (CHAR(36), Unique, NOT NULL) -> ID publik yang aman dipakai di URL/API (agar id BIGINT internal tidak tertebak orang luar)
  - school_id (BIGINT, FK, NOT NULL) -> References schools(id). Sekolah pemilik pengadaan ini
  - supplier_id (BIGINT, FK, Nullable) -> References suppliers(id). NULL selama supplier belum ditentukan (masih tahap draft/submitted)
  - status (VARCHAR(30), NOT NULL) -> hardcode: draft, submitted, verified,
      supplier_assigned, items_prepared, completed, rejected.
      Tahap pipeline transaksi saat ini, dipakai untuk filter dashboard & alur approval

  -- Transaction Specifics (FK ke tabel master)
  - package_category_id (BIGINT, FK, NOT NULL) -> References package_categories(id). Kategori paket pengadaan ini
  - budget_year_id (BIGINT, FK, NOT NULL) -> References budget_years(id). Tahun anggaran yang dipakai
  - funding_source_id (BIGINT, FK, NOT NULL) -> References funding_sources(id). Sumber dana yang dipakai
  - start_date (DATE, Nullable) -> tanggal mulai pekerjaan/pengadaan
  - end_date (DATE, Nullable) -> tanggal selesai pekerjaan/pengadaan
  - work_duration_text (VARCHAR(50), Nullable) -> durasi pekerjaan dalam teks untuk keperluan cetak dokumen, misal "14 (empat belas) hari kalender"

  -- Tax Configuration (Global)
  - is_taxable (BOOLEAN, DEFAULT TRUE) -> apakah transaksi ini kena pajak sama sekali
  - ppn_rate (DECIMAL(5,2), DEFAULT 11.00) -> persentase PPN yang dipakai saat transaksi dibuat (disimpan per transaksi karena tarif pajak bisa berubah dari waktu ke waktu)
  - pph_22_rate (DECIMAL(5,2), DEFAULT 0.00) -> persentase PPh Pasal 22 yang berlaku untuk transaksi ini
  - pph_23_rate (DECIMAL(5,2), DEFAULT 0.00) -> persentase PPh Pasal 23 yang berlaku untuk transaksi ini

  -- Price Snapshot (dikunci setelah final, mencegah perubahan retroaktif
  --                  pada dokumen legal/finansial yang sudah digenerate)
  - subtotal (DECIMAL(15,2), Nullable) -> SUM(quantity x official_price) seluruh item, diisi saat snapshot dikunci
  - tax_amount (DECIMAL(15,2), Nullable) -> total gabungan PPN + PPh, diisi saat snapshot dikunci
  - grand_total (DECIMAL(15,2), Nullable) -> subtotal + tax_amount, angka final yang tercetak di dokumen
  - totals_locked_at (TIMESTAMP, Nullable) -> waktu snapshot dikunci (biasanya saat
      status naik ke 'verified' atau saat dokumen pertama kali digenerate).
      Selama NULL, total masih dihitung live dari procurement_request_items.
      Begitu terisi, aplikasi WAJIB menolak perubahan item kecuali lewat alur revisi resmi

  -- Admin Notes
  - cv_notes (TEXT, Nullable) -> catatan internal dari admin CV terkait transaksi ini
  - requested_at (TIMESTAMP, Nullable) -> waktu sekolah resmi mengajukan permintaan (submit dari draft)
  - verified_at (TIMESTAMP, Nullable) -> waktu barang dikonfirmasi benar sudah diterima (berdasarkan bukti di procurement_verification_files)
  - verified_by (BIGINT, Foreign Key, Nullable) -> References users(id). Siapa yang melakukan konfirmasi penerimaan barang
  - created_at (TIMESTAMP, Nullable) -> waktu baris dibuat (biasanya sama dengan draft pertama dibuat)
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir header ini diubah
  - deleted_at (TIMESTAMP, Nullable) -> soft delete transaksi

  Indexes:
  - PRIMARY KEY (id)
  - UNIQUE (uuid)
  - FOREIGN KEY (school_id) REFERENCES schools(id)
  - FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
  - FOREIGN KEY (package_category_id) REFERENCES package_categories(id)
  - FOREIGN KEY (budget_year_id) REFERENCES budget_years(id)
  - FOREIGN KEY (funding_source_id) REFERENCES funding_sources(id)
  - FOREIGN KEY (verified_by) REFERENCES users(id)
  - INDEX (status)
  - INDEX (budget_year_id)
  - INDEX (created_at)
  - INDEX (totals_locked_at)
  - INDEX (verified_at)

--------------------------------------------------------------------------------
12. Table: procurement_signatories (One-to-Many)
--------------------------------------------------------------------------------
Deskripsi: Pejabat penandatangan untuk satu transaksi pengadaan spesifik.
           Berbentuk one-to-many supaya mendukung peran dinamis (Kepala
           Sekolah, Pemeriksa, Bendahara) dan tetap menyimpan riwayat
           penandatangan lama kalau berganti orang di kemudian hari.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris penandatangan
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). Transaksi mana yang ditandatangani
  - role (VARCHAR(30), NOT NULL) -> hardcode: headmaster, inspector, treasurer, finance, other. Peran penandatangan dalam dokumen
  - name (VARCHAR(255), NOT NULL) -> nama pejabat penandatangan
  - nip (VARCHAR(20), Nullable) -> Nomor Induk Pegawai, dicetak di dokumen resmi kalau ada
  - title (VARCHAR(100), Nullable) -> jabatan spesifik untuk ditampilkan di dokumen, misal "Bendahara BOSP"
  - created_at (TIMESTAMP, Nullable) -> waktu baris dibuat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id) ON DELETE CASCADE
  - INDEX (procurement_request_id)

--------------------------------------------------------------------------------
13. Table: procurement_documents (One-to-Many)
--------------------------------------------------------------------------------
Deskripsi: Nomor dan tanggal registrasi untuk setiap dokumen resmi yang
           dihasilkan sepanjang siklus pengadaan (Perencanaan, SP, BAST, dll).
           Menambah jenis dokumen baru cukup update daftar valid di aplikasi,
           tidak perlu ubah struktur tabel ini.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris dokumen
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). Dokumen ini milik transaksi mana
  - document_type (VARCHAR(30), NOT NULL) -> hardcode: cover, planning, negotiation,
      purchase_order, inspection, bast, invoice, receipt, supplier_declaration.
      Jenis dokumen resmi. supplier_declaration = surat "Identitas Penyedia" (pakta integritas Direktur CV)
  - document_number (VARCHAR(100), Nullable) -> nomor registrasi dokumen resmi, idealnya di-generate otomatis dari document_number_sequences (bukan diketik manual admin) lalu diformat sesuai pola resmi (mis. "900/xxx/SMAN.01/CADISDIKWIL.VI/xxx/2026")
  - document_date (DATE, Nullable) -> tanggal dokumen diterbitkan
  - created_at (TIMESTAMP, Nullable) -> waktu baris dicatat
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir diubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id) ON DELETE CASCADE
  - INDEX (procurement_request_id)

--------------------------------------------------------------------------------
14. Table: procurement_request_items
--------------------------------------------------------------------------------
Deskripsi: Daftar barang yang diminta dalam satu transaksi pengadaan.
           unit sekarang FK ke item_units (bukan teks bebas), dan ada
           negotiation_status untuk melacak ringkas status tawar-menawar
           harga per item.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris item
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). Item ini bagian dari transaksi mana
  - line_number (INT, DEFAULT 0) -> urutan tampil item di dalam daftar/dokumen cetak
  - item_name (VARCHAR(255), NOT NULL) -> nama barang
  - specification (TEXT, NOT NULL) -> spesifikasi teknis barang, misal merk/ukuran/bahan
  - unit_id (BIGINT, FK, NOT NULL) -> References item_units(id). Satuan barang, misal Pcs/Rim/Unit
  - quantity (INT, NOT NULL) -> jumlah barang yang diminta
  - estimated_price (DECIMAL(15,2), NOT NULL) -> perkiraan harga awal per unit saat sekolah membuat permintaan
  - official_price (DECIMAL(15,2), Nullable) -> harga final per unit setelah disepakati/dinegosiasikan dengan supplier
  - is_pph (BOOLEAN, DEFAULT FALSE) -> apakah item ini dikenakan PPh secara spesifik
  - negotiation_status (VARCHAR(20), NOT NULL, DEFAULT 'not_started') -> hardcode:
      not_started, negotiating, accepted, rejected. Ringkasan status agar
      daftar item tidak perlu JOIN ke procurement_negotiations tiap kali ditampilkan
  - created_at (TIMESTAMP, Nullable) -> waktu item ditambahkan
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir item diubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id) ON DELETE CASCADE
  - FOREIGN KEY (unit_id) REFERENCES item_units(id)
  - INDEX (procurement_request_id)
  - INDEX (line_number)
  - INDEX (negotiation_status)

--------------------------------------------------------------------------------
15. Table: procurement_negotiations (One-to-Many, per item)
--------------------------------------------------------------------------------
Deskripsi: Riwayat tawar-menawar harga per item. Setiap baris = satu putaran
           tawaran. Supplier tidak punya akun login, jadi tawaran atas nama
           supplier diinput oleh admin_cv (offered_by = 'admin_cv'). Keputusan
           terima/tolak HANYA boleh dilakukan oleh pihak sekolah — ini
           ditegakkan di level aplikasi, bukan constraint database.

           Alur tipikal:
           Round 1: offered_by='admin_cv',  status='pending' (tawaran awal supplier)
           Round 2: offered_by='school',    status='pending' (tawar balik sekolah)
                    -> Round 1 diupdate aplikasi jadi status='countered'
           Round 3: offered_by='admin_cv',  status='pending' (counter dari supplier)
                    -> Round 2 diupdate jadi status='countered'
           Round 3 lalu ditutup oleh sekolah: status='accepted' atau 'rejected'.
           Kalau 'accepted', aplikasi menyalin offered_price ke
           procurement_request_items.official_price dan set
           negotiation_status='accepted' pada item terkait.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris negosiasi
  - procurement_request_item_id (BIGINT, FK, NOT NULL) -> References procurement_request_items(id). Negosiasi ini untuk item barang yang mana
  - round_number (INT, NOT NULL, DEFAULT 1) -> urutan putaran tawar-menawar per item, dimulai dari 1
  - offered_by (VARCHAR(20), NOT NULL) -> hardcode: school, admin_cv. Pihak mana yang mengajukan harga di putaran ini
  - user_id (BIGINT, FK, NOT NULL) -> References users(id). User spesifik yang menginput tawaran ini (jejak audit siapa yang bertindak)
  - offered_price (DECIMAL(15,2), NOT NULL) -> harga yang diajukan di putaran ini
  - status (VARCHAR(20), NOT NULL, DEFAULT 'pending') -> hardcode: pending, countered, accepted, rejected. Status tawaran di putaran ini
  - notes (TEXT, Nullable) -> alasan tawar/tolak, misal "Harga terlalu tinggi dibanding pasar"
  - created_at (TIMESTAMP, Nullable) -> waktu tawaran diajukan
  - updated_at (TIMESTAMP, Nullable) -> waktu status tawaran ini terakhir berubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_item_id) REFERENCES procurement_request_items(id) ON DELETE CASCADE
  - FOREIGN KEY (user_id) REFERENCES users(id)
  - UNIQUE (procurement_request_item_id, round_number)
  - INDEX (status)

--------------------------------------------------------------------------------
16. Table: procurement_verification_files (NEW - One-to-Many)
--------------------------------------------------------------------------------
Deskripsi: Bukti fisik penerimaan barang — foto barang dan/atau scan dokumen
           yang sudah ditandatangani (BAST, kuitansi, dll), diunggah manual
           oleh sekolah/admin. Dipisah dari `generated_documents` karena isinya
           beda konteks: `generated_documents` = PDF mentah hasil generate
           sistem (belum ditandatangani), tabel ini = bukti fisik yang sudah
           final. Satu transaksi bisa punya banyak file (beberapa foto +
           beberapa scan halaman).

           Mengunggah file di sini TIDAK otomatis mengubah status verifikasi
           transaksi — konfirmasi "barang sudah diterima" tetap tindakan
           terpisah lewat kolom procurement_requests.verified_at/verified_by,
           supaya proses upload bukti dan proses approval bisa berjalan
           independen (misal ada bukti tambahan diunggah setelah verified).

Columns:
  - id (BIGINT, PK, AI) -> ID unik file
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). File ini bukti untuk transaksi mana
  - file_type (VARCHAR(30), NOT NULL) -> hardcode: photo, signed_document. Jenis bukti yang diunggah
  - file_path (VARCHAR(255), NOT NULL) -> lokasi file tersimpan di server/storage
  - uploaded_by (BIGINT, FK, NOT NULL) -> References users(id). Siapa yang mengunggah file ini
  - notes (TEXT, Nullable) -> catatan tambahan terkait file, misal "Foto kondisi kertas saat diterima"
  - uploaded_at (TIMESTAMP, Nullable) -> waktu file diunggah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id) ON DELETE CASCADE
  - FOREIGN KEY (uploaded_by) REFERENCES users(id)
  - INDEX (procurement_request_id)
  - INDEX (file_type)

--------------------------------------------------------------------------------
17. Table: procurement_request_histories
--------------------------------------------------------------------------------
Deskripsi: Jejak linimasa perubahan status transaksi pengadaan, dari draft
           sampai completed/rejected. Dipakai untuk menampilkan riwayat/timeline
           di halaman detail transaksi dan untuk audit siapa mengubah apa kapan.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris riwayat
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). Riwayat ini milik transaksi mana
  - user_id (BIGINT, FK, NOT NULL) -> References users(id). Siapa yang melakukan perubahan status ini
  - status (VARCHAR(30), NOT NULL) -> hardcode: draft, submitted, verified,
      supplier_assigned, items_prepared, completed, rejected. Status baru yang dicatat pada momen ini
  - notes (TEXT, Nullable) -> catatan tambahan terkait perubahan status, misal alasan penolakan
  - created_at (TIMESTAMP, Nullable) -> waktu perubahan status terjadi

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id) ON DELETE CASCADE
  - FOREIGN KEY (user_id) REFERENCES users(id)

--------------------------------------------------------------------------------
18. Table: generated_documents
--------------------------------------------------------------------------------
Deskripsi: Metadata file PDF yang sudah digenerate sistem untuk sebuah
           transaksi (cover, BAST, invoice, dll), termasuk token unduhan yang
           aman untuk dibagikan tanpa memaparkan path/id internal.

Columns:
  - id (BIGINT, PK, AI) -> ID unik baris dokumen hasil generate
  - procurement_request_id (BIGINT, FK, NOT NULL) -> References procurement_requests(id). Dokumen ini hasil generate dari transaksi mana
  - document_type (VARCHAR(30), NOT NULL) -> hardcode: cover, planning, negotiation,
      purchase_order, inspection, bast, invoice, receipt, supplier_declaration. Jenis dokumen yang digenerate
  - file_path (VARCHAR(255), NOT NULL) -> lokasi file PDF tersimpan di server/storage
  - download_token (VARCHAR(64), Unique, Nullable) -> token acak untuk link unduh publik yang aman, tanpa membocorkan id internal
  - generated_at (TIMESTAMP, NOT NULL) -> waktu dokumen ini digenerate

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id)
  - UNIQUE (download_token)

--------------------------------------------------------------------------------
19. Table: notifications
--------------------------------------------------------------------------------
Deskripsi: Log antrian dan arsip isi lengkap notifikasi WhatsApp (via WAHA) dan
           email. Dipakai untuk melacak status pengiriman dan sebagai bukti
           audit isi pesan yang pernah dikirim ke pengguna.

Columns:
  - id (BIGINT, PK, AI) -> ID unik notifikasi
  - procurement_request_id (BIGINT, FK, Nullable) -> References procurement_requests(id). Notifikasi terkait transaksi mana (NULL kalau notifikasi umum/sistem)
  - channel (VARCHAR(20), NOT NULL) -> hardcode: whatsapp, email. Media pengiriman notifikasi ini
  - recipient (VARCHAR(255), NOT NULL) -> nomor WhatsApp atau alamat email tujuan
  - message_content (TEXT, Nullable) -> isi pesan lengkap yang dikirim, disimpan untuk arsip/audit
  - status (VARCHAR(20), NOT NULL, DEFAULT 'pending') -> hardcode: pending, sent, failed. Status pengiriman notifikasi ini
  - error_message (TEXT, Nullable) -> pesan error dari provider kalau pengiriman gagal
  - sent_at (TIMESTAMP, Nullable) -> waktu notifikasi berhasil terkirim
  - created_at (TIMESTAMP, Nullable) -> waktu notifikasi masuk antrian
  - updated_at (TIMESTAMP, Nullable) -> waktu terakhir status notifikasi diubah

  Indexes:
  - PRIMARY KEY (id)
  - FOREIGN KEY (procurement_request_id) REFERENCES procurement_requests(id)
  - INDEX (status)
  - INDEX (channel)
