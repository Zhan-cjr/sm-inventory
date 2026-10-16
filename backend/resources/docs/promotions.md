# Buku Panduan Pengguna: Promo Diskon, Voucher & Aturan Keranjang

Menu **Promosi & Voucher** digunakan oleh tim pemasaran (*Marketing*) dan manajer toko untuk merancang program stimulus penjualan, diskon harga coret, kupon voucher belanja, promo bundling beli X gratis Y, serta aturan rekomendasi belanja (*Market Basket Analysis*).

---

## 1. Jenis-Jenis Skema Promosi di SM Inventory

Sistem mendukung beragam variasi skema diskon ritel modern:

1. **Diskon Persentase (%)**:
   - Potongan harga dalam persen (misal: *Diskon 20% untuk semua biskuit kaleng jelang Lebaran*).
   - Dapat dibatasi dengan *Maksimal Potongan Diskon (Max Discount Cap)* dalam rupiah.
2. **Diskon Nominal Tetap (Rp)**:
   - Potongan rupiah langsung (misal: *Potongan Rp 5.000 untuk pembelian sabun cuci piring 800ml*).
3. **Harga Spesial / Harga Coret**:
   - Menetapkan harga jual promosi sementara yang menggantikan harga normal di kasir POS selama periode tertentu.
4. **Promo Pembelian Bersyarat (Buy X Get Y / Bundling)**:
   - Contoh: *Beli 2 Botol Sirup Gratis 1 Pcs Nata de Coco*, atau *Beli Minyak 2L + Gula 1kg hemat Rp 3.000*.
5. **Kupon Voucher Belanja (Vouchers)**:
   - Kode voucher unik (misal: `DISCOUNT25K`, `WEEKENDSERU`) yang diketik kasir atau dimasukkan saat belanja online dengan kuota penggunaan terbatas.

---

## 2. Struktur Formulir Pembuatan Promosi

1. **Nama Promo**: Nama kampanye promosi (contoh: *Promo Gajian Awal Bulan Oktober*).
2. **Tipe Promosi**: *Persentase*, *Nominal*, *Harga Khusus*, atau *Hadiah Barang*.
3. **Periode Berlaku**:
   - Tanggal & jam mulai aktif.
   - Tanggal & jam berakhir (promo otomatis non-aktif tepat saat jam berakhir lewat tanpa perlu dimatikan manual).
4. **Ruang Lingkup Produk**:
   - *Semua Produk* dalam kategori tertentu.
   - *Produk Pilihan Spesifik* (dipilih per SKU/barcode).
5. **Syarat Belanja Minimum (Min Spend)**: Nilai belanja keranjang minimal agar promo aktif.
6. **Batas Kuota Pemakaian**: Maksimal berapa kali promo boleh digunakan di seluruh toko.
7. **Khusus Member**: Toggle jika promosi hanya berhak dinikmati oleh konsumen yang memiliki kartu member terdaftar.
8. **Cabang Pelaksana**: Memilih apakah promo berlaku di semua cabang atau hanya cabang tertentu.

---

## 3. Aturan Keranjang Belanja (Market Basket Rules)

Melalui menu **Market Basket Rules**, sistem menerapkan algoritma kecerdasan buatan (*Association Rule Mining*) untuk menganalisis kebiasaan belanja pelanggan:
- Menemukan pola belanja: *"Jika konsumen membeli Susu Bayi, ada probabilitas 78% mereka juga membeli Tisu Basah"*.
- Kasir POS dapat memberikan saran cerdas (*Cross-Selling*) kepada konsumen di layar kasir untuk meningkatkan nilai rata-rata belanja toko (*Basket Size*).
