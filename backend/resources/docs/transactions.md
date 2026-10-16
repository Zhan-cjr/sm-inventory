# Buku Panduan Pengguna: Riwayat Transaksi & Arsip Struk Penjualan

Menu **Riwayat Transaksi (Transactions)** dan **Arsip Transaksi** menyediakan buku catatan lengkap untuk seluruh bukti struk penjualan yang telah terjadi di seluruh terminal kasir POS maupun pesanan e-commerce di SM Inventory ERP.

---

## 1. Ikhtisar & Fitur Pelacakan Struk

- **Pencarian Cepat Struk**: Cari transaksi berdasarkan **Nomor Struk**, tanggal belanja, nama kasir pelaksana, atau nomor telepon pelanggan.
- **Rincian Transaksi Lengkap**:
  - Jam presisi cetak struk.
  - Nama kasir bertugas dan nomor mesin kassa (*POS Terminal*).
  - Metode pembayaran yang digunakan (Tunai, QRIS, Kartu Debit, atau Split Payment gabungan).
  - Rincian item barang, harga saat transaksi, diskon promosi, dan nomor batch/barcode.
- **Cetak Ulang Struk (Reprint Receipt)**:
  - Mencetak ulang salinan struk belanja jika kertas struk pelanggan hilang atau printer kasir tadi sempat macet.
- **Pembatalan Transaksi Pasca-Cetak (Void / Retur Penjualan)**:
  - Pembatalan transaksi resmi dengan verifikasi supervisor toko yang otomatis mengembalikan saldo stok ke rak dan mencatat kas keluar pengembalian uang (*Refund*).
- **Pengarsipan Transaksi Berkala (Arsip Transaksi)**:
  - Data transaksi lama (misal: di atas 1 tahun) dapat dipindahkan ke tabel arsip untuk menjaga database operasional kasir tetap ringan dan responsif tanpa menghapus bukti riwayat keuangan.

---

## 2. Langkah demi Langkah Operasional (SOP)

### Cara Mencetak Ulang Struk Konsumen:
1. Buka menu **Penjualan > Riwayat Transaksi**.
2. Masukkan nomor struk yang tertera di bukti pembayaran atau cari berdasarkan jam belanja dan cabang.
3. Klik tombol **Lihat Detail (View)** pada baris transaksi bersangkutan.
4. Klik tombol **Cetak Ulang Struk (Reprint)**. Struk akan dicetak ulang dengan tanda air *(Salinan / Copy)* untuk mencegah penyalahgunaan.

### Cara Melakukan Retur Penjualan Konsumen:
1. Konsumen datang membawa barang cacat beserta struk fisik asli.
2. Cari nomor transaksi di menu **Riwayat Transaksi**.
3. Klik aksi **Proses Retur Penjualan**.
4. Pilih barang yang diretur dan kuantitasnya.
5. Pilih bentuk pengembalian: *Tukar Barang Serupa* atau *Kembalikan Uang Tunai (Refund)*.
6. Masukkan PIN otorisasi Supervisor.
7. Simpan dokumen retur. Sistem secara otomatis menambah kembali stok barang ke kartu stok dan memotong omset kasir terkait.
