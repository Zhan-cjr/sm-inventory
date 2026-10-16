# Buku Panduan Pengguna: Kategori Produk

Menu **Kategori Produk** digunakan untuk menyusun taksonomi dan klasifikasi seluruh barang dagangan di SM Inventory ERP agar mudah dicari di kasir POS, tertata rapi dalam katalog e-commerce, serta terorganisir dalam pelaporan analitik bisnis.

---

## 1. Ikhtisar & Manfaat Struktur Kategori

Klasifikasi kategori yang teratur memberikan manfaat langsung pada operasional:
- **Pencarian Cepat di Layar Sentuh Kasir POS**: Kasir dapat memfilter produk berdasarkan kategori (misalnya: *Minuman Dingin*, *Rokok*, *Sembako*, *Snack*) saat barcode tidak terbaca.
- **Analisis Kinerja Departemen Barang**: Laporan penjualan dapat membandingkan kategori mana yang paling menguntungkan (*High Margin*) dan kategori mana yang perputarannya paling cepat (*Fast Turnover*).
- **Target Margin Kategori**: Membantu menetapkan standar batas margin keuntungan minimum saat staf mendaftarkan produk baru.

---

## 2. Struktur Formulir Kategori Produk

1. **Nama Kategori**: Nama kelompok barang (contoh: *Makanan Ringan / Snack*, *Minuman Ringan*, *Bumbu Dapur*, *Produk Susu & Olahan*).
2. **Kode Kategori**: Singkatan unik untuk kebutuhan pelabelan rak dan SKU (contoh: `CAT-SNCK`, `CAT-BVG`).
3. **Kategori Induk (Parent Category - Opsional)**:
   - Digunakan untuk membentuk sub-kategori bertingkat.
   - Contoh: Kategori Induk: *Minuman* $\rightarrow$ Sub-Kategori: *Kopi & Teh Siap Minum*, *Air Mineral*, *Jus Buah*.
4. **Ikon / Gambar Kategori**: Ikon visual yang akan tampil pada tombol layar POS atau etalase e-commerce.
5. **Urutan Tampilan (Sort Order)**: Angka urutan prioritas kategori pada antarmuka kasir (angka terkecil tampil paling depan).
6. **Status Aktif**: Mengaktifkan atau menyembunyikan seluruh kategori dari sistem kasir.

---

## 3. Langkah demi Langkah Pengelolaan (SOP)

### Cara Menambah Kategori Baru:
1. Buka menu **Data Master > Kategori Produk**.
2. Klik tombol **Buat Kategori Baru**.
3. Ketikkan **Nama Kategori** dan **Kode Kategori**.
4. Jika kategori ini merupakan bagian dari kelompok yang lebih besar, pilih **Kategori Induk**.
5. Unggah gambar/ikon penjelas jika diperlukan.
6. Klik **Simpan**.

### Cara Mengubah Kategori Banyak Produk Sekaligus:
Jika Anda baru saja merapikan struktur kategori dan ingin memindahkan banyak barang ke kategori baru:
1. Buka menu **Data Master > Produk**.
2. Beri centang pada produk-produk yang ingin dipindahkan.
3. Klik dropdown **Aksi Massal** di atas tabel dan pilih **Ubah Kategori Massal**.
4. Pilih kategori tujuan baru dan konfirmasi simpan.
