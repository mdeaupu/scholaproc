# Dokumentasi Workbook `format-dokumen.xlsx`

## 1. Tujuan Dokumen

Dokumen ini menjelaskan struktur dan hubungan antar-sheet dalam workbook `format-dokumen.xlsx` agar mudah dipahami oleh pengguna dan AI coding assistant.

Workbook berfungsi sebagai template kerja pengadaan barang/jasa. Workbook berisi sheet input, daftar barang dan perhitungan harga, serta template dokumen yang mengambil data dari sheet input.

**Penting:** dokumentasi ini menjelaskan struktur yang teramati pada file. Ini bukan validasi bahwa seluruh rumus, tanggal, nilai, atau isi dokumen sudah benar secara bisnis maupun hukum.

## 2. Aturan untuk AI Coding Assistant

Saat menggunakan workbook ini sebagai acuan:

1. Perlakukan file `.xlsx` asli sebagai sumber tata letak, format cetak, rumus, dan struktur sheet.
2. Jangan mengubah atau menghapus sheet, rumus, merged cells, format cetak, atau referensi antarsheet tanpa analisis dampak.
3. Jangan menganggap teks yang terlihat sebagai satu-satunya isi workbook. Periksa juga rumus dan referensi antarsheet.
4. Bedakan **nilai input**, **nilai hasil rumus**, dan **nilai yang diketik manual**.
5. Jangan mengganti rumus dengan nilai tetap kecuali diminta secara eksplisit dan dampaknya sudah dijelaskan.
6. Sebelum mengubah rumus, periksa semua sheet yang mengambil nilai dari sel tersebut.
7. Jangan menyimpulkan bahwa rumus sudah benar hanya karena rumusnya tersedia. Uji dengan contoh input dan hasil yang diharapkan.
8. Jangan menyalin data pribadi, nomor identitas, alamat, nomor telepon, atau data sensitif dari contoh workbook ke kode sumber, dokumentasi publik, atau data uji.
9. Jika workbook diubah menjadi fitur aplikasi, pertahankan perbedaan antara dokumen perencanaan, hasil negosiasi, surat pesanan, pemeriksaan, BAST, faktur, dan kuitansi.
10. Sebelum perubahan diterapkan, buat salinan cadangan dan bandingkan hasil dokumen sebelum dan sesudah perubahan.

## 3. Daftar Sheet

Workbook berisi 11 sheet:

1. `In Produk`
2. `In Data`
3. `Dok Perencanaan`
4. `Dok Hasil Nego`
5. `Surat Pesanan`
6. `Surat Hasil Pemeriksaan`
7. `Berita Acara Serah Terima`
8. `Faktur`
9. `Kuitansi`
10. `Cover`
11. `Identitas Penyedia`

## 4. Fungsi dan Struktur Setiap Sheet

### 4.1 `In Produk`

**Fungsi:** daftar barang/jasa dan perhitungan nilai penawaran serta negosiasi.

Kolom yang teramati pada header:
- `NO`
- `NAMA BARANG`
- `SATUAN`
- `HARGA PENAWARAN` (dua kolom dengan judul yang sama)
- `HARGA NEGOSIASI`
- `QTY`
- `JML HARGA NEGOSIASI`
- `JUMLAH HARGA PENAWARAN`

Rumus yang teramati pada baris barang:
- Harga penawaran pada kolom E dihitung dengan `ROUNDDOWN(F, -3)`.
- Kolom F menghitung `G / 0.99`.
- Jumlah harga negosiasi menghitung `G * H`.
- Jumlah harga penawaran menghitung `H * E`.
- Ringkasan total berada di sekitar baris 35; beberapa sheet lain merujuk ke `In Produk!I35`, `In Produk!J35`, dan `In Produk!H35`.

**Catatan pemeriksaan:** dua kolom sama-sama berjudul `HARGA PENAWARAN`. Pastikan perbedaan makna kolom E dan F terdokumentasi dengan jelas sebelum memindahkan rumus ke aplikasi.

### 4.2 `In Data`

**Fungsi:** pusat input data yang digunakan oleh berbagai template dokumen.

Bagian yang teramati:
- **Proses dan berita acara:** uraian kegiatan, nomor surat, tanggal pelaksanaan, serta tanggal dalam angka dan huruf.
- **Bahan kop surat:** informasi instansi, satuan pendidikan, alamat, dan telepon.
- **Pelaksana:** jabatan, nama, dan NIP.
- **Pemeriksa barang:** jabatan, nama, dan NIP.
- **Data paket pekerjaan:** kategori barang/jasa, nilai penawaran, nilai negosiasi, nilai terbilang, tanggal mulai/selesai, lama pekerjaan, tahun anggaran, dan sumber dana.
- **Data penyedia:** identitas direktur/komanditer, identitas dan kontak perusahaan, informasi pajak, akta, serta izin usaha.

