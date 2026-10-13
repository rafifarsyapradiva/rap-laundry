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
- [ ] Buat form pesanan baru.
- [ ] Pilih pelanggan atau tambahkan pelanggan baru tanpa kehilangan data pesanan.
- [ ] Dukung beberapa item layanan dalam satu pesanan.
- [ ] Tampilkan satuan dan harga berdasarkan master layanan.
- [ ] Dukung kuantitas desimal untuk layanan berbasis kilogram.
- [ ] Validasi kuantitas harus lebih besar dari nol.
- [ ] Sediakan estimasi selesai dan catatan opsional.
- [ ] Sediakan diskon dan biaya tambahan sesuai izin yang ditentukan.

### Task 6.2 — Logika perhitungan
- [ ] Hitung subtotal setiap item di server.
- [ ] Hitung subtotal pesanan, diskon, biaya tambahan, dan total di server.
- [ ] Gunakan `DECIMAL` untuk nominal uang.
- [ ] Jangan mempercayai nilai harga atau total yang dikirim dari browser.
- [ ] Simpan snapshot nama layanan, satuan, dan harga.
- [ ] Gunakan database transaction untuk menyimpan order dan semua item.
- [ ] Pastikan kegagalan menyimpan item membatalkan seluruh transaksi database.
- [ ] Buat nomor transaksi unik, misalnya `LD-YYYYMMDD-0001`.
- [ ] Pastikan nomor transaksi aman dari duplikasi pada akses bersamaan.

### Task 6.3 — Detail transaksi
- [ ] Buat halaman detail pesanan.
- [ ] Tampilkan pelanggan, item layanan, total, pembayaran, sisa tagihan, estimasi selesai, dan status.
- [ ] Tampilkan petugas penerima pesanan.
- [ ] Tampilkan riwayat status.
- [ ] Sediakan tindakan sesuai role dan status transaksi.

### Task 6.4 — Pengujian transaksi
- [ ] Uji pesanan dengan satu item.
- [ ] Uji pesanan dengan beberapa item.
- [ ] Uji berat desimal, misalnya 2,5 kg.
- [ ] Uji kuantitas nol/negatif.
- [ ] Uji diskon dan biaya tambahan.
- [ ] Uji manipulasi harga melalui browser.
- [ ] Uji nomor transaksi unik.
- [ ] Uji rollback ketika penyimpanan gagal.

**Gate 6:** Jangan lanjut sebelum total transaksi dapat diverifikasi dengan perhitungan manual dan pengujian otomatis.

---

## Fase 7 — Status Cucian dan Riwayat

### Task 7.1 — Transisi status
- [ ] Implementasikan status `Diterima`.
- [ ] Implementasikan status `Menunggu Proses`.
- [ ] Implementasikan status `Dicuci`.
- [ ] Implementasikan status `Dikeringkan`.
- [ ] Implementasikan status `Disetrika` bila layanan memerlukannya.
- [ ] Implementasikan status `Siap Diambil`.
- [ ] Implementasikan status `Selesai/Diambil`.
- [ ] Implementasikan status `Dibatalkan`.
- [ ] Validasi transisi status di server.
- [ ] Jangan mewajibkan semua layanan melewati tahap yang tidak relevan.

### Task 7.2 — Riwayat status
- [ ] Simpan status sebelumnya dan status baru.
- [ ] Simpan petugas, waktu, dan catatan opsional.
- [ ] Tampilkan kronologi perubahan pada detail pesanan.
- [ ] Batasi koreksi status sesuai role.
- [ ] Pastikan pembatalan tidak menghapus riwayat transaksi.

### Task 7.3 — Pengujian status
- [ ] Uji transisi yang valid.
- [ ] Uji transisi yang tidak valid.
- [ ] Uji status siap diambil tetapi belum lunas.
- [ ] Uji status lunas tetapi belum diambil.
- [ ] Pastikan status cucian terpisah dari status pembayaran.

**Gate 7:** Status cucian dan pembayaran harus bekerja secara independen dan konsisten.

---

## Fase 8 — Pembayaran Manual

