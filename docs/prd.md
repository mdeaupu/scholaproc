PRODUCT REQUIREMENTS DOCUMENT

Sistem Informasi Pengadaan Barang Sekolah

Dokumen kebutuhan produk --- versi 1.1

Model operasional: satu CV sebagai penyedia barang dan pengelola sistem

## Informasi Dokumen

**Status dokumen**
- **Ketentuan:** Draft kebutuhan produk untuk validasi pemilik proses bisnis dan tim pelaksana

**Penyedia layanan/barang**
- **Ketentuan:** Satu CV yang mengoperasikan sistem dan bertransaksi dengan sekolah

**Pengguna eksternal**
- **Ketentuan:** Sekolah

**Pengguna internal**
- **Ketentuan:** Superadmin dan Admin dari CV yang sama

**Prinsip dokumen**
- **Ketentuan:** Mandiri; hanya memuat kebutuhan dan keputusan yang ditetapkan dalam dokumen ini

# 1. Ringkasan Eksekutif

Sistem Informasi Pengadaan Barang Sekolah adalah aplikasi web untuk
mengelola pengajuan kebutuhan barang dari sekolah, pembahasan dan
persetujuan harga, pemrosesan pesanan oleh CV, pencatatan penerimaan
barang, pengelolaan dokumen transaksi, serta pencatatan pembayaran
sampai transaksi selesai.

CV merupakan satu-satunya penyedia yang bertransaksi dengan sekolah
melalui sistem. CV dapat berkoordinasi dengan pemasok untuk memenuhi
pesanan, tetapi pemasok tersebut bukan pengguna utama yang mengelola
transaksi sekolah di dalam sistem.

Superadmin dan Admin adalah pengguna internal dari CV yang sama.
Superadmin memiliki kewenangan tertinggi dan dapat menjalankan seluruh
fungsi Admin, serta menjadi satu-satunya role yang dapat mengelola
master data. Sekolah mengajukan kebutuhan, meninjau penawaran,
menyepakati harga dari sisi sekolah, memantau status, dan mencatat atau
mengunggah bukti penerimaan sesuai kewenangannya.

# 2. Tujuan Produk

-   Menyediakan alur pengadaan yang jelas dari pengajuan kebutuhan
    sekolah sampai transaksi diselesaikan.

-   Mencatat harga per barang, proses negosiasi, dan persetujuan secara
    dapat ditelusuri.

-   Mendukung penerimaan barang secara bertahap dengan pencatatan
    kuantitas dan kondisi setiap pengiriman.

-   Menyediakan dokumen transaksi yang konsisten dengan data dan tahapan
    proses.

-   Mencatat status pembayaran dan bukti pembayaran secara sederhana.

-   Menerapkan hak akses sesuai peran serta menyimpan riwayat aktivitas
    penting.

-   Mengurangi pengisian data berulang melalui master data terpusat.

# 3. Ruang Lingkup

## 3.1 Fitur yang termasuk

-   Login, pengelolaan sesi, dan pengaturan hak akses.

-   Pengelolaan akun sekolah dan pengguna internal CV.

-   Profil CV sebagai penyedia dan profil sekolah.

-   Master data terpusat: kategori/paket pengadaan, barang, satuan,
    sumber dana, dan tahun anggaran.

-   Pengajuan kebutuhan barang oleh sekolah.

-   Pemeriksaan kelengkapan dan pemrosesan pengajuan oleh CV.

-   Negosiasi harga per barang serta pencatatan hasil persetujuan.

-   Pembuatan dan pengelolaan dokumen transaksi.

-   Pencatatan pemrosesan pesanan dan pengiriman.

-   Penerimaan barang sebagian atau seluruhnya, termasuk kuantitas dan
    kondisi.

-   Pencatatan invoice, status pembayaran, dan bukti pembayaran.

-   Notifikasi, dashboard, laporan, ekspor, dan riwayat aktivitas.

## 3.2 Batasan ruang lingkup

