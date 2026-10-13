# PRD — Website Pengelolaan Cuci Pakaian Offline

**Dokumen:** Product Requirements Document (PRD) dan prompt implementasi untuk Gemini 3.8 Flash High  
**Nama proyek sementara:** LaundryDesk  
**Versi:** 1.0  
**Bahasa aplikasi:** Bahasa Indonesia  
**Target pengguna:** Pemilik laundry, kasir, dan staf operasional  
**Framework:** Laravel 12  
**Database:** MySQL, dikelola melalui phpMyAdmin  
**UI:** Glassmorphism dengan kombinasi biru dan putih  
**Mode operasi:** Lokal/offline, tanpa ketergantungan internet untuk fitur inti

---

## 1. Prompt Utama untuk Gemini 3.8 Flash High

Bertindaklah sebagai **Senior Product Engineer, System Analyst, UI/UX Designer, Laravel 12 Developer, dan QA Engineer**. Tugasmu adalah merancang dan membangun aplikasi website **Pengelolaan Cuci Pakaian Offline** untuk usaha laundry skala kecil hingga menengah.

Bangun aplikasi yang berfungsi nyata, bukan sekadar mockup atau halaman statis. Aplikasi harus memiliki autentikasi, database, pengelolaan pelanggan, transaksi laundry, pelacakan status cucian, pembayaran manual, laporan, validasi, dan dokumentasi instalasi.

Ikuti PRD ini sebagai acuan implementasi. Bila menemukan kebutuhan yang belum ditentukan, pilih solusi yang sederhana, aman, konsisten, dan cocok untuk operasional laundry offline. Catat asumsi penting di dokumentasi.

### Aturan kerja sebelum implementasi

1. Periksa folder proyek dan kondisi kode yang sudah tersedia.
2. Jika proyek sudah berisi file, **buat backup sebelum mengubah atau menghapus apa pun**. Simpan backup di folder terpisah dengan penamaan yang jelas, misalnya `backups/pre-implementation-YYYYMMDD-HHmm/`. Jangan memasukkan file rahasia seperti `.env` ke repositori publik.
3. Buat rencana implementasi berurutan: analisis, desain database, struktur Laravel, UI, fitur, pengujian, dan dokumentasi.
4. Periksa kompatibilitas Laravel 12 dengan versi PHP dan dependensi yang dipakai. Jangan menurunkan versi framework tanpa alasan dan persetujuan.
5. Implementasikan fitur secara bertahap. Setelah tiap tahap, jalankan pemeriksaan yang relevan dan perbaiki error sebelum melanjutkan.
6. Jangan mengklaim fitur sudah selesai atau lulus pengujian jika belum dijalankan. Berikan daftar fitur yang selesai, hasil tes, dan keterbatasan yang masih ada.
7. Jangan berhenti pada rencana atau contoh kode. Lanjutkan sampai aplikasi berjalan sesuai kemampuan lingkungan kerja.
8. Jangan menghapus atau menimpa pekerjaan pengguna tanpa backup dan penjelasan.

---

## 2. Tujuan Produk

Aplikasi ini membantu usaha laundry mengelola kegiatan harian secara terpusat melalui komputer lokal. Tujuan utamanya:

- Mencatat pelanggan dan pesanan laundry.
- Menghitung biaya berdasarkan layanan, berat/jumlah, dan tambahan layanan.
- Melacak progres cucian dari penerimaan hingga selesai dan diambil pelanggan.
- Mencatat pembayaran dan sisa tagihan secara manual.
- Mengurangi kesalahan pencatatan kertas dan perhitungan.
- Menyediakan bukti transaksi yang dapat dicetak.
- Menampilkan ringkasan transaksi dan laporan operasional.
- Tetap berfungsi ketika koneksi internet tidak tersedia.

**Di luar cakupan:** marketplace laundry, aplikasi mobile native, payment gateway online, sinkronisasi cloud, pengiriman WhatsApp otomatis, dan integrasi layanan pihak ketiga.

---

## 3. Ketentuan Offline dan Lingkungan

Gunakan **Laravel 12**, PHP yang kompatibel dengan Laravel 12, dan MySQL/MariaDB lokal. Database dibuat dan dikelola melalui **phpMyAdmin**; phpMyAdmin adalah alat administrasi database, bukan database itu sendiri.

