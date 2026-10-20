# AGENTS.md — Instruksi Utama AI Coding

Dokumen ini menjadi instruksi utama bagi AI coding assistant yang bekerja pada repository Sistem Informasi Pengadaan Barang Sekolah. Ikuti instruksi ini sebelum membaca, merencanakan, atau mengubah kode.

## 1. Cara Memulai Pekerjaan

Sebelum mengerjakan permintaan:

1. Pahami permintaan pengguna dan tentukan bagian aplikasi yang terdampak.
2. Baca dokumen referensi yang relevan di folder `docs/`, terutama:
   - `docs/prd.md` — kebutuhan produk, modul, alur kerja, peran, dan aturan bisnis.
   - `docs/mvc_architecture.md` — arsitektur dan keputusan desain aplikasi.
   - `docs/database.md` — rancangan/rujukan struktur data dan relasi.
   - `docs/tech_stack.md` — teknologi dan dependensi yang direncanakan atau digunakan.
   - `docs/format-dokumen.md` — penjelasan format dokumen administrasi.
   - `docs/format-dokumen.xlsx` — workbook sumber format dokumen administrasi.
3. Periksa struktur repository dan kode yang benar-benar tersedia. Jangan menganggap semua dokumen di atas pasti ada; periksa nama dan lokasi file aktual. Jika nama file berbeda dalam huruf besar-kecil, gunakan nama aktual.
4. Untuk perubahan pada aplikasi yang sudah berjalan, telusuri implementasi terkait sebelum mengusulkan atau melakukan perubahan.

Dokumen desain adalah referensi, bukan bukti bahwa fitur, tabel, kolom, route, model, atau dependensi tersebut sudah diimplementasikan.

## 2. Prinsip Perubahan yang Aman

- Utamakan perubahan sekecil mungkin yang memenuhi kebutuhan.
- Pertahankan pola, gaya, dan konvensi yang sudah digunakan repository, kecuali ada alasan kuat dan perubahan telah disetujui.
- Jangan melakukan refactor besar, mengganti arsitektur, mengganti library, atau memperbarui dependensi sebagai efek samping dari tugas kecil.
- Jangan menghapus, menimpa, atau memindahkan file dan fitur yang sudah ada tanpa alasan jelas dan persetujuan bila berdampak signifikan.
- Jangan membuat data, bukti, konfigurasi, atau perilaku aplikasi seolah-olah sudah tersedia jika belum diverifikasi.
- Jangan mengubah dokumen referensi utama kecuali diminta secara eksplisit.

## 3. Aturan Database dan Migrasi

- **Jangan mengubah skema database tanpa audit dan analisis dampak terlebih dahulu.**
- Sebelum mengusulkan perubahan skema, periksa migrasi, model, relasi, query, data yang sudah ada, serta pemakaian kolom/tabel oleh fitur terkait.
- Jelaskan alasan, manfaat, risiko, kompatibilitas dengan data lama, rencana migrasi, dan cara rollback.
- Untuk perubahan skema yang berdampak pada data atau alur utama, minta persetujuan pengguna sebelum menerapkannya.
- Jangan menghapus atau mengganti nama tabel/kolom, mengubah tipe data, atau menghapus data produksi secara otomatis.
- Jangan membuat migrasi hanya karena rancangan `docs/database.md` berbeda dari implementasi aktual. Laporkan perbedaannya dan analisis pilihan terlebih dahulu.
- Setelah perubahan database disetujui, pastikan migrasi dan kode aplikasi konsisten serta pengujian yang relevan dilakukan.

## 4. Aturan Bisnis Pengadaan

Gunakan PRD dan implementasi aktual sebagai acuan. Jika aturan bisnis belum jelas atau dokumen saling bertentangan, jelaskan konflik dan minta keputusan sebelum membuat perubahan yang berdampak penting.