-   Sistem tidak beroperasi sebagai marketplace yang menghubungkan
    banyak CV sebagai penyedia transaksi sekolah.

-   Tidak ada portal mandiri pemasok eksternal dalam ruang lingkup inti.

-   Pembayaran dan rekonsiliasi bank otomatis bukan bagian dari
    kebutuhan inti; sistem mencatat status dan bukti pembayaran secara
    sederhana.

-   Integrasi langsung dengan sistem perpajakan atau sistem pemerintah
    tidak termasuk dalam kebutuhan inti.

-   Tanda tangan elektronik terintegrasi bukan prasyarat. Dokumen dapat
    diproses melalui tanda tangan sederhana dan/atau cetak-pindai sesuai
    kebutuhan operasional.

# 4. Aktor dan Hak Akses

**Superadmin CV**
- **Fungsi utama:** Menjalankan seluruh fungsi Admin; mengelola akun internal, sekolah, profil CV, master data, pengaturan, transaksi, laporan, dan audit.
- **Batasan:** Perubahan penting tetap harus tercatat. Dokumen atau transaksi final tidak boleh diubah tanpa jejak koreksi.

**Admin CV**
- **Fungsi utama:** Memeriksa pengajuan, berkomunikasi dengan sekolah, melakukan negosiasi dan menyepakati harga sesuai kewenangan, memproses pesanan, mengelola dokumen, pengiriman, penerimaan, dan pencatatan pembayaran.
- **Batasan:** Tidak dapat mengelola master data. Akses terhadap pengaturan sensitif dan koreksi transaksi final mengikuti kewenangan yang ditetapkan Superadmin.

**Sekolah**
- **Fungsi utama:** Mengelola pengajuan sendiri, menyampaikan kebutuhan dan estimasi harga, menanggapi penawaran, menyetujui harga dari sisi sekolah, memantau status, mengakses dokumen, serta mencatat atau mengunggah bukti penerimaan.
- **Batasan:** Hanya dapat mengakses data sekolah dan transaksi miliknya. Tidak dapat mengubah harga resmi atau dokumen final secara langsung.

Hak akses wajib diperiksa di sisi server pada setiap permintaan data dan
tindakan. Menyembunyikan tombol di antarmuka saja tidak cukup sebagai
pengamanan.

# 5. Alur Bisnis Utama

1.  Sekolah membuat pengajuan kebutuhan barang, termasuk spesifikasi,
    satuan, jumlah, sumber dana, dan tahun anggaran.

2.  CV memeriksa kelengkapan serta kelayakan pengajuan. Jika ada
    kekurangan, pengajuan dikembalikan kepada sekolah disertai alasan.

3.  CV menyiapkan penawaran harga per barang berdasarkan kebutuhan yang
    diajukan.

4.  Sekolah dan CV melakukan negosiasi. Setiap penawaran dan tanggapan
    disimpan sebagai riwayat, bukan menimpa catatan sebelumnya.

5.  Sekolah menyetujui atau menolak penawaran akhir dari sisi sekolah.
    Harga dinyatakan final setelah persetujuan sekolah dan persetujuan
    pihak CV sesuai kewenangan.

6.  Sistem merekam harga final, kuantitas, dan total transaksi. Data
    yang digunakan untuk dokumen final disimpan sebagai snapshot agar
    tidak berubah akibat perubahan data berikutnya.

7.  CV memproses pesanan dan mengoordinasikan pemenuhan barang. CV dapat
    berkoordinasi dengan pemasok sesuai kebutuhan operasionalnya.

8.  CV mengirimkan atau menyerahkan barang kepada sekolah. Pengiriman
    dapat dilakukan dalam beberapa tahap.

9.  Sekolah atau petugas yang berwenang mencatat jumlah barang yang
    diterima dan kondisi barang untuk setiap pengiriman. Kekurangan atau
    kerusakan dicatat untuk ditindaklanjuti.

