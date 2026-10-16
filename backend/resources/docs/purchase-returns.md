# Buku Panduan Pengguna: Retur Pembelian ke Supplier (Purchase Return)

Menu **Retur Pembelian (Purchase Return)** digunakan untuk mengelola pengembalian fisik barang dagangan kepada pihak distributor/supplier karena kondisi rusak (*bad stock*), mendekati kadaluarsa (*near-expired*), salah spesifikasi, atau pengembalian sisa barang uji coba listing.

---

## 1. Ikhtisar & Skema Kompensasi Retur

Setiap dokumen retur pembelian di SM Inventory secara otomatis memproses dua aspek:
1. **Aspek Persediaan**: Mengurangi kuantitas stok fisik dari gudang toko pada kartu stok dengan kode transaksi `PURCHASE_RETURN`.
2. **Aspek Keuangan**:
   - **Pemotongan Hutang (Nota Debet / Debit Note)**: Nilai total barang yang diretur otomatis mengurangi sisa hutang pada faktur GR asal atau menjadi pengurang pada dokumen Kontrabon berikutnya.
   - **Tukar Barang Baru (Replacement)**: Supplier mengganti dengan barang fisik baru yang utuh saat pengiriman berikutnya.
   - **Pengembalian Dana Tunai/Transfer (Refund)**: Supplier mengembalikan uang tunai jika faktur sudah terlanjur lunas.

---

## 2. Struktur Formulir Retur Pembelian

1. **Nomor Dokumen Retur**: Format otomatis (contoh: `RET-202610-0004`).
2. **Pilih Supplier**: Distributor tujuan pengembalian barang.
3. **Pilih Nomor Faktur GR (Opsional namun Direkomendasikan)**:
   - Menautkan retur ke faktur penerimaan aslinya agar sistem dapat memvalidasi harga beli awal barang tersebut.
4. **Tanggal Retur**: Tanggal fisik penyerahan barang ke pihak supplier.
5. **Cabang**: Lokasi toko/gudang tempat barang dikeluarkan.
6. **Tabel Item yang Diretur**:
   - **Produk**: Barcode dan nama produk.
   - **Alasan Pengembalian**: *Kadaluarsa (Expired)*, *Kemasan Rusak (Damaged)*, *Barang Cacat Pabrik (Defect)*, atau *Salah Kirim (Wrong Item)*.
   - **Kuantitas Retur (Qty)**: Jumlah unit yang dikembalikan.
   - **Harga Satuan Retur**: Otomatis terisi harga beli saat barang diterima.
   - **Subtotal Nilai Retur**: Akumulasi nilai klaim rupiah.

---

## 3. Langkah demi Langkah Pelaksanaan (SOP)

### Langkah 1: Pengumpulan & Verifikasi Barang Bad Stock
1. Kumpulkan seluruh barang cacat/kadaluarsa di area transit retur gudang (*Return Quarantine Area*).
2. Konfirmasikan jadwal penjemputan barang dengan sales distributor supplier.

### Langkah 2: Menginput Dokumen Retur di Sistem
1. Buka menu **Transaksi Pembelian > Retur Pembelian**.
2. Klik tombol **Buat Retur Pembelian**.
3. Pilih **Supplier** dan tautkan nomor **Penerimaan Barang (GR)** asalnya.
4. Tambahkan produk-produk yang hendak diserahkan beserta kuantitas dan alasannya.
5. Klik **Simpan**.

### Langkah 3: Cetak Surat Jalan Retur & Serah Terima Fisik
1. Klik tombol **Cetak Surat Jalan Retur (PDF)**.
2. Cetak rangkap 2:
   - **Lembar 1**: Diserahkan kepada sopir / ekspedisi supplier sebagai tanda terima barang.
   - **Lembar 2**: Wajib ditandatangani dan diberi nama terang oleh sopir penjemput, lalu disimpan oleh tim gudang toko sebagai bukti sah pengeluaran fisik.
3. Saat dokumen disimpan, stok fisik barang langsung berkurang dari sistem dan saldo hutang toko ke supplier tersebut berkurang senilai barang yang diretur.
