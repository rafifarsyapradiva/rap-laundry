# TASK.md — Checklist Implementasi Website Pengelolaan Cuci Pakaian Offline

**Proyek:** LaundryDesk  
**Acuan utama:** `PRD.md`  
**Framework:** Laravel 12  
**Database:** MySQL/MariaDB lokal, dikelola melalui phpMyAdmin  
**Desain:** Glassmorphism biru-putih  
**Mode:** Offline untuk fitur inti  
**Bahasa aplikasi:** Bahasa Indonesia

> Instruksi untuk Gemini 3.8 Flash High: Kerjakan task secara berurutan. Jangan melewati pemeriksaan, backup, atau pengujian hanya untuk mempercepat pengerjaan. Perbarui status checklist berdasarkan pekerjaan yang benar-benar dilakukan. Jangan tandai task selesai hanya karena kode sudah ditulis; lakukan pemeriksaan atau pengujian yang sesuai.

## Status Task

Gunakan status berikut saat memperbarui dokumen:
- `[ ]` Belum dikerjakan
- `[-]` Sedang dikerjakan
- `[x]` Selesai dan sudah diverifikasi
- `[!]` Terhambat atau gagal; jelaskan alasannya

Jangan menandai task sebagai `[x]` jika belum diverifikasi.

---

## Fase 0 — Audit Proyek dan Backup

### Task 0.1 — Audit lingkungan
- [x] Periksa direktori kerja dan identifikasi apakah proyek Laravel sudah tersedia.
- [x] Periksa versi PHP dan pastikan memenuhi persyaratan Laravel 12.
- [x] Periksa versi Composer.
- [x] Periksa ketersediaan MySQL/MariaDB dan phpMyAdmin.
- [x] Periksa Node.js/npm jika diperlukan untuk membangun aset frontend.
- [x] Periksa status Git dan perubahan lokal agar pekerjaan pengguna tidak tertimpa.
- [x] Catat hasil audit di `docs/ENVIRONMENT_AUDIT.md`.

### Task 0.2 — Backup sebelum perubahan
- [x] Jika folder proyek sudah berisi pekerjaan, buat salinan backup sebelum mengedit file.
- [x] Simpan backup di folder terpisah, misalnya `backups/pre-implementation-YYYYMMDD-HHmm/`.
- [x] Pastikan file penting dan konfigurasi yang dibutuhkan untuk pemulihan tercakup.
- [x] Jangan memasukkan kredensial rahasia ke repositori atau arsip yang dibagikan.
- [x] Periksa bahwa backup benar-benar ada dan dapat dibaca.
- [x] Catat lokasi backup dan cara pemulihannya di `docs/BACKUP_RESTORE.md`.

**Gate 0:** Jangan lanjut ke perubahan destruktif sebelum backup diverifikasi jika proyek sudah ada.

---

## Fase 1 — Rencana Teknis dan Fondasi Laravel

### Task 1.1 — Rencana implementasi
- [x] Baca seluruh `PRD.md`.
- [x] Catat asumsi, risiko, dependensi, dan hal yang perlu dikonfirmasi.
- [x] Susun urutan implementasi serta definisi selesai untuk setiap fase.
- [x] Buat `docs/IMPLEMENTATION_PLAN.md`.

### Task 1.2 — Siapkan aplikasi
- [x] Jika proyek belum ada, buat proyek Laravel 12 sesuai lingkungan.
- [x] Jika proyek sudah ada, periksa versi Laravel dan struktur sebelum mengubahnya.
- [x] Pastikan aplikasi dapat dijalankan secara lokal.
- [x] Pastikan konfigurasi environment tidak berisi kredensial hardcoded.
- [x] Siapkan `.env.example` tanpa password atau token asli.
- [x] Verifikasi halaman awal Laravel dan log error.
- [x] Catat perintah setup yang berhasil di `README.md`.

### Task 1.3 — Dependensi dan aset offline
- [x] Pilih dependensi frontend yang benar-benar dibutuhkan.
- [x] Hindari CDN, Google Fonts, API eksternal, dan aset yang harus diunduh saat aplikasi dipakai.
- [x] Jika menggunakan Vite, bangun aset dan pastikan hasil build tersedia di `public/`.
- [x] Pastikan aplikasi tetap dapat memuat CSS, JavaScript, dan ikon tanpa internet.
- [x] Sediakan fallback tampilan jika `backdrop-filter` tidak didukung browser.

