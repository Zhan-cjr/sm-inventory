# Buku Panduan Pengguna: Terminal Kasir & Perangkat POS (Hardware)

Menu **Terminal & Perangkat POS** mengatur pendaftaran mesin kasir fisik (*Hardware Kassa*), printer struk termal, laci kasir otomatis (*cash drawer*), dan layar pelanggan (*Customer Display*) di SM Inventory ERP.

---

## 1. Ikhtisar & Arsitektur Hardware POS

Sistem POS SM Inventory mendukung integrasi perangkat keras kasir standar ritel:
- **Terminal Kassa**: Identitas unik komputer kasir (contoh: *Kassa 1 - Pintu Masuk*, *Kassa 2 - Express*, *Kassa Grosir*).
- **Printer Struk Termal (ESC/POS)**: Dukungan printer thermal USB, LAN/Ethernet, maupun Bluetooth dengan ukuran kertas standar **58mm** atau **80mm**.
- **Laci Uang Kasir (Cash Drawer)**: Terhubung ke printer via kabel RJ11 untuk otomatis terbuka (*kick pulse*) setiap kali transaksi tunai selesai.
- **Teks Header & Footer Struk**: Pengaturan nama toko, alamat, nomor telepon izin usaha, ucapan terima kasih, dan syarat penukaran barang.

---

## 2. Langkah Konfigurasi Perangkat Kassa

### A. Mendaftarkan Terminal Kasir Baru
1. Buka menu **Pengaturan POS > Terminal**.
2. Klik tombol **Buat Terminal Baru**.
3. Beri nama terminal (misal: *Kassa 1 Sariwangi*).
4. Pilih **Cabang** lokasi mesin kassa berada.
5. Simpan data. Terminal ini sekarang akan muncul di daftar pilihan saat kasir hendak membuka shift kerja.

### B. Menyesuaikan Pengaturan Struk Kasir
1. Buka menu **Pengaturan POS > Pengaturan POS (POS Settings)**.
2. Tentukan **Lebar Kertas Struk**:
   - `58mm` (biasanya printer mini / portable).
   - `80mm` (printer kasir standar ritel dengan kapasitas tulisan lebih lebar).
3. Atur **Header Struk**:
   - Ketikkan slogan toko atau informasi NPWP/Izin Toko.
4. Atur **Footer Struk**:
   - Contoh: *"Barang yang sudah dibeli dapat ditukar maksimal 2 hari dengan membawa struk ini. Terima kasih atas kunjungan Anda!"*.
5. Centang opsi **Buka Laci Otomatis (Auto Open Drawer)** jika laci uang kasir terhubung ke printer.
6. Simpan konfigurasi. Pengaturan langsung diterapkan ke seluruh transaksi kasir di cabang tersebut.
