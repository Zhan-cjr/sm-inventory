# Buku Panduan Pengguna: Penyesuaian & Koreksi Stok (Stock Adjustment)

Menu **Penyesuaian Stok (Stock Adjustment)** digunakan oleh pihak manajemen dan supervisor gudang untuk melakukan koreksi saldo fisik inventori di luar transaksi jual-beli normal, seperti mencatat barang pecah/rusak, barang kadaluarsa (*expired*), penyusutan (*shrinkage*), atau pemakaian internal operasional toko.

---

## 1. Ikhtisar & Alasan Penyesuaian (Adjustment Reasons)

Setiap perubahan stok manual di SM Inventory wajib memiliki dasar pertanggungjawaban audit yang jelas. Sistem menyediakan klasifikasi alasan koreksi:

| Kode Alasan | Kategori Mutasi | Pengaruh Akuntansi | Contoh Kasus Nyata |
| :--- | :--- | :--- | :--- |
| **EXP (Expired)** | Pengurangan (-) | Beban Kerugian Barang Kadaluarsa | Susu cair melewati batas tanggal aman konsumsi. |
| **DMG (Damaged)** | Pengurangan (-) | Beban Kerusakan Barang | Botol sirup jatuh dan pecah di lantai display. |
| **SHR (Shrinkage)** | Pengurangan (-) | Beban Kehilangan Persediaan | Selisih minus barang kecil setelah audit harian. |
| **INT (Internal Use)** | Pengurangan (-) | Beban Operasional Perlengkapan Toko | Mengambil 1 botol karbol & sabun cuci untuk kebersihan toko. |
| **FND (Found Stock)** | Penambahan (+) | Pendapatan Penyesuaian Persediaan | Ditemukan 1 dus biskuit di pojok gudang yang belum tercatat. |

---

## 2. Struktur Formulir Penyesuaian Stok

### A. Informasi Dokumen & Cabang
1. **Nomor Dokumen**: Dibuat otomatis (contoh: `ADJ-202610-0005`).
2. **Tanggal Penyesuaian**: Tanggal fisik kejadian atau tanggal audit.
3. **Cabang**: Lokasi toko/gudang terjadinya penyesuaian.
4. **Alasan Utama (Adjustment Reason)**: Memilih kategori alasan dari daftar terstandarisasi.
5. **Keterangan / Berita Acara**: Catatan mendalam penyebab kejadian (contoh: *Pecah akibat gempa kecil / tersenggol troli belanja konsumen*).

### B. Tabel Item Penyesuaian
1. **Pilih Produk**: Scan barcode atau cari nama barang.
2. **Stok Sistem Saat Ini**: Ditampilkan otomatis oleh sistem (*read-only*) sebagai referensi awal.
3. **Tipe Aksi**:
   - `Pengurangan (-)`: Mengurangi kuantitas fisik.
   - `Penambahan (+)`: Menambah kuantitas fisik.
   - `Tetapkan Saldo Riil`: Mengetik langsung angka fisik riil yang ada di rak.
4. **Kuantitas Penyesuaian (Qty Adjust)**: Jumlah unit yang disesuaikan.
5. **Stok Akhir Baru**: Nilai stok setelah penyesuaian diterapkan.
6. **Nilai Rupiah Penyesuaian**: Dihitung otomatis berdasarkan nilai HPP barang:  
   $$\text{Total Nilai Koreksi} = \text{Qty Adjust} \times \text{HPP}$$

---

## 3. Alur Kerja Operasional (Step-by-Step SOP)

### Langkah 1: Pelaporan & Pemeriksaan Fisik
1. Staf toko yang menemukan barang rusak/pecah memisahkan fisik barang tersebut dan melaporkan kepada Supervisor Toko / Kepala Gudang.
2. Supervisor memeriksa kondisi fisik barang dan memastikan barang tersebut memang tidak layak dijual kembali.

### Langkah 2: Menginput Penyesuaian di Sistem
1. Login dengan akun yang memiliki hak akses supervisor/manajer.
2. Buka menu **Persediaan > Penyesuaian Stok**.
3. Klik tombol **Buat Penyesuaian Stok**.
4. Pilih **Cabang** dan **Alasan Penyesuaian** (misal: *DMG - Barang Rusak*).
5. Masukkan rincian barang dan jumlah unit yang rusak.
6. Tuliskan keterangan kronologi pada kolom catatan.
7. Klik **Simpan**.

### Langkah 3: Dampak Pasca-Penyesuaian
- Saldo stok fisik di cabang bersangkutan langsung terkoreksi seketika.
- Kartu Stok (*Inventory Log*) mencatat mutasi berkurang dengan referensi nomor dokumen penyesuaian.
- Nilai HPP dari barang yang dibuang dicatat ke dalam laporan laba rugi sebagai **Beban Kerusakan Persediaan**.
- Fisik barang yang rusak dimusnahkan atau diserahkan ke bagian retur jika dapat diajukan kompensasi ke supplier.

---

## 4. Otoritas Keamanan & Pertanyaan Umum (FAQ)

> [!CAUTION]
> **Proteksi Pencegahan Manipulasi Stok**:
> Kasir biasa dan staf toko biasa **TIDAK** diberikan hak untuk melakukan penyesuaian stok manual. Tindakan ini hanya boleh dilakukan oleh akun dengan peran **Supervisor**, **Kepala Toko**, atau **Superadmin** untuk mencegah praktik penyembunyian barang hilang.

**T: Apakah penyesuaian stok bisa dibatalkan atau diedit setelah disimpan?**  
J: Demi kepatuhan audit keuangan (*Financial Audit Compliance*), dokumen penyesuaian stok yang sudah disimpan tidak dapat diedit atau dihapus secara sembarangan. Jika terjadi kesalahan input angka, buatlah dokumen penyesuaian koreksi lawan dengan alasan penemuan atau perbaikan catatan.