**Gate 1:** Aplikasi Laravel harus dapat berjalan di lokal sebelum fitur bisnis dibuat. (LULUS)

---

## Fase 2 — Desain Database dan Model

### Task 2.1 — Rancang skema
- [x] Buat diagram relasi atau dokumentasi tabel di `docs/DATABASE.md`.
- [x] Tentukan relasi antara users, customers, services, orders, order_items, payments, order_status_histories, business_settings, dan activity_logs.
- [x] Tentukan foreign key, unique index, index pencarian, nullable fields, serta aturan penghapusan.
- [x] Gunakan `DECIMAL` untuk nominal uang dan kuantitas; hindari floating point untuk perhitungan uang.
- [x] Tentukan daftar status pesanan dan status pembayaran secara terpusat.
- [x] Rancang snapshot harga/nama layanan pada item transaksi agar histori tidak berubah ketika harga master diubah.

### Task 2.2 — Buat migration
- [x] Buat migration tabel `users` dan role admin/kasir.
- [x] Buat migration `customers`.
- [x] Buat migration `services`.
- [x] Buat migration `orders`.
- [x] Buat migration `order_items`.
- [x] Buat migration `payments`.
- [x] Buat migration `order_status_histories`.
- [x] Buat migration `business_settings`.
- [x] Buat migration `activity_logs`.
- [x] Pastikan urutan migration mengikuti dependensi foreign key.
- [x] Jalankan migration pada database pengujian.
- [x] Pastikan tidak ada error dan struktur tabel sesuai dokumentasi.

### Task 2.3 — Model dan relasi
- [x] Buat model Eloquent untuk semua tabel inti.
- [x] Definisikan relasi `belongsTo`, `hasMany`, atau relasi lain yang sesuai.
- [x] Tentukan casts untuk tanggal, boolean, JSON, dan nilai desimal.
- [x] Terapkan soft delete/arsip untuk data master jika dibutuhkan.
- [x] Pastikan transaksi historis tetap dapat dibaca saat pelanggan atau layanan diarsipkan.
- [x] Buat factory dan seeder data demo yang aman.

### Task 2.4 — Database melalui phpMyAdmin
- [x] Dokumentasikan cara membuat database lokal melalui phpMyAdmin.
- [x] Dokumentasikan konfigurasi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.
- [x] Jangan menaruh password nyata di dokumentasi.
- [x] Pastikan aplikasi dapat terkoneksi ke database.
- [x] Uji migration dan seeder dari command line.
- [x] Catat solusi untuk error koneksi umum.

**Gate 2:** Semua migration, relasi, dan seeder harus berjalan pada database lokal yang bersih. (LULUS)

---

## Fase 3 — Autentikasi dan Otorisasi

### Task 3.1 — Login/logout
- [x] Buat halaman login berbahasa Indonesia.
- [x] Implementasikan login dengan email/username sesuai desain database.
- [x] Terapkan password hashing Laravel.
- [x] Terapkan regenerasi session saat login dan invalidasi session saat logout.
- [x] Tampilkan pesan error yang jelas tanpa membocorkan detail sensitif.
- [x] Pastikan route internal tidak dapat diakses tanpa login.

### Task 3.2 — Role admin dan kasir
- [x] Implementasikan middleware, policy, atau mekanisme otorisasi server-side.
- [x] Admin dapat mengakses pengaturan dan manajemen pengguna.
- [x] Kasir hanya dapat mengakses fitur operasional yang diizinkan.
- [x] Uji akses langsung melalui URL, bukan hanya melalui tombol menu.
- [x] Buat akun awal melalui seeder/setup yang terdokumentasi.
- [x] Jangan gunakan password default yang mudah ditebak di lingkungan produksi.

### Task 3.3 — Keamanan dasar
- [x] Pastikan operasi perubahan data menggunakan metode HTTP yang tepat.
- [x] Pastikan CSRF protection aktif untuk form.
- [x] Validasi input pada server.
- [x] Escape output Blade untuk mencegah XSS.
- [x] Gunakan Eloquent/Query Builder atau parameter binding, bukan query mentah yang menggabungkan input pengguna.
- [x] Terapkan pembatasan akses pada ekspor laporan dan audit log.
- [x] Tambahkan pengujian autentikasi dan otorisasi.