Sheet ini berisi kombinasi nilai input manual dan rumus. Beberapa tanggal dan teks terbilang dihitung dari sel lain. Sejumlah rumus merujuk ke fungsi atau workbook eksternal, misalnya referensi berbentuk `[1]!terbilang(...)` dan `[2]INPUT DATA`. Referensi tersebut harus diperiksa di Excel dan tidak boleh diasumsikan akan bekerja sama ketika file dipindahkan atau diproses oleh aplikasi lain.

### 4.3 `Dok Perencanaan`

**Fungsi:** template dokumen perencanaan.

Informasi yang diambil dari sheet lain mencakup:
- Identitas/kop satuan pendidikan.
- Kategori barang/jasa.
- Jumlah barang/jasa dari `In Produk!H35`.
- Total anggaran dari `In Data`.
- Tanggal mulai dan selesai pekerjaan.
- Lama pengerjaan.

Sheet ini memiliki merged cells untuk tata letak dokumen. Perubahan lebar kolom, tinggi baris, merged cells, atau pengaturan cetak dapat mengubah hasil cetak.

### 4.4 `Dok Hasil Nego`

**Fungsi:** template dokumen hasil negosiasi.

Informasi yang teramati:
- Identitas satuan pendidikan.
- Nama dan alamat calon penyedia.
- Tanggal penerimaan barang/jasa.
- Hasil negosiasi.
- Daftar barang, jumlah, harga penawaran, harga negosiasi, dan total perhitungan.

Sebagian besar isi diambil melalui rumus dari `In Data` dan `In Produk`. Periksa hasil perhitungan per item dan total sebelum dokumen disahkan.

### 4.5 `Surat Pesanan`

**Fungsi:** template surat pesanan (SP).

Informasi yang teramati:
- Identitas dan kop satuan pendidikan.
- Nomor dan tanggal surat.
- Nama pekerjaan/paket.
- Identitas pihak pertama dari sekolah.
- Identitas pihak kedua/penyedia.
- Rincian lain yang ditarik dari `In Data`.

Jangan mengubah isi atau identitas pihak secara otomatis tanpa memastikan peran penandatangan dan data sumbernya benar.

### 4.6 `Surat Hasil Pemeriksaan`

**Fungsi:** template surat hasil pemeriksaan barang/pekerjaan.

Informasi yang teramati:
- Nomor dan tanggal surat pesanan.
- Nama pekerjaan dan tahun anggaran.
- Identitas pihak sekolah.
- Rincian pemeriksaan barang/pekerjaan.
- Tanggal yang ditulis dalam bentuk angka maupun huruf.

Sheet ini menggunakan rumus untuk mengambil data tanggal dan identitas dari `In Data`.

### 4.7 `Berita Acara Serah Terima`

**Fungsi:** template berita acara serah terima (BAST).

Informasi yang teramati:
- Nomor dan tanggal surat pesanan.
- Nama pekerjaan dan tahun anggaran.
- Identitas pihak yang menyerahkan dan menerima.
- Rincian penerimaan dan bagian tanda tangan.

BAST merupakan template dokumen penerimaan. Keberadaan template ini tidak membuktikan bahwa barang sudah benar-benar diterima; data penerimaan tetap harus diverifikasi sesuai proses bisnis.

### 4.8 `Faktur`

**Fungsi:** template faktur/tagihan.

Informasi yang teramati:
- Nomor faktur dan tanggal.
- Identitas pihak yang ditagih.
- Daftar barang, satuan, kuantitas, harga satuan, dan total harga.
- Data barang diambil dari `In Produk`.
- Nomor dan tanggal diambil dari `In Data`.

Faktur adalah dokumen tagihan, bukan bukti bahwa pembayaran telah diterima.

### 4.9 `Kuitansi`

**Fungsi:** template kuitansi pembayaran.

Informasi yang teramati:
- Tahun anggaran, nomor kuitansi, dan sumber dana.
- Pihak yang menyerahkan/menerima pembayaran.
- Jumlah uang dan terbilang.
- Uraian pembayaran.
- Tanggal dan bagian tanda tangan.
- Beberapa data ditarik dari `In Data`.

**Kontrol bisnis:** kuitansi seharusnya diterbitkan setelah pembayaran dikonfirmasi. Jangan menyamakan faktur dengan kuitansi.

### 4.10 `Cover`

**Fungsi:** halaman sampul dokumen/paket.

Informasi yang teramati:
- Identitas satuan pendidikan.
- Nomor kuitansi dan tanggal.
- Nama paket pekerjaan.
- Harga dan nilai terbilang.
- Nama serta alamat penyedia.
- Tahun anggaran.

Sebagian nilai diambil dari `In Data`. Pertahankan tata letak dan pengaturan cetak saat melakukan perubahan.

### 4.11 `Identitas Penyedia`

**Fungsi:** template identitas/pernyataan penyedia.

