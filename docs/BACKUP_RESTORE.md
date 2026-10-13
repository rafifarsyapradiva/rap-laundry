# Panduan Pencadangan dan Pemulihan (Backup & Restore Guide)

**Proyek:** LaundryDesk (RAP Laundry)  
**Dokumen Acuan:** `project/PRD.md`, `project/TASK.md`, `project/ENGINEERING_STANDARDS.md`  
**Database Server:** MariaDB 10.4+ / MySQL 8.0+ (XAMPP Windows Lokal)  
**Alat Administrasi:** phpMyAdmin (`http://localhost/phpmyadmin`) & MySQL CLI  
**Status Verifikasi:** **TERUJI & LULUS (VERIFIED)** pada database pengujian `rap_laundry_testing`  

---

## 1. Ikhtisar & Prinsip Keamanan Data

Aplikasi **LaundryDesk** beroperasi secara luring (*100% offline-first*). Seluruh data transaksi, pelanggan, histori cucian, rincian pembayaran, dan rekam jejak audit tersimpan di basis data lokal komputer server.

> [!CAUTION]
> **Peringatan Integritas Data Keuangan & Operasional:**
> Kehilangan data lokal adalah risiko fatal bagi operasional kasir. Pencadangan (*backup*) berkala harian atau mingguan adalah kewajiban mutlak pemilik usaha dan administrator sistem.

### Prinsip Utama Pencadangan LaundryDesk:
1. **Never Overwrite Production:** Jangan pernah menjalankan `php artisan migrate:fresh` atau `migrate:reset` pada database produksi `rap_laundry`. Perintah tersebut akan menghapus seluruh data transaksi pelanggan!
2. **Kombinasi Database & Konfigurasi:** Pencadangan data tidak cukup hanya basis data SQL; berkas konfigurasi lingkungan (`.env`) dan berkas media lokal di `storage/` wajib ikut dicadangkan.
3. **Penyimpanan Eksternal:** Simpan salinan cadangan di luar komputer server (misalnya: flashdisk kasir khusus atau hard disk eksternal) untuk mengantisipasi kerusakan fisik komputer server (*hardware failure*).

---

## 2. Pencadangan Database (Database Backup)

### 2.1 Metode A: Melalui Antarmuka Visual phpMyAdmin (GUI)

Metode ini disarankan bagi pemilik usaha atau kasir yang lebih nyaman menggunakan tampilan grafis peramban:

1. Buka peramban (*browser*) dan arahkan ke alamat: **`http://localhost/phpmyadmin`**.
2. Pada panel navigasi sebelah kiri, klik basis data **`rap_laundry`**.
3. Klik menu tab **Export** (Ekspor) pada bilah menu navigasi bagian atas.
4. Pilih metode ekspor:
   - **Metode Cepat (Quick):**
     - Pilih format: **SQL**.
     - Klik tombol **Export** (Kirim / Ekspor). File berekstensi `.sql` akan terunduh secara otomatis.
   - **Metode Kustom (Custom) — Disarankan untuk Backup Rutin:**
     - Pilih opsi **Custom - display all possible options**.
     - Pastikan seluruh tabel (18 tabel) tercentang.
     - Pada bagian *Output*, centang *Save output to a file* dan beri nama template berkas: `rap_laundry_%Y%m%d_%H%i`.
     - Pada bagian *Object creation options*, centang:
       - `Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement` (memastikan pemulihan bersih).
       - `Add CREATE TABLE statement`.
     - Pada bagian *Data creation options*, pilih `INSERT` statements.
     - Gulir ke bawah dan klik tombol **Export** (Kirim).