- Sistem dikelola oleh satu CV sebagai penyedia utama yang bertransaksi dengan sekolah.
- Admin dan Superadmin adalah staf dari CV yang sama. Superadmin memiliki seluruh kemampuan Admin dan berwenang mengelola master data.
- Sekolah merupakan pengguna/pelanggan eksternal dan hanya boleh mengakses data serta transaksi milik sekolahnya sendiri.
- Supplier adalah pihak sumber barang yang dikoordinasikan oleh CV, bukan penyedia terpisah yang bertransaksi langsung dengan sekolah melalui marketplace.
- Alur umum: sekolah mengajukan kebutuhan → CV memverifikasi → CV memberikan penawaran harga → negosiasi → persetujuan harga dicatat → pemeriksaan administrasi/perpajakan dan persetujuan terkait → sekolah menerbitkan surat pesanan kepada CV → CV mengoordinasikan supplier dan pengiriman → sekolah memeriksa barang dan membuat BAST → penagihan dan pembayaran → transaksi selesai dan diarsipkan.
- Penerimaan barang dapat dilakukan bertahap. Setiap penerimaan perlu mencatat jumlah yang diterima dan kondisi barang sesuai kemampuan implementasi yang telah diverifikasi.
- Transaksi hanya boleh dinyatakan selesai setelah barang yang diwajibkan diterima, dokumen yang diwajibkan lengkap, dan pembayaran terkonfirmasi.
- Faktur/invoice bukan bukti pembayaran. Kuitansi pembayaran hanya diterbitkan setelah pembayaran terkonfirmasi.
- Jangan mengarang atau meng-hard-code tarif maupun perlakuan pajak. Perlakuan pajak harus divalidasi berdasarkan kondisi transaksi, status perpajakan CV, dan ketentuan yang berlaku.
- Sumber dana dapat dibatasi sesuai pengaturan tiap sekolah jika fitur tersebut memang tersedia atau telah disetujui untuk dikembangkan.
- Gunakan tahun anggaran/tahun fiskal sebagai acuan tahun anggaran; jangan membuat konsep tahun akademik terpisah tanpa kebutuhan yang disetujui.

Aturan di atas merupakan konteks proyek. Jika bertentangan dengan keputusan pengguna yang lebih baru atau bukti implementasi yang perlu dipertahankan, hentikan perubahan yang berisiko dan minta klarifikasi.

## 5. Peran, Akses, dan Keamanan

- Periksa otorisasi di sisi server, bukan hanya menyembunyikan tombol atau menu di antarmuka.
- Pastikan sekolah tidak dapat membaca atau mengubah data sekolah lain dengan mengganti ID, URL, parameter request, atau payload.
- Validasi input di sisi server dan gunakan mekanisme autentikasi/otorisasi yang sudah dipakai repository.
- Jangan menaruh kredensial, token API, kata sandi, data pribadi, atau rahasia lain di kode, log, dokumentasi publik, contoh, maupun commit.
- Jangan menonaktifkan pemeriksaan keamanan untuk mempermudah pengujian.
- Jangan menggunakan data pribadi atau transaksi nyata dalam fixture, screenshot, seed, atau pengujian publik. Gunakan data dummy.
- Hindari menampilkan detail sensitif pada pesan error dan log.
- Jangan menjalankan perintah destruktif atau menyentuh lingkungan produksi tanpa instruksi dan persetujuan yang jelas.

## 6. Dokumen Administrasi dan Workbook

- Gunakan `docs/format-dokumen.xlsx` sebagai sumber format jika file tersebut tersedia; gunakan `docs/format-dokumen.md` sebagai penjelas struktur sheet dan keterkaitannya.
- Jangan menganggap formula, referensi workbook eksternal, fungsi khusus, atau hasil cetak di Excel otomatis dapat digunakan langsung oleh aplikasi. Audit formula dan ketergantungannya terlebih dahulu.
- Pertahankan nama, urutan, isi penting, tata letak, dan kebutuhan dokumen yang telah disepakati.
- Jangan menyalin data pribadi atau data transaksi nyata dari workbook ke source code, pengujian, atau dokumentasi umum.
- Pastikan dokumen yang dihasilkan menggunakan data transaksi yang benar, nomor dokumen yang konsisten, dan status transaksi yang sesuai.
- Jangan menerbitkan kuitansi sebagai bukti pembayaran sebelum pembayaran terkonfirmasi.

