# Buku Panduan Pengguna: Pengaturan POS (General, Tombol, Struk & Timbangan)

Menu **Pengaturan POS (`admin/pos-settings`)** adalah pusat konfigurasi operasional aplikasi kasir SM Inventory ERP yang mencakup 4 pilar pengaturan: Pengaturan Umum (*General Setting*), Penyesuaian Pintasan Tombol (*Keyboard Shortcuts*), Tata Letak Struk Belanja (*Receipt Layout*), dan Integrasi Timbangan Digital.

---

## 1. Tab General Setting (Pengaturan Umum Kasir)

1. **Pelanggan Default (Default Customer)**:
   - Menetapkan akun pelanggan standar untuk transaksi kasir tunai eceran (*walk-in customer / Umum*).
2. **Metode Pembayaran Aktif**:
   - Menentukan opsi pembayaran yang muncul di kasir: Tunai, QRIS, Kartu Debit, Kartu Kredit, Split Payment.
3. **Pemberitahuan Stok Menipis di Layar Kasir**:
   - Menampilkan peringatan visual jika barang yang discan kasir stoknya tinggal sedikit.

---

## 2. Tab Pengaturan Tombol (Pintasan Keyboard Dinamis)

Tab ini memberikan kebebasan penuh bagi Anda untuk menyesuaikan seluruh tombol pintasan keyboard (*Keyboard Shortcuts*) pada aplikasi kasir agar cocok dengan keyboard kasir toko Anda:

### Struktur Tabel Tombol Kasir (23 Fungsi POS):
- **Fungsi POS (`key_name`)**: Kode sistem internal fungsi kasir (misal: `btn_subtotal`, `btn_tunai`, `btn_qty`, `btn_disc_item_rp`, dll.).
- **Label Tombol (`display_name`)**: Teks nama tombol yang muncul pada layar kasir (dapat diubah sesuai istilah toko Anda).
- **Shortcut (Klik & Tekan)**: Tombol keyboard fisik yang menjalankan fungsi tersebut.
- **Toggle Aktif (`is_active`)**: Mengaktifkan atau menonaktifkan fungsi/tombol tersebut dari layar kasir.

### Cara Mengubah Tombol Pintasan (Sangat Mudah):
1. Buka menu **Pengaturan POS > Ubah Pengaturan POS**.
2. Klik tab **Pengaturan Tombol** (ikon kalkulator).
3. Cari fungsi tombol yang ingin diubah (contoh: ingin tombol Subtotal menjadi `F9` bukan `-`).
4. Klik langsung pada kotak **Shortcut (Klik & Tekan)**.
5. **Tekan tombol keyboard yang Anda inginkan** (misal tekan tombol <kbd>F9</kbd>).
6. Kotak seketika mencatat tombol yang Anda tekan.
7. Jika ingin menghapus shortcut, tekan tombol <kbd>Backspace</kbd>.
8. Klik tombol **Simpan Perubahan** di bagian bawah halaman. Pengaturan baru seketika aktif di kasir POS!

---

## 3. Tab Pengaturan Struk (Layout Cetak Printer Thermal)

Mengatur format cetakan kertas pada printer kasir thermal per cabang:
1. **Pilih Lokasi Toko / Cabang**: Memilih cabang yang ingin diatur struknya.
2. **Tampilkan Logo Toko**: Menampilkan logo grafik di bagian paling atas struk.
3. **Baris Header Struk (Line 1 s/d Line 4)**:
   - Nama toko (bisa dicetak tebal / bold).
   - Alamat lengkap cabang toko.
   - Nomor telepon / WhatsApp layanan konsumen toko.
   - Nomor NPWP / Izin Usaha Ritel.
4. **Pengaturan Pajak (PPN)**:
   - Menampilkan rincian DPP dan nilai PPN 11% yang sudah termasuk dalam harga barang.
5. **Baris Footer Struk (Line 1 s/d Line 6)**:
   - Kebijakan penukaran barang (misal: *"Barang yang sudah dibeli dapat ditukar maksimal 2x24 jam dengan membawa struk ini"*).
   - Akun media sosial toko (Instagram / TikTok / Facebook).
   - Kalimat ucapan terima kasih (*"Terima kasih atas kunjungan Anda"*).

---

## 4. Tab Pengaturan Timbangan (Digital Scale)

Untuk toko buah, sayur, daging, atau sembako curah yang menggunakan timbangan digital:
- Pengaturan awalan kode barcode timbangan (*Prefix Barcode*).
- Pola pembacaan barcode timbangan (Kode Barang + Berat Gram / Harga).
