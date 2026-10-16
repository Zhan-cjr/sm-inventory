# Buku Panduan Pengguna: Listing Produk & Uji Coba Cabang (Pilot)

Menu **Listing Produk** mengatur proses penerimaan barang baru (*New Product Introduction - NPI*) yang diajukan oleh distributor/prinsipal untuk diuji coba di rak toko SM Inventory ERP, pengelolaan biaya sewa rak (*Listing Fee*), alokasi cabang percontohan (*Pilot Branch*), serta evaluasi kinerja berkala.

---

## 1. Ikhtisar & Alur Masa Uji Coba Produk Baru

Dalam bisnis ritel modern, tidak semua produk baru langsung disebarkan ke seluruh cabang. Toko menerapkan seleksi ketat untuk memaksimalkan efisiensi ruang rak (*space productivity*):

```
[Pengajuan Listing] ──> [Pembayaran Listing Fee] ──> [Uji Coba Cabang Pilot (90 Hari)] ──> [Evaluasi Performa]
                                                                                                  │
                                                                   ┌──────────────────────────────┴──────────┐
                                                                   ▼                                         ▼
                                                          [LULUS: Permanen Nasional]             [GAGAL: Retur & Stop]
```

1. **Pengajuan & Penagihan Listing Fee**:
   - Prinsipal membayar biaya pendaftaran/sewa ruang pajang perdana per SKU untuk komitmen promosi toko.
2. **Uji Coba Cabang Terbatas (Pilot Branches)**:
   - Barang baru hanya dipasok dan dipajang di cabang percontohan tertentu (misal: 1 atau 2 cabang dengan lalu lintas pengunjung tertinggi).
3. **Masa Uji Coba Standar (90 Hari)**:
   - Sistem memantau laju penjualan harian (*Sales Velocity*) dan perputaran stok barang tersebut selama periode evaluasi.
4. **Keputusan Komite Produk (Evaluation Outcome)**:
   - **Lulus Evaluasi**: Produk dipromosikan menjadi *Master Produk Permanen* dan dibuka pasokannya ke seluruh cabang toko.
   - **Gagal Evaluasi**: Produk dihentikan peredarannya, sisa stok diretur penuh ke supplier, dan kode produk dinonaktifkan.

---

## 2. Struktur Formulir Listing Produk

1. **Nama Produk Usulan**: Nama lengkap barang yang diajukan supplier.
2. **Supplier Pengusul**: Distributor yang mengajukan produk baru.
3. **Biaya Listing (Listing Fee)**: Besaran rupiah biaya masuk rak yang disepakati (misal: Rp 1.500.000 per SKU).
4. **Status Pembayaran Listing Fee**: *Pending (Belum Bayar)* atau *Paid (Lunas)*.
5. **Daftar Cabang Pilot (Pilihan Multi-Cabang)**:
   - Memilih cabang mana saja yang dijadikan lokasi uji coba pajang.
6. **Target Penjualan Minimum (Sales Target Qty)**: Target jumlah unit yang wajib terjual per bulan agar produk dinyatakan layak jual jangka panjang.
7. **Tanggal Mulai & Selesai Masa Percobaan**: Biasanya berlangsung selama 3 bulan (90 hari kalender).

---

## 3. Evaluasi Performa Listing (Halaman Performa Listing)

Melalui menu **Laporan / Analisis > Performa Listing**, tim merchandising dapat melihat dasbor evaluasi objektif:
- **Pencapaian Target (%)**: Persentase realisasi penjualan terhadap target unit yang disepakati.
- **Rata-Rata Penjualan Harian (ADS)** di cabang pilot.
- **Laba Kotor yang Dihasilkan**: Total kontribusi keuntungan bagi toko selama masa uji coba.
- **Rekomendasi Keputusan**: Tombol aksi langsung untuk *Angkat ke Master Produk Permanen* atau *Buat Dokumen Retur Pengembalian Barang*.