10. Setelah penerimaan barang dan dokumen penerimaan/BAST diproses
    sesuai prosedur, CV menerbitkan invoice sesuai ketentuan transaksi.

11. Sekolah melakukan pembayaran setelah barang diterima. CV mencatat
    status pembayaran dan bukti pembayaran. Kuitansi diterbitkan setelah
    pembayaran dikonfirmasi.

12. Transaksi dinyatakan selesai setelah penerimaan barang, dokumen
    administrasi yang diwajibkan, dan pembayaran telah dikonfirmasi.

Penerimaan sebagian tidak boleh dianggap sebagai penerimaan penuh.
Sistem harus tetap menunjukkan jumlah yang dipesan, jumlah yang telah
diterima, dan sisa yang belum diterima.

# 6. Status Transaksi

**Draft**
- **Makna:** Pengajuan sedang disusun oleh sekolah.

**Submitted**
- **Makna:** Pengajuan telah dikirim dan menunggu pemeriksaan CV.

**Needs Revision**
- **Makna:** Pengajuan perlu diperbaiki atau dilengkapi oleh sekolah.

**Verified**
- **Makna:** Pengajuan telah lolos pemeriksaan awal.

**Negotiation**
- **Makna:** Harga satu atau lebih barang masih dalam proses negosiasi.

**Price Agreed**
- **Makna:** Harga dan kuantitas final telah disetujui pihak sekolah dan CV.

**Processing**
- **Makna:** CV sedang memproses pemenuhan pesanan.

**Partially Received**
- **Makna:** Sebagian barang telah diterima dan masih terdapat sisa atau tindak lanjut.

**Received**
- **Makna:** Seluruh barang yang disepakati telah diterima dan dicatat.

**Invoiced**
- **Makna:** Invoice telah diterbitkan.

**Payment Pending**
- **Makna:** Pembayaran belum dikonfirmasi.

**Completed**
- **Makna:** Barang, dokumen administrasi, dan pembayaran telah memenuhi kriteria penyelesaian.

**Disputed**
- **Makna:** Ada kekurangan, kerusakan, atau keberatan yang membutuhkan tindak lanjut.

**Rejected**
- **Makna:** Pengajuan ditolak dengan alasan yang tercatat.

**Cancelled**
- **Makna:** Transaksi dibatalkan dengan alasan dan riwayat tindakan.

Status dapat disesuaikan pada tahap desain rinci, tetapi setiap status
yang diterapkan harus memiliki definisi, pihak yang boleh mengubah, dan
transisi yang diizinkan.

# 7. Kebutuhan Fungsional

## M1. Authentication & Authorization

-   Login dan logout yang aman untuk Superadmin CV, Admin CV, dan
    Sekolah.

-   Role-Based Access Control untuk membatasi menu, data, dan tindakan
    berdasarkan peran.

-   Reset kata sandi dan pengelolaan sesi pengguna.

-   Validasi hak akses dilakukan di sisi server; menyembunyikan menu
    atau tombol saja tidak cukup.

-   Setiap tindakan penting menyimpan identitas pengguna, waktu, dan
    jenis tindakan.

## M2. Dashboard

-   Superadmin melihat ringkasan sekolah aktif, pengguna, pengajuan,
    transaksi, dokumen, status pengadaan, dan aktivitas pengguna.

-   Admin melihat pengajuan yang perlu diperiksa, negosiasi yang
    menunggu tanggapan, pemrosesan pesanan, pengiriman, penerimaan, dan
    pembayaran yang perlu ditindaklanjuti.

-   Sekolah melihat pengajuan miliknya, status terakhir, negosiasi yang
    menunggu keputusan, dokumen, notifikasi, dan status pembayaran.

-   Informasi dashboard harus berasal dari data transaksi dan memakai
    definisi status yang konsisten.

## M3. Manajemen Sekolah

-   Superadmin dapat membuat, melihat, mengubah, mengaktifkan, atau
    menonaktifkan data sekolah.

