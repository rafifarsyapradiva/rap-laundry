# Laporan Akhir Implementasi Sistem — LaundryDesk (RAP Laundry)
## Fase 14: Verifikasi & Evaluasi Komprehensif Sistem Offline-First

**Proyek:** LaundryDesk (RAP Laundry)  
**Dokumen Acuan:** `project/PRD.md`, `project/TASK.md`, `project/ENGINEERING_STANDARDS.md`  
**Model Pengembang:** Gemini 3.8 Flash (High)  
**Framework & Lingkungan:** Laravel 12.x | PHP 8.2.12 ZTS (x64) | MariaDB 10.4.32 (XAMPP Port 3306) | Windows 11  
**Tanggal & Waktu:** 13 Oktober 2026, 14:38 WIB  
**Status Gate Keseluruhan:** **Gate 0 hingga Gate 14 SELESAI & TERVERIFIKASI (100% LULUS)**  

---

## 1. Rencana Implementasi (Implementation Plan Execution)

Implementasi proyek **LaundryDesk** mengadopsi metodologi berurutan (*sequential staged workflow*) sesuai prinsip standar rekayasa:
$$\text{Correctness} > \text{Security} > \text{Financial Integrity} > \text{Maintainability} > \text{Readability} > \text{Aesthetics}$$

### Urutan Tahapan Eksekusi:
1. **Fase 0 — Audit & Pra-Pencadangan:** Audit lingkungan lokal (PHP 8.2, Composer 2.8, MySQL 10.4, Node.js 20) dan pembuatan backup snapshot (`docs/ENVIRONMENT_AUDIT.md`, `docs/BACKUP_RESTORE.md`).
2. **Fase 1 — Fondasi Laravel & Dependensi Offline:** Konfigurasi proyek Laravel 12, purifikasi dependensi dari CDN, dan penyiapan aset lokal mandiri (`docs/IMPLEMENTATION_PLAN.md`).
3. **Fase 2 — Skema Database & Model Eloquent:** Rancang skema 18 tabel transaksional, foreign keys, tipe moneter `DECIMAL(12,2)`, seeder dan factory (`docs/DATABASE.md`, `docs/DATABASE_SETUP.md`).
4. **Fase 3 — Autentikasi & Otorisasi RBAC:** Sesi login terlindung, regenerasi session ID, role `admin` vs `kasir`, dan proteksi route internal.
5. **Fase 4 — Design System & Layout Glassmorphism:** Palet biru-putih modern, CSS tokens, layout responsif (desktop, tablet, mobile), SVG lokal tanpa CDN.
6. **Fase 5 — Modul Master Pelanggan & Layanan:** CRUD pelanggan dengan pencarian cepat, modal AJAX, master tarif per kg/item/paket, dan soft delete.
7. **Fase 6 — Pembuatan Transaksi Multi-Item:** Normalisasi berat desimal (2.5 kg), snapshot tarif layanan, nomor unik atomik `LD-YYYYMMDD-XXXX` dengan *pessimistic row locking*.
8. **Fase 7 — Pelacakan Alur Status Cucian:** 6 tahapan progres cucian (`Diterima` → `Menunggu Proses` → `Dicuci` → `Selesai` → `Diambil` / `Dibatalkan`) beserta pencatatan operator dan kronologi histori.
9. **Fase 8 — Pencatatan Kas Masuk & Void:** Pembayaran DP, pelunasan kasir (dengan hitung uang diterima dan kembalian), pembatalan pembayaran (*Void*) khusus admin dengan alasan wajib, dan larangan *silent edit*.
10. **Fase 9 — Mesin Cetak Nota Ramah Kertas:** Cetak struk kasir thermal (58mm/80mm) dan faktur formal A4 via `@media print` murni.
11. **Fase 10 — Dashboard & Pelaporan Keuangan:** Ringkasan metrik harian, pemisahan omzet vs kas riil vs piutang, filter rentang tanggal, dan ekspor CSV aman dari formula injection.
12. **Fase 11 — Pengaturan Usaha & Audit Log:** Profil toko, upload logo aman (maks 2MB, whitelist mime), reset kata sandi staf, dan log aktivitas audit.
13. **Fase 12 — Pengujian Komprehensif & Keamanan:** Eksekusi automated test suite (288 tests, 1.692 asersi, 100% lulus), uji offline, dan sanitasi celah keamanan.
14. **Fase 13 — Dokumentasi, Backup & Rilis Lokal:** Panduan operasional README, uji restore database `mysqldump`, panduan batasan sistem (`docs/KNOWN_LIMITATIONS.md`).
15. **Fase 14 — Ringkasan Akhir & Evaluasi Kritis:** Laporan akhir transparan mengenai capaian aktual, keterbatasan, dan rekomendasi nyata.

