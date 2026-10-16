# Buku Panduan Pengguna: Paket Bundling & Perakitan Produk (Product Assemblies)

Menu **Perakitan Produk (Product Assemblies / Bill of Materials)** digunakan oleh tim gudang dan operasional toko untuk merakit atau membundel beberapa barang mentah/eceran menjadi satu produk paket baru yang bernilai jual lebih tinggi (seperti: Parcel Hari Raya, Paket Sembako Hemat, atau Paket Promosi Bundling).

---

## 1. Ikhtisar & Mekanisme Pengurangan Stok Otomatis

Saat Anda merakit sebuah produk paket (misalnya: *Paket Sembako Berkah*):
- **Stok Komponen Pengurang**:
  - Beras 5kg: Berkurang 1 sak.
  - Minyak Goreng 2L: Berkurang 1 pouch.
  - Gula Pasir 1kg: Berkurang 1 kg.
  - Tas Spunbond: Berkurang 1 pcs.
- **Stok Produk Jadi Penambah**:
  - *Paket Sembako Berkah*: Bertambah 1 paket.
- **Kalkulasi HPP Akurat**:
  - HPP dari produk paket jadi dihitung otomatis dari akumulasi HPP seluruh komponen bahan penyusunnya ditambah estimasi biaya kemasan perakitan.

---

## 2. Langkah demi Langkah Pembuatan Paket Produk (SOP)

### Langkah 1: Mendefinisikan Resep Paket (Bill of Materials)
1. Buka menu **Data Master > Perakitan Produk**.
2. Klik tombol **Buat Perakitan Baru**.
3. Pilih **Produk Jadi (Finished Good)** yang akan dihasilkan (pastikan produk paket ini sudah didaftarkan di Master Produk terlebih dahulu).
4. Pada tabel komponen penyusun (*Recipe Components*):
   - Masukkan barang-barang penyusun beserta kuantitasnya masing-masing untuk 1 paket.
5. Klik **Simpan Resep**.

### Langkah 2: Mengeksekusi Produksi / Perakitan Fisik (Assembly Execution)
1. Ketika toko hendak membuat 50 paket parcel:
   - Klik aksi **Eksekusi Perakitan (Assemble Now)** pada resep paket tersebut.
   - Masukkan jumlah paket yang hendak dirakit (misal: `50`).
   - Sistem akan memeriksa apakah stok fisik seluruh bahan komponen di gudang mencukupi.
   - Klik **Konfirmasi Perakitan**.
2. Sistem secara otomatis memotong stok seluruh bahan penyusun dan menambah saldo stok produk paket jadi sebanyak 50 unit di cabang tersebut.
