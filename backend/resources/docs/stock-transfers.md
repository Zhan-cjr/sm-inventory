# Buku Panduan Pengguna: Transfer Stok Antar Cabang

Menu **Transfer Stok (Stock Transfer)** mengatur mutasi persediaan barang antar cabang atau dari gudang pusat ke cabang toko di lingkungan SM Inventory ERP, lengkap dengan pelacakan status dalam perjalanan (*in-transit*), surat jalan resmi, dan algoritma saran transfer otomatis.

---

## 1. Ikhtisar & Alur Status Transfer

Transfer stok tidak memindahkan barang seketika secara gaib, melainkan mengikuti alur fisik pengiriman nyata:

```
[DRAFT] ──> [IN TRANSIT] ──> [RECEIVED] (Selesai)
   │               │
   └───────────────┴─────────> [CANCELLED / REJECTED]
```

1. **DRAFT**: Dokumen permintaan/rencana kirim dibuat. Stok di kedua cabang belum berubah.
2. **IN TRANSIT (Dalam Perjalanan)**:
   - Barang fisik telah dimuat ke armada angkutan/kendaraan dan keluar dari gudang cabang asal.
   - **Dampak Stok**: Saldo stok fisik di Cabang Asal langsung berkurang. Namun, saldo di Cabang Tujuan belum bertambah, melainkan tercatat sebagai aset persediaan *Dalam Perjalanan (In-Transit Inventory)*.
3. **RECEIVED (Diterima)**:
   - Barang fisik tiba di toko cabang tujuan dan dihitung oleh staf gudang penerima.
   - **Dampak Stok**: Saldo stok fisik di Cabang Tujuan resmi bertambah pada kartu stok. Status dokumen ditandai tuntas (*Completed*).
4. **REJECTED (Ditolak Sebagian/Seluruhnya)**:
   - Jika barang rusak di perjalanan atau salah kirim, cabang tujuan dapat mencatat kuantitas yang ditolak untuk dikembalikan ke cabang asal.

---

## 2. Fitur Saran Transfer Cerdas (Suggested Stock Transfers)

SM Inventory ERP memiliki modul canggih **Saran Transfer Stok** yang menganalisis ketidakseimbangan stok antar cabang secara otomatis:

### Cara Kerja Algoritma Saran Transfer:
1. **Deteksi Cabang Kelebihan Stok (Overstock Branch)**: Sistem mendeteksi cabang yang memiliki persediaan melebihi *Max Stock* atau perputaran barangnya lambat (*slow-moving*).
2. **Deteksi Cabang Kekurangan Stok (Understock Branch)**: Sistem mendeteksi cabang yang stok barang tersebut menyentuh batas kritis *Reorder Point (ROP)* dengan laju penjualan harian tinggi.
3. **Rekomendasi Pemindahan Otomatis**: Alih-alih membeli barang baru ke supplier (yang menambah beban hutang dan menumpuk stok global), sistem merekomendasikan mutasi dari cabang yang berlebih ke cabang yang membutuhkan.
4. Manajer cukup mengklik tombol **Eksekusi Saran Transfer** untuk otomatis membuat draf transfer antar cabang.

---

## 3. Langkah demi Langkah Operasional (Step-by-Step SOP)

### Langkah 1: Membuat Permintaan / Rencana Transfer
1. Buka menu **Persediaan > Transfer Stok Antar Cabang**.
2. Klik tombol **Buat Transfer Stok**.
3. Pilih **Cabang Asal (Pengirim)** dan **Cabang Tujuan (Penerima)**.
4. Masukkan tanggal rencana kirim.
5. Pada tabel barang, pilih produk dan tentukan kuantitas yang hendak dikirim.
6. Periksa kolom *Stok Tersedia Cabang Asal* untuk memastikan kuantitas pengiriman tidak melebihi stok yang ada di gudang pengirim.
7. Simpan dokumen dengan status `DRAFT`.

### Langkah 2: Mengirim Barang & Cetak Surat Jalan (Status IN TRANSIT)
1. Tim gudang cabang asal menyiapkan fisik barang dan memuatnya ke kendaraan.
2. Buka dokumen transfer tersebut, ubah status menjadi `IN TRANSIT`.
3. Klik tombol **Cetak Surat Jalan Transfer (PDF)**.
4. Cetak surat jalan rangkap 2:
   - 1 lembar dibawa oleh pengemudi / kurir toko.
   - 1 lembar ditinggal sebagai arsip gudang asal.
5. Klik **Simpan**. Pada tahap ini, stok di gudang asal telah resmi berkurang.

### Langkah 3: Penerimaan & Verifikasi di Cabang Tujuan (Status RECEIVED)
1. Kendaraan tiba di cabang tujuan. Staf penerima memeriksa fisik barang bersama pengemudi berdasarkan lembar surat jalan fisik.
2. Buka menu **Transfer Stok Antar Cabang**, cari nomor dokumen transfer yang berstatus `IN TRANSIT`.
3. Klik aksi **Konfirmasi Penerimaan (Receive)**.
4. Periksa kuantitas yang tiba:
   - Jika cocok 100%, konfirmasi kuantitas diterima sama dengan kuantitas kirim.
   - Jika terdapat selisih/rusak di perjalanan, input kuantitas diterima riil dan kuantitas rusak.
5. Tanda tangani surat jalan fisik.
6. Klik **Konfirmasi Simpan**. Status berubah menjadi `RECEIVED` dan stok di cabang tujuan bertambah detik itu juga.

---

## 4. Pertanyaan Umum & Aturan Khusus (FAQ)

**T: Bisakah Cabang A mengirim barang ke Cabang B jika produk tersebut belum pernah dijual di Cabang B?**  
J: Ya, sistem secara otomatis akan mendaftarkan relasi stok produk tersebut di Cabang B dengan harga jual default yang diwarisi dari master produk, sehingga Cabang B langsung siap menjual barang tersebut di kasir.

**T: Apa yang terjadi jika pengiriman transfer dibatalkan saat sudah berstatus IN TRANSIT?**  
J: Pembatalan hanya dapat dilakukan dengan wewenang Manajer Toko. Jika dibatalkan, sistem secara otomatis mengembalikan (*roll back*) saldo stok fisik ke Cabang Asal dan menghapus catatan *in-transit*.
