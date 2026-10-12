# LaundryDesk (RAP Laundry)

Aplikasi Website Pengelolaan Operasional Cuci Pakaian Luring (*Offline-First*) berbasis **Laravel 12** dan **MySQL/MariaDB**.

---

## 1. Ringkasan Proyek
LaundryDesk dirancang khusus untuk usaha laundry pakaian skala kecil hingga menengah yang memerlukan pencatatan operasional harian secara cepat, andal, dan aman tanpa bergantung pada koneksi internet.

### Fitur Utama:
- Pencatatan pelanggan dan order laundry multi-layanan (satuan kg pecahan desimal, item satuan, paket).
- Snapshot harga master layanan pada setiap nota transaksi.
- Pelacakan alur progres cucian (*workflow status history*).
- Pencatatan pembayaran kas manual bertahap (DP dan pelunasan) dengan mekanisme Void berjejak audit.
- Cetak nota ramah kertas thermal (58mm/80mm) dan kertas A4 via CSS `@media print`.
- Dashboard metrik operasional dan laporan keuangan kas masuk harian.
- Desain antarmuka Glassmorphism modern elegan (biru-putih) yang 100% mandiri lokal (*zero external CDN*).

---

## 2. Prasyarat Sistem

Sistem telah diuji dan memenuhi spesifikasi lingkungan kerja lokal (XAMPP Windows):
- **Sistem Operasi:** Windows 10/11 atau lingkungan Linux/macOS yang setara.
- **PHP Runtime:** PHP `>= 8.2.0` (Terverifikasi: PHP 8.2.12 ZTS x64).
  - Ekstensi PHP wajib aktif: `pdo_mysql`, `bcmath`, `mbstring`, `openssl`, `tokenizer`, `xml`, `fileinfo`, `intl`.
- **Composer:** Composer `>= 2.2` (Terverifikasi: Composer 2.8.12).
- **Database Engine:** MariaDB `>= 10.4` atau MySQL `>= 5.7` (Terverifikasi: MariaDB 10.4.32 XAMPP pada port 3306).
- **Node.js & npm:** Node.js `>= 20.x` / npm `>= 10.x` (hanya diperlukan saat proses kompilasi build aset frontend).

---

## 3. Panduan Instalasi dan Setup Cepat

### Langkah 1: Kloning Repositori & Masuk ke Direktori
```bash
cd c:\xampp\htdocs\rap-laundry
```

### Langkah 2: Instalasi Dependensi PHP
```bash
composer install
```

### Langkah 3: Konfigurasi Berkas Environment (`.env`)
Salin berkas template environment:
```bash
cp .env.example .env
```
*(Pada Windows PowerShell / CMD):*
```powershell
copy .env.example .env
```

Generate application key:
```bash
php artisan key:generate
```

Pastikan parameter koneksi database lokal XAMPP pada `.env` telah sesuai:
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

### Langkah 4: Pembuatan Database Lokal & Migrasi

Panduan lengkap, langkah visual phpMyAdmin, dan pemecahan masalah koneksi dapat dibaca di **[Panduan Setup Database & phpMyAdmin](docs/DATABASE_SETUP.md)**.

#### 1. Buat Database di phpMyAdmin
1. Buka peramban dan navigasikan ke: **`http://localhost/phpmyadmin`**
2. Klik menu **Databases** (Basis Data).
3. Buat dua basis data dengan Collation `utf8mb4_unicode_ci`:
   - `rap_laundry` (database operasional utama)
   - `rap_laundry_testing` (database pengujian terisolasi)

*(Atau via terminal CLI):*
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS rap_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS rap_laundry_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### 2. Jalankan Migrasi dan Seeder
Eksekusi migrasi skema tabel dan data awal:
```bash
php artisan migrate
php artisan db:seed
```

#### 3. Akun Demo Awal Bawaan Seeder
| Peran (*Role*) | Username | Password Default | Akses |
|---|---|---|---|
| **Admin (Pemilik)** | `admin` | `admin123` | Akses penuh dashboard, pengaturan, master tarif, user |
| **Kasir (Staf)** | `kasir` | `kasir123` | Transaksi, order, cucian, kas masuk, cetak nota |

---

### Langkah 5: Menjalankan Server Lokal
Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Aplikasi dapat diakses melalui peramban di: **`http://localhost:8000`** (atau `http://127.0.0.1:8000`).

---

## 4. Pengujian Otomatis

Untuk menjalankan seluruh test suite otomatis pada database terisolasi (`rap_laundry_testing`):
```bash
php artisan test
```

---

## 5. Akses Multi-Device Jaringan Lokal (LAN / Wi-Fi)

Jika aplikasi diakses dari perangkat kasir lain dalam jaringan Wi-Fi/LAN yang sama:
1. Cari IP lokal komputer server:
   ```cmd
   ipconfig
   ```
   *(Misalnya: `192.168.1.100`)*.
2. Jalankan Laravel serve yang mengikat semua antarmuka jaringan:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. Buka browser pada perangkat kasir lain:
   `http://192.168.1.100:8000`
*(Koneksi internet publik tidak diperlukan, tetapi komputer host dan jaringan LAN/Wi-Fi harus tetap aktif).*

---

## 6. Dokumentasi Proyek Terkait

- [Product Requirements Document (PRD)](project/PRD.md)
- [Software Engineering Standards](project/ENGINEERING_STANDARDS.md)
- [Checklist Pelacakan Tugas (TASK)](project/TASK.md)
- [Spesifikasi Skema Database (DATABASE)](docs/DATABASE.md)
- [Panduan Setup Database & phpMyAdmin (DATABASE SETUP)](docs/DATABASE_SETUP.md)
- [Rencana Implementasi Teknis (IMPLEMENTATION PLAN)](docs/IMPLEMENTATION_PLAN.md)
- [Hasil Audit Lingkungan Lokal (ENVIRONMENT AUDIT)](docs/ENVIRONMENT_AUDIT.md)
- [Panduan Pencadangan dan Pemulihan (BACKUP & RESTORE)](docs/BACKUP_RESTORE.md)