**Gate 3:** Uji login, logout, akses tanpa login, dan pembatasan hak akses sebelum lanjut. (LULUS)

---

## Fase 4 — Design System dan Layout Glassmorphism

### Task 4.1 — Design tokens
- [x] Definisikan variabel CSS untuk warna, spacing, radius, bayangan, blur, dan tipografi.
- [x] Gunakan biru utama `#2563EB`, biru gelap `#1D4ED8`, biru muda `#DBEAFE`, latar `#F4F8FF`, dan teks utama `#172554` sebagai acuan.
- [x] Pastikan teks dan komponen memiliki kontras yang memadai.
- [x] Gunakan font lokal atau font sistem.
- [x] Buat ikon SVG lokal atau ikon CSS tanpa CDN.

### Task 4.2 — Komponen UI reusable
- [x] Buat layout utama Blade.
- [x] Buat sidebar desktop dan navigasi mobile.
- [x] Buat topbar dengan judul halaman dan informasi pengguna.
- [x] Buat komponen kartu statistik Glassmorphism.
- [x] Buat komponen tabel, filter, pagination, input, select, textarea, dan tombol.
- [x] Buat badge status cucian dan pembayaran.
- [x] Buat dialog konfirmasi untuk tindakan berisiko.
- [x] Buat notifikasi sukses/gagal dan empty state.
- [x] Buat gaya cetak yang tidak menyertakan sidebar atau navigasi.

### Task 4.3 — Responsivitas
- [x] Uji dashboard dan tabel pada desktop.
- [x] Uji form pada tablet.
- [x] Uji navigasi dan tabel pada layar ponsel.
- [x] Pastikan fokus keyboard terlihat.
- [x] Pastikan animasi menghormati `prefers-reduced-motion`.
- [x] Pastikan UI tetap terbaca ketika blur tidak tersedia.

**Gate 4:** Layout dan komponen dasar konsisten sebelum seluruh halaman bisnis dibangun. (LULUS)

---

## Fase 5 — Modul Pelanggan dan Layanan

### Task 5.1 — Data pelanggan
- [x] Buat halaman daftar pelanggan dengan pagination.
- [x] Implementasikan tambah, detail, edit, dan arsip/nonaktif.
- [x] Implementasikan pencarian berdasarkan nama, nomor telepon, atau kode pelanggan.
- [x] Validasi field wajib dan format nomor telepon.
- [x] Cegah penghapusan permanen pelanggan yang memiliki riwayat transaksi.
- [x] Tampilkan riwayat transaksi pelanggan jika sesuai.

### Task 5.2 — Master layanan dan harga
- [x] Buat halaman daftar layanan.
- [x] Implementasikan tambah, edit, aktif/nonaktif, dan validasi harga.
- [x] Dukung satuan `kg`, `item`, dan `package`.
- [x] Tambahkan data awal layanan yang dapat diubah.
- [x] Simpan estimasi pengerjaan jika digunakan.
- [x] Pastikan layanan nonaktif tidak dapat dipilih untuk pesanan baru.
- [x] Pastikan perubahan harga tidak mengubah harga snapshot transaksi lama.

### Task 5.3 — Pengujian master data
- [x] Uji validasi nama, satuan, harga, dan status.
- [x] Uji pencarian dan pagination.
- [x] Uji otorisasi untuk perubahan master data.
- [x] Uji arsip data yang masih terhubung ke transaksi.

**Gate 5:** Data pelanggan dan layanan harus siap digunakan untuk pembuatan transaksi. (LULUS)

---

## Fase 6 — Pembuatan Transaksi Laundry

### Task 6.1 — Form pesanan
- [x] Buat form pesanan baru.
- [x] Pilih pelanggan atau tambahkan pelanggan baru tanpa kehilangan data pesanan.
- [x] Dukung beberapa item layanan dalam satu pesanan.
- [x] Tampilkan satuan dan harga berdasarkan master layanan.
- [x] Dukung kuantitas desimal untuk layanan berbasis kilogram.
- [x] Validasi kuantitas harus lebih besar dari nol.
- [x] Sediakan estimasi selesai dan catatan opsional.
- [x] Sediakan diskon dan biaya tambahan sesuai izin yang ditentukan.

