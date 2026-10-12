# Panduan Backup dan Pemulihan (Backup & Restore Guide)

**Proyek:** LaundryDesk (RAP Laundry)  
**Dokumen Acuan:** `project/PRD.md`, `project/TASK.md`, `project/ENGINEERING_STANDARDS.md`  
**Waktu Backup Baseline:** 12 Oktober 2026, 14:49 WIB  
**Status Gate 0:** **TERPENUHI (PASS)**  

---

## 1. Ikhtisar Snapshot Backup

Sesuai instruksi pada **Fase 0 (Task 0.2)**, salinan cadangan (*baseline snapshot*) dibuat sebelum inisialisasi framework Laravel 12 atau modifikasi file apapun dilakukan.

- **Direktori Backup:** `backups/pre-implementation-20261012-1449/`
- **Tipe Snapshot:** Baseline Pre-Implementation (Sebelum Inisialisasi Proyek)
- **Status Akses:** Terverifikasi dapat dibaca (*readable* & *intact*)
- **Total Berkas:** 6 berkas (94.083 bytes)

---

## 2. Struktur Berkas yang Dicadangkan

```text
backups/pre-implementation-20261012-1449/
├── .gitignore
├── README.md
├── docs/
│   └── ENVIRONMENT_AUDIT.md
└── project/
    ├── ENGINEERING_STANDARDS.md
    ├── PRD.md
    └── TASK.md
```

### Rincian Verifikasi Berkas:
1. `project/PRD.md` (28.262 bytes) — Dokumen spesifikasi kebutuhan produk.
2. `project/TASK.md` (23.246 bytes) — Checklist implementasi tahapan tugas.
3. `project/ENGINEERING_STANDARDS.md` (36.163 bytes) — Standar rekayasa perangkat lunak LaundryDesk.
4. `docs/ENVIRONMENT_AUDIT.md` (5.710 bytes) — Hasil audit lingkungan sistem lokal.
5. `README.md` (446 bytes) — Ringkasan informasi repositori.
6. `.gitignore` (256 bytes) — Konfigurasi pengecualian Git.

---

## 3. Kebijakan Keamanan dan Perlindungan Rahasia

1. **Pengecualian Git (`.gitignore`):**
   - Folder `backups/` telah terdaftar pada `.gitignore` (baris ke-21).
   - Seluruh snapshot lokal di dalam `backups/` **tidak akan pernah ter-commit atau terdorong ke repositori remote**.
2. **Ketiadaan Kredensial Rahasia:**
   - Tidak ada berkas kredensial sensitif (`.env`, kunci enkripsi, token API, atau password database) yang tersimpan dalam repositori atau paket backup ini.
   - Konfigurasi environment baru yang akan dibuat pada Fase 1 wajib menggunakan template `.env.example` tanpa kredensial rahasia nyata yang di-*commit*.

---

## 4. Prosedur Pemulihan (Restore Procedure)

Jika terjadi kesalahan konfigurasi, kegagalan saat scaffolding Laravel 12, atau kebutuhan untuk mengembalikan proyek ke status awal (*clean baseline*), ikuti langkah pemulihan berikut:

### Skenario: Pemulihan Penuh ke Baseline Awal

#### Langkah 1: Buka Terminal pada Direktori Root Proyek
Pastikan berada di direktori kerja:
```cmd
cd c:\xampp\htdocs\rap-laundry
```

#### Langkah 2: Salin Kembali Seluruh Berkas dari Snapshot Backup
Jalankan perintah copy berikut melalui Command Prompt (`cmd.exe`):
```cmd
xcopy backups\pre-implementation-20261012-1449\* .\ /E /I /Y
```
*Atau menggunakan PowerShell:*
```powershell
Copy-Item -Path "backups\pre-implementation-20261012-1449\*" -Destination ".\" -Recurse -Force
```

#### Langkah 3: Bersihkan Berkas Tambahan yang Rusak (Jika Ada)
Jika saat inisialisasi terbentuk folder baru yang rusak (misalnya folder `vendor/` atau `app/` yang korup), hapus folder tersebut dan kembalikan status Git:
```cmd
git status
git restore .
git clean -fd
```

#### Langkah 4: Verifikasi Pasca Pemulihan
Pastikan berkas acuan dapat dibuka kembali dan integritas repositori kembali bersih:
```cmd
git status
```
Status yang diharapkan adalah `working tree clean`.

---

## 5. Jadwal dan Kebijakan Backup Lanjutan

Untuk fase implementasi selanjutnya, snapshot backup baru wajib dibuat pada setiap kondisi berikut:
1. **Sebelum Migrasi Database Pertama Kali:** Mencadangkan skema dan data MySQL (`mysqldump`).
2. **Sebelum Refactoring Arsitektural Besar:** Mencadangkan direktori `app/`, `resources/`, dan `routes/`.
3. **Sebelum Pembaruan Dependensi Composer:** Mencadangkan `composer.json` dan `composer.lock`.