Target implementasi standar:
- Laravel 12 dengan pola MVC.
- Blade untuk templating antarmuka.
- HTML, CSS, dan JavaScript yang disimpan lokal.
- MySQL/MariaDB lokal melalui XAMPP atau lingkungan PHP lokal setara.
- Composer untuk dependensi PHP.
- Vite hanya untuk proses build saat pengembangan; hasil CSS/JS produksi harus sudah dibangun dan tersimpan di folder publik sehingga aplikasi tidak memerlukan server Vite saat dipakai.
- Hindari Google Fonts, CDN, API eksternal, font eksternal, dan aset yang perlu diunduh saat aplikasi digunakan.
- Gunakan ikon SVG lokal atau ikon CSS sederhana. Jangan membuat antarmuka bergantung pada CDN ikon.
- Zona waktu aplikasi: `Asia/Jakarta`.
- Format tanggal: `dd-mm-yyyy`; format waktu 24 jam; mata uang Rupiah, misalnya `Rp25.000`.
- Aplikasi dapat dijalankan pada satu komputer melalui `localhost`. Jika dibutuhkan beberapa perangkat di jaringan lokal, pengguna dapat mengakses IP lokal komputer server selama jaringan LAN/Wi-Fi tersedia. Internet tidak diperlukan, tetapi komputer server dan jaringan lokal harus aktif.
- Fitur inti wajib tetap berfungsi tanpa internet. Jangan menyebut aplikasi “offline” jika ada fitur wajib yang masih memerlukan layanan eksternal.

### Catatan keamanan offline

Offline bukan berarti bebas risiko. Gunakan autentikasi, otorisasi, validasi input, CSRF protection, password hashing, prepared queries melalui Eloquent/Query Builder, escaping output Blade, dan validasi file upload bila fitur upload ditambahkan. Jangan menyimpan password polos atau menaruh kredensial database dalam source code.

---

## 4. Pengguna dan Hak Akses

### 4.1 Admin/Pemilik
- Login dan logout.
- Melihat dashboard dan seluruh transaksi.
- Mengelola akun staf.
- Mengelola pelanggan, layanan, harga, dan pengaturan usaha.
- Mengubah status cucian sesuai kewenangan.
- Mencatat dan mengoreksi pembayaran dengan jejak audit.
- Melihat, mencetak, dan mengekspor laporan.
- Melihat ringkasan pendapatan dan piutang.

### 4.2 Kasir/Staf
- Login dan logout.
- Menambah dan mencari pelanggan.
- Membuat pesanan laundry.
- Melihat transaksi sesuai izin.
- Memperbarui status cucian sesuai alur yang diizinkan.
- Mencatat pembayaran dan mencetak nota.
- Tidak boleh mengelola akun admin atau mengubah pengaturan kritis kecuali diberi izin.

Terapkan middleware/authorization Laravel untuk setiap route yang memerlukan autentikasi. Jangan hanya menyembunyikan tombol di antarmuka; validasi hak akses juga di server.

---

## 5. Modul dan Kebutuhan Fungsional

### 5.1 Autentikasi
- Halaman login dengan email/username dan password.
- Logout dengan aman.
- Password disimpan menggunakan hashing Laravel.
- Validasi kredensial dan pesan error berbahasa Indonesia.
- Sediakan akun admin awal melalui seeder atau langkah setup terdokumentasi. Jangan hardcode password produksi yang mudah ditebak.
- Registrasi publik tidak diperlukan.
- Fitur lupa password melalui email tidak diperlukan karena sistem offline. Sediakan prosedur reset password admin melalui perintah/Seeder/Artisan yang terdokumentasi dan aman.

### 5.2 Dashboard
Tampilkan kartu ringkasan:
- Pesanan masuk hari ini.
- Pesanan yang sedang diproses.
- Cucian siap diambil.
- Pesanan yang sudah diambil.
- Pendapatan yang benar-benar sudah dibayar hari ini.
- Total tagihan yang belum lunas.

Tambahkan:
- Tabel transaksi terbaru.
- Ringkasan jumlah pesanan berdasarkan status.
- Grafik sederhana pendapatan per hari/per bulan bila bisa dibuat tanpa dependensi online.
- Filter rentang tanggal untuk laporan ringkas.

Semua angka harus dihitung dari database, bukan angka dummy.

### 5.3 Data Pelanggan
Data minimum:
- ID pelanggan.
- Kode pelanggan otomatis.
- Nama.
- Nomor telepon.
- Alamat (opsional).
- Catatan (opsional).
- Tanggal dibuat dan diperbarui.

Fungsi:
- Tambah, lihat, ubah, dan arsipkan pelanggan.
- Cari berdasarkan nama, nomor telepon, atau kode pelanggan.
- Validasi nomor telepon dan field wajib.
- Cegah penghapusan permanen pelanggan yang sudah memiliki transaksi; gunakan soft delete atau status aktif/nonaktif.

