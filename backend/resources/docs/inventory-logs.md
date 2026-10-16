# Buku Panduan Pengguna: Kartu Stok & Riwayat Mutasi (Inventory Logs)

Menu **Kartu Stok (Inventory Logs)** adalah buku catatan audit terlengkap yang merekam setiap detik pergerakan keluar-masuk barang (*Stock Ledger / Audit Trail*) di seluruh cabang toko SM Inventory ERP.

---

## 1. Ikhtisar & Prinsip Kartu Stok

Dalam akuntansi persediaan yang benar, stok barang tidak boleh berubah tanpa adanya dokumen pemicu (*Trigger Document*). Setiap kali stok fisik bertambah atau berkurang, sistem secara otomatis mencatat 1 baris mutasi kartu stok yang memuat:
- **Waktu Presisi**: Tanggal, jam, menit, dan detik terjadinya mutasi.
- **Cabang**: Lokasi toko/gudang terjadinya pergerakan barang.
- **Produk**: SKU, barcode, dan nama barang.
- **Tipe Transaksi (Reference Type)**: Dokumen apa yang menyebabkan perubahan stok.
- **Perubahan Kuantitas (+ / -)**:
  - Nilai Positif (+): Barang Masuk.
  - Nilai Negatif (-): Barang Keluar.
- **Saldo Stok Sebelum & Sesudah (Before & After Balance)**: Membuktikan bahwa perhitungan matematika saldo stok tidak ada yang loncat atau hilang.
- **Pengguna Pelaksana (User Actor)**: Nama staf atau kasir yang mengeksekusi dokumen terkait.

---

## 2. Kode Referensi Mutasi di Kartu Stok

| Kode Transaksi | Sumber Menu | Arah Mutasi | Keterangan |
| :--- | :--- | :---: | :--- |
| **GOODS_RECEIPT** | Penerimaan Barang (GR) | **+ Masuk** | Kedatangan barang dari faktur pembelian supplier. |
| **POS_SALE** | Transaksi Kasir POS | **- Keluar** | Barang laku terjual kepada konsumen di meja kasir. |
| **POS_RETURN** | Retur Penjualan POS | **+ Masuk** | Barang dikembalikan oleh konsumen dan masuk rak kembali. |
| **PURCHASE_RETURN** | Retur Pembelian | **- Keluar** | Barang rusak/kadaluarsa dikembalikan ke supplier. |
| **TRANSFER_OUT** | Transfer Stok Antar Cabang | **- Keluar** | Barang diberangkatkan dari cabang asal ke cabang lain. |
| **TRANSFER_IN** | Transfer Stok Antar Cabang | **+ Masuk** | Barang kiriman dari cabang lain tiba dan diterima di toko. |
| **STOCK_ADJUSTMENT** | Penyesuaian Stok Manual | **+ / -** | Koreksi barang rusak, pecah, kadaluarsa, atau hilang. |
| **STOCK_OPNAME** | Sesi Stock Opname | **+ / -** | Penyesuaian otomatis hasil rekonsiliasi hitung fisik rak. |

---

## 3. Cara Melakukan Investigasi Selisih Stok

Jika supervisor toko mencurigai adanya kejanggalan pada stok suatu barang (misalnya fisik di rak tinggal 5 pcs namun sistem mencatat 12 pcs):
1. Buka menu **Persediaan > Kartu Stok / Riwayat Mutasi**.
2. Ketik barcode atau nama barang pada kolom filter pencarian.
3. Pilih **Cabang** tempat investigasi dilakukan.
4. Tentukan rentang tanggal (misal: 7 hari terakhir).
5. Amati kolom saldo:
   - Telusuri baris per baris dari saldo awal hingga saldo akhir.
   - Periksa apakah ada transaksi penjualan yang tidak wajar atau penyesuaian stok yang mencurigakan.
   - Klik nomor referensi transaksi untuk langsung membuka dokumen aslinya dan melihat nama staf pelaksana.