### Task 8.1 — Pencatatan pembayaran
- [ ] Buat form pencatatan pembayaran.
- [ ] Dukung pembayaran penuh dan sebagian.
- [ ] Dukung metode tunai, transfer bank, QRIS eksternal, dan metode lain yang dikonfigurasi.
- [ ] Simpan waktu pembayaran, nominal, metode, petugas, dan referensi opsional.
- [ ] Gunakan database transaction saat mencatat pembayaran.
- [ ] Validasi nominal pembayaran di server.
- [ ] Tolak pembayaran melebihi sisa tagihan sesuai aturan bisnis.
- [ ] Jangan menganggap pembayaran valid hanya karena metode dipilih.

### Task 8.2 — Status pembayaran dan koreksi
- [ ] Hitung total pembayaran valid.
- [ ] Hitung sisa tagihan.
- [ ] Perbarui status `Belum Dibayar`, `Dibayar Sebagian`, atau `Lunas`.
- [ ] Jika koreksi pembayaran diperlukan, gunakan mekanisme void/pembatalan dengan alasan dan jejak audit.
- [ ] Batasi koreksi pembayaran kepada role yang berwenang.
- [ ] Pastikan riwayat pembayaran tidak dihapus diam-diam.

### Task 8.3 — Pengujian pembayaran
- [ ] Uji pesanan belum dibayar.
- [ ] Uji pembayaran sebagian.
- [ ] Uji pelunasan.
- [ ] Uji pembayaran berlebih.
- [ ] Uji pembatalan/koreksi pembayaran.
- [ ] Uji perhitungan sisa tagihan setelah koreksi.
- [ ] Uji laporan kas masuk berdasarkan tanggal pembayaran.

**Gate 8:** Nilai transaksi, uang diterima, dan saldo tagihan harus dibedakan dengan benar.

---

## Fase 9 — Nota dan Cetak

- [ ] Buat tampilan nota yang dapat dicetak.
- [ ] Tampilkan nama usaha, nomor transaksi, pelanggan, tanggal, layanan, kuantitas, harga, subtotal, diskon, biaya tambahan, total, pembayaran, dan saldo.
- [ ] Tampilkan status cucian dan pembayaran.
- [ ] Tampilkan estimasi selesai dan catatan penting.
- [ ] Buat CSS `@media print`.
- [ ] Sembunyikan sidebar, topbar, tombol, dan elemen yang tidak perlu saat mencetak.
- [ ] Pastikan nota tetap bisa dicetak tanpa koneksi internet.
- [ ] Uji cetak A4 dan format nota ringkas jika disediakan.
- [ ] Uji cetak ulang transaksi lama.

**Gate 9:** Data pada nota harus sama dengan data transaksi di database.

---

## Fase 10 — Dashboard, Pencarian, dan Laporan

### Task 10.1 — Dashboard
- [ ] Hitung jumlah pesanan masuk hari ini.
- [ ] Hitung pesanan yang sedang diproses.
- [ ] Hitung cucian siap diambil.
- [ ] Hitung pesanan yang sudah diambil.
- [ ] Hitung pembayaran yang diterima hari ini.
- [ ] Hitung total saldo tagihan belum lunas.
- [ ] Tampilkan transaksi terbaru.
- [ ] Tambahkan grafik sederhana jika dapat berjalan lokal.
- [ ] Pastikan semua angka berasal dari database, bukan data dummy.

### Task 10.2 — Daftar transaksi
- [ ] Tambahkan pagination.
- [ ] Tambahkan pencarian nomor transaksi, nama pelanggan, dan nomor telepon.
- [ ] Tambahkan filter tanggal, status cucian, status pembayaran, dan petugas.
- [ ] Pertahankan filter ketika berpindah halaman jika memungkinkan.
- [ ] Pastikan query filter aman dan efisien.

### Task 10.3 — Laporan
- [ ] Buat laporan jumlah pesanan.
- [ ] Buat laporan nilai transaksi.
- [ ] Buat laporan kas masuk berdasarkan tanggal pembayaran.
- [ ] Buat laporan sisa tagihan/piutang.
- [ ] Buat laporan pembayaran berdasarkan metode.
- [ ] Buat laporan layanan paling sering dipesan.
- [ ] Buat laporan pesanan berdasarkan status.
- [ ] Tambahkan filter rentang tanggal.
- [ ] Implementasikan ekspor CSV yang aman.
- [ ] Batasi akses laporan sesuai role.
- [ ] Dokumentasikan definisi setiap angka laporan.