### 5.4 Master Layanan dan Harga
Admin dapat mengelola jenis layanan. Isi awal bersifat contoh dan dapat diubah:
- Cuci Kering — harga per kg.
- Cuci Setrika — harga per kg.
- Cuci + Setrika — harga per kg.
- Setrika Saja — harga per kg.
- Express — tambahan biaya atau tarif tersendiri.
- Bed cover/selimut — harga per item.
- Sepatu/tas atau layanan lain — harga per item, bila usaha membutuhkannya.

Setiap layanan memiliki:
- Nama layanan.
- Deskripsi singkat.
- Satuan penagihan: kg, item, atau paket.
- Harga.
- Estimasi durasi pengerjaan.
- Status aktif/nonaktif.

Jangan mengunci harga di dalam kode. Harga diambil dari master layanan. Simpan snapshot nama layanan, satuan, dan harga saat transaksi dibuat agar perubahan harga di kemudian hari tidak mengubah nota transaksi lama.

### 5.5 Pembuatan Pesanan Laundry
Form pesanan memuat:
- Nomor transaksi otomatis dan unik, misalnya `LD-YYYYMMDD-0001`.
- Tanggal dan waktu masuk (`received_at`, default: saat ini).
- Pelanggan yang sudah ada atau pembuatan pelanggan baru seketika.
- Satu atau lebih detail layanan (multi-item dinamis).
- Kuantitas/berat sesuai satuan layanan.
- Harga satuan (snapshot dari master layanan).
- Subtotal per detail.
- Diskon opsional, dengan batasan dan izin yang ditentukan.
- Biaya tambahan opsional (express/ongkir/perlakuan khusus).
- Total transaksi (dihitung ulang di server).
- Uang muka/pembayaran awal opsional.
- Sisa tagihan.
- Estimasi selesai (otomatis dan dapat disesuaikan).
- Catatan khusus pesanan dan catatan per item.
- Petugas yang menerima pesanan (otomatis dari pengguna aktif yang sedang login).

Ketentuan operasional dan teknis jangka panjang:
- **Pelanggan Cepat (In-Place Quick Add):** Disediakan modal dialog untuk menambah pelanggan baru langsung dari form pesanan via AJAX lokal tanpa me-refresh halaman, sehingga data baris layanan yang telah diisi tidak hilang.
- **Kuantitas Desimal dan Bilangan Bulat:** Untuk layanan berbasis kilogram (`kg`), wajib mendukung bilangan desimal (misal `2,5 kg` atau `3,75 kg`) dengan presisi 2 angka di belakang koma. Sistem otomatis menormalisasi tanda koma (`,`) menjadi titik (`.`). Untuk layanan per item atau paket, kuantitas berupa bilangan bulat positif (`integer`).
- **Validasi Kuantitas:** Kuantitas harus lebih besar dari nol (`quantity > 0`).
- **Estimasi Selesai Otomatis & Presets:** Estimasi selesai dihitung otomatis berdasarkan durasi terlama dari layanan yang dipilih (`received_at + max(service.estimated_hours)`) atau konfigurasi default usaha (`default_estimated_hours`), disertai tombol pintas praktis (`+1 Hari`, `+2 Hari`, `+3 Hari`) dan opsi penyesuaian manual kasir.
- **Kebijakan Diskon Berbasis Konfigurasi Usaha:** Diskon dikontrol melalui konfigurasi dinamis `cashier_max_discount_percent` di pengaturan usaha (default: 20%). Staf kasir dibatasi maksimal persentase tersebut dari subtotal, sedangkan Admin/Pemilik memiliki izin diskon penuh hingga 100%. Diskon tidak boleh melebihi subtotal (`0 ≤ discount_amount ≤ subtotal`).
- **Biaya Tambahan:** Kasir dan Admin dapat menambahkan biaya tambahan non-negatif (`extra_charge ≥ 0`) dengan input rupiah bulat.
- **Integritas Finansial Server-Side:** Hitung ulang seluruh subtotal, diskon, biaya tambahan, dan total di server. Jangan mempercayai kalkulasi dari browser. Nilai moneter disimpan dengan tipe `DECIMAL(12,2)`.
- **Nomor Transaksi Unik:** Nomor transaksi harus unik dan aman dari tabrakan saat diakses bersamaan melalui mekanisme *pessimistic locking* pada tabel `number_sequences`.
- **Atomisitas Database:** Gunakan `DB::transaction` saat menyimpan pesanan dan seluruh detail itemnya.
- **Pencegahan Double-Submission:** Tombol submit dinonaktifkan otomatis saat proses simpan berlangsung untuk mencegah pembuatan pesanan ganda.

