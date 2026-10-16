# Buku Panduan Pengguna: Pengecekan Fisik Gudang (Warehouse Check)

Menu **Pengecekan Fisik Gudang (Warehouse Check / Spot Check)** digunakan oleh supervisor gudang dan auditor internal untuk melakukan audit fisik cepat (*ad-hoc blind count*) terhadap sampel produk tertentu tanpa perlu membekukan operasional toko atau membuka sesi Stock Opname formal.

---

## 1. Ikhtisar & Tujuan Pengecekan Gudang

- **Audit Produk Berisiko Tinggi (High-Risk Items)**: Digunakan untuk memeriksa barang-barang yang rawan hilang atau memiliki nilai rupiah tinggi (misalnya: rokok, susu formula kaleng, kosmetik, minyak goreng, dan baterai).
- **Verifikasi Sebelum Mutasi / Penerimaan**: Memastikan kondisi kemasan, tanggal kadaluarsa (*expired date*), dan kesesuaian kuantitas fisik sebelum barang ditransfer ke cabang lain atau saat proses bongkar muat besar.
- **Deteksi Dini Selisih (Early Variance Detection)**: Mengetahui penyusutan barang lebih awal sehingga tidak menumpuk hingga akhir bulan saat jadwal Stock Opname resmi tiba.

---

## 2. Struktur Formulir & Prosedur Pemeriksaan

1. **Nomor Dokumen Cek**: Terbit otomatis (contoh: `WHC-202610-0003`).
2. **Tanggal Pemeriksaan**: Tanggal pelaksanaan audit fisik.
3. **Cabang & Lokasi Gudang**: Lokasi toko atau rak spesifik yang diperiksa.
4. **Petugas Pemeriksa**: Nama auditor / supervisor yang bertanggung jawab.
5. **Tabel Sampel Produk yang Diperiksa**:
   - **Produk**: Barcode dan nama barang.
   - **Kondisi Fisik Kemasan**: *Baik*, *Kemasan Rusak / Penyok*, atau *Segel Terbuka*.
   - **Tanggal Kadaluarsa (Expired Date)**: Mencatat tanggal kadaluarsa terdekat yang ditemukan di rak.
   - **Kuantitas Fisik Riil**: Jumlah unit nyata yang dihitung.
   - **Kuantitas Saldo Sistem**: Ditampilkan setelah fisik diinput untuk melihat selisih langsung.
   - **Catatan / Tindak Lanjut**: Rekomendasi (misal: *Barang harus segera dipajang di rak depan karena sisa masa kadaluarsa 3 bulan lagi*).

---

## 3. Alur Tindak Lanjut Temuan

- Jika hasil pengecekan menunjukkan **Kondisi Fisik Baik & Saldo Cocok**: Dokumen disimpan sebagai bukti audit berkala.
- Jika ditemukan **Kemasan Rusak / Kadaluarsa**: Segera buat dokumen **Penyesuaian Stok (Stock Adjustment)** dengan alasan *DMG* atau *EXP* untuk menarik barang dari rak penjualan.
- Jika ditemukan **Selisih Minus Signifikan**: Laporkan ke kepala toko untuk dilakukan investigasi rekaman CCTV kasir/gudang.
