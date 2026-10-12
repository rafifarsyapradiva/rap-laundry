# Panduan Setup Database Lokal & phpMyAdmin

**Proyek:** LaundryDesk (RAP Laundry)  
**Dokumen Terkait:** `docs/DATABASE.md`, `project/PRD.md`, `project/ENGINEERING_STANDARDS.md`  
**Database Server:** MariaDB 10.4+ / MySQL 8.0+ (Lingkungan XAMPP Lokal)  
**Alat Administrasi:** phpMyAdmin (`http://localhost/phpmyadmin`)  

---

## 1. Pendahuluan

Sistem LaundryDesk beroperasi secara luring (*100% offline-first*) menggunakan basis data relasional **MariaDB/MySQL lokal** yang umumnya terpasang melalui paket XAMPP. 

> [!IMPORTANT]
> **phpMyAdmin** adalah perangkat lunak berbasis web untuk mengelola dan mengadministrasikan basis data MySQL/MariaDB secara visual (GUI), **bukan basis data itu sendiri**. Mesin basis data yang sesungguhnya berjalan sebagai service lokal di komputer server pada port `3306`.

Dokumen ini menjelaskan prosedur penyiapan basis data lokal, konfigurasi berkas `.env`, perintah eksekusi dari command line (CLI), serta solusi praktis untuk menangani masalah koneksi yang sering terjadi.

---

## 2. Cara Membuat Database Lokal Melalui phpMyAdmin

Aplikasi LaundryDesk memerlukan **dua basis data lokal**:
1. **`rap_laundry`**: Basis data utama untuk operasional harian kasir dan pengembangan aplikasi.
2. **`rap_laundry_testing`**: Basis data pengujian terisolasi untuk menjalankan automated test suite tanpa risiko merusak atau menghapus data transaksi operasional.

### 2.1 Pembuatan Melalui Antarmuka Visual (GUI)

1. Pastikan modul **Apache** dan **MySQL** telah berstatus **Running** (berwarna hijau) pada **XAMPP Control Panel**.
2. Buka peramban (*web browser*) dan akses URL:  
   ```
   http://localhost/phpmyadmin
   ```
3. Pada bilah navigasi kiri atas, klik tab **Databases** (atau **Basis Data**).
4. Di bagian **Create database** (Buat basis data):
   - Masukkan nama basis data: `rap_laundry`.
   - Pilih Collation / Penyortiran: **`utf8mb4_unicode_ci`** (atau `utf8mb4_general_ci`).
   - Klik tombol **Create** (Buat).
5. Ulangi langkah di atas untuk membuat basis data pengujian:
   - Masukkan nama basis data: `rap_laundry_testing`.
   - Pilih Collation: **`utf8mb4_unicode_ci`**.
   - Klik tombol **Create** (Buat).

### 2.2 Pembuatan Alternatif Melalui Tab SQL phpMyAdmin

Jika lebih menyukai eksekusi perintah langsung di phpMyAdmin:
1. Klik tab **SQL** pada menu navigasi atas phpMyAdmin.
2. Masukkan perintah SQL berikut:
   ```sql
   CREATE DATABASE IF NOT EXISTS `rap_laundry` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE IF NOT EXISTS `rap_laundry_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Klik tombol **Go** (Kirim) di kanan bawah.

---

## 3. Konfigurasi Berkas Environment (`.env`)

Laravel menghubungkan aplikasi ke database melalui konfigurasi berkas `.env`.

### 3.1 Parameter Konfigurasi Database

