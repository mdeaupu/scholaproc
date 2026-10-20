# Tech Stack — Sistem Informasi Pengadaan Barang Sekolah

Dokumen ini merapikan isi `Tech Stack.txt` ke format Markdown. Daftar di bawah mempertahankan teknologi yang dicantumkan pada file sumber; keberadaan dan versi yang benar-benar terpasang tetap perlu diverifikasi melalui repository dan lockfile.

## Core Engine
- **Framework:** Laravel 12
- **Language:** PHP 8.3
- **Database:** MySQL

## User Interface
- **Styling:** Tailwind CSS
- **Frontend Logic:** AlpineJS
- **Reactivity:** Livewire
- **UI Components:** Mary UI
- **Icons:** Blade Icons

## Functional Libraries
- **Authentication & Role:** Laravel Breeze & Spatie Permissions
- **Document Generation:** Barryvdh Laravel-DOMPDF
- **Data Export:** Rap2hpoutre Fast-Excel
- **Security / Activity Log:** Spatie Laravel Activity Log
- **Storage & Asset:** Spatie Laravel Media Library
- **System Backup:** Spatie Backup

## Validation & Tracking
- **QR Generator:** SimpleSoftwareIO/Simple-QRCode
- **QR Scanner:** HTML5-QRCode

## External Integration
- **Messaging:** WhatsApp HTTP API (Docker Compose / WAHA)
- **Email Service:** Laravel Mail
- **Process Queue:** Laravel Queue

## Development & Deployment
- **API Testing:** Postman
- **Virtualization:** Docker (khusus WAHA / WhatsApp HTTP API)
- **Version Control:** Git

## Catatan Verifikasi
Sebelum mengubah kode atau menggunakan API paket:
1. Periksa `composer.json` dan `composer.lock`.
2. Periksa `package.json` dan lockfile JavaScript.
3. Periksa konfigurasi serta implementasi aktual.
4. Jangan melakukan upgrade atau mengganti dependency tanpa analisis dampak dan persetujuan.