-   Sistem menyimpan identitas sekolah, alamat, kontak, serta informasi
    yang diperlukan untuk dokumen resmi.

-   Sistem mendukung pengaturan kop surat atau identitas dokumen sekolah
    bila diperlukan.

-   Superadmin dapat mengelola akun sekolah dan membatasi sumber dana
    yang tersedia untuk masing-masing sekolah.

-   Perubahan profil sekolah tidak boleh mengubah isi dokumen final yang
    telah diterbitkan.

## M4. Manajemen Supplier

-   Superadmin dapat mengelola daftar pemasok yang digunakan CV untuk
    memenuhi kebutuhan pengadaan.

-   Data pemasok sekurang-kurangnya mencakup nama, alamat, kontak, dan
    informasi operasional yang diperlukan.

-   Jika dokumen legal pemasok diperlukan dalam proses bisnis, sistem
    dapat mencatat nomor, tanggal, dan pihak penerbit dokumen tersebut.

-   Pemasok tidak memiliki akun pengguna dalam ruang lingkup inti ini;
    interaksi dan pemrosesan transaksi sekolah dilakukan oleh staf CV.

-   Pemilihan pemasok merupakan proses internal CV dan tidak mengubah
    fakta bahwa CV adalah pihak yang bertransaksi dengan sekolah.

## M5. Manajemen Admin

-   Superadmin dapat melihat daftar Admin, menambah akun, mengubah data,
    mengaktifkan atau menonaktifkan akun, dan melakukan reset kata
    sandi.

-   Penghapusan akun yang pernah melakukan aktivitas transaksi dilakukan
    melalui penonaktifan agar jejak audit tetap terjaga.

-   Admin tidak dapat mengelola master data.

-   Superadmin memiliki semua kewenangan Admin ditambah fungsi
    pengelolaan akun, master data, pengaturan, dan monitoring yang lebih
    luas.

## M6. Manajemen Master Data

-   Hanya Superadmin yang dapat menambah, mengubah, mengaktifkan, atau
    menonaktifkan master data.

-   Master data meliputi kategori/paket pengadaan, barang, satuan,
    sumber dana, dan tahun anggaran.

-   Sumber dana yang dapat dipilih sekolah dapat dibatasi per sekolah.

-   Tahun anggaran menggunakan tanggal mulai dan tanggal selesai bila
    dibutuhkan untuk validasi periode; tahun akademik tidak menjadi
    field terpisah.

-   Master data yang telah digunakan dalam transaksi tidak dihapus
    secara permanen apabila penghapusan merusak riwayat transaksi.

## M7. Pengajuan Pengadaan

-   Sekolah dapat membuat, menyimpan sebagai draf, dan mengirim
    pengajuan kebutuhan barang.

-   Data pengajuan meliputi kategori/paket, sumber dana, tahun anggaran,
    nama dan spesifikasi barang, satuan, kuantitas, serta estimasi harga
    bila relevan.

-   Sistem memvalidasi kolom wajib, format angka, kuantitas, dan
    konsistensi data sebelum pengajuan dikirim.

-   Setiap pengajuan memperoleh nomor referensi unik dan memiliki status
    yang dapat dipantau sekolah.

-   Setelah pengajuan dikirim, perubahan dilakukan melalui mekanisme
    revisi yang tercatat.

## M8. Pemrosesan Pengadaan

-   Admin memeriksa kelengkapan dan kelayakan pengajuan serta mencatat
    hasil pemeriksaan.

-   Admin dapat meminta revisi atau menolak pengajuan dengan alasan yang
    tercatat.

-   CV memproses pengadaan sebagai penyedia yang bertransaksi dengan
    sekolah dan dapat mengoordinasikan pemasok untuk pemenuhan barang.

-   Setelah harga disepakati, data barang, kuantitas, dan harga final
    menjadi dasar pemrosesan pesanan serta dokumen terkait.

-   Pengaturan pajak, pejabat penandatangan, dan format dokumen harus
    mengikuti konfigurasi yang telah divalidasi; sistem tidak boleh
    menetapkan tarif pajak berdasarkan asumsi.