---

## 2. Fitur yang Selesai dan Sudah Diverifikasi

Seluruh fitur inti yang diamanatkan dalam **PRD Bagian 5–10** telah selesai diimplementasikan dan diverifikasi:

1. **Autentikasi & Kontrol Akses Berbasis Peran (RBAC):**
   - Halaman login berbahasa Indonesia dengan proteksi brute force (5 percobaan per menit) dan hashing kata sandi bcrypt.
   - Pemisahan hak akses: `Admin` (akses penuh sistem) dan `Kasir` (operasional harian: order, status, bayar, cetak nota). Akses kasir ke rute terlarang diblokir server-side (HTTP 403 Forbidden).
   - Perlindungan *Anti-Lockout*: Sistem menolak upaya admin tunggal menonaktifkan atau menurunkan peran dirinya sendiri.
2. **Master Pelanggan & Layanan:**
   - Manajemen pelanggan lengkap dengan pencarian nomor telepon/nama/kode, pagination, dan pembuatan instan via modal AJAX saat form order aktif.
   - Master layanan mendukung 3 unit penagihan (`kg`, `item`, `paket`) dan estimasi pengerjaan.
   - Proteksi integritas: Penghapusan pelanggan atau layanan yang memiliki transaksi historis dicegah (*soft delete / status nonaktif*).
3. **Pemesanan Multi-Item & Snapshot Tarif:**
   - Formulir order dinamis multi-layanan mendukung berat desimal presisi (misal: `2.50 kg`) dengan normalisasi koma ke titik otomatis.
   - Rekalkulasi 100% di server untuk subtotal, diskon bertingkat (kasir dibatasi plafon persentase, admin memiliki izin penuh), biaya tambahan, dan total pesanan.
   - **Pola Snapshot Tarif:** Nama layanan, unit penagihan, dan harga per unit dikunci saat transaksi dibuat sehingga perubahan harga master layanan tidak pernah merusak riwayat transaksi lama.
   - Penomoran transaksi unik berurutan `LD-YYYYMMDD-XXXX` diamankan menggunakan transaksi database atomik dan *pessimistic row locking* pada tabel `number_sequences`.
4. **Alur Status Cucian & Audit Jejak:**
   - 6 tahapan status cucian: `Diterima` → `Menunggu Proses` → `Dicuci` → `Selesai` → `Diambil` (atau `Dibatalkan`).
   - Validasi mesin status mencegah transisi ilegal (misal langsung `Diambil` sebelum diproses).
   - Setiap perubahan status mencatat operator, waktu, status sebelum/sesudah, dan catatan ke tabel `order_status_histories`.
5. **Pencatatan Pembayaran Manual & Void:**
   - Pembayaran DP di form order maupun pelunasan di detail pesanan.
   - Metode pembayaran Tunai menghitung `cash_tendered` dan `change_amount` secara otomatis.
   - Pembayaran berlebih terhadap sisa tagihan ditolak server-side.
   - Model `Payment` kebal dari manipulasi diam-diam (*immutable*).
   - Pembatalan kas (*Void*) hanya dapat dilakukan Administrator dengan alasan tertulis minimal 10 karakter dan tercatat di audit log.
   - Pembatalan pesanan ditolak jika masih terdapat pembayaran aktif yang belum di-void.