## 7. Teknologi dan Dependensi

- Periksa versi serta dependensi aktual dari file seperti `composer.json`, `composer.lock`, `package.json`, dan lockfile terkait sebelum menggunakan API atau sintaks tertentu.
- `docs/tech_stack.md` adalah referensi; kondisi repository yang telah diverifikasi menentukan teknologi yang benar-benar terpasang.
- Jangan menambahkan paket baru atau mengganti paket yang ada tanpa alasan yang relevan. Jelaskan manfaat, dampak, dan alternatif sebelum menambahkan dependensi yang signifikan.
- Ikuti pola yang sesuai dengan versi framework dan library aktual. Jangan mengasumsikan contoh dari versi lain kompatibel.
- Jangan menyimpan salinan kredensial atau file `.env` ke dokumentasi atau artefak yang dibagikan.

## 8. Kualitas Kode dan Pengujian

- Tulis kode yang mudah dipahami, konsisten, dan sesuai konvensi proyek.
- Hindari duplikasi logika, perubahan yang tidak terkait, dan abstraksi berlebihan.
- Periksa validasi, penanganan error, otorisasi, status transaksi, serta kasus batas yang relevan.
- Tambahkan atau perbarui pengujian jika sesuai dengan struktur test yang ada.
- Jalankan pengujian atau pemeriksaan yang relevan jika lingkungan memungkinkan.
- Jangan menyatakan pengujian berhasil jika belum dijalankan. Sebutkan perintah yang dijalankan, hasilnya, dan keterbatasannya secara jujur.
- Jika pengujian tidak dapat dijalankan, jelaskan sebabnya dan pemeriksaan manual yang masih diperlukan.

## 9. Alur Kerja yang Diharapkan

Untuk setiap tugas coding:

1. **Pahami:** rangkum tujuan dan kriteria selesai.
2. **Audit:** periksa file, pola kode, dependensi, dan alur terkait yang sudah ada.
3. **Rencanakan:** jelaskan file yang akan diubah dan risiko utama jika perubahan cukup besar.
4. **Implementasikan:** lakukan perubahan minimal yang disetujui.
5. **Verifikasi:** jalankan pengujian atau pemeriksaan yang relevan.
6. **Laporkan:** ringkas perubahan, file terdampak, hasil pengujian, risiko tersisa, dan langkah lanjutan.

Untuk tugas kecil yang jelas dan berisiko rendah, alur ini boleh dilakukan secara ringkas tanpa laporan rencana yang panjang.

## 10. Ketidakjelasan dan Konflik Informasi

- Jangan menebak fakta teknis atau aturan bisnis yang belum didukung oleh kode, dokumen, atau keputusan pengguna.
- Jika sumber saling bertentangan, sebutkan nama sumber dan konflik yang ditemukan; jangan diam-diam memilih salah satunya.
- Jika detail yang hilang tidak memengaruhi keputusan penting, lanjutkan dengan asumsi minimal dan nyatakan asumsi tersebut.
- Jika ketidakjelasan dapat memengaruhi keamanan, data, pembayaran, pajak, otorisasi, atau alur bisnis utama, minta klarifikasi terlebih dahulu.
- Jangan mengklaim suatu fitur, tabel, kolom, atau integrasi sudah ada sebelum memverifikasinya.

## 11. Format Laporan Setelah Bekerja

Setelah menyelesaikan tugas, laporkan secara ringkas:

- **Hasil:** apa yang diubah atau diperiksa.
- **File terdampak:** daftar file yang relevan.
- **Verifikasi:** tes/perintah yang dijalankan dan hasilnya.
- **Catatan:** risiko, asumsi, konflik, atau hal yang belum dapat diverifikasi.

Jangan membuat file dokumentasi tambahan hanya untuk mengulang informasi yang sudah jelas tercakup dalam dokumen utama.