-   Perubahan harga atau rincian final harus melalui tindakan koreksi
    yang berwenang dan menyimpan riwayat.

## M9. Negosiasi Harga

-   Admin mengajukan penawaran harga per barang kepada sekolah.

-   Sekolah dapat menerima, menolak, atau mengajukan penawaran balik;
    Admin dapat memberikan penawaran berikutnya.

-   Setiap putaran penawaran menyimpan harga, waktu, pengguna, catatan,
    dan keputusan sehingga riwayat tidak tertimpa.

-   Harga final ditetapkan setelah persetujuan dari sisi sekolah dan
    persetujuan pihak CV sesuai kewenangan tercatat.

-   Sistem membedakan estimasi harga, harga yang sedang dinegosiasikan,
    dan harga final.

-   Setelah harga final disetujui, sistem mengunci snapshot harga dan
    kuantitas yang menjadi dasar dokumen transaksi.

## M10. Generate Dokumen

-   Sistem menghasilkan atau menyediakan data untuk dokumen yang
    digunakan dalam alur transaksi.

-   Dokumen yang didukung sesuai kebutuhan proses: ringkasan pengajuan,
    penawaran/hasil negosiasi, surat pesanan, dokumen pengiriman,
    dokumen pemeriksaan/penerimaan, BAST, invoice, dan kuitansi.

-   Dokumen identitas atau pernyataan penyedia dapat disediakan apabila
    memang dibutuhkan dalam proses operasional CV.

-   Dokumen dibuat pada tahap yang tepat: surat pesanan setelah
    kesepakatan harga; dokumen penerimaan setelah penerimaan
    diverifikasi; invoice setelah penerimaan dan administrasi terkait
    diproses; kuitansi setelah pembayaran dikonfirmasi.

-   Penomoran dokumen harus unik dan konsisten. Data yang tercetak
    disimpan sebagai snapshot agar perubahan profil atau master data
    tidak mengubah dokumen historis.

-   Dokumen dapat ditandatangani melalui cara sederhana secara
    elektronik atau dicetak dan dipindai kembali. Draf harus dibedakan
    dari dokumen yang telah disahkan.

## M11. Verifikasi Penerimaan Barang

-   Sistem mendukung penerimaan barang secara bertahap maupun sekaligus.

-   Untuk setiap penerimaan, pengguna berwenang mencatat tanggal,
    barang, jumlah diterima, kondisi, catatan, dan petugas pencatat.

-   Sistem menghitung jumlah kumulatif yang telah diterima dan sisa yang
    belum diterima.

-   Foto barang atau dokumen penerimaan yang telah ditandatangani dapat
    diunggah sebagai bukti pendukung.

-   Konfirmasi penerimaan merupakan tindakan terpisah dari unggah
    berkas.

-   Kekurangan, kerusakan, atau ketidaksesuaian harus dapat dicatat dan
    ditindaklanjuti; penerimaan sebagian tidak dianggap sebagai
    penerimaan penuh.

## M12. Notifikasi

-   Sistem menyediakan notifikasi dalam aplikasi untuk peristiwa penting
    pada transaksi.

-   Peristiwa notifikasi meliputi pengajuan baru, permintaan revisi,
    keputusan pemeriksaan, penawaran harga baru, tawaran diterima atau
    ditolak, pengiriman, penerimaan barang, dokumen tersedia, dan status
    pembayaran.

-   Notifikasi email atau WhatsApp dapat diaktifkan apabila konfigurasi
    dan integrasi yang diperlukan tersedia.

-   Kegagalan pengiriman notifikasi eksternal tidak boleh menghilangkan
    data transaksi atau riwayat notifikasi di sistem.

## M13. Laporan

-   Superadmin dan pengguna internal yang berwenang dapat melihat
    laporan sesuai hak akses.