### 5.6 Alur Status Cucian
Gunakan status yang konsisten:
1. `Diterima` — pesanan baru dicatat.
2. `Menunggu Proses` — siap masuk proses operasional.
3. `Dicuci` — proses pencucian berjalan.
4. `Dikeringkan` — proses pengeringan.
5. `Disetrika` — proses setrika jika layanan membutuhkannya.
6. `Siap Diambil` — pekerjaan selesai.
7. `Selesai/Diambil` — pelanggan telah menerima cucian.
8. `Dibatalkan` — pesanan dibatalkan sesuai aturan.

Tidak semua layanan harus melewati seluruh status. Rancang transisi status yang masuk akal dan izinkan konfigurasi atau penyesuaian alur jika perlu. Status `Dibatalkan` tidak boleh digunakan untuk menghapus jejak transaksi.

Simpan riwayat perubahan status, termasuk status sebelumnya, status baru, waktu perubahan, petugas, dan catatan opsional. Tampilkan riwayat pada halaman detail pesanan.

### 5.7 Pembayaran Manual
Tidak ada payment gateway online. Pembayaran dicatat oleh kasir ketika uang diterima.

Metode yang dapat dicatat:
- Tunai.
- Transfer bank (diverifikasi manual).
- QRIS di luar aplikasi (diverifikasi manual).
- Metode lain yang dapat diatur.

Fungsi:
- Pembayaran penuh atau sebagian.
- Pembayaran awal/uang muka.
- Pelunasan pada waktu berbeda.
- Catatan referensi pembayaran opsional.
- Riwayat semua pembayaran.
- Status pembayaran otomatis: `Belum Dibayar`, `Dibayar Sebagian`, `Lunas`, atau `Pengembalian` jika fitur refund manual diterapkan.
- Hitung sisa tagihan dari total transaksi dikurangi total pembayaran yang valid.
- Jangan menganggap pembayaran berhasil hanya karena metode dipilih; kasir harus menyimpan catatan pembayaran yang benar-benar diterima.
- Jangan izinkan total pembayaran melebihi saldo yang harus dibayar kecuali ada alur kelebihan bayar yang dirancang dan diuji.
- Perubahan/pembatalan pembayaran harus dibatasi dan tercatat dalam audit log.

### 5.8 Nota dan Cetak
Sediakan nota transaksi yang ramah cetak:
- Nama dan informasi usaha yang dapat dikonfigurasi.
- Nomor transaksi.
- Tanggal masuk dan estimasi selesai.
- Nama serta nomor telepon pelanggan.
- Daftar layanan, kuantitas/berat, harga satuan, dan subtotal.
- Diskon/biaya tambahan bila ada.
- Total, pembayaran, dan sisa tagihan.
- Status cucian dan pembayaran.
- Nama petugas.
- Catatan singkat tentang pengambilan cucian.

Gunakan CSS `@media print` untuk menyembunyikan navigasi dan elemen yang tidak diperlukan. Sediakan format cetak A4 dan/atau nota ringkas yang mudah disesuaikan. Jangan mewajibkan printer khusus.

### 5.9 Pengelolaan Pesanan
- Tabel transaksi dengan pagination.
- Cari berdasarkan nomor transaksi, nama pelanggan, atau nomor telepon.
- Filter tanggal, status cucian, status pembayaran, dan petugas.
- Lihat detail transaksi.
- Ubah status sesuai izin dan alur.
- Cetak ulang nota.
- Batalkan pesanan dengan alasan dan catatan audit.
- Jangan menghapus transaksi yang sudah memiliki pembayaran atau riwayat status; gunakan pembatalan/arsip.

### 5.10 Laporan
Sediakan laporan berdasarkan rentang tanggal:
- Jumlah pesanan.
- Pendapatan berdasarkan pembayaran yang benar-benar diterima.
- Total nilai pesanan.
- Total piutang/sisa tagihan.
- Rincian pembayaran per metode.
- Layanan paling sering dipesan.
- Pesanan berdasarkan status.
- Riwayat transaksi yang dapat difilter.

Bedakan dengan jelas:
- **Nilai transaksi:** total harga pesanan.
- **Uang diterima:** pembayaran yang sudah dicatat dan valid.
- **Sisa tagihan:** nilai transaksi dikurangi pembayaran yang valid.
- **Pendapatan periode:** tentukan dan dokumentasikan apakah laporan berbasis tanggal pembayaran atau tanggal pesanan. Gunakan tanggal pembayaran untuk laporan kas masuk.