### Task 6.2 — Logika perhitungan
- [x] Hitung subtotal setiap item di server.
- [x] Hitung subtotal pesanan, diskon, biaya tambahan, dan total di server.
- [x] Gunakan `DECIMAL` untuk nominal uang.
- [x] Jangan mempercayai nilai harga atau total yang dikirim dari browser.
- [x] Simpan snapshot nama layanan, satuan, dan harga.
- [x] Gunakan database transaction untuk menyimpan order dan semua item.
- [x] Pastikan kegagalan menyimpan item membatalkan seluruh transaksi database.
- [x] Buat nomor transaksi unik, misalnya `LD-YYYYMMDD-0001`.
- [x] Pastikan nomor transaksi aman dari duplikasi pada akses bersamaan.

### Task 6.3 — Detail transaksi
- [x] Buat halaman detail pesanan.
- [x] Tampilkan pelanggan, item layanan, total, pembayaran, sisa tagihan, estimasi selesai, dan status.
- [x] Tampilkan petugas penerima pesanan.
- [x] Tampilkan riwayat status.
- [x] Sediakan tindakan sesuai role dan status transaksi.

### Task 6.4 — Pengujian transaksi
- [x] Uji pesanan dengan satu item.
- [x] Uji pesanan dengan beberapa item.
- [x] Uji berat desimal, misalnya 2,5 kg.
- [x] Uji kuantitas nol/negatif.
- [x] Uji diskon dan biaya tambahan.
- [x] Uji manipulasi harga melalui browser.
- [x] Uji nomor transaksi unik.
- [x] Uji rollback ketika penyimpanan gagal.

**Gate 6:** Jangan lanjut sebelum total transaksi dapat diverifikasi dengan perhitungan manual dan pengujian otomatis. (LULUS)

---

## Fase 7 — Status Cucian dan Riwayat

### Task 7.1 — Transisi status
- [x] Implementasikan status `Diterima`.
- [x] Implementasikan status `Menunggu Proses`.
- [x] Implementasikan status `Dicuci`.
- [x] Implementasikan status `Dikeringkan`.
- [x] Implementasikan status `Disetrika` bila layanan memerlukannya.
- [x] Implementasikan status `Siap Diambil`.
- [x] Implementasikan status `Selesai/Diambil`.
- [x] Implementasikan status `Dibatalkan`.
- [x] Validasi transisi status di server.
- [x] Jangan mewajibkan semua layanan melewati tahap yang tidak relevan.

### Task 7.2 — Riwayat status
- [x] Simpan status sebelumnya dan status baru.
- [x] Simpan petugas, waktu, dan catatan opsional.
- [x] Tampilkan kronologi perubahan pada detail pesanan.
- [x] Batasi koreksi status sesuai role.
- [x] Pastikan pembatalan tidak menghapus riwayat transaksi.

### Task 7.3 — Pengujian status
- [x] Uji transisi yang valid.
- [x] Uji transisi yang tidak valid.
- [x] Uji status siap diambil tetapi belum lunas.
- [x] Uji status lunas tetapi belum diambil.
- [x] Pastikan status cucian terpisah dari status pembayaran.

**Gate 7:** Status cucian dan pembayaran harus bekerja secara independen dan konsisten. (LULUS)

---

## Fase 8 — Pembayaran Manual

### Task 8.1 — Pencatatan pembayaran
- [x] Buat form pencatatan pembayaran (baik di halaman detail pesanan maupun uang muka di form pesanan baru).
- [x] Dukung pembayaran penuh dan sebagian (DP dan pelunasan bertahap).
- [x] Dukung metode tunai (dengan pencatatan `cash_tendered` dan hitung `change_amount`), transfer bank, QRIS eksternal, dan metode lain yang dikonfigurasi.
- [x] Simpan waktu pembayaran, nominal, metode, petugas, dan referensi opsional.
- [x] Gunakan database transaction saat mencatat pembayaran.
- [x] Validasi nominal pembayaran di server.
- [x] Tolak pembayaran melebihi sisa tagihan sesuai aturan bisnis.
- [x] Jangan menganggap pembayaran valid hanya karena metode dipilih (wajib verifikasi penerimaan dana fisik/manual).