-   Laporan dapat difilter berdasarkan periode, sekolah, status
    transaksi, sumber dana, dan tahun anggaran; informasi pemasok hanya
    ditampilkan untuk kebutuhan internal yang berwenang.

-   Laporan memuat nilai transaksi dan status dengan definisi yang
    konsisten dengan data transaksi.

-   Sistem mendukung ekspor ke format yang ditetapkan pada implementasi,
    sekurang-kurangnya PDF dan/atau Excel sesuai kebutuhan operasional.

-   Data laporan harus dapat ditelusuri kembali ke transaksi sumbernya.

# 8. Kebutuhan Dokumen

**Ringkasan pengajuan**
- **Waktu pembuatan:** Setelah data pengajuan cukup lengkap
- **Kontrol utama:** Dapat berupa draf dan harus dibedakan dari dokumen final.

**Penawaran/hasil negosiasi**
- **Waktu pembuatan:** Saat penawaran dibuat dan setelah harga final disepakati
- **Kontrol utama:** Riwayat penawaran dan pihak yang menyetujui harus dapat ditelusuri.

**Surat pesanan**
- **Waktu pembuatan:** Setelah harga dan kuantitas final disepakati
- **Kontrol utama:** Nomor dokumen unik; data sesuai snapshot transaksi.

**Dokumen pengiriman**
- **Waktu pembuatan:** Saat barang dikirim atau diserahkan
- **Kontrol utama:** Mencatat tanggal, barang, dan jumlah per pengiriman.

**Berita Acara Serah Terima (BAST)**
- **Waktu pembuatan:** Setelah penerimaan diverifikasi sesuai prosedur
- **Kontrol utama:** Draf dibedakan dari dokumen yang sudah ditandatangani.

**Invoice**
- **Waktu pembuatan:** Setelah penerimaan dan dokumen penerimaan diproses sesuai prosedur
- **Kontrol utama:** Nilai dan komponen pajak harus sesuai ketentuan yang telah divalidasi.

**Kuitansi**
- **Waktu pembuatan:** Setelah pembayaran dikonfirmasi
- **Kontrol utama:** Menjadi bukti pembayaran, bukan bukti pengiriman.

# 9. Aturan Data dan Kontrol

-   Setiap pengajuan, transaksi, dokumen, dan penerimaan memiliki
    identitas unik.

-   Harga final dan data yang dicetak pada dokumen disimpan sebagai
    snapshot transaksi.

-   Perubahan data master tidak boleh mengubah isi dokumen final yang
    telah diterbitkan.

-   Setiap perubahan penting menyimpan pengguna, waktu, data
    sebelum/sesudah bila relevan, dan alasan untuk tindakan koreksi.

-   File unggahan divalidasi berdasarkan tipe dan ukuran, disimpan
    dengan akses terbatas, dan tidak dapat diakses hanya dengan menebak
    alamat file.

-   Sistem mencegah sekolah mengakses transaksi milik sekolah lain.

-   Sistem mencegah penerimaan kumulatif melampaui jumlah pesanan
    kecuali ada mekanisme koreksi atau perubahan pesanan yang sah.

-   Penomoran dokumen harus mencegah nomor ganda.

-   Penghapusan permanen data transaksi yang telah memiliki riwayat atau
    dokumen final harus dibatasi; gunakan pembatalan atau penonaktifan
    dengan jejak audit.

# 10. Kebutuhan Nonfungsional

**Keamanan**
- **Kebutuhan:** Autentikasi, otorisasi sisi server, perlindungan sesi, validasi masukan, dan pembatasan akses file.

**Keandalan data**
- **Kebutuhan:** Validasi konsistensi transaksi, pencegahan nomor dokumen ganda, dan penyimpanan perubahan secara atomik untuk proses kritis.

**Auditabilitas**
- **Kebutuhan:** Riwayat aktivitas untuk perubahan penting dapat ditelusuri oleh pengguna berwenang.

**Kegunaan**
- **Kebutuhan:** Formulir menggunakan label yang jelas, validasi yang mudah dipahami, dan status transaksi yang konsisten.

