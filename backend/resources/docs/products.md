# Buku Panduan Pengguna: Master Produk & Barang

Menu **Master Produk** adalah pusat data utama (*single source of truth*) untuk seluruh barang yang diperjualbelikan di SM Inventory ERP, baik barang fisik toko minimarket/grosir maupun produk digital (PPOB).

---

## 1. Ikhtisar & Fitur Utama

- **Identitas Unik Produk**: Kode SKU otomatis, nama produk lengkap, dan dukungan **Multi-Barcode** (bisa memiliki lebih dari satu barcode untuk satu barang yang sama).
- **Klasifikasi & Pemasok**: Pengelompokan Kategori, Supplier Utama, dan Sub-Divisi Supplier.
- **Retail Intelligence Hub**: Widget analitik terintegrasi untuk melihat tren penjualan, status kecepatan gerak barang (*Fast Moving / Slow Moving*), serta usia rak (*shelf age* 90 hari) dengan kode warna (Hijau, Kuning, Merah).
- **Perhitungan HPP & Pajak (PPN 11%)**: Kalkulasi otomatis DPP (Dasar Pengenaan Pajak), PPN 11% dinamis, dan HPP riil.
- **Multi-Golongan Harga Jual (3 Tingkat)**:
  - **Golongan 1 (Eceran)**: Untuk konsumen umum / pembelian eceran (Min Qty: 1).
  - **Golongan 2 (Semi-Grosir)**: Untuk pembelian jumlah tertentu dengan harga khusus.
  - **Golongan 3 (Grosir / Partai)**: Untuk reseller, warung mitra, atau pembelian karton besar.
- **Parameter Rantai Pasok (Supply Chain / DSD)**:
  - *Reorder Point (ROP)*: Batas minimal stok sebelum sistem menyarankan pemesanan ulang.
  - *Reorder Quantity (ROQ)*: Kuantitas standar saran pemesanan ulang ke supplier.
  - *Lead Time*: Estimasi hari pengiriman supplier sampai tiba di toko.
- **Stok & Harga Khusus Cabang**: Pengaturan stok fisik, batas minimum/maksimum, dan override harga jual khusus per cabang via tab **Data Stok Cabang**.
- **Aksi Massal (Bulk Actions)**: Cetak label barcode/rak, pindah supplier massal, ubah kategori massal, dan aktif/non-aktifkan produk sekaligus.

---

## 2. Struktur Formulir Produk (Tambah & Edit)

### A. Bagian Informasi Utama & Identitas
1. **Nama Produk**: Nama lengkap dagang barang (Wajib diisi, contoh: *Indomie Goreng Spesial 85g*).
2. **Kode SKU**: Dibuat otomatis oleh sistem dengan pola `SKU-dmy####` saat pertama kali disimpan. Pada mode edit, SKU bersifat terkunci (*read-only*) demi menjaga integritas kartu stok.
3. **Barcode Utama**: Barcode fisik kemasan yang akan discan oleh kasir.
4. **Barcode Tambahan (Multi-Barcode)**:
   - Terletak di sub-tabel metadata barcode.
   - Gunakan jika produk berganti kemasan dengan barcode baru, atau memiliki barcode karton/renceng berbeda namun stoknya sama.
   - Sistem memvalidasi secara ketat agar barcode tidak duplikat dengan produk lain.
5. **Tipe Produk**:
   - `Fisik`: Barang inventori nyata yang memiliki stok fisik di rak/gudang.
   - `Digital`: Produk pulsa, token PLN, e-wallet, atau paket data (terintegrasi dengan provider PPOB Digiflazz/AMA).
6. **Kategori & Supplier**:
   - Pilih Kategori barang.
   - Pilih Supplier utama pemasok barang.
   - Pilih Sub-Divisi supplier jika supplier memiliki beberapa divisi penagihan (misal: *Unilever - Foods* vs *Unilever - Home & Personal Care*).
7. **Satuan Dasar (UoM)**: Satuan terkecil penjualan, contoh: `PCS`, `BTL`, `BKS`, `DUS`, `PAK`.
8. **Berat (Gram)**: Berat produk dalam gram, digunakan untuk perhitungan ongkos kirim jika produk diaktifkan pada modul E-Commerce.

---

### B. Bagian HPP, Pajak & 3 Tingkat Harga Jual
SM Inventory menerapkan formula proteksi margin otomatis:

1. **Harga Beli / DPP (Dasar Pengenaan Pajak)**: Nilai beli neto sebelum pajak per satuan dasar.
2. **Kena PPN (Toggle `is_taxable`)**:
   - Jika diaktifkan: Sistem otomatis menghitung `PPN = DPP × 11%` dan `HPP Akhir = DPP + PPN`.
   - Jika dinonaktifkan: `HPP Akhir = DPP`.