Sediakan ekspor CSV yang dapat dibuka di aplikasi spreadsheet. Amankan ekspor agar hanya pengguna berwenang yang dapat mengaksesnya. Hindari menyebut seluruh nilai transaksi sebagai uang yang sudah diterima.

### 5.11 Pengaturan Usaha
Admin dapat mengatur:
- Nama usaha.
- Alamat dan nomor telepon.
- Logo lokal opsional.
- Format nomor transaksi.
- Jam operasional (opsional).
- Estimasi pengerjaan default.
- Teks catatan pada nota.
- Preferensi format cetak.

Validasi upload logo: tipe MIME, ukuran maksimum, nama file aman, dan simpan hanya di lokasi yang sesuai. Jika upload tidak diperlukan untuk versi awal, tampilkan nama usaha tanpa logo.

### 5.12 Audit dan Riwayat
Catat aktivitas penting:
- Login/logout bila layak.
- Pembuatan transaksi.
- Perubahan status.
- Pencatatan, koreksi, atau pembatalan pembayaran.
- Perubahan harga layanan.
- Pembatalan pesanan.
- Perubahan pengaturan penting.

Minimal simpan aktor, tindakan, objek terkait, waktu, dan ringkasan perubahan. Batasi akses audit log kepada admin.

---

## 6. Kebutuhan UI/UX

### 6.1 Arah Visual
Gunakan gaya **Glassmorphism** dengan kombinasi biru dan putih. Antarmuka harus terasa bersih, modern, profesional, dan tetap mudah dibaca untuk pekerjaan kasir yang dilakukan berulang kali.

Palet acuan:
- Biru utama: `#2563EB`.
- Biru gelap: `#1D4ED8` atau `#1E40AF`.
- Biru muda: `#DBEAFE`.
- Latar: `#F4F8FF`.
- Putih/transparan: `rgba(255, 255, 255, 0.72)`.
- Teks utama: `#172554`.
- Teks sekunder: `#64748B`.
- Garis/border: `rgba(148, 163, 184, 0.25)`.
- Hijau untuk status sukses, amber untuk peringatan, merah untuk kesalahan/pembatalan.

Gunakan warna ini sebagai pedoman, bukan alasan untuk mengorbankan kontras. Periksa kontras teks, termasuk pada kartu transparan.

### 6.2 Prinsip Glassmorphism
- Kartu memakai latar putih transparan, border tipis, blur halus, dan bayangan lembut.
- Latar boleh memiliki gradasi biru sangat ringan atau bentuk dekoratif abstrak.
- Gunakan `backdrop-filter: blur(...)` dengan fallback latar solid agar tetap nyaman jika browser tidak mendukungnya.
- Hindari blur berlebihan, teks transparan, dan ornamen yang mengurangi keterbacaan.
- Form input dan tabel harus jelas; jangan membuat semua elemen terlalu transparan.
- Gunakan radius konsisten, jarak antarelemen teratur, dan hierarki visual yang jelas.
- Animasi sederhana dan singkat; hormati `prefers-reduced-motion`.

### 6.3 Tata Letak
- Sidebar navigasi di desktop: Dashboard, Transaksi, Buat Pesanan, Pelanggan, Layanan & Harga, Laporan, Pengaturan, Pengguna (khusus admin).
- Topbar menampilkan nama halaman, pencarian bila relevan, nama pengguna, dan tombol logout.
- Konten utama menggunakan kartu ringkasan, tabel yang rapi, filter, dan form bertahap jika form panjang.
- Pada layar kecil, sidebar berubah menjadi drawer/menu ringkas; tabel memiliki perilaku responsif yang tetap dapat digunakan.
- Halaman login sederhana, fokus pada form dan identitas usaha.
- Gunakan badge status dengan warna konsisten dan label teks, bukan warna saja.

### 6.4 Aksesibilitas dan Usability
- Label form selalu terlihat dan terhubung dengan input.
- Error validasi ditampilkan dekat field terkait.
- Tombol memiliki teks tindakan yang jelas.
- Konfirmasi untuk tindakan berisiko, seperti pembatalan.
- Sediakan empty state, loading state jika diperlukan, dan pesan sukses/gagal.
- Navigasi keyboard dan focus ring harus jelas.
- Jangan gunakan ikon tanpa label untuk tindakan penting.
- Prioritaskan alur kasir: membuat pesanan baru, mencatat pembayaran, mengubah status, dan mencetak nota harus mudah ditemukan.

