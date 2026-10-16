# Buku Panduan Pengguna: Pesanan Pembelian (Purchase Order / PO)

Menu **Pesanan Pembelian (Purchase Order / PO)** digunakan oleh bagian purchasing/pengadaan untuk merencanakan, mengontrol, dan menerbitkan surat pesanan resmi kepada supplier secara terstruktur, mencegah pembelian berlebih (*over-stock*), dan menjamin ketersediaan barang di toko.

---

## 1. Ikhtisar & Siklus Hidup Dokumen PO

Setiap dokumen PO di SM Inventory melewati siklus status (*lifecycle*) yang ketat:

```
[DRAFT] ──> [PENDING APPROVAL] ──> [APPROVED] ──> [PARTIAL] ──> [COMPLETED]
   │                                   │
   └─── (Dibatalkan) ──> [CANCELLED] <─┘
```

1. **DRAFT**: Dokumen sedang disusun oleh staf purchasing. Item barang, kuantitas, dan estimasi harga masih dapat diubah bebas.
2. **PENDING APPROVAL**: Dokumen telah diajukan ke kepala toko / manajer operasional untuk peninjauan anggaran dan kuantitas.
3. **APPROVED**: PO telah disetujui. Siap dikirimkan ke supplier dalam bentuk PDF / WhatsApp / Email resmi. Dokumen terkunci dari pengeditan item.
4. **PARTIAL**: Barang dari PO bersangkutan telah diterima sebagian di gudang melalui menu *Penerimaan Barang (GR)*.
5. **COMPLETED**: Seluruh kuantitas barang yang dipesan telah diterima 100% di gudang.
6. **CANCELLED**: Pesanan dibatalkan (karena supplier kehabisan stok, perubahan kebijakan, dll.).

---

## 2. Fitur Saran Order Cerdas (Suggested Orders)

SM Inventory ERP dilengkapi dengan algoritma **Saran Order Cerdas** yang dapat diakses melalui menu **Saran Order (Suggested Orders)** atau tombol bantu pada PO:

### Rumus Perhitungan Kebutuhan Pesanan:
Sistem menghitung rekomendasi kuantitas belanja berdasarkan data riil:
1. **Rata-Rata Penjualan Harian (Average Daily Sales - ADS)**: Total penjualan produk selama periode tertentu dibagi jumlah hari aktif.
2. **Lead Time Pasokan**: Waktu tunggu pengiriman distributor dari pesanan dibuat hingga barang tiba (hari).
3. **Stok Pengaman (Safety Stock)**: Batas minimal stok untuk mengantisipasi lonjakan permintaan tiba-tiba.
4. **Sisa Stok Fisik + Stok Sedang Dipesan (On Order)**.

$$\text{Saran Pesan} = (\text{ADS} \times \text{Lead Time}) + \text{Safety Stock} - (\text{Stok Fisik} + \text{PO On-Going})$$

Jika hasil perhitungan bernilai positif dan stok saat ini berada di bawah *Reorder Point (ROP)*, sistem secara otomatis memasukkan barang tersebut ke dalam daftar saran pesanan. Staf purchasing cukup mencentang barang-barang tersebut untuk langsung dijadikan dokumen PO baru!

---

## 3. Struktur Formulir Pembuatan PO

### A. Informasi Dokumen
1. **Nomor PO**: Terbit otomatis dengan format `PO-YYMM-#####`.
2. **Pilih Supplier**: Menentukan supplier tujuan pesanan.
3. **Tanggal PO**: Tanggal dokumen diterbitkan.
4. **Estimasi Tanggal Tiba (Expected Date)**: Target tanggal barang wajib tiba di gudang.
5. **Cabang Pemesan**: Cabang toko yang membutuhkan stok tersebut.
6. **Catatan / Instruksi Pengiriman**: Pesan khusus ke pihak supplier (contoh: *Harap kirim barang dengan expired date minimal 1 tahun ke depan*).

### B. Tabel Item Pesanan
1. **Pilih Produk**: Pencarian nama barang atau scan barcode.
2. **Satuan Pemesanan**: Satuan pesanan (misal: `DUS`, `KARTON`, `PCS`).
3. **Kuantitas Pesanan (Qty Order)**: Jumlah unit yang dipesan.
4. **Harga Satuan Terakhir (Estimasi Beli)**: Diambil otomatis dari harga beli terakhir atau kesepakatan master produk.
5. **Subtotal Baris**: Dihitung otomatis per baris item.

---

## 4. Panduan Operasional (Step-by-Step SOP)

### Cara Membuat PO Manual:
1. Buka menu **Transaksi Pembelian > Pesanan Pembelian (PO)**.
2. Klik tombol **Buat Pesanan Pembelian**.
3. Pilih **Supplier** dan **Cabang Pemesan**.
4. Masukkan **Estimasi Tanggal Tiba**.
5. Pada tabel item, tambahkan produk-produk yang ingin dipesan beserta kuantitasnya.
6. Periksa estimasi total anggaran PO pada bagian ringkasan.
7. Pilih status awal:
   - Pilih `DRAFT` jika masih ingin merevisi nanti.
   - Pilih `PENDING APPROVAL` untuk mengajukan ke manajer.
8. Klik **Simpan**.

### Cara Menyetujui (Approval) & Cetak PDF PO:
1. Pengguna dengan hak akses Manajer/Pimpinan membuka daftar PO dengan filter status `PENDING APPROVAL`.
2. Buka dokumen PO, periksa kewajaran jumlah pesanan dan total nominal.
3. Ubah status menjadi `APPROVED` lalu klik **Simpan**.
4. Klik tombol **Cetak PDF PO** di bagian atas halaman.
5. Dokumen PDF formal akan terunduh lengkap dengan logo perusahaan, nomor PO resmi, rincian barang, serta kolom tanda tangan *Dibuat Oleh* dan *Disetujui Oleh*.
6. Kirimkan file PDF tersebut ke pihak sales/distributor supplier.

### Cara Melacak Pemenuhan Barang dari PO:
- Setiap kali tim gudang melakukan **Penerimaan Barang (GR)** dengan memilih nomor PO ini, sistem akan otomatis mengurangi sisa kuantitas pesanan.
- Anda dapat memantau persentase pemenuhan pada tabel PO (kolom *Progress Penerimaan*).
- Jika supplier mengonfirmasi bahwa sisa barang tidak dapat dikirim lagi, staf dapat mengubah status menjadi `COMPLETED` atau menambahkan catatan penutupan PO.

---

## 5. Pertanyaan Umum & Aturan Khusus (FAQ)

**T: Bisakah mengedit isi barang pada PO yang sudah APPROVED?**  
J: Demi integritas audit dan kepatuhan anggaran, PO yang sudah berstatus `APPROVED` tidak dapat diedit langsung. Jika ada perubahan pesanan yang signifikan, ubah status kembali ke `DRAFT` (hanya oleh wewenang manajer) atau batalkan PO tersebut dan buat PO revisi baru.

**T: Mengapa barang yang baru saja dipesan belum menambah stok di kasir?**  
J: PO hanyalah dokumen *pemesanan* ke supplier dan belum merupakan barang fisik yang tiba di toko. Stok fisik baru akan bertambah saat tim gudang menerima barang secara riil melalui menu **Penerimaan Barang (GR)**.
