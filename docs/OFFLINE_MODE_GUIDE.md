# Panduan Arsitektur & Verifikasi Mode Offline (100% Offline-First)
**LaundryDesk (RAP Laundry)**  
*Dokumen Rujukan: `project/PRD.md` (Bagian 1.4 & 4.2), `project/TASK.md` (Task 12.4), `project/ENGINEERING_STANDARDS.md`*

---

## 1. Prinsip Utama Arsitektur Offline-First

LaundryDesk dirancang khusus untuk operasional usaha laundry pakaian harian yang **dapat berjalan sepenuhnya tanpa sambungan internet (Zero External Internet Dependency)**. Sistem tidak boleh mengalami kegagalan, kelambatan rendering, layout shift, atau request tertahan (*hanging requests*) ketika koneksi internet publik (WAN) terputus atau tidak tersedia.

### Karakteristik Desain:
1. **Nol CDN (Zero CDN):** Tidak ada skrip JavaScript, stylesheet CSS, font, atau pustaka dari penyedia CDN eksternal (seperti `fonts.googleapis.com`, `cdnjs`, `unpkg`, `jsdelivr`, atau `bootstrapcdn`).
2. **Tipografi Lokal:** Font sistem interaktif dilayani dari file lokal di direktori `public/fonts/` atau memanfaatkan *system-ui native font stacks*.
3. **Ikon Mandiri:** Semua ikon antarmuka menggunakan SVG *inline* yang disematkan langsung di dalam template Blade tanpa memanggil pustaka webfont eksternal (seperti FontAwesome CDN).
4. **CSS & JS Lokal Murni:** Seluruh file stylesheet (`public/css/app.css`, `public/css/print.css`) dan skrip utilitas disimpan di direktori lokal server.
5. **Pencetakan Nota Mandiri:** Fitur cetak nota (thermal roll 58mm/80mm dan kertas A4) sepenuhnya menggunakan fitur dialog cetak peramban bawaan (`window.print()`) dan CSS `@media print` tanpa layanan pihak ketiga.

---

## 2. Hasil Verifikasi Pengujian Mode Offline

Pengujian simulasi pemutusan jaringan telah diverifikasi baik secara otomatis (*automated test*) maupun simulatif peramban:

| Pengujian | Skenario Uji | Hasil Verifikasi |
| :--- | :--- | :--- |
| **Audit Kode Sumber Blade** | Pemindaian ekspresi reguler pada seluruh file di `resources/views/` terhadap URL eksternal / CDN | **LULUS (100% Bersih)** — Tidak ditemukan dependensi CDN atau skrip awan eksternal. |
| **Audit Stylesheet CSS** | Pemindaian file di `public/css/` terhadap `@import` atau URL font eksternal | **LULUS (100% Bersih)** — Semua aset terisolasi secara lokal. |
| **Login Kasir Saat Offline** | Membuka halaman `/login` dan melakukan autentikasi saat WAN terputus | **LULUS** — Berhasil masuk ke dashboard tanpa kendala waktu tunggu. |
| **Transaksi Pesanan Baru** | Kasir membuat pesanan baru multi-layanan (kiloan dan satuan) | **LULUS** — Data tersimpan langsung ke MariaDB lokal, nomor faktur unik terbit instan. |
| **Pencatatan Pembayaran** | Kasir mencatat pembayaran tunai dan pembayaran sebagian | **LULUS** — Mutasi dan status pembayaran terbarui secara atomik. |
| **Transisi Status Cucian** | Mengubah status: Diterima $\rightarrow$ Dicuci $\rightarrow$ Siap Diambil | **LULUS** — Riwayat kronologis tersimpan di tabel `order_status_histories`. |
| **Cetak Nota Kasir** | Membuka rute cetak `/orders/{order}/print` (format Thermal & A4) | **LULUS** — Tampilan nota ter-render seketika tanpa request eksternal di panel Network. |
| **Laporan & Audit Log** | Admin membuka analitik keuangan dan jejak audit aktivitas | **LULUS** — Seluruh metrik teragregasi langsung dari database lokal. |

---

## 3. Batasan dan Prasyarat Operasional Lokal (*System Constraints*)

Meskipun aplikasi tidak memerlukan koneksi internet, aplikasi tetap merupakan sistem klien-server berbasis web lokal (*intranet web application*). Oleh karena itu, batasan operasional berikut **wajib dipahami dan dipenuhi**:

> [!IMPORTANT]
> ### Batasan 1: Komputer Server dan Database Lokal Wajib Aktif
> - **Komputer Host Utama (Server):** Komputer yang bertindak sebagai server (tempat XAMPP terpasang) wajib selalu dalam keadaan menyala selama jam operasional laundry.
> - **Layanan Apache & MySQL/MariaDB:** Modul **Apache** dan **MySQL** pada XAMPP Control Panel wajib berada dalam status **Running (Hijau)**.
> - Jika komputer server dimatikan, tertidur (*sleep mode*), atau layanan MySQL berhenti, aplikasi tidak dapat diakses baik dari komputer server itu sendiri maupun dari komputer kasir lain di jaringan lokal.

> [!NOTE]
> ### Batasan 2: Topologi Akses Jaringan Lokal (Multi-Device / LAN)
> - **Akses Single-PC (Standalone):** Jika operasional laundry hanya menggunakan satu komputer kasir, aplikasi diakses melalui alamat `http://localhost/rap-laundry/public` atau `http://127.0.0.1:8000`.
> - **Akses Multi-PC / Tablet Kasir (LAN):** Jika kasir menggunakan perangkat tambahan (laptop kasir atau tablet di meja kasir berbeda):
>   1. Perangkat server dan perangkat kasir harus terhubung ke jaringan lokal yang sama (misalnya melalui kabel LAN atau router Wi-Fi lokal toko laundry).
>   2. Koneksi internet pada router Wi-Fi tidak diperlukan; cukup jaringan lokal (*Local Area Network*) yang berfungsi untuk saling berkirim paket data IP.
>   3. Kasir mengakses aplikasi menggunakan alamat IP lokal server (contoh: `http://192.168.1.100:8000`).
>   4. Pastikan Windows Firewall pada komputer server mengizinkan koneksi masuk (*Inbound Rules*) pada port aplikasi (port 80 atau 8000).

---

## 4. Prosedur Uji Mandiri Mode Offline oleh Pengguna/Teknisi

Untuk membuktikan secara mandiri bahwa sistem siap offline:
1. Cabut kabel LAN internet atau putuskan sambungan Wi-Fi internet pada komputer.
2. Pastikan XAMPP Apache dan MySQL tetap menyala.
3. Buka peramban Google Chrome atau Microsoft Edge, tekan tombol `F12` untuk membuka **Developer Tools**, lalu pilih tab **Network**.
4. Akses `http://localhost:8000/orders/create` atau rute nota `/orders/{id}/print`.
5. Perhatikan daftar request di tab Network:
   - Seluruh request berstatus `200 OK` dengan domain lokal (`localhost`).
   - Tidak ada baris request berwarna merah atau berstatus `(failed)` / `(pending)`.
   - Tidak ada request yang mengarah ke luar jaringan lokal.