---

## 7. Rancangan Database Awal

Gunakan migration Laravel untuk seluruh skema. Nama tabel boleh disesuaikan jika ada alasan yang terdokumentasi, tetapi relasi dan kebutuhan data harus dipertahankan.

### Tabel inti yang disarankan

1. `users`
   - `id`, `name`, `username` atau `email` unik, `password`, `role` (`admin`/`kasir`), `is_active`, timestamps.
2. `customers`
   - `id`, `customer_code` unik, `name`, `phone`, `address` nullable, `notes` nullable, `is_active`, timestamps, soft deletes jika sesuai.
3. `services`
   - `id`, `name`, `description` nullable, `billing_unit` (`kg`/`item`/`package`), `price` DECIMAL, `estimated_hours` nullable, `is_active`, timestamps, soft deletes jika sesuai.
4. `orders`
   - `id`, `order_number` unik, `customer_id`, `created_by`, `received_at`, `estimated_completion_at` nullable, `completed_at` nullable, `subtotal`, `discount_amount`, `extra_charge`, `total_amount`, `notes` nullable, `laundry_status`, `payment_status`, `cancelled_at` nullable, `cancellation_reason` nullable, timestamps.
5. `order_items`
   - `id`, `order_id`, `service_id` nullable bila layanan diarsipkan/dihapus secara logis, `service_name_snapshot`, `billing_unit_snapshot`, `quantity` DECIMAL, `unit_price` DECIMAL, `subtotal` DECIMAL, `notes` nullable, timestamps.
6. `payments`
   - `id`, `order_id`, `recorded_by`, `payment_date`, `amount` DECIMAL, `method`, `reference` nullable, `notes` nullable, `status` (misalnya valid/voided), `voided_by` nullable, `voided_at` nullable, timestamps.
7. `order_status_histories`
   - `id`, `order_id`, `from_status` nullable, `to_status`, `changed_by`, `note` nullable, timestamps.
8. `business_settings`
   - `id`, `setting_key` unik, `setting_value` nullable, timestamps.
9. `activity_logs`
   - `id`, `user_id` nullable, `action`, `subject_type` nullable, `subject_id` nullable, `description` nullable, `metadata` JSON nullable, timestamps.

### Aturan database
- Gunakan foreign key, index, unique constraint, dan tipe data yang sesuai.
- Gunakan `DECIMAL` untuk uang dan kuantitas; tentukan presisi yang konsisten.
- Gunakan transaction database saat membuat pesanan beserta item dan saat mencatat pembayaran.
- Gunakan constraint/index untuk nomor transaksi unik.
- Rancang soft delete atau status arsip agar transaksi historis tetap utuh.
- Jangan bergantung hanya pada validasi JavaScript.
- Buat model, relasi Eloquent, migration, factory, dan seeder yang sesuai.
- Pastikan nilai status tersimpan konsisten. Gunakan PHP Enum atau konstanta terpusat bila cocok dengan struktur proyek.
- Simpan snapshot harga dan nama layanan pada item transaksi agar histori tidak berubah saat master layanan diedit.

---

## 8. Struktur Kode Laravel

Gunakan struktur Laravel yang konvensional dan mudah dipelihara. Minimal:
- `app/Models/`
- `app/Http/Controllers/`
- `app/Http/Requests/` untuk validasi form yang kompleks
- `app/Policies/` atau middleware untuk otorisasi
- `app/Services/` untuk perhitungan transaksi, nomor transaksi, status, dan pembayaran bila diperlukan
- `database/migrations/`
- `database/factories/`
- `database/seeders/`
- `resources/views/` dengan layout Blade, komponen, halaman, dan partial
- `resources/css/` dan `resources/js/`
- `public/` untuk hasil build dan aset lokal
- `routes/web.php`
- `tests/Feature/` dan `tests/Unit/`

Jangan menaruh seluruh logika bisnis di Blade atau satu controller besar. Gunakan Form Request, Eloquent, database transaction, dan service class jika meningkatkan keterbacaan. Hindari arsitektur berlebihan untuk aplikasi skala kecil.

---

## 9. Route dan Halaman Minimal

Halaman minimum:
- Login.
- Dashboard.
- Daftar transaksi.
- Form buat transaksi.
- Detail transaksi dan riwayat status.
- Cetak nota.
- Daftar pelanggan, tambah, edit, detail.
- Daftar layanan dan harga, tambah, edit.
- Pembayaran dan riwayat pembayaran.
- Laporan dengan filter.
- Pengaturan usaha.
- Manajemen pengguna untuk admin.
- Halaman 403, 404, dan error yang informatif.

