# Daftar Batasan Sistem yang Diketahui (Known Limitations)

**Proyek:** LaundryDesk (RAP Laundry)  
**Dokumen Acuan:** `project/PRD.md`, `project/TASK.md`, `project/ENGINEERING_STANDARDS.md`  
**Versi:** 1.0 (Rilis Lokal Offline-First)  
**Status Evaluasi:** Terverifikasi dan Transparan  

---

## 1. Pendahuluan

Sesuai standar rekayasa perangkat lunak pada [ENGINEERING_STANDARDS.md](project/ENGINEERING_STANDARDS.md), dokumentasi sistem yang jujur dan profesional tidak boleh mengklaim aplikasi "sempurna tanpa celah" atau "tanpa keterbatasan". 

Dokumen ini mencatat batasan teknis, operasional, dan arsitektural yang berlaku pada implementasi **LaundryDesk (RAP Laundry)** versi saat ini agar pemilik usaha, kasir, dan pengelola sistem dapat mengantisipasi langkah mitigasi yang tepat.

---

## 2. Rincian Batasan Teknis dan Operasional

### 2.1 Ketergantungan pada Komputer Host Server Lokal
- **Kondisi:** LaundryDesk adalah aplikasi berbasis web yang di-host secara lokal di lingkungan komputer server toko laundry (menggunakan XAMPP/PHP/MariaDB).
- **Batasan:** Komputer server lokal wajib dalam kondisi menyala (*powered-on*) dan service Apache/MySQL harus berstatus aktif. Jika komputer server mati, masuk ke mode tidur (*sleep mode*), atau mati lampu tanpa cadangan daya (UPS), seluruh kasir di komputer lokal maupun jaringan LAN tidak dapat mengakses sistem.
- **Rekomendasi Mitigasi:** Gunakan perangkat Uninterruptible Power Supply (UPS) pada komputer server dan atur pengaturan daya Windows agar *Never Sleep* saat jam operasional toko.

### 2.2 Arsitektur 100% Offline (Tanpa Sinkronisasi Cloud)
- **Kondisi:** Sistem dirancang tanpa dependensi internet sedikit pun sesuai instruksi PRD.
- **Batasan:** Tidak ada fitur sinkronisasi otomatis ke cloud (*cloud backup sync*), tidak ada payment gateway online otomatis (seperti QRIS dinamis Midtrans/Xendit yang memerlukan webhook internet), dan tidak ada dashboard pemantauan jarak jauh via internet publik secara bawaan.
- **Rekomendasi Mitigasi:** Seluruh pencatatan pembayaran non-tunai (Transfer Bank / QRIS statis) dicatat secara manual oleh kasir setelah memeriksa mutasi rekening. Lakukan pencadangan database berkala ke flashdisk/drive eksternal fisik.

### 2.3 Pencetakan Nota Berbasis Dialog Cetak Peramban (Browser Print Engine)
- **Kondisi:** Nota thermal (58mm/80mm) dan faktur A4 dibangun menggunakan HTML semantik dan stylesheet CSS `@media print` murni.
- **Batasan:** Pencetakan nota memicu dialog cetak bawaan peramban (*browser print dialog* via `window.print()`). Sistem tidak mengirimkan perintah raw byte ESC/POS langsung ke port hardware printer secara native (karena peramban web memiliki *sandbox environment*). Driver printer thermal wajib terpasang dan dipilih di sistem operasi Windows.
- **Rekomendasi Mitigasi:** Pada browser (Chrome/Edge), centang opsi printer kasir default dan aktifkan opsi *Headers and Footers: Off* serta atur margin ke *None/Minimum* agar cetakan struk thermal presisi.