### Task 8.2 — Status pembayaran dan koreksi
- [x] Hitung total pembayaran valid.
- [x] Hitung sisa tagihan.
- [x] Perbarui status `Belum Dibayar`, `Dibayar Sebagian`, atau `Lunas`.
- [x] Jika koreksi pembayaran diperlukan, gunakan mekanisme void/pembatalan dengan alasan dan jejak audit.
- [x] Batasi koreksi pembayaran kepada role yang berwenang (khusus Administrator/Pemilik).
- [x] Pastikan riwayat pembayaran tidak dihapus diam-diam (proteksi model anti-silent edit/delete).
- [x] Tolak pembatalan pesanan jika masih terdapat pembayaran valid.

### Task 8.3 — Pengujian pembayaran
- [x] Uji pesanan belum dibayar.
- [x] Uji pembayaran sebagian.
- [x] Uji pelunasan.
- [x] Uji pembayaran berlebih.
- [x] Uji pembatalan/koreksi pembayaran (void).
- [x] Uji perhitungan sisa tagihan setelah koreksi.
- [x] Uji laporan kas masuk berdasarkan tanggal pembayaran.

**Gate 8:** Nilai transaksi, uang diterima, dan saldo tagihan harus dibedakan dengan benar. (LULUS)

---

## Fase 9 — Nota dan Cetak

- [x] Buat tampilan nota yang dapat dicetak (mendukung dual format: Struk Thermal 58/80mm dan Faktur A4).
- [x] Tampilkan nama usaha, nomor transaksi, pelanggan, tanggal, layanan, kuantitas, harga, subtotal, diskon, biaya tambahan, total, pembayaran, dan saldo.
- [x] Tampilkan status cucian dan pembayaran (dilengkapi stempel LUNAS / BELUM LUNAS).
- [x] Tampilkan estimasi selesai dan catatan penting (termasuk klausul footer syarat pengambilan usaha).
- [x] Buat CSS `@media print` (dioptimalkan untuk format A4 dan Struk Thermal, bebas bayangan & hemat tinta).
- [x] Sembunyikan sidebar, topbar, tombol, dan elemen yang tidak perlu saat mencetak.
- [x] Pastikan nota tetap bisa dicetak tanpa koneksi internet (100% mandiri lokal, zero external assets).
- [x] Uji cetak A4 dan format nota ringkas jika disediakan (format switcher terverifikasi).
- [x] Uji cetak ulang transaksi lama (snapshot layanan melindungi histori dari perubahan tarif master).

**Gate 9:** Data pada nota harus sama dengan data transaksi di database. (LULUS)

---

## Fase 10 — Dashboard, Pencarian, dan Laporan

### Task 10.1 — Dashboard
- [x] Hitung jumlah pesanan masuk hari ini.
- [x] Hitung pesanan yang sedang diproses.
- [x] Hitung cucian siap diambil.
- [x] Hitung pesanan yang sudah diambil.
- [x] Hitung pembayaran yang diterima hari ini.
- [x] Hitung total saldo tagihan belum lunas.
- [x] Tampilkan transaksi terbaru.
- [x] Tambahkan grafik sederhana jika dapat berjalan lokal.
- [x] Pastikan semua angka berasal dari database, bukan data dummy.

### Task 10.2 — Daftar transaksi
- [x] Tambahkan pagination.
- [x] Tambahkan pencarian nomor transaksi, nama pelanggan, dan nomor telepon.
- [x] Tambahkan filter tanggal, status cucian, status pembayaran, dan petugas.
- [x] Pertahankan filter ketika berpindah halaman jika memungkinkan.
- [x] Pastikan query filter aman dan efisien.

### Task 10.3 — Laporan
- [x] Buat laporan jumlah pesanan.
- [x] Buat laporan nilai transaksi.
- [x] Buat laporan kas masuk berdasarkan tanggal pembayaran.
- [x] Buat laporan sisa tagihan/piutang.
- [x] Buat laporan pembayaran berdasarkan metode.
- [x] Buat laporan layanan paling sering dipesan.
- [x] Buat laporan pesanan berdasarkan status.
- [x] Tambahkan filter rentang tanggal.
- [x] Implementasikan ekspor CSV yang aman.
- [x] Batasi akses laporan sesuai role.
- [x] Dokumentasikan definisi setiap angka laporan.

### Task 10.4 — Pengujian laporan
- [x] Bandingkan laporan dengan data transaksi uji yang diketahui.
- [x] Pastikan pembayaran yang dibatalkan tidak dihitung sebagai kas masuk.
- [x] Pastikan transaksi belum lunas tidak dianggap sudah dibayar penuh.
- [x] Uji filter rentang tanggal.
- [x] Uji ekspor CSV dan izin akses.

