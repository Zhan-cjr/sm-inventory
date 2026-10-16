# Buku Panduan Pengguna: Stock Opname (SO)

Menu **Stock Opname (SO)** mengatur seluruh siklus penghitungan fisik barang (*physical count*) di rak toko maupun gudang untuk mencocokkan saldo fisik nyata dengan saldo buku (*book stock*) di sistem, mendeteksi penyusutan (*shrinkage/loss*), dan melakukan koreksi persediaan resmi.

---

## 1. Arsitektur & Siklus Sesi Stock Opname

Proses Stock Opname di SM Inventory ERP terbagi menjadi 3 tingkatan terstruktur:

```
[1. Buat Sesi Opname] ──> [2. Hitung Fisik Per Rak] ──> [3. Rekonsiliasi & Finalisasi]
  (Freeze Saldo Buku)       (Scan Barcode di Rak)         (Auto Koreksi Stok & Jurnal)
```

1. **Sesi Opname (Header / Session)**:
   - Menetapkan tanggal opname, cabang pelaksanaan, dan ruang lingkup:
     - *Full Toko (Toko Penuh)*: Seluruh barang di toko/gudang dihitung serentak.
     - *Per Kategori / Departemen*: Opname parsial untuk kategori tertentu (misal: hanya produk susu atau rokok).
     - *Per Rak / Zona Tertentu*: Opname bergilir harian/mingguan (*Cycle Count*).
   - Saat sesi dibuka, sistem mengambil **Snapshot Saldo Buku (Freeze Quantity)** untuk menjadi patokan pembanding.
2. **Penghitungan Per Rak (Rack Counting)**:
   - Petugas menghitung fisik barang per rak/lorong secara independen menggunakan scanner barcode.
   - Satu rak dapat dihitung oleh petugas berbeda secara paralel.
3. **Rekonsiliasi & Finalisasi (Variance Review & Finalize)**:
   - Sistem menggabungkan seluruh hasil hitung dari setiap rak untuk tiap produk.
   - Menghitung **Selisih Unit (Variance Qty)**:  
     $$\text{Selisih Qty} = \text{Total Hitung Fisik} - \text{Saldo Buku Sistem}$$
   - Menghitung **Nilai Selisih Nominal (Rupiah)** berdasarkan HPP barang.
   - Saat disahkan (*Finalized*), sistem secara otomatis membuat transaksi penyesuaian stok (*Stock Adjustment*) dan mencatatnya ke kartu stok serta jurnal keuangan.

---

## 2. Langkah demi Langkah Pelaksanaan (Step-by-Step SOP)

### Langkah 1: Membuka Sesi Opname Baru
1. Buka menu **Persediaan > Sesi Stock Opname**.
2. Klik tombol **Buat Sesi Opname**.
3. Masukkan **Nama/Judul Sesi** (contoh: *SO Bulanan Oktober 2026 - Cabang Sariwangi*).
4. Pilih **Cabang Pelaksana**.
5. Tentukan **Cakupan Opname** (Semua Produk, Kategori Tertentu, atau Rak Tertentu).
6. Masukkan catatan instruksi atau tim pelaksana.
7. Klik **Buka Sesi (Start Session)**. Sistem akan merekam saldo buku pada detik tersebut.

### Langkah 2: Menginput Hasil Hitung Fisik di Rak
1. Buka sub-menu **Hitung Rak (Opname Racks)** atau klik tombol *Detail Rak* pada sesi yang aktif.
2. Tambahkan rak yang sedang dihitung (misal: *Rak A-01 Makanan Ringan*).
3. Arahkan kursor pada kolom **Scan Barcode**:
   - Setiap kali barcode barang discan, kuantitas fisik bertambah +1.
   - Anda juga dapat mengetik kuantitas secara langsung jika menghitung kartonan utuh (misal: scan barcode lalu ketik qty: 24).
4. Lanjutkan proses hitung untuk semua barang di rak tersebut sampai tuntas.
5. Simpan data hitung rak. Jika toko memiliki 20 rak, buat 20 data hitung rak terpisah agar pencarian barang yang terlewat menjadi sangat mudah ditelusuri.

### Langkah 3: Meninjau Selisih (Variance Analysis)
1. Buka kembali halaman **Detail Sesi Opname**.
2. Buka tab **Rekapitulasi Selisih (Variance Review)**.
3. Tabel akan menampilkan daftar seluruh produk lengkap dengan indikator warna:
   - **Warna Hijau (Match)**: Fisik = Sistem (Selisih 0).
   - **Warna Kuning (Surplus/Plus)**: Fisik > Sistem (Barang berlebih di rak).
   - **Warna Merah (Shortage/Minus)**: Fisik < Sistem (Barang kurang / potensi hilang).
4. Periksa barang-barang yang memiliki nilai selisih rupiah besar (*High-Value Variance*). Jika dirasa mencurigakan, tim dapat melakukan hitung ulang (*re-count*) khusus untuk rak tempat barang tersebut berada.

### Langkah 4: Finalisasi & Penyesuaian Saldo Sistem
1. Pastikan seluruh rak selesai dihitung dan tidak ada rak yang tertinggal.
2. Klik tombol **Finalisasi Sesi Opname (Finalize Session)** di kanan atas.
3. Sistem akan meminta konfirmasi otorisasi supervisor/manajer toko.
4. Setelah disahkan:
   - Status sesi berubah menjadi `FINALIZED` dan terkunci permanen.
   - Saldo stok fisik sistem langsung diperbarui mengikuti angka hitung fisik riil.
   - Kartu stok mencatat mutasi jenis `STOCK_OPNAME_ADJUSTMENT`.
   - Mengeluarkan dokumen Berita Acara Stock Opname (PDF) untuk ditandatangani oleh tim audit dan kepala toko.

---

## 3. Aturan Operasional Penting

> [!WARNING]
> **Aktivitas Penjualan Saat Opname**:
> Sangat disarankan melakukan Stock Opname saat toko sedang tutup (malam hari setelah EOD atau pagi hari sebelum toko buka). Jika terpaksa dilakukan saat jam operasional buka, transaksi penjualan yang terjadi selama opname harus diperhitungkan dengan teliti agar tidak menimbulkan selisih semu.

> [!TIP]
> **Pencegahan Barang Salah Letak**:
> Dengan metode hitung per rak (*Rack Counting*), jika produk yang sama tersebar di dua rak berbeda (misal di rak display dan di gudang atas), petugas tidak perlu mencari manual. Sistem otomatis menjumlahkan fisik dari kedua rak tersebut saat proses rekonsiliasi akhir.

---

## 4. Pertanyaan Umum (FAQ)

**T: Bagaimana jika ada barang di rak yang tidak memiliki barcode atau belum terdaftar di sistem?**  
J: Pisahkan barang tersebut terlebih dahulu ke keranjang khusus (*Quarantine Box*). Petugas admin harus mendaftarkan barang tersebut di *Master Produk* terlebih dahulu sebelum barcode dapat diinput ke dalam sesi hitung rak.

**T: Bisakah sesi opname dibatalkan jika terjadi kesalahan fatal sebelum finalisasi?**  
J: Ya, selama sesi belum di-*Finalize*, pengguna dengan level Manajer/Admin dapat memilih aksi **Batalkan Sesi (Cancel Session)**. Seluruh data hitung sementara akan diabaikan dan saldo stok sistem tidak akan mengalami perubahan apapun.