Semua route internal harus dilindungi sesuai hak akses. Gunakan named routes, route model binding dengan aman, dan metode HTTP yang sesuai. Operasi perubahan data tidak boleh dilakukan melalui GET.

---

## 10. Aturan Bisnis Penting

1. Total pesanan dihitung dari subtotal item dikurangi diskon ditambah biaya tambahan.
2. Subtotal item dihitung dari kuantitas dikali harga satuan snapshot.
3. Harga dan total dihitung ulang dan diverifikasi di server.
4. Sisa tagihan = total transaksi dikurangi jumlah pembayaran valid yang belum dibatalkan.
5. Status pembayaran diperbarui berdasarkan total pembayaran valid.
6. Status cucian dan status pembayaran adalah dua hal terpisah.
7. Pesanan yang siap diambil belum otomatis berarti sudah dibayar atau sudah diambil.
8. Pesanan baru tidak boleh langsung dianggap selesai tanpa perubahan status yang sah.
9. Pembatalan harus menyimpan alasan, aktor, waktu, dan dampak ke pembayaran.
10. Pembayaran yang sudah tercatat tidak dihapus diam-diam. Koreksi harus menggunakan mekanisme void/pembatalan yang tercatat.
11. Pesanan dan pembayaran historis tidak boleh rusak ketika nama atau harga layanan berubah.
12. Pastikan pembatasan akses berlaku pada server untuk setiap tindakan sensitif.
13. Gunakan pagination untuk daftar panjang dan validasi filter.
14. Gunakan pesan berbahasa Indonesia yang ringkas dan tidak membocorkan detail teknis sensitif.

---

## 11. Kriteria Penerimaan

Aplikasi dianggap layak untuk rilis awal apabila:

- [ ] Dapat dipasang dan dijalankan di lingkungan lokal dengan Laravel 12 dan MySQL/MariaDB.
- [ ] Instruksi database melalui phpMyAdmin jelas dan berhasil diikuti.
- [ ] Login/logout berjalan dan hak akses admin/kasir diuji.
- [ ] Admin dapat mengelola pelanggan dan layanan.
- [ ] Kasir dapat membuat pesanan dengan beberapa item layanan.
- [ ] Perhitungan subtotal, diskon, biaya tambahan, total, pembayaran, dan saldo benar di server.
- [ ] Nomor transaksi unik.
- [ ] Perubahan status cucian tercatat dalam riwayat.
- [ ] Pembayaran penuh/sebagian dapat dicatat dan status pembayaran konsisten.
- [ ] Nota dapat dicetak dengan informasi yang benar.
- [ ] Laporan membedakan nilai transaksi, uang diterima, dan sisa tagihan.
- [ ] Filter dan pencarian transaksi berfungsi.
- [ ] Aplikasi tidak memerlukan internet untuk alur inti setelah dependensi dan aset dipasang/build.
- [ ] UI responsif dan menggunakan Glassmorphism biru-putih dengan kontras yang memadai.
- [ ] Validasi server-side, CSRF, hashing password, escaping output, dan otorisasi diterapkan.
- [ ] Ada seeder/demo data yang tidak memakai kredensial produksi berbahaya.
- [ ] Ada automated test untuk alur kritis dan hasilnya dilaporkan.
- [ ] Dokumentasi instalasi, konfigurasi, backup, restore, dan troubleshooting tersedia.

---

## 12. Strategi Pengujian

Buat dan jalankan pengujian untuk setidaknya skenario berikut:

1. Login berhasil dan gagal.
2. Kasir tidak dapat mengakses manajemen pengguna admin.
3. Membuat pelanggan baru dan mencegah data wajib kosong.
4. Membuat pesanan dengan satu dan beberapa item.
5. Kuantitas nol/negatif ditolak.
6. Harga dimanipulasi dari browser tidak mengubah hasil perhitungan server.
7. Diskon atau biaya tambahan tidak valid ditolak.
8. Nomor transaksi tetap unik.
9. Pembayaran sebagian, pelunasan, dan status saldo.
10. Pembayaran melebihi sisa tagihan ditolak sesuai aturan.
11. Pembatalan transaksi tercatat dan tidak menghilangkan histori.
12. Perubahan harga master tidak mengubah transaksi lama.
13. Riwayat status menyimpan aktor dan waktu.
14. Filter tanggal/status dan laporan menghasilkan data yang sesuai.
15. Nota cetak menampilkan data yang benar.
16. Fitur inti tetap berjalan saat internet dimatikan, selama server dan database lokal berjalan.
17. Proteksi CSRF dan otorisasi diuji pada endpoint sensitif.
18. Tampilan diuji pada desktop, tablet, dan layar ponsel.