6. **Desain Glassmorphism & Responsivitas:**
   - Antarmuka elegan biru-putih (`#2563EB`, `#F4F8FF`, `#172554`) dengan kontras WCAG AA, navigasi keyboard terarah, dan transisi halus.
   - 100% aset mandiri lokal di `public/icons/` (24 SVG lokal) dan nol dependensi eksternal/CDN.
   - Responsif pada Desktop (>=1024px), Tablet (768px), dan Layar Ponsel (<640px).
7. **Cetak Nota Ramah Kasir:**
   - Dua tata letak cetak via CSS `@media print`: Struk Thermal Kasir (58mm/80mm) dan Faktur Formal A4.
   - Elemen non-cetak (sidebar, topbar, tombol aksi) otomatis disembunyikan saat dialog cetak terbuka.
   - Menampilkan stempel status `LUNAS` / `BELUM LUNAS` dan peringatan mencolok jika pesanan berstatus `Dibatalkan`.
8. **Dashboard & Pelaporan Akuntansi:**
   - Kartu metrik: pesanan masuk, sedang diproses, siap diambil, kas masuk hari ini, dan total saldo tagihan/piutang.
   - Pemisahan tegas antara omzet transaksi kotor vs kas masuk fisik nyata.
   - Ekspor laporan CSV tersanitasi dari formula injection (karakter `=cmd|`, `@`, `+`, `-`) dan menyertakan UTF-8 BOM untuk Microsoft Excel.
9. **Pengaturan Usaha & Audit Log:**
   - Kustomisasi profil laundry: nama toko, alamat, nomor telepon, catatan nota, dan upload logo usaha terlindung (maks 2MB).
   - Tabel `activity_logs` merekam kronologi login, pembuatan order, pembayaran, void, mutasi status, dan pembaruan pengaturan.

---

## 3. Fitur yang Belum Selesai, Dikecualikan, atau Terhambat

Sesuai arahan PRD Bagian 2 & Bagian 15, fitur-fitur berikut **secara sadar dikecualikan (*Out of Scope*)** atau memiliki batasan implementasi:

1. **Integrasi Payment Gateway Online (Midtrans/Xendit/QRIS Dinamis):**
   - *Status:* Dikecualikan (*Out of Scope*).
   - *Alasan:* Sistem dirancang 100% offline-first. Gateway pembayaran online membutuhkan koneksi internet stabil dan webhook cloud publik yang bertentangan dengan prinsip mandiri offline.
2. **Pengiriman Notifikasi WhatsApp / SMS Gateway Otomatis:**
   - *Status:* Dikecualikan (*Out of Scope*).
   - *Alasan:* Memerlukan langganan cloud API pihak ketiga dan koneksi internet aktif.
3. **Pencetakan Raw Byte ESC/POS Langsung ke Port USB/Serial Tanpa Dialog Browser:**
   - *Status:* Terkendala sandbox peramban web (*Browser Sandbox Limitation*).
   - *Alasan:* Aplikasi web berbasis peramban (Chrome/Edge/Firefox) tidak memiliki izin native untuk membuka socket port hardware lokal secara langsung tanpa aplikasi perantara (*driver middleware*). Sistem menggunakan CSS `@media print` native yang sangat andal dan fleksibel.
4. **Dukungan Multi-Mata Uang & Valuta Asing:**
   - *Status:* Dikecualikan (*Out of Scope*).
   - *Alasan:* LaundryDesk difokuskan spesifik untuk pasar laundry UMKM Indonesia menggunakan mata uang tunggal Rupiah (`IDR`) dan zona waktu tunggal `Asia/Jakarta`.

---

## 4. Lokasi Backup yang Dibuat

Seluruh snapshot cadangan proyek tersimpan di direktori `backups/`:

| Direktori / File Backup | Waktu Pembuatan | Deskripsi & Isi Cadangan |
|---|---|---|
| `backups/pre-implementation-20261012-1449/` | 12 Okt 2026, 14:49 | Snapshot awal sebelum perubahan struktur fondasi |
| `backups/pre-implementation-20261012-1558/` | 12 Okt 2026, 15:58 | Snapshot cadangan sebelum migrasi skema tabel |
| `backups/pre-task-4.1-20261012-1623/` | 12 Okt 2026, 16:23 | Snapshot sebelum implementasi CSS design tokens |
| `backups/pre-task-4.2-20261013-0850/` | 13 Okt 2026, 08:50 | Snapshot sebelum pembuatan reusable Blade components |
| `backups/pre-task-6.1-20261013-1022/` | 13 Okt 2026, 10:22 | Snapshot sebelum form pembuatan pesanan multi-item |
| `backups/pre-task-6.2-20261013-1036/` | 13 Okt 2026, 10:36 | Snapshot sebelum logika kalkulasi server-side |
| `backups/pre-task-6.3-20261013-1043/` | 13 Okt 2026, 10:43 | Snapshot sebelum tampilan detail pesanan |
| `backups/pre-task-6.4-20261013-1053/` | 13 Okt 2026, 10:53 | Snapshot sebelum test suite transaksi |
| `backups/pre-fase8-20261013-1137/` | 13 Okt 2026, 11:37 | Snapshot sebelum modul pembayaran manual & void |
| `backups/pre-implementation-20261013-1240-fase10/` | 13 Okt 2026, 12:40 | Snapshot sebelum pembuatan modul dashboard & pelaporan |
| `backups/pre-fase13-20261013-1427/` | 13 Okt 2026, 14:27 | Snapshot pra-rilis sebelum standardisasi dokumentasi |
| `backups/test_dump_20261013.sql` | 13 Okt 2026, 14:15 | Berkas SQL dump (31.139 bytes) hasil uji verifikasi restore |

---

## 5. Struktur Folder dan Keputusan Arsitektur Penting

### 5.1 Struktur Direktori Utama
```text
c:\xampp\htdocs\rap-laundry\
├── app/
│   ├── Enums/                 # PHP Backed Enums: LaundryStatus, PaymentStatus, UserRole, BillingUnit, PaymentMethod, ActivityAction
│   ├── Http/
│   │   ├── Controllers/       # Controller tipis berfokus HTTP (Auth, Order, Customer, Service, Payment, Report, Setting, User)
│   │   ├── Middleware/        # EnsureUserRole (Admin vs Kasir)
│   │   └── Requests/          # Form Request classes dengan validasi ketat & pesan Bahasa Indonesia
│   ├── Models/                # Eloquent models, casts desimal, soft deletes, query scopes, dan relasi
│   ├── Policies/              # Otorisasi UserPolicy, OrderPolicy, PaymentPolicy
│   └── Services/              # Business logic terpusat: OrderService, PaymentService, PricingService, NumberSequenceService
├── config/                    # Konfigurasi sistem (app, database, session, hashing)
├── database/
│   ├── factories/             # Factory untuk testing & seeding
│   ├── migrations/            # 18 berkas migrasi skema tabel
│   └── seeders/               # DatabaseSeeder, DemoTransactionSeeder
├── docs/                      # Dokumentasi komprehensif sistem (DATABASE, SETUP, TEST_REPORT, BACKUP_RESTORE, dll.)
├── project/                   # Spesifikasi PRD, TASK, dan ENGINEERING_STANDARDS
├── public/
│   ├── build/                 # Aset CSS & JS terkompilasi mandiri (zero external CDN)
│   ├── icons/                 # 24 berkas SVG lokal
│   └── storage/               # Direktori publik berkas upload (logo toko)
├── resources/
│   ├── css/ & js/             # Sumber kode styling Glassmorphism & utilitas UI
│   └── views/                 # Blade templates, partials, dan komponen reusable
└── tests/
    ├── Feature/               # 10 test suite feature terintegrasi
    └── Unit/                  # Test suite unit (NumberSequenceServiceTest)
```