Informasi yang teramati:
- Nama, nomor identitas, jabatan, dan alamat pihak yang mewakili penyedia.
- Nama serta alamat perusahaan.
- Telepon, email, dan informasi perpajakan.
- Informasi akta pendirian/perubahan dan izin usaha.
- Pernyataan-pernyataan penyedia serta bagian tanda tangan.

Beberapa bagian teks pernyataan tampak memiliki frasa yang dirangkai secara manual atau placeholder. Validasi isi dokumen dengan pemilik proses sebelum digunakan sebagai dokumen resmi.

## 5. Peta Hubungan Antarsheet

Hubungan utama yang teramati:

- `In Data` menjadi sumber informasi bagi sebagian besar template dokumen.
- `In Produk` menyediakan daftar barang, kuantitas, harga penawaran, harga negosiasi, dan total.
- `Dok Perencanaan` mengambil jumlah barang dari `In Produk` dan data paket dari `In Data`.
- `Dok Hasil Nego` mengambil data pihak dan tanggal dari `In Data`, serta rincian barang dari `In Produk`.
- `Surat Pesanan` mengambil identitas, tanggal, nomor surat, dan data penyedia dari `In Data`.
- `Surat Hasil Pemeriksaan` dan `Berita Acara Serah Terima` mengambil data surat pesanan dan identitas dari `In Data`.
- `Faktur` mengambil data barang dari `In Produk` dan nomor/tanggal dari `In Data`.
- `Kuitansi`, `Cover`, dan `Identitas Penyedia` mengambil sebagian besar informasi dari `In Data`.

Peta ini merupakan ringkasan berdasarkan formula yang ditemukan; bukan daftar lengkap setiap referensi sel.

## 6. Hal yang Wajib Diverifikasi Sebelum Otomatisasi

1. **Formula terbilang:** beberapa rumus menggunakan referensi fungsi/workbook eksternal. Pastikan sumber fungsi tersedia dan hasilnya benar.
2. **Formula tanggal:** sejumlah tanggal dokumen dihitung dari tanggal lain dengan penambahan/pengurangan hari. Validasi urutan tanggal dan apakah aturan tersebut memang sesuai proses bisnis.
3. **Harga penawaran:** pastikan makna dua kolom `HARGA PENAWARAN` tidak tertukar.
4. **Total dan pembulatan:** uji total penawaran, total negosiasi, kuantitas, dan pembulatan dengan contoh yang diketahui benar.
5. **Nilai yang diketik manual:** ada bagian nilai yang diisi manual, termasuk nilai harga negosiasi ketik manual dan lama pekerjaan ketik manual. Pastikan tidak bertentangan dengan nilai hasil rumus.
6. **Referensi eksternal:** periksa referensi seperti `[1]!terbilang(...)` dan `[2]INPUT DATA` karena dapat bergantung pada workbook atau add-in lain.
7. **Nomor surat:** pastikan nomor final tidak menggunakan placeholder dan mengikuti aturan penomoran yang disetujui.
8. **Data contoh:** sebelum file digunakan sebagai template umum, hapus atau ganti data contoh yang bersifat pribadi atau spesifik sekolah/perusahaan melalui prosedur yang aman.
9. **Pajak:** jangan menyimpulkan tarif atau perlakuan pajak dari template saja. Validasi aturan berdasarkan transaksi dan status perpajakan yang berlaku.
10. **Hasil cetak:** periksa setiap sheet melalui Print Preview/PDF untuk memastikan halaman, margin, pemenggalan tabel, dan area cetak benar.

## 7. Cara Menggunakan Workbook sebagai Acuan Fitur Aplikasi

Jika workbook akan dijadikan acuan pembuatan fitur aplikasi pengadaan:

1. Petakan setiap kolom input ke field aplikasi.
2. Pisahkan data master, data transaksi, dan snapshot dokumen.
3. Pindahkan rumus bisnis ke implementasi aplikasi hanya setelah rumus divalidasi.
4. Simpan nomor, tanggal, rincian barang, harga, total, dan identitas pihak yang tercetak sebagai snapshot dokumen pada saat dokumen diterbitkan.
5. Bedakan status dokumen dari status transaksi.
6. Simpan jejak siapa yang membuat, memeriksa, menyetujui, dan mengubah data.
7. Jangan mengubah skema database yang sudah berjalan tanpa analisis dampak dan persetujuan eksplisit.
8. Uji bahwa perubahan data sumber tidak mengubah dokumen historis yang sudah diterbitkan.

## 8. Batasan Dokumentasi

Dokumentasi ini tidak menetapkan:
- Apakah seluruh rumus sudah benar.
- Apakah format dan isi setiap dokumen sudah memenuhi ketentuan hukum/administrasi terbaru.
- Apakah setiap tanggal, nilai, atau nomor contoh benar.
- Apakah referensi eksternal berfungsi di semua aplikasi spreadsheet.
- Apakah semua field sudah sesuai dengan skema database aplikasi yang berjalan.

Semua hal tersebut memerlukan validasi terpisah sebelum workbook dijadikan acuan implementasi final.