Gunakan PHPUnit atau Pest sesuai konfigurasi proyek. Jika tes tidak bisa dijalankan karena lingkungan, tuliskan penyebab spesifik dan jangan menyatakan lulus.

---

## 13. Dokumentasi dan Hasil Akhir

Buat `README.md` yang menjelaskan:
- Prasyarat versi PHP, Composer, Node.js (hanya bila diperlukan untuk build), dan MySQL/MariaDB.
- Cara membuat database melalui phpMyAdmin.
- Cara menyiapkan `.env` tanpa membagikan kredensial.
- Perintah instalasi Composer dan build aset.
- Perintah migration dan seeder.
- Cara membuat/reset akun admin dengan aman.
- Cara menjalankan aplikasi lokal.
- Cara akses dari perangkat lain di LAN jika dibutuhkan.
- Cara backup dan restore database.
- Cara memperbarui aplikasi tanpa menghilangkan data.
- Troubleshooting error umum.
- Penjelasan bahwa aplikasi tidak menggunakan internet untuk fitur inti setelah pemasangan, tetapi server lokal harus tetap aktif.

Hasil akhir yang diminta:
1. Source code aplikasi Laravel 12 yang terimplementasi.
2. Migration, model, controller, request validation, policy/middleware, service, view, dan route yang diperlukan.
3. Seeder/factory untuk data demo.
4. Automated tests untuk alur inti.
5. `README.md` dan dokumentasi setup.
6. Ringkasan struktur folder dan keputusan teknis.
7. Laporan pengujian yang membedakan tes berhasil, gagal, dan belum dijalankan.
8. Daftar keterbatasan yang diketahui dan saran peningkatan berikutnya.

---

## 14. Urutan Implementasi yang Disarankan

### Tahap 1 — Audit dan perencanaan
- Periksa lingkungan dan folder proyek.
- Backup jika proyek sudah ada.
- Periksa versi PHP, Composer, Laravel, Node.js, dan MySQL.
- Susun daftar tugas dan struktur folder.

### Tahap 2 — Fondasi
- Siapkan Laravel 12 dan konfigurasi database.
- Buat migration, model, relasi, factory, dan seeder.
- Siapkan autentikasi, role, middleware/policy, dan layout dasar.

### Tahap 3 — Desain UI
- Buat design tokens warna, tipografi, spacing, komponen kartu Glassmorphism, form, tabel, badge status, modal, dan notifikasi.
- Implementasikan sidebar, topbar, login, dashboard, dan responsivitas.
- Pastikan aset tidak bergantung pada CDN.

### Tahap 4 — Fitur operasional
- Pelanggan.
- Master layanan dan harga.
- Pembuatan transaksi beserta item.
- Riwayat dan perubahan status.
- Pembayaran manual.
- Nota cetak.

### Tahap 5 — Laporan dan administrasi
- Filter transaksi.
- Laporan dan ekspor CSV.
- Pengaturan usaha.
- Manajemen pengguna.
- Audit log.

### Tahap 6 — QA dan penyelesaian
- Jalankan migration fresh di database uji.
- Jalankan automated tests.
- Uji alur manual dari pembuatan pesanan sampai pengambilan.
- Periksa keamanan, responsivitas, dan mode offline.
- Perbaiki error, lengkapi README, dan laporkan status akhir secara jujur.

---

## 15. Instruksi Output dari Gemini 3.8 Flash High

Mulailah dengan:
1. Menjelaskan asumsi teknis dan risiko utama secara singkat.
2. Menampilkan rencana implementasi dan struktur folder.
3. Menyusun urutan migration dan relasi database.
4. Setelah itu, implementasikan proyek pada file yang sesuai, bukan hanya menampilkan potongan kode di chat.

Jika bekerja di lingkungan yang dapat mengedit file, edit file proyek secara langsung. Jika hanya dapat memberikan teks, keluarkan file lengkap secara bertahap dengan nama path yang jelas. Jangan menghilangkan bagian penting dengan placeholder seperti “implementasikan sisanya sendiri”.

Prioritas tertinggi adalah **ketepatan perhitungan transaksi, integritas data, alur kerja kasir, keamanan dasar, tampilan yang mudah digunakan, dan kemampuan offline**. Tampilan visual tidak boleh mengorbankan fungsi atau keterbacaan.