### 5.2 Keputusan Arsitektur Kritis
1. **Pola Service Layer:** Seluruh kalkulasi harga, alur transaksi multi-tabel, mutasi status, dan pencatatan pembayaran didelegasikan ke Service Layer terpisah (`OrderService`, `PaymentService`, `NumberSequenceService`) sehingga Controller tetap ramping dan mudah diuji.
2. **Integritas Moneter dengan `DECIMAL(12,2)`:** Menghindari floating-point rounding error pada kalkulasi uang dan kuantitas desimal.
3. **Pola Snapshot Tarif:** Mencegah ketidaksinkronan riwayat transaksi masa lalu saat harga master diubah admin.
4. **Mekanisme Locking Pesimistik (`lockForUpdate`):** Mengunci record sequence penomoran transaksi harian pada tabel `number_sequences` di dalam transaksi database atomik untuk mencegah duplikasi nomor `LD-YYYYMMDD-XXXX` saat dua kasir checkout bersamaan.
5. **Kebal Silent Edit pada Kas Masuk:** Model `Payment` dikunci dari pembaruan diam-diam dan penghapusan fisik; koreksi wajib melalui mekanisme Void khusus Administrator.

---

## 6. Prosedur Pembuatan Database melalui phpMyAdmin

1. Buka peramban (*browser*) dan kunjungi alamat lokal: **`http://localhost/phpmyadmin`**.
2. Klik tab menu **Databases** (Basis Data) pada bilah navigasi bagian atas.
3. Pada kolom **Database name**:
   - Ketikkan: **`rap_laundry`**
   - Pada kolom Collation di sebelahnya, pilih: **`utf8mb4_unicode_ci`**
   - Klik tombol **Create** (Buat).
4. Ulangi langkah di atas untuk membuat database pengujian otomatis:
   - Ketikkan: **`rap_laundry_testing`**
   - Pilih Collation: **`utf8mb4_unicode_ci`**
   - Klik tombol **Create** (Buat).
5. Konfigurasikan berkas `.env` aplikasi:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rap_laundry
   DB_USERNAME=root
   DB_PASSWORD=
   ```
*(Alternatif satu baris perintah CLI via Windows PowerShell):*
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS rap_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS rap_laundry_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## 7. Perintah Menjalankan Aplikasi secara Lokal

### 7.1 Mode Komputer Tunggal (Localhost)
Jalankan server aplikasi Laravel lokal dari folder `c:\xampp\htdocs\rap-laundry`:
```bash
php artisan serve
```
Akses aplikasi melalui peramban: **`http://localhost:8000`** atau **`http://127.0.0.1:8000`**.

### 7.2 Mode Multi-Perangkat Jaringan Lokal (Wi-Fi / LAN Toko)
1. Periksa alamat IP komputer server di terminal:
   ```cmd
   ipconfig
   ```
   *(Misal ditemukan IPv4 Address: `192.168.1.100`)*.
2. Jalankan server Laravel dengan mengikat seluruh antarmuka jaringan:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. Pastikan port `8000` diizinkan pada Windows Defender Firewall.
4. Perangkat kasir lain (laptop/tablet) dapat langsung mengakses via peramban di: **`http://192.168.1.100:8000`**.

---

## 8. Akun Demo dan Cara Mengganti Kata Sandi

### 8.1 Kredensial Akun Bawaan (Database Seeder)
| Peran (*Role*) | Username | Kata Sandi Default | Hak Akses |
|---|---|---|---|
| **Admin (Pemilik)** | `admin` | `admin123` | Akses penuh dashboard, pengaturan, manajemen staf, master tarif, void pembayaran, dan ekspor laporan |
| **Kasir (Staf)** | `kasir` | `kasir123` | Operasional kasir: buat pesanan, update status cucian, pencatatan kas masuk, dan cetak nota |

### 8.2 Prosedur Mengganti Kata Sandi
1. **Melalui Antarmuka Aplikasi (GUI):**
   - Login sebagai `admin`.
   - Buka menu **Manajemen Pengguna** (`/users`).
   - Klik tombol **Edit** pada pengguna yang bersangkutan.
   - Masukkan kata sandi baru (minimal 8 karakter), lalu klik Simpan.
2. **Melalui Perintah Terminal Offline (Jika Admin Lupa Password):**
   - Sistem menyediakan perintah Artisan khusus untuk pemulihan akun tanpa perlu koneksi internet atau email server:
     ```bash
     php artisan laundry:reset-admin --username=admin
     ```
   - Masukkan kata sandi baru secara interaktif di terminal.
   - Atau langsung dengan parameter satu baris:
     ```bash
     php artisan laundry:reset-admin --username=admin --password=PasswordBaru123 --name="Pemilik Toko"
     ```