Buka berkas `.env` di direktori utama proyek (`c:\xampp\htdocs\rap-laundry\.env`) dan sesuaikan parameter berikut:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rap_laundry
DB_USERNAME=root
DB_PASSWORD=
```

#### Penjelasan Variabel:
- **`DB_CONNECTION`**: Driver koneksi database Laravel. Wajib bernilai `mysql` (mendukung MariaDB).
- **`DB_HOST`**: Alamat IP host server database. Disarankan menggunakan `127.0.0.1` daripada `localhost` untuk menghindari resolusi socket Unix/Windows yang lambat.
- **`DB_PORT`**: Port layanan MySQL lokal. Standar instalasi XAMPP adalah `3306`.
- **`DB_DATABASE`**: Nama basis data operasional yang telah dibuat di phpMyAdmin (`rap_laundry`).
- **`DB_USERNAME`**: Nama pengguna database. Standar instalasi default XAMPP adalah `root`.
- **`DB_PASSWORD`**: Kata sandi pengguna database. Pada instalasi standar default XAMPP, kata sandi root dikosongkan (`DB_PASSWORD=`). Jika Anda telah menetapkan kata sandi pada MariaDB lokal Anda, masukkan kata sandi tersebut di sini.

### 3.2 Prinsip Keamanan Kredensial

> [!CAUTION]
> **Dilarang menaruh atau membagikan kata sandi nyata dalam dokumentasi atau repositori publik!**
> 
> 1. Berkas `.env` asli berisi rahasia sistem dan **wajib terdaftar di `.gitignore`** agar tidak pernah terunggah ke repositori Git.
> 2. Berkas `.env.example` disediakan sebagai templat resmi tanpa kredensial sensitif.
> 3. Jangan mengubah kata sandi default produksi di kode program; selalu gunakan pembacaan dari environment variable via `config('database.connections.mysql')`.

---

## 4. Verifikasi Konektivitas Database dari CLI

Sebelum menjalankan migrasi, verifikasi bahwa Laravel dapat berkomunikasi secara langsung dengan database MariaDB lokal.

Buka terminal PowerShell pada direktori proyek:
```powershell
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Koneksi ke database ' . config('database.connections.mysql.database') . ' BERHASIL.' . PHP_EOL;"
```

Jika koneksi sukses, terminal akan menampilkan pesan:
```
Koneksi ke database rap_laundry BERHASIL.
```

---

## 5. Menjalankan Migration dan Seeder dari Terminal

Setelah basis data dibuat di phpMyAdmin dan file `.env` dikonfigurasi, struktur tabel dan data awal di-generate menggunakan perintah Artisan CLI.

### 5.1 Menjalankan Migrasi Skema
Untuk membuat 10 tabel inti sistem beserta indeks dan foreign key:
```bash
php artisan migrate
```

### 5.2 Memeriksa Status Migrasi
Untuk memastikan seluruh berkas migrasi telah berstatus `Ran: Yes`:
```bash
php artisan migrate:status
```

### 5.3 Menjalankan Seeder Data Awal
Untuk mengisi data master layanan, akun admin/kasir awal, identitas usaha, dan contoh transaksi demo:
```bash
php artisan db:seed
```

### 5.4 Reset Bersih untuk Instalasi Baru (*Fresh Install*)
Jika ingin mengosongkan seluruh tabel dan membangun ulang struktur serta data awal secara bersih:
```bash
php artisan migrate:fresh --seed
```

> [!WARNING]
> Perintah `migrate:fresh` akan **menghapus seluruh data yang ada di database**. Hanya gunakan perintah ini saat inisialisasi awal sistem atau di lingkungan pengembangan, **jangan jalankan pada komputer kasir yang sudah memuat data operasional riil**.

---

## 6. Akun Demo Awal Bawaan Seeder

Setelah menjalankan `php artisan db:seed`, sistem LaundryDesk menyediakan akun awal untuk keperluan pengujian lokal:

| Peran (*Role*) | Username | Password Default | Hak Akses |
|---|---|---|---|
| **Admin (Pemilik)** | `admin` | `admin123` | Akses penuh dashboard, pengaturan usaha, master tarif, manajemen user, dan laporan. |
| **Kasir (Staf)** | `kasir` | `kasir123` | Operasional harian: pendaftaran pelanggan, transaksi order, perubahan status cucian, pembayaran kas, dan cetak nota. |

*(Kata sandi dapat diganti melalui antarmuka manajemen pengguna setelah fitur UI aktif atau melalui artisan seeder).*

---

## 7. Solusi untuk Masalah Koneksi Database Umum (*Troubleshooting*)

Berikut adalah katalog pemecahan masalah jika terjadi kegagalan koneksi database di lingkungan XAMPP:

### Masalah 1: `SQLSTATE[HY000] [2002] Connection refused` atau `No connection could be made`
- **Penyebab:** Service MySQL/MariaDB pada XAMPP belum aktif atau mati secara tiba-tiba.
- **Solusi:**
  1. Buka **XAMPP Control Panel**.
  2. Periksa baris **MySQL**. Jika belum berjalan, klik tombol **Start**.
  3. Pastikan indikator berubah menjadi hijau dengan nomor port `3306`.

### Masalah 2: `SQLSTATE[HY000] [1049] Unknown database 'rap_laundry'`
- **Penyebab:** Basis data dengan nama `rap_laundry` belum dibuat di MariaDB atau terdapat salah ketik (*typo*) pada parameter `DB_DATABASE` di file `.env`.
- **Solusi:**
  1. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
  2. Periksa daftar database di sebelah kiri.
  3. Jika belum ada, buat database baru dengan nama `rap_laundry` (Collation: `utf8mb4_unicode_ci`).
  4. Periksa kembali baris `DB_DATABASE=rap_laundry` pada file `.env`.

### Masalah 3: `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`
- **Penyebab:** Kredensial username atau password di `.env` tidak cocok dengan setelan pengguna di MariaDB/phpMyAdmin.
- **Solusi:**
  1. Pada instalasi default XAMPP, password root adalah kosong (`DB_PASSWORD=`).
  2. Jika Anda pernah mengubah password root phpMyAdmin, buka file `.env` dan masukkan kata sandi yang sesuai pada `DB_PASSWORD=kata_sandi_anda`.
  3. Bersihkan cache konfigurasi Laravel:
     ```bash
     php artisan config:clear
     ```

### Masalah 4: Konflik Port MySQL (`Port 3306 in use by another application`)
- **Penyebab:** Terdapat instalasi MySQL mandiri lain yang sedang berjalan di background Windows (misal MySQL Server bawaan Oracle atau Docker) yang menduduki port 3306.
- **Solusi:**
  1. Buka `services.msc` di Windows (tekan `Win + R` lalu ketik `services.msc`).
  2. Cari layanan bernama `MySQL` atau `MySQL80`. Jika statusnya **Running**, klik kanan dan pilih **Stop** (atau ubah startup type ke Manual).
  3. Buka XAMPP Control Panel dan klik tombol **Start** pada MySQL.

### Masalah 5: `SQLSTATE[42000] [1273] Unknown collation: 'utf8mb4_unicode_ci'`
- **Penyebab:** Versi MySQL/MariaDB yang sangat lama tidak mendukung collation tertentu.
- **Solusi:**
  Gunakan Collation `utf8mb4_general_ci` saat membuat basis data di phpMyAdmin, atau perbarui XAMPP ke versi yang menyertakan MariaDB 10.4+.

### Masalah 6: Error `performance_schema.session_status doesn't exist` saat `db:show`
- **Penyebab:** Konfigurasi tabel statistik sesi MariaDB di beberapa distribusi XAMPP dinonaktifkan secara bawaan.
- **Solusi:**
  Ini bukan galat koneksi basis data; aplikasi Laravel berjalan normal. Gunakan perintah verifikasi koneksi standar melalui `php artisan migrate:status` atau `php artisan tinker`.

---

*Dokumen ini merupakan panduan resmi instalasi dan perawatan database LaundryDesk.*