3. **Target Margin & Harga Jual 3 Tingkat**:
   - **Tingkat 1 (Eceran)**:
     - Masukkan persentase *Margin Gol 1 (%)* -> Sistem menghitung otomatis *Harga Jual 1*.
     - Atau ketik langsung nominal *Harga Jual 1* -> Sistem menghitung persentase margin yang didapat.
     - *Harga Jual 1* akan otomatis menjadi harga standar pada kasir POS (`selling_price`).
   - **Tingkat 2 (Semi-Grosir)**:
     - Tentukan *Min Qty Gol 2* (misal: beli minimal 3 pcs).
     - Tentukan *Margin Gol 2 (%)* atau nominal *Harga Jual 2*.
   - **Tingkat 3 (Grosir/Partai)**:
     - Tentukan *Min Qty Gol 3* (misal: beli minimal 12 pcs / 1 dus).
     - Tentukan *Margin Gol 3 (%)* atau nominal *Harga Jual 3*.

> [!IMPORTANT]
> **Proteksi Anti-Rugi**: Sistem akan menolak penyimpanan jika nominal Harga Jual yang dimasukkan berada di bawah nilai HPP (Harga Pokok Pembelian).

---

### C. Bagian Parameter Pasokan & E-Commerce
1. **Reorder Point (Min Stok)**: Saat total stok fisik menyentuh atau berada di bawah angka ini, barang akan otomatis masuk ke daftar **Saran Order (Suggested PO)**.
2. **Reorder Qty**: Jumlah saran pemesanan yang direkomendasikan sistem saat membuat PO ke supplier.
3. **Status Aktif**: Toggle status barang. Jika dinonaktifkan, barang tidak akan muncul di kasir POS maupun daftar pencarian stok harian.
4. **Aktifkan E-Commerce**: Centang untuk mengekspos produk ke katalog toko online / aplikasi pesanan pelanggan.

---

## 3. Tab Relasi Stok Cabang (Stocks Relation Manager)

Saat membuka halaman **Edit Produk**, di bagian bawah terdapat tab **Stok Cabang**:
1. **Penetapan Harga Khusus Cabang**:
   - Jika suatu cabang memiliki ongkos kirim lebih tinggi atau berada di lokasi khusus, harga jual dapat di-*override* khusus untuk cabang tersebut tanpa mengubah harga cabang lain.
   - Mengatur nilai `Harga Jual 1`, `Harga Jual 2`, dan `Harga Jual 3` cabang.
2. **Batas Stok Cabang**:
   - Menetapkan *Min Qty*, *Max Qty*, dan *Target Days* konsumsi cabang.
3. **Riwayat Kartu Stok Cabang**:
   - Klik aksi pada baris cabang untuk melihat riwayat mutasi masuk/keluar barang (pembelian, penjualan POS, opname, dan transfer).

---

## 4. Panduan Operasional (Step-by-Step)

### Cara Menambah Produk Baru
1. Buka menu **Data Master > Produk**.
2. Klik tombol **Buat Produk** di pojok kanan atas.
3. Isi **Nama Produk**, scan/ketik **Barcode Utama**, pilih **Kategori** dan **Supplier**.
4. Masukkan **Harga Beli / DPP**.
5. Centang **Kena PPN** jika supplier menerbitkan faktur PPN.
6. Masukkan target margin atau nominal harga jual pada **Golongan 1, 2, dan 3**.
7. Tentukan **Reorder Point** dan **Satuan (UoM)**.
8. Klik tombol **Buat** atau **Buat & Tambah Lainnya**.
9. Sistem otomatis membuat stok awal bernilai 0 di cabang aktif.

### Cara Mencari Produk dengan Barcode Scanner
1. Di halaman tabel daftar produk, langsung arahkan kursor ke kolom pencarian (atau tekan `/`).
2. Scan barcode fisik kemasan dengan scanner.
3. Sistem menggunakan pencarian pintar (*exact match scanner first*, dilanjutkan Meilisearch / SQL) untuk langsung menampilkan produk yang cocok dalam hitungan milidetik.

### Cara Melakukan Aksi Massal (Bulk Actions)
1. Beri tanda centang pada kotak di samping produk-produk yang ingin dipilih (atau centang kotak paling atas untuk memilih seluruh baris).
2. Klik menu dropdown **Aksi Massal (Bulk Actions)** di atas tabel:
   - **Cetak Label Barcode / Price Tag**: Menyiapkan format cetak label harga rak dengan barcode siap tempel.
   - **Pindah Supplier Massal**: Memindahkan sekelompok produk ke supplier baru atau cabang target tertentu.
   - **Ubah Kategori Massal**: Menata ulang kategori produk sekaligus.
   - **Non-aktifkan Produk**: Menonaktifkan produk musiman atau diskontinu.

---

## 5. Pertanyaan Umum & Kendala (Troubleshooting)

**T: Kenapa harga jual cabang tidak berubah saat harga master produk diubah?**  
J: Jika cabang tersebut pernah diatur memiliki *harga khusus cabang* (override), maka sistem memprioritaskan harga cabang tersebut. Untuk mengembalikan ke harga master, buka tab *Stok Cabang* pada produk lalu sesuaikan harga cabang agar mengikuti master.

**T: Barcode gagal disimpan dan muncul peringatan duplikasi?**  
J: Sistem mencegah duplikasi barcode baik pada barcode utama maupun daftar multi-barcode di seluruh database. Periksa apakah produk tersebut sudah pernah diinput sebelumnya dengan mengetik barcode di kolom pencarian tabel.