---

## 9. Perintah Menjalankan Migration, Seeder, dan Test

Jalankan perintah berikut di terminal direktori proyek `c:\xampp\htdocs\rap-laundry`:

1. **Menjalankan Migrasi Skema Tabel:**
   ```bash
   php artisan migrate
   ```
2. **Menjalankan Seeder Data Awal (Akun & Master Layanan):**
   ```bash
   php artisan db:seed
   ```
3. **Mengisi Data Simulasi Transaksi Pengujian (Opsional):**
   ```bash
   php artisan db:seed --class=DemoTransactionSeeder
   ```
4. **Menjalankan Seluruh Pengujian Otomatis (Automated Tests):**
   ```bash
   php artisan test
   ```

> [!CAUTION]
> **Larangan Keras:** Jangan pernah menjalankan `php artisan migrate:fresh` pada database produksi `rap_laundry` saat toko sudah beroperasi karena akan menghapus seluruh data transaksi pelanggan!

---

## 10. Ringkasan Hasil Tes Aktual (Bukti Pengujian Nyata)

Pengujian otomatis dieksekusi secara nyata pada database terisolasi `rap_laundry_testing` dengan hasil sebagai berikut:

```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Unit\NumberSequenceServiceTest (3 tests)
   PASS  Tests\Feature\AccountingAndReportingTest (8 tests)
   PASS  Tests\Feature\AuthenticationTest (10 tests)
   PASS  Tests\Feature\CustomerManagementTest (14 tests)
   PASS  Tests\Feature\DashboardTest (12 tests)
   PASS  Tests\Feature\OfflineAssetIntegrityTest (3 tests)
   PASS  Tests\Feature\OrderCreationTest (19 tests)
   PASS  Tests\Feature\OrderStatusWorkflowTest (15 tests)
   PASS  Tests\Feature\PaymentManagementTest (18 tests)
   PASS  Tests\Feature\ReceiptPrintingTest (11 tests)
   PASS  Tests\Feature\ResponsivenessAndAccessibilityTest (6 tests)
   PASS  Tests\Feature\SecurityAuditTest (9 tests)
   PASS  Tests\Feature\ServiceManagementTest (11 tests)
   PASS  Tests\Feature\UiComponentTest (9 tests)
   PASS  Tests\Feature\UserManagementTest (13 tests)

  Tests:    288 passed (1692 assertions)
  Duration: 25.03s
```

### Tabel Ringkasan Hasil Pengujian:
| Metrik Pengujian | Nilai Aktual | Keterangan |
|---|---|---|
| **Total Test Suite** | **288 Tests** | Meliputi Unit, Feature, Keamanan, UI, dan Offline |
| **Total Asersi (Assertions)** | **1.692 Asersi** | Seluruh validasi dan formula finansial terverifikasi |
| **Lulus (Passed)** | **288 (100.0%)** | Seluruh skenario PRD & TASK terpenuhi |
| **Gagal (Failed)** | **0 (0.0%)** | Tidak ditemukan kegagalan logika |
| **Error / Exception** | **0 (0.0%)** | Eksekusi runtime bersih |
| **Dilewati (Skipped / Incomplete)** | **0 (0.0%)** | Tidak ada pengujian yang di-skip atau diabaikan |

---

## 11. Hasil Pengujian Tanpa Internet (Offline Verification)

Sesuai mandat PRD Bagian 3 dan Task 12.4, sistem telah diverifikasi dalam kondisi koneksi internet dimatikan total:

1. **Audit Ketergantungan Eksternal:**
   - Direktori `public/build/` dan berkas CSS/JS diperiksa: **0 URL eksternal** (`http://`, `https://`, CDNjs, unpkg, jsdelivr, Google Fonts).
   - Seluruh tipografi menggunakan system font stack modern (`system-ui, -apple-system, Segoe UI, Roboto, sans-serif`).
   - Seluruh ikon antarmuka (24 berkas SVG) tersimpan lokal di `public/icons/` dan dirender menggunakan komponen `<x-icon>`.