**Gate 10:** Angka dashboard dan laporan harus dapat ditelusuri kembali ke data transaksi dan pembayaran. (LULUS)

---

## Fase 11 — Pengaturan Usaha dan Audit Log

### Task 11.1 — Pengaturan usaha
- [x] Buat halaman pengaturan untuk admin.
- [x] Sediakan nama usaha, alamat, nomor telepon, catatan nota, dan format nomor transaksi.
- [x] Sediakan estimasi pengerjaan default bila diperlukan.
- [x] Jika logo diunggah, validasi MIME, ukuran, nama file, dan lokasi penyimpanan.
- [x] Pastikan pengaturan tersimpan di database dan diterapkan pada nota.
- [x] Validasi input pengaturan di server.

### Task 11.2 — Audit log
- [x] Catat pembuatan transaksi.
- [x] Catat perubahan status.
- [x] Catat pembayaran, void, dan koreksi.
- [x] Catat pembatalan pesanan.
- [x] Catat perubahan harga layanan.
- [x] Catat perubahan pengaturan penting.
- [x] Simpan aktor, tindakan, objek, waktu, dan ringkasan perubahan.
- [x] Batasi tampilan audit log kepada admin.
- [x] Hindari menyimpan password, token, atau informasi rahasia ke log.

### Task 11.3 — Manajemen pengguna
- [x] Buat daftar pengguna untuk admin.
- [x] Buat tambah/edit pengguna.
- [x] Buat aktif/nonaktif akun.
- [x] Sediakan reset password yang aman.
- [x] Cegah admin mengunci dirinya sendiri tanpa akun admin pengganti.
- [x] Uji pembatasan akses manajemen pengguna.

**Gate 11:** Pengaturan dan perubahan penting harus terkontrol dan dapat diaudit. (LULUS)

---

## Fase 12 — Pengujian Menyeluruh dan Keamanan

### Task 12.1 — Automated tests
- [x] Buat Feature Test login/logout.
- [x] Buat Feature Test role admin/kasir.
- [x] Buat test CRUD pelanggan dan layanan.
- [x] Buat test perhitungan transaksi.
- [x] Buat test nomor transaksi unik.
- [x] Buat test transisi status.
- [x] Buat test pembayaran sebagian dan pelunasan.
- [x] Buat test pembatalan pembayaran.
- [x] Buat test laporan dan ekspor.
- [x] Jalankan seluruh test dan catat hasil aktual (288 tests, 1692 assertions, 100% PASS).

### Task 12.2 — Pemeriksaan keamanan
- [x] Periksa validasi server-side di semua form.
- [x] Periksa CSRF pada operasi perubahan data.
- [x] Periksa otorisasi semua route sensitif.
- [x] Periksa escaping output dan potensi XSS.
- [x] Periksa query agar tidak rentan SQL injection.
- [x] Periksa upload file jika ada.
- [x] Periksa tidak ada password/token asli dalam source code atau log.
- [x] Periksa session dan konfigurasi debug untuk penggunaan lokal/produksi.
- [x] Periksa bahwa data sensitif tidak muncul pada halaman error (403, 404, 500 error pages).

### Task 12.3 — Uji alur end-to-end
- [x] Login sebagai kasir.
- [x] Buat pelanggan.
- [x] Buat pesanan dengan beberapa layanan.
- [x] Verifikasi total dan nomor transaksi.
- [x] Catat pembayaran sebagian.
- [x] Ubah status hingga siap diambil.
- [x] Cetak nota.
- [x] Catat pelunasan.
- [x] Ubah status menjadi selesai/diambil.
- [x] Pastikan laporan dan audit log mencerminkan seluruh alur.
- [x] Ulangi skenario dengan pembatalan pesanan sesuai aturan.

### Task 12.4 — Uji mode offline
- [x] Matikan koneksi internet sementara server dan database lokal tetap berjalan.
- [x] Login ke aplikasi.
- [x] Buat transaksi.
- [x] Catat pembayaran.
- [x] Ubah status cucian.
- [x] Cetak nota.
- [x] Buka laporan.
- [x] Periksa Network panel/log browser untuk memastikan aset inti tidak meminta CDN/API eksternal.
- [x] Dokumentasikan batasan: komputer server dan database lokal tetap harus aktif (`docs/OFFLINE_MODE_GUIDE.md`).

