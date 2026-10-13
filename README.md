# LaundryDesk (RAP Laundry)

Aplikasi Website Pengelolaan Operasional Cuci Pakaian Luring (*100% Offline-First*) berbasis **Laravel 12** dan **MySQL/MariaDB**.

[![Tests](https://img.shields.io/badge/tests-288%20passed-success)](tests)
[![Laravel](https://img.shields.io/badge/laravel-12.x-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/php-8.2%2B-blue)](https://php.net)
[![Offline](https://img.shields.io/badge/mode-100%25%20offline-brightgreen)](docs/OFFLINE_MODE_GUIDE.md)

---

## 1. Ikhtisar Sistem
**LaundryDesk (RAP Laundry)** dirancang khusus untuk usaha laundry pakaian skala kecil hingga menengah yang memerlukan sistem pencatatan kasir dan pelacakan cucian yang cepat, akurat, aman, dan dapat beroperasi penuh tanpa ketergantungan koneksi internet (*zero external CDN/API*).

### Fitur Unggulan Sistem:
- **Pelanggan & Order Multi-Item:** Pencatatan pelanggan cepat via modal AJAX dan form order dinamis multi-layanan (satuan `kg` desimal presisi 2 angka, satuan `item`, dan satuan `paket`).
- **Integritas Finansial Server-Side:** Rekalkulasi otomatis subtotal, diskon (kebijakan bertingkat kasir vs admin), biaya tambahan, uang muka (DP), dan sisa tagihan di server menggunakan tipe `DECIMAL(12,2)`.
- **Snapshot Tarif Layanan:** Nama layanan, satuan penagihan, dan tarif dikunci pada saat transaksi dibuat sehingga perubahan harga master di masa depan tidak mengubah nota transaksi lama.
- **Pelacakan Alur Status Cucian:** 6 tahapan progres cucian (`Diterima` → `Menunggu Proses` → `Dicuci` → `Selesai` → `Diambil` / `Dibatalkan`) lengkap dengan pencatatan operator dan stempel waktu.
- **Pencatatan Kas Masuk Bertahap & Void Berjejak:** Dukungan pembayaran DP, pelunasan kasir (dengan hitung uang diterima dan kembalian), pembatalan pembayaran (*Void*) khusus admin dengan alasan wajib, serta pencegahan pembatalan pesanan jika masih ada kas aktif.
- **Cetak Nota Ramah Kertas:** Cetak struk kasir thermal (58mm & 80mm) dan faktur formal A4 menggunakan CSS `@media print` murni tanpa dialog cetak pihak ketiga.
- **Dashboard & Laporan Kasir:** Ringkasan metrik harian, pemisahan omzet vs kas masuk nyata vs piutang, filter rentang tanggal, serta ekspor CSV yang disanitasi dari formula injection.
- **Manajemen Pengguna & Pengaturan Usaha:** Kontrol hak akses berbasis peran (Admin vs Kasir), log aktivitas audit, dan pengaturan identitas usaha (nama, logo, kontak, jam operasional).
- **Desain Glassmorphism Modern Elegan:** Antarmuka biru-putih dengan kontras tinggi, navigasi keyboard ramah kasir, tata letak responsif desktop/tablet/ponsel, dan 100% mandiri lokal.

---

## 2. Prasyarat Sistem Lingkungan

Sistem telah diuji dan terverifikasi secara penuh pada lingkungan kerja lokal Windows 10/11 menggunakan paket XAMPP:

| Komponen Runtime | Versi Minimum | Versi Terverifikasi | Keterangan |
|---|---|---|---|
| **Sistem Operasi** | Windows 10/11 / Linux / macOS | Windows 10/11 x64 | Komputer host server lokal |
| **PHP Runtime** | PHP `>= 8.2.0` | PHP `8.2.12` ZTS (x64) | Wajib CLI & Web Server |
| **Ekstensi PHP Wajib** | - | `pdo_mysql`, `bcmath`, `mbstring`, `openssl`, `tokenizer`, `xml`, `fileinfo`, `intl`, `ctype` | Harus aktif pada berkas `php.ini` |
| **Database Engine** | MariaDB `>= 10.4` / MySQL `>= 8.0` | MariaDB `10.4.32` (XAMPP Port 3306) | Dikelola via phpMyAdmin lokal |
| **Composer** | Composer `>= 2.2` | Composer `2.8.12` | Pengelola dependensi PHP |
| **Node.js & npm** | Node.js `>= 20.x` / npm `>= 10.x` | Node.js `20.x` | Hanya untuk build aset frontend |

---

## 3. Panduan Instalasi dan Setup Cepat

### Langkah 1: Buka Terminal pada Direktori Proyek
Buka Command Prompt atau PowerShell di direktori proyek:
```powershell
cd C:\xampp\htdocs\rap-laundry
```

### Langkah 2: Instalasi Dependensi PHP
```bash
composer install
```
*(Untuk instalasi produksi murni tanpa alat pengujian):*
```bash
composer install --no-dev --optimize-autoloader
```

### Langkah 3: Konfigurasi Berkas Lingkungan (`.env`)
Salin berkas template lingkungan:
```powershell
copy .env.example .env
```
*(Atau via Git Bash / Linux: `cp .env.example .env`)*

Generate kunci enkripsi aplikasi:
```bash
php artisan key:generate
```

Buka berkas `.env` dan pastikan parameter koneksi database lokal XAMPP telah sesuai:
```dotenv
APP_NAME=LaundryDesk
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost:8000
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rap_laundry
DB_USERNAME=root
DB_PASSWORD=
```
> [!NOTE]
> Untuk penggunaan operasional kasir harian (*production deployment*), ubah `APP_ENV=production` dan `APP_DEBUG=false` agar informasi teknis tidak bocor jika terjadi kesalahan jaringan.

### Langkah 4: Pembuatan Database melalui phpMyAdmin
1. Buka peramban (*browser*) dan arahkan ke: **`http://localhost/phpmyadmin`**
2. Klik tab menu **Databases** (Basis Data).
3. Buat dua basis data dengan Collation **`utf8mb4_unicode_ci`**:
   - `rap_laundry` (Database operasional harian kasir)
   - `rap_laundry_testing` (Database pengujian terisolasi untuk automated tests)
4. *(Alternatif CLI cepat melalui terminal)*:
   ```powershell
   & "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS rap_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS rap_laundry_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

### Langkah 5: Eksekusi Migrasi Skema dan Seeder Data
Jalankan migrasi skema tabel:
```bash
php artisan migrate
```
Jalankan seeder data awal (pengaturan usaha, master layanan standar, dan akun demo bawaan):
```bash
php artisan db:seed
```
*(Opsional: untuk mengisi data simulasi transaksi pengujian):*
```bash
php artisan db:seed --class=DemoTransactionSeeder
```

### Langkah 6: Kompilasi Aset Frontend (CSS & JavaScript)
Kompilasi aset antarmuka produksi:
```cmd
npm.cmd run build
```
*(Semua aset SVG, stylesheet, dan script telah tersimpan secara mandiri di direktori `public/` dan tidak memerlukan koneksi internet maupun dev server Vite).*

---

## 4. Akun Bawaan & Prosedur Reset Akun Admin Offline

### 4.1 Akun Demo Awal Bawaan Seeder
| Peran (*Role*) | Username | Kata Sandi Default | Hak Akses |
|---|---|---|---|
| **Admin (Pemilik)** | `admin` | `admin123` | Akses penuh dashboard, manajemen user, pengaturan usaha, master tarif, audit log, laporan & ekspor |
| **Kasir (Staf)** | `kasir` | `kasir123` | Penerimaan order, transaksi pelanggan, update status cucian, pencatatan kas masuk, cetak nota |

### 4.2 Prosedur Membuat atau Reset Kata Sandi Admin secara Offline
Karena sistem beroperasi tanpa jaringan internet, pemulihan kata sandi tidak menggunakan email. Gunakan perintah Artisan offline berikut di terminal server:

```bash
php artisan laundry:reset-admin --username=admin
```
Sistem akan meminta kata sandi baru secara interaktif dan aman (minimal 8 karakter) serta langsung mengaktifkan akun peran Administrator tanpa memerlukan koneksi eksternal.

Opsi langsung dengan parameter:
```bash
php artisan laundry:reset-admin --username=admin --password=PasswordBaru123 --name="Pemilik Laundry"
```

---

## 5. Menjalankan Aplikasi

### 5.1 Mode Komputer Tunggal (Localhost)
Jalankan server aplikasi Laravel lokal:
```bash
php artisan serve
```
Buka peramban pada komputer server di: **`http://localhost:8000`** atau **`http://127.0.0.1:8000`**.

### 5.2 Mode Multi-Perangkat Jaringan Lokal (LAN / Wi-Fi Kasir)
Jika kasir menggunakan beberapa laptop/tablet/perangkat lain yang terhubung dalam satu jaringan Wi-Fi/LAN lokal:

1. Periksa alamat IP komputer server di terminal:
   ```cmd
   ipconfig
   ```
   *(Contoh IPv4 Address: `192.168.1.100`)*.
2. Jalankan Laravel serve dengan mengikat seluruh antarmuka jaringan:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. Pastikan port `8000` diizinkan pada **Windows Defender Firewall** (Inbound Rules).
4. Buka peramban pada laptop/tablet kasir lain:
   ```
   http://192.168.1.100:8000
   ```
*(Koneksi internet publik tidak dibutuhkan, tetapi komputer server dan router Wi-Fi/LAN lokal wajib tetap aktif).*

---

## 6. Prosedur Pencadangan dan Pemulihan (Backup & Restore)

Panduan operasional lengkap tersedia di **[docs/BACKUP_RESTORE.md](docs/BACKUP_RESTORE.md)**.

### Backup Cepat Database via Terminal CLI (`mysqldump`):
```cmd
C:\xampp\mysql\bin\mysqldump.exe -u root rap_laundry > backups\db-rap_laundry-manual.sql
```

### Restore Database via Terminal CLI (`mysql.exe`):
```cmd
C:\xampp\mysql\bin\mysql.exe -u root rap_laundry < backups\db-rap_laundry-manual.sql
```

> [!CAUTION]
> **Larangan Keras:** JANGAN PERNAH menjalankan `php artisan migrate:fresh` pada database produksi `rap_laundry` karena seluruh data pelanggan, pesanan, dan histori pembayaran akan terhapus permanen!

---

## 7. Panduan Pemecahan Masalah (Troubleshooting)

### 1. Error: `npm.ps1 cannot be loaded because running scripts is disabled`
- **Penyebab:** Kebijakan eksekusi skrip PowerShell Windows memblokir file wrapper `.ps1`.
- **Solusi:** Gunakan perintah `npm.cmd` alih-alih `npm`:
  ```powershell
  npm.cmd run build
  ```
  Atau ubah execution policy untuk proses saat ini: `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass`.

### 2. Error: `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it`
- **Penyebab:** Layanan MySQL/MariaDB pada XAMPP belum aktif atau berjalan di port yang berbeda.
- **Solusi:** Buka **XAMPP Control Panel**, pastikan tombol di sebelah MySQL bertuliskan **Stop** (artinya sedang berjalan hijau). Jika MySQL menggunakan port non-standar (misal `3307`), sesuaikan nilai `DB_PORT=3307` di file `.env`.

### 3. Error: `No application encryption key has been specified`
- **Penyebab:** Nilai `APP_KEY` pada file `.env` masih kosong.
- **Solusi:** Jalankan perintah `php artisan key:generate` lalu restart server `php artisan serve`.

### 4. Ekstensi PHP Belum Aktif (`Call to undefined function bcsub`, dll)
- **Penyebab:** Ekstensi `bcmath` atau `intl` dinonaktifkan di `php.ini`.
- **Solusi:** Buka `C:\xampp\php\php.ini`, cari baris `;extension=bcmath` dan hapus tanda titik-koma (`;`) di depannya menjadi `extension=bcmath`. Lakukan hal yang sama untuk `extension=intl` dan `extension=pdo_mysql`, lalu restart modul Apache di XAMPP.

### 5. Masalah Izin Direktori Cache & Sesi
- **Solusi:** Bersihkan seluruh cache konfigurasi:
  ```bash
  php artisan optimize:clear
  ```

---

## 8. Pengujian Otomatis (Automated Testing)

LaundryDesk dilengkapi dengan pengujian otomatis komprehensif pada database terisolasi (`rap_laundry_testing`):

```bash
php artisan test
```

**Ringkasan Hasil Pengujian Terverifikasi (PASS):**
- **Total Pengujian:** `288 tests passed`
- **Total Asersi:** `1.692 assertions`
- **Tingkat Kelulusan:** `100% Pass Rate (0 failed, 0 skipped)`
- **Laporan Lengkap:** Lihat **[docs/TEST_REPORT.md](docs/TEST_REPORT.md)**.

---

## 9. Indeks Dokumentasi Proyek Terkait

- [Product Requirements Document (PRD)](project/PRD.md)
- [Software Engineering Standards](project/ENGINEERING_STANDARDS.md)
- [Checklist Pelacakan Tugas (TASK)](project/TASK.md)
- [Spesifikasi Skema Basis Data (DATABASE)](docs/DATABASE.md)
- [Panduan Setup Database & phpMyAdmin (DATABASE SETUP)](docs/DATABASE_SETUP.md)
- [Panduan Pencadangan dan Pemulihan (BACKUP & RESTORE)](docs/BACKUP_RESTORE.md)
- [Laporan Pengujian Terverifikasi (TEST REPORT)](docs/TEST_REPORT.md)
- [Daftar Batasan Sistem yang Diketahui (KNOWN LIMITATIONS)](docs/KNOWN_LIMITATIONS.md)
- [Panduan Pengoperasian Mode Offline (OFFLINE MODE GUIDE)](docs/OFFLINE_MODE_GUIDE.md)

---
&copy; 2026 LaundryDesk (RAP Laundry). Dilindungi Hak Cipta.