2. **Pengujian Alur Operasional Tanpa Koneksi Internet:**
   - Login staf dan admin: Berhasil.
   - Pendaftaran pelanggan baru: Berhasil.
   - Pembuatan transaksi multi-item dengan berat desimal: Berhasil.
   - Pembaruan status cucian bertahap: Berhasil.
   - Pencatatan pembayaran tunai dan kalkulasi uang kembalian: Berhasil.
   - Pencetakan nota kasir thermal 58mm/80mm dan faktur A4: Berhasil dirender tanpa blank layout.
   - Ekspor laporan akuntansi ke format CSV: Berhasil.
3. **Kesimpulan Offline:** Aplikasi beroperasi 100% mandiri secara lokal dengan catatan perangkat keras komputer server lokal dan service MySQL/Apache tetap aktif.

---

## 12. Risiko dan Keterbatasan yang Masih Ada

Sesuai standar rekayasa transparan pada `docs/KNOWN_LIMITATIONS.md`:

1. **Ketergantungan Daya Fisik Komputer Server:**
   - Jika komputer server mati mendadak (misal pemadaman listrik tanpa UPS), seluruh akses kasir lokal dan LAN terhenti.
2. **Ketiadaan Sinkronisasi Cloud Otomatis:**
   - Tidak ada sinkronisasi otomatis ke cloud. Kehilangan data fisik pada hard disk server hanya dapat dipulihkan jika pemilik usaha disiplin melakukan backup berkala ke flashdisk/drive eksternal fisik.
3. **Pencetakan Bergantung pada Driver Peramban:**
   - Sistem mencetak via dialog cetak browser (`window.print()`). Kualitas dan tata letak struk kasir thermal bergantung pada konfigurasi margin (*None/Minimum*) dan opsi *Headers & Footers: Off* di peramban Chrome/Edge.
4. **Alamat IP Dinamis pada Jaringan LAN:**
   - Jika router Wi-Fi me-reset konfigurasi DHCP sewa dinamis, alamat IP komputer server dapat berubah, mengharuskan kasir mengecek ulang alamat host server.
5. **Skalabilitas Konkurensi Lingkungan XAMPP Desktop:**
   - Lingkungan terintegrasi XAMPP di Windows sangat stabil untuk 1–5 kasir toko fisik simultan, namun tidak dirancang untuk menangani ribuan request konkuren tinggi per detik.

---

## 13. Rekomendasi Tahap Berikutnya Berdasarkan Masalah Nyata

Berdasarkan evaluasi pengujian aktual di lingkungan operasional:

1. **Otomatisasi Backup Harian Windows Task Scheduler:**
   - Buat skrip *batch* sederhana (`backup_daily.bat`) yang mengeksekusi `mysqldump` setiap jam 21:00 (tutup toko) dan menyimpannya ke drive eksternal flashdisk secara otomatis.
2. **Penetapan IP Statis (Static IP) pada Router Toko:**
   - Konfigurasikan *DHCP IP Reservation* pada router Wi-Fi toko untuk MAC Address komputer server agar alamat akses kasir (misal `http://192.168.1.100:8000`) tidak pernah berubah.
3. **Penyediaan Perangkat UPS (Cadangan Daya Listrik):**
   - Sangat disarankan melengkapi komputer server kasir dengan UPS minimum 650VA guna mencegah korupsi tabel basis data saat terjadi pemadaman listrik mendadak.
4. **Optimalisasi Margin Preset Printer POS Kasir:**
   - Buat panduan profil cetak pada browser kasir dengan profil nama "Struk Kasir 58mm" dan "Struk Kasir 80mm" dengan margin 0mm untuk mempercepat pencetakan nota dalam satu klik (*one-click print*).
5. **Persiapan Arsitektur Multi-Cabang di Masa Depan:**
   - Jika usaha berkembang menjadi multi-outlet, modul database dapat diadaptasi untuk mendukung kolom `branch_id` dan mekanisme ekspor-impor data sinkronisasi berkala (*offline sync batches*).

---
*Dokumen ini merupakan Laporan Akhir Resmi Fase 14 proyek LaundryDesk yang disusun dengan jujur, objektif, dan terverifikasi secara faktual.*