### Task 10.4 — Pengujian laporan
- [ ] Bandingkan laporan dengan data transaksi uji yang diketahui.
- [ ] Pastikan pembayaran yang dibatalkan tidak dihitung sebagai kas masuk.
- [ ] Pastikan transaksi belum lunas tidak dianggap sudah dibayar penuh.
- [ ] Uji filter rentang tanggal.
- [ ] Uji ekspor CSV dan izin akses.

**Gate 10:** Angka dashboard dan laporan harus dapat ditelusuri kembali ke data transaksi dan pembayaran.

---

## Fase 11 — Pengaturan Usaha dan Audit Log

### Task 11.1 — Pengaturan usaha
- [ ] Buat halaman pengaturan untuk admin.
- [ ] Sediakan nama usaha, alamat, nomor telepon, catatan nota, dan format nomor transaksi.
- [ ] Sediakan estimasi pengerjaan default bila diperlukan.
- [ ] Jika logo diunggah, validasi MIME, ukuran, nama file, dan lokasi penyimpanan.
- [ ] Pastikan pengaturan tersimpan di database dan diterapkan pada nota.
- [ ] Validasi input pengaturan di server.

### Task 11.2 — Audit log
- [ ] Catat pembuatan transaksi.
- [ ] Catat perubahan status.
- [ ] Catat pembayaran, void, dan koreksi.
- [ ] Catat pembatalan pesanan.
- [ ] Catat perubahan harga layanan.
- [ ] Catat perubahan pengaturan penting.
- [ ] Simpan aktor, tindakan, objek, waktu, dan ringkasan perubahan.
- [ ] Batasi tampilan audit log kepada admin.
- [ ] Hindari menyimpan password, token, atau informasi rahasia ke log.

### Task 11.3 — Manajemen pengguna
- [ ] Buat daftar pengguna untuk admin.
- [ ] Buat tambah/edit pengguna.
- [ ] Buat aktif/nonaktif akun.
- [ ] Sediakan reset password yang aman.
- [ ] Cegah admin mengunci dirinya sendiri tanpa akun admin pengganti.
- [ ] Uji pembatasan akses manajemen pengguna.

**Gate 11:** Pengaturan dan perubahan penting harus terkontrol dan dapat diaudit.

---

## Fase 12 — Pengujian Menyeluruh dan Keamanan

### Task 12.1 — Automated tests
- [ ] Buat Feature Test login/logout.
- [ ] Buat Feature Test role admin/kasir.
- [ ] Buat test CRUD pelanggan dan layanan.
- [ ] Buat test perhitungan transaksi.
- [ ] Buat test nomor transaksi unik.
- [ ] Buat test transisi status.
- [ ] Buat test pembayaran sebagian dan pelunasan.
- [ ] Buat test pembatalan pembayaran.
- [ ] Buat test laporan dan ekspor.
- [ ] Jalankan seluruh test dan catat hasil aktual.

### Task 12.2 — Pemeriksaan keamanan
- [ ] Periksa validasi server-side di semua form.
- [ ] Periksa CSRF pada operasi perubahan data.
- [ ] Periksa otorisasi semua route sensitif.
- [ ] Periksa escaping output dan potensi XSS.
- [ ] Periksa query agar tidak rentan SQL injection.
- [ ] Periksa upload file jika ada.
- [ ] Periksa tidak ada password/token asli dalam source code atau log.
- [ ] Periksa session dan konfigurasi debug untuk penggunaan lokal/produksi.
- [ ] Periksa bahwa data sensitif tidak muncul pada halaman error.