### 2.4 Akses Multi-Device Jaringan Lokal (LAN) Membutuhkan IP Statis
- **Kondisi:** Beberapa perangkat kasir dapat mengakses server melalui alamat IP lokal (misalnya: `http://192.168.1.100:8000`).
- **Batasan:** Sistem tidak memiliki mekanisme *Zero-Configuration Service Discovery (mDNS/Bonjour)*. Jika router Wi-Fi me-reset konfigurasi DHCP sewa dinamis, alamat IP komputer server dapat berubah, sehingga kasir perlu mengecek ulang IP server melalui `ipconfig`.
- **Rekomendasi Mitigasi:** Konfigurasikan IP Statis (*Static IP*) atau *DHCP IP Reservation* pada router toko untuk adapter jaringan komputer server.

### 2.5 Batasan Lingkup Mata Uang dan Format Finansial
- **Kondisi:** Sistem dioptimalkan secara spesifik untuk operasional usaha laundry di Indonesia.
- **Batasan:** Hanya mendukung mata uang tunggal Rupiah Indonesia (IDR) dengan zona waktu tunggal `Asia/Jakarta` (WIB). Tidak mendukung multi-currency atau konversi kurs valuta asing otomatis.
- **Rekomendasi Mitigasi:** Cukup dan ideal untuk target pasar laundry UMKM lokal di Indonesia.

### 2.6 Ketiadaan Fitur Pengiriman Notifikasi Otomatis (WhatsApp / SMS Gateway)
- **Kondisi:** Fitur WhatsApp otomatis dan SMS blast berada di luar cakupan PRD (*Out of Scope*) karena memerlukan langganan API gateway cloud berbayar dan koneksi internet stabil.
- **Batasan:** Kasir harus menghubungi atau mengirim pesan secara manual jika ingin mengabari pelanggan bahwa cucian telah selesai diambil.
- **Rekomendasi Mitigasi:** Nomor telepon pelanggan tertera dengan jelas pada halaman detail pesanan dan struk nota untuk memudahkan komunikasi langsung via ponsel kasir.

### 2.7 Skalabilitas Beban Konkurensi
- **Kondisi:** Menggunakan arsitektur server terintegrasi XAMPP lokal pada sistem operasi desktop Windows.
- **Batasan:** Arsitektur ini sangat ideal dan stabil untuk 1 hingga 5 kasir simultan dalam toko laundry fisik. Namun tidak dirancang untuk menangani ribuan permintaan konkuren tinggi per detik seperti pada platform e-commerce skala besar.
- **Rekomendasi Mitigasi:** Untuk ekspansi multi-cabang terpusat di masa depan, sistem dapat dimigrasikan ke server dedicated Linux Ubuntu dengan web server Nginx terpisah dan optimasi PHP-FPM.

### 2.8 Disiplin Pencadangan Data Manual
- **Kondisi:** Database tersimpan di mesin lokal `C:\xampp\mysql\data\rap_laundry`.
- **Batasan:** Jika terjadi kegagalan hardware hard disk (*drive failure*) atau infeksi ransomware pada komputer server tanpa adanya pencadangan rutin, data transaksi dapat hilang.
- **Rekomendasi Mitigasi:** Ikuti panduan [BACKUP_RESTORE.md](BACKUP_RESTORE.md) untuk melakukan ekspor backup database minimal seminggu sekali atau setiap tutup buku harian ke flashdisk fisik terpisah.

---

## 3. Rangkuman Risiko dan Tindakan Pemilik Usaha

| Potensi Risiko | Tingkat Dampak | Tindakan Pencegahan / Penanganan |
|---|---|---|
| Komputer server mati mendadak | Sedang | Pasang UPS, aktifkan pengaturan *auto-restart* service MySQL di Windows Services. |
| Perubahan IP host server pada router | Rendah | Tetapkan IP statis (misal `192.168.1.100`) pada adapter jaringan komputer server. |
| Hard disk komputer server rusak fisik | Tinggi | Rutin ekspor database ke drive eksternal flashdisk minimal 1x per minggu. |
| Operator tidak sengaja menghapus data | Rendah | Seluruh aksi mutasi sensitif dilindungi otorisasi peran Admin dan tercatat di Audit Log; soft delete aktif pada data pelanggan & layanan. |

---
Dokumen ini disusun sebagai panduan operasional nyata dan panduan pengembangan rilis berikutnya (*roadmap* masa depan).