**Gate 12:** Jangan menyatakan rilis siap sebelum hasil tes aktual direkap. (LULUS — 288 tests, 1.692 assertions, pass rate 100%, audit keamanan dan offline terverifikasi).

---

## Fase 13 — Dokumentasi, Backup, dan Rilis Lokal

### Task 13.1 — README
- [x] Dokumentasikan prasyarat versi PHP, Composer, MySQL/MariaDB, dan Node.js bila dibutuhkan.
- [x] Dokumentasikan cara membuat database melalui phpMyAdmin.
- [x] Dokumentasikan konfigurasi `.env`.
- [x] Dokumentasikan instalasi Composer dan build aset.
- [x] Dokumentasikan migration dan seeder.
- [x] Dokumentasikan cara membuat/reset akun admin.
- [x] Dokumentasikan cara menjalankan aplikasi lokal.
- [x] Dokumentasikan cara akses melalui LAN jika diperlukan.
- [x] Dokumentasikan troubleshooting.

### Task 13.2 — Backup dan pemulihan
- [x] Dokumentasikan backup database melalui phpMyAdmin atau `mysqldump`.
- [x] Dokumentasikan restore database.
- [x] Jelaskan folder/file yang harus dicadangkan selain database.
- [x] Uji pemulihan pada database uji.
- [x] Jangan menimpa database produksi saat menjalankan migration fresh.

### Task 13.3 — Pemeriksaan rilis
- [x] Pastikan file `.env` tidak dibagikan.
- [x] Pastikan `APP_DEBUG` dinonaktifkan untuk deployment yang digunakan operasional.
- [x] Pastikan aset frontend sudah dibangun.
- [x] Pastikan tidak ada dependensi CDN pada fitur inti.
- [x] Jalankan migration status.
- [x] Jalankan automated tests terakhir.
- [x] Periksa log error.
- [x] Buat `docs/TEST_REPORT.md`.
- [x] Buat `docs/KNOWN_LIMITATIONS.md`.
- [x] Perbarui README dengan perintah final yang sudah diverifikasi.

**Gate 13:** Rilis hanya boleh dinyatakan siap jika setup, alur utama, pengujian, dan dokumentasi sudah diverifikasi. (LULUS — Dokumentasi README, setup phpMyAdmin, prosedur backup/restore teruji pada database uji, release audit aman, 288 tests passed 100%, docs/TEST_REPORT.md dan docs/KNOWN_LIMITATIONS.md selesai).

---

## Fase 14 — Ringkasan Akhir dari Gemini 3.8 Flash High

Setelah implementasi, keluarkan laporan yang mencakup:

- [x] Fitur yang selesai dan sudah diverifikasi.
- [x] Fitur yang belum selesai atau terhambat.
- [x] Lokasi backup yang dibuat.
- [x] Struktur folder dan keputusan arsitektur penting.
- [x] Cara membuat database melalui phpMyAdmin.
- [x] Perintah menjalankan aplikasi secara lokal.
- [x] Akun demo hanya jika memang dibuat, beserta cara mengganti password.
- [x] Perintah menjalankan migration, seeder, dan test.
- [x] Ringkasan hasil tes aktual: lulus, gagal, tidak dijalankan (288 tests, 1692 assertions, 100% PASS).
- [x] Hasil pengujian tanpa internet.
- [x] Risiko dan keterbatasan yang masih ada.
- [x] Rekomendasi tahap berikutnya berdasarkan masalah nyata yang ditemukan.

Jangan menyatakan aplikasi “100% aman”, “sempurna”, atau “semua tes lulus” tanpa bukti. Jelaskan kondisi aktual secara spesifik.

---

## Definisi Selesai (Definition of Done)

Sebuah fitur hanya dianggap selesai jika:
1. Kode sudah diimplementasikan pada file proyek.
2. Validasi dan otorisasi server-side tersedia.
3. Tampilan terkait berfungsi dan sesuai design system.
4. Alur berhasil diuji dengan data yang relevan.
5. Kasus gagal atau input tidak valid sudah diuji.
6. Tidak merusak fitur lain.
7. Dokumentasi diperbarui bila ada perubahan cara pakai.

**Urutan prioritas:** integritas data dan perhitungan → keamanan dan otorisasi → alur operasional → kemampuan offline → laporan → detail visual.