### Task 12.3 — Uji alur end-to-end
- [ ] Login sebagai kasir.
- [ ] Buat pelanggan.
- [ ] Buat pesanan dengan beberapa layanan.
- [ ] Verifikasi total dan nomor transaksi.
- [ ] Catat pembayaran sebagian.
- [ ] Ubah status hingga siap diambil.
- [ ] Cetak nota.
- [ ] Catat pelunasan.
- [ ] Ubah status menjadi selesai/diambil.
- [ ] Pastikan laporan dan audit log mencerminkan seluruh alur.
- [ ] Ulangi skenario dengan pembatalan pesanan sesuai aturan.

### Task 12.4 — Uji mode offline
- [ ] Matikan koneksi internet sementara server dan database lokal tetap berjalan.
- [ ] Login ke aplikasi.
- [ ] Buat transaksi.
- [ ] Catat pembayaran.
- [ ] Ubah status cucian.
- [ ] Cetak nota.
- [ ] Buka laporan.
- [ ] Periksa Network panel/log browser untuk memastikan aset inti tidak meminta CDN/API eksternal.
- [ ] Dokumentasikan batasan: komputer server dan database lokal tetap harus aktif.

**Gate 12:** Jangan menyatakan rilis siap sebelum hasil tes aktual direkap.

---

## Fase 13 — Dokumentasi, Backup, dan Rilis Lokal

### Task 13.1 — README
- [ ] Dokumentasikan prasyarat versi PHP, Composer, MySQL/MariaDB, dan Node.js bila dibutuhkan.
- [ ] Dokumentasikan cara membuat database melalui phpMyAdmin.
- [ ] Dokumentasikan konfigurasi `.env`.
- [ ] Dokumentasikan instalasi Composer dan build aset.
- [ ] Dokumentasikan migration dan seeder.
- [ ] Dokumentasikan cara membuat/reset akun admin.
- [ ] Dokumentasikan cara menjalankan aplikasi lokal.
- [ ] Dokumentasikan cara akses melalui LAN jika diperlukan.
- [ ] Dokumentasikan troubleshooting.

### Task 13.2 — Backup dan pemulihan
- [ ] Dokumentasikan backup database melalui phpMyAdmin atau `mysqldump`.
- [ ] Dokumentasikan restore database.
- [ ] Jelaskan folder/file yang harus dicadangkan selain database.
- [ ] Uji pemulihan pada database uji.
- [ ] Jangan menimpa database produksi saat menjalankan migration fresh.

### Task 13.3 — Pemeriksaan rilis
- [ ] Pastikan file `.env` tidak dibagikan.
- [ ] Pastikan `APP_DEBUG` dinonaktifkan untuk deployment yang digunakan operasional.
- [ ] Pastikan aset frontend sudah dibangun.
- [ ] Pastikan tidak ada dependensi CDN pada fitur inti.
- [ ] Jalankan migration status.
- [ ] Jalankan automated tests terakhir.
- [ ] Periksa log error.
- [ ] Buat `docs/TEST_REPORT.md`.
- [ ] Buat `docs/KNOWN_LIMITATIONS.md`.
- [ ] Perbarui README dengan perintah final yang sudah diverifikasi.

**Gate 13:** Rilis hanya boleh dinyatakan siap jika setup, alur utama, pengujian, dan dokumentasi sudah diverifikasi.

---

## Fase 14 — Ringkasan Akhir dari Gemini 3.8 Flash High

Setelah implementasi, keluarkan laporan yang mencakup:

- [ ] Fitur yang selesai dan sudah diverifikasi.
- [ ] Fitur yang belum selesai atau terhambat.
- [ ] Lokasi backup yang dibuat.
- [ ] Struktur folder dan keputusan arsitektur penting.
- [ ] Cara membuat database melalui phpMyAdmin.
- [ ] Perintah menjalankan aplikasi secara lokal.
- [ ] Akun demo hanya jika memang dibuat, beserta cara mengganti password.
- [ ] Perintah menjalankan migration, seeder, dan test.
- [ ] Ringkasan hasil tes aktual: lulus, gagal, tidak dijalankan.
- [ ] Hasil pengujian tanpa internet.
- [ ] Risiko dan keterbatasan yang masih ada.
- [ ] Rekomendasi tahap berikutnya berdasarkan masalah nyata yang ditemukan.

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
