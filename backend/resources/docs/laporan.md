# Buku Panduan Pengguna: Pusat Laporan, Analisis & Keuangan

Modul **Laporan & Analisis** di SM Inventory ERP menyediakan informasi menyeluruh bagi pemilik bisnis, manajer operasional, dan tim akuntansi untuk memantau performa penjualan, valuasi aset persediaan, profitabilitas laba kotor, efektivitas supplier, dan kesehatan arus kas secara akurat.

---

## 1. Klasifikasi Menu Laporan di SM Inventory

Sistem mengelompokkan laporan ke dalam 4 pilar utama:

### A. Pilar Penjualan & Kasir
1. **Laporan Penjualan Global (`admin/laporan-penjualan`)**:
   - Memantau total omset, total transaksi, rata-rata belanja per konsumen (*Basket Size*), dan tren penjualan harian/bulanan.
2. **Laporan Barang Dijual (`admin/laporan-barang-dijual`)**:
   - Rincian barang terlaris (*Top Selling Products*), kuantitas terjual, total omset, dan kontribusi profit tiap produk.
3. **Laporan Penjualan Per Kasir (`admin/laporan-penjualan-kasir`)**:
   - Mengukur produktivitas dan kecepatan kerja tiap staf kasir (jumlah struk yang dilayani, total rupiah yang dihimpun, dan rata-rata waktu per transaksi).
4. **Rekapitulasi Transaksi Pembayaran (`admin/rekapitulasi-transaksi`)**:
   - Rekap penerimaan uang per metode bayar: Tunai Fisik di Kasir, QRIS Dinamis/Statis, Mesin EDC Debit/Kredit, dan Transfer Bank untuk rekonsiliasi dengan rekening koran.
5. **Arsip Laporan EOD / Shift Kasir (`admin/arsip-laporan-eod`)**:
   - Kumpulan arsip struk rekap harian tutup kasir untuk audit fisik uang kas toko.

### B. Pilar Persediaan & Pergudangan
1. **Laporan Persediaan & Valuasi Aset (`admin/laporan-persediaan`)**:
   - Menghitung total kekayaan toko yang tertanam dalam bentuk barang:  
     $$\text{Nilai Aset Persediaan} = \sum (\text{Stok Fisik Cabang} \times \text{HPP Terakhir})$$
2. **Analisis Pergerakan Produk (`admin/analisis-pergerakan-produk`)**:
   - Mengklasifikasikan produk menjadi:
     - **Fast Moving**: Perputaran sangat cepat, omset tinggi (prioritaskan ketersediaan stok).
     - **Medium Moving**: Perputaran normal/stabil.
     - **Slow Moving**: Perputaran lambat (waspada potensi kadaluarsa / overstock).
     - **Dead Stock**: Tidak ada penjualan sama sekali dalam 90 hari terakhir.
3. **Laporan Stok Opname (Ringkas & Detail)**:
   - Rekapitulasi hasil audit berkala, mendokumentasikan nilai selisih plus/minus stok dan kerugian penyusutan barang.

### C. Pilar Pembelian & Evaluasi Supplier
1. **Laporan Barang Dibeli (`admin/laporan-barang-dibeli`)**:
   - Rekap seluruh pembelian barang masuk per periode, per supplier, dan per kategori.
2. **Laporan Service Level Supplier (`admin/laporan-service-level-supplier`)**:
   - Mengukur keandalan dan profesionalisme distributor:
     - *On-Time Delivery Rate*: Ketepatan waktu pengiriman barang sesuai tanggal janji PO.
     - *Fill Rate (Fulfillment Rate)*: Persentase kelengkapan barang yang dikirim dibandingkan dengan jumlah yang dipesan di PO.
3. **Laporan Rekap Tipe Supplier**:
   - Memisahkan volume belanja supplier Reguler (Beli Putih/Kredit) vs Supplier Konsinyasi.

### D. Pilar Keuangan, HPP & Laba Rugi
1. **Laporan HPP (Harga Pokok Penjualan)**:
   - Melacak HPP riil dari setiap barang yang terjual dan pergerakan kenaikan harga beli dari distributor.
2. **Laporan Laba Rugi Komprehensif (`admin/laporan-laba-rugi`)**:
   - Ringkasan performa finansial bisnis:
     - **Penjualan Kotor (Gross Sales)**
     - *(-) Retur & Diskon Penjualan*
     - **Penjualan Bersih (Net Sales)**
     - *(-) Harga Pokok Penjualan (HPP / COGS)*
     - **Laba Kotor (Gross Profit)**
     - *(-) Beban Operasional (Gaji, Listrik, Sewa, Kerusakan Barang)*
     - **Laba Bersih Operasional (Net Operating Income)**

---

## 2. Cara Menggunakan Filter & Ekspor Data

Setiap halaman laporan dilengkapi bilah filter pintar (*Smart Filter Toolbar*):
1. **Rentang Tanggal (Date Range)**:
   - Pilih preset instan: *Hari Ini*, *Kemarin*, *7 Hari Terakhir*, *Bulan Ini*, *Bulan Lalu*, atau tentukan tanggal spesifik (*Custom Range*).
2. **Pilihan Cabang**:
   - Pemilik/Pimpinan dapat memilih *Semua Cabang (Konsolidasi)* untuk melihat gambaran bisnis secara makro atau memilih *Cabang Tertentu* untuk evaluasi mikro.
3. **Filter Kategori & Supplier**:
   - Menyaring laporan hanya untuk departemen barang atau distributor tertentu.
4. **Tombol Ekspor (Export to Excel / PDF)**:
   - Klik tombol **Ekspor Excel** untuk mengunduh data mentah ke dalam format `.xlsx` yang siap diolah lebih lanjut dengan pivot table.
   - Klik tombol **Cetak / Ekspor PDF** untuk menghasilkan laporan formal siap cetak untuk rapat manajemen.

---

## 3. Pertanyaan Umum (FAQ)

**T: Kenapa laporan laba rugi menunjukkan angka minus pada hari tertentu?**  
J: Hal ini biasanya terjadi jika pada hari tersebut toko mencatat biaya operasional besar (misal pembayaran tagihan listrik bulanan atau biaya sewa) sementara omset harian belum menutupi biaya tersebut. Periksa laporan dalam rentang *Bulan Berjalan* untuk melihat performa yang lebih berimbang.

**T: Bagaimana cara melihat barang yang paling sering hilang?**  
J: Buka menu **Laporan Stok Opname Detail**, lalu urutkan kolom *Selisih Qty* dari nilai minus terkecil. Barang-barang dengan angka selisih minus tertinggi adalah barang yang paling sering mengalami penyusutan/hilang.