**Kompatibilitas dokumen**
- **Kebutuhan:** Dokumen dapat ditampilkan dan dicetak dengan tata letak yang konsisten.

**Pemeliharaan**
- **Kebutuhan:** Struktur aplikasi dan data perlu didokumentasikan agar perubahan dapat diuji dan dipelihara.

**Pencadangan**
- **Kebutuhan:** Data dan dokumen penting dicadangkan serta tersedia prosedur pemulihan.

# 11. Prinsip Pajak dan Administrasi

Sistem tidak boleh menetapkan tarif atau perlakuan pajak berdasarkan
asumsi. Jenis pajak, tarif, dasar pengenaan, dan format dokumen harus
ditentukan berdasarkan karakteristik transaksi, status perpajakan CV,
serta ketentuan yang berlaku dan divalidasi oleh pihak yang bertanggung
jawab.

Sebelum konfigurasi pajak digunakan pada transaksi nyata, pemilik proses
bisnis harus memastikan aturan yang berlaku dan menguji hasil
perhitungan pada contoh transaksi. Jika aturan belum divalidasi, sistem
tidak boleh menyajikan hasil sebagai perhitungan pajak final.

# 12. Kriteria Penerimaan

1.  Sekolah dapat membuat dan mengirim pengajuan kebutuhan barang dengan
    data wajib yang tervalidasi.

2.  CV dapat memeriksa pengajuan, meminta revisi dengan alasan, atau
    melanjutkan pengajuan yang memenuhi syarat.

3.  Setiap putaran negosiasi harga tersimpan dan dapat dilihat oleh
    pihak yang berwenang.

4.  Harga final hanya terbentuk setelah persetujuan sekolah dan CV
    tercatat.

5.  Hanya Superadmin yang dapat mengelola master data.

6.  Penerimaan bertahap menampilkan jumlah yang dipesan, jumlah
    diterima, dan sisa yang belum diterima secara benar.

7.  Kondisi barang dan catatan kekurangan/kerusakan dapat disimpan pada
    setiap penerimaan.

8.  Invoice tidak diperlakukan sebagai bukti pembayaran; kuitansi baru
    dapat diterbitkan setelah pembayaran dikonfirmasi.

9.  Transaksi tidak dapat ditandai selesai sebelum penerimaan,
    administrasi wajib, dan pembayaran memenuhi ketentuan.

10. Sekolah tidak dapat melihat atau mengubah transaksi sekolah lain.

11. Perubahan penting pada harga, dokumen, penerimaan, pembayaran, dan
    status memiliki jejak audit.

12. Perubahan profil atau master data tidak mengubah dokumen final
    historis.

# 13. Catatan Implementasi

Dokumen ini menetapkan kebutuhan produk dan alur bisnis. Pilihan
struktur tabel, API, antarmuka, mekanisme migrasi, integrasi, serta
strategi pengujian ditetapkan pada tahap desain teknis berdasarkan
kondisi implementasi yang akan digunakan.

Sebelum perubahan diterapkan pada sistem yang telah berjalan, tim
pelaksana perlu memeriksa perilaku aplikasi, struktur data, data yang
telah tersimpan, integrasi, dan dampak terhadap laporan. Perubahan
dilakukan melalui rencana implementasi dan pengujian regresi agar
transaksi serta dokumen historis tetap konsisten.

# 14. Definisi Penyelesaian Transaksi

Transaksi dapat berstatus Completed apabila seluruh barang yang
disepakati telah diterima dan dicatat, dokumen penerimaan serta
administrasi wajib telah dipenuhi, invoice dan pembayaran telah diproses
sesuai prosedur, pembayaran telah dikonfirmasi oleh CV, dan tidak ada
tindak lanjut penerimaan yang masih terbuka. Jika ada pengiriman
bertahap atau masalah barang yang belum diselesaikan, transaksi tetap
berada pada status yang mencerminkan kondisi sebenarnya.