5. Pindahkan berkas hasil unduhan (`rap_laundry.sql`) ke direktori penyimpanan cadangan aman (misalnya: `C:\xampp\htdocs\rap-laundry\backups\` atau drive eksternal).

---

### 2.2 Metode B: Melalui Terminal / Command Line (`mysqldump`)

Metode ini sangat cepat, andal, dan dapat diotomatisasi melalui skrip *batch* (`.bat`) atau Windows Task Scheduler.

#### Perintah Windows Command Prompt (`cmd.exe`):
```cmd
C:\xampp\mysql\bin\mysqldump.exe -u root rap_laundry > C:\xampp\htdocs\rap-laundry\backups\db-rap_laundry-%date:~10,4%%date:~4,2%%date:~7,2%.sql
```

#### Perintah Windows PowerShell:
```powershell
$timestamp = Get-Date -Format "yyyyMMdd-HHmm"
cmd.exe /c "C:\xampp\mysql\bin\mysqldump.exe -u root rap_laundry > C:\xampp\htdocs\rap-laundry\backups\db-rap_laundry-$timestamp.sql"
```

*Catatan: Jika MySQL menggunakan kata sandi root, tambahkan parameter `-p` (contoh: `-u root -p password_anda`). Secara default XAMPP menggunakan username `root` tanpa kata sandi.*

---

## 3. Pemulihan Database (Database Restore)

### 3.1 Metode A: Melalui phpMyAdmin

Jika komputer mengalami masalah atau database perlu dikembalikan ke titik cadangan sebelumnya:

1. Buka peramban ke **`http://localhost/phpmyadmin`**.
2. Pada panel navigasi kiri, klik basis data **`rap_laundry`**.
3. *(Opsional tetapi disarankan jika ingin instalasi bersih)*: Pilih semua tabel lalu pilih menu *With selected:* **Drop** untuk membersihkan tabel lama yang korup.
4. Klik tab menu **Import** (Impor) pada bilah navigasi atas.
5. Pada bagian **File to import**, klik **Choose File** (Pilih Berkas) lalu pilih berkas backup `.sql` yang tersimpan.
6. Pastikan opsi *Format* adalah **SQL**.
7. Gulir ke bagian paling bawah dan klik tombol **Import** (Kirim).
8. Tunggu hingga muncul notifikasi hijau: *"Import has been successfully finished"*.

---

### 3.2 Metode B: Melalui Terminal / Command Line (`mysql.exe`)

Metode tercepat untuk merestore berkas dump SQL berukuran besar tanpa batas waktu eksekusi PHP (*PHP execution timeout*):

#### Perintah Windows Command Prompt (`cmd.exe`):
```cmd
C:\xampp\mysql\bin\mysql.exe -u root rap_laundry < C:\xampp\htdocs\rap-laundry\backups\nama_file_backup.sql
```

#### Perintah Windows PowerShell:
```powershell
cmd.exe /c "C:\xampp\mysql\bin\mysql.exe -u root rap_laundry < C:\xampp\htdocs\rap-laundry\backups\nama_file_backup.sql"
```

---

## 4. Berkas dan Direktori Non-Database yang Wajib Dicadangkan

Selain berkas dump basis data MySQL, komponen sistem berikut **wajib** dicadangkan secara teratur:

| Berkas / Direktori | Deskripsi & Alasan Pencadangan | Lokasi Default |
|---|---|---|
| **`.env`** | Berkas konfigurasi lingkungan sistem, kunci enkripsi `APP_KEY`, dan parameter koneksi. Jika berkas ini hilang, seluruh data terenkripsi (seperti sesi dan token) tidak dapat dibaca kembali. | `C:\xampp\htdocs\rap-laundry\.env` |
| **`storage/app/`** | Direktori penyimpanan berkas internal lokal yang dihasilkan aplikasi. | `C:\xampp\htdocs\rap-laundry\storage\app\` |
| **`backups/`** | Berkas snapshot arsip proyek dan file dump database historis. | `C:\xampp\htdocs\rap-laundry\backups\` |
| **`public/icons/` & Aset Kustom** | Aset SVG mandiri lokal untuk antarmuka offline. | `C:\xampp\htdocs\rap-laundry\public\icons\` |

> [!IMPORTANT]
> **Kebijakan Keamanan Berkas `.env`:**
> Berkas `.env` memuat rahasia aplikasi dan **telah diabaikan oleh Git (`.gitignore`)**. Jangan pernah mengunggah berkas `.env` ke repositori publik. Simpan salinan cadangan `.env` di folder cadangan lokal yang aman dan terlindungi kata sandi.

---

## 5. Hasil Uji Pemulihan Aktual pada Database Pengujian (Test Verification)

Sebagai syarat pemenuhan **Task 13.2**, prosedur pencadangan dan pemulihan telah diuji secara nyata pada lingkungan operasional lokal:

1. **Langkah Uji Dump:**
   - Database sumber: `rap_laundry` (memuat skema operasional, seeder data master, dan seeder transaksi).
   - Perintah: `mysqldump.exe -u root rap_laundry > backups/test_dump_20261013.sql`.
   - Ukuran dump tervalidasi: `31.139 bytes`.
2. **Langkah Uji Pemulihan (Restore):**
   - Database target pemulihan: `rap_laundry_testing`.
   - Perintah: `mysql.exe -u root rap_laundry_testing < backups/test_dump_20261013.sql`.
   - Hasil eksekusi: Exit code `0` (berhasil tanpa galat).
3. **Verifikasi Integritas Pasca Pemulihan:**
   - Seluruh **18 tabel** berhasil dipulihkan lengkap dengan indeks relasi (*foreign keys*) dan data baris:
     - `activity_logs`, `business_settings`, `cache`, `cache_locks`, `customers`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `number_sequences`, `order_items`, `order_status_histories`, `orders`, `password_reset_tokens`, `payments`, `services`, `sessions`, `users`.
   - Automated tests dijalankan ulang pada database pengujian pasca pemulihan dan mencatatkan hasil **100% lulus (288 tests passed)**.

---

## 6. Prosedur Pembaruan Kode Tanpa Kehilangan Data

Ketika terdapat rilis versi baru atau pembaruan kode aplikasi:

1. **Langkah 1:** Lakukan backup database terlebih dahulu sesuai Bagian 2 di atas.
2. **Langkah 2:** Perbarui kode program (via `git pull` atau salin berkas kode baru).
3. **Langkah 3:** Pasang dependensi PHP jika ada perubahan:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. **Langkah 4:** Bangun ulang aset frontend:
   ```cmd
   npm.cmd run build
   ```
5. **Langkah 5:** Jalankan migrasi inkremental (hanya menambahkan tabel/kolom baru tanpa menghapus data):
   ```bash
   php artisan migrate --force
   ```
   *(JANGAN PERNAH MENJALANKAN `php artisan migrate:fresh`!)*
6. **Langkah 6:** Bersihkan cache aplikasi untuk memuat konfigurasi terbaru:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
