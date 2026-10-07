# DOKUMENTASI DISKUSI: PERAMALAN KEBUTUHAN PEMBELIAN (FORECAST PO)
**Sistem Manajemen Inventaris & Rantai Pasok (SM Inventory)**  
*Tanggal Diskusi: 7 Oktober 2026*  
*Status: Tahap Diskusi & Perancangan Konsep (Belum Tahap Eksekusi)*

---

## 📌 1. Latar Belakang & Tujuan Diskusi

Diskusi ini membahas konsep, metodologi, dan potensi penerapan formula peramalan kebutuhan stok barang (*Demand & PO Forecasting*) berdasarkan file acuan **`Forecast_PO.xlsx`** ke dalam sistem aplikasi **SM Inventory**.

### Tujuan Utama:
1. **Mencegah Barang Kosong (*Zero Out of Stock*)**: Menghindari kehilangan omzet penjualan akibat rak toko kehabisan barang.
2. **Mencegah Penumpukan Barang (*Anti Overstock*)**: Menjaga arus kas (*cash flow*) agar uang modal perusahaan tidak tertahan di gudang pada barang yang lambat laku.
3. **Otomatisasi Belanja Supplier (*Smart Auto-PO*)**: Membantu staf purchasing agar tidak lagi menghitung manual di Excel secara terpisah.

---

## 🧭 2. Kondisi Fitur "Saran Order" yang Berjalan Saat Ini

Di dalam aplikasi SM Inventory, saat ini sudah terdapat modul **Saran Order** (`SuggestedOrders`):

### Cara Kerja Saat Ini:
- **Metode Tunggal**: Menggunakan **Rata-rata Penjualan Harian 30 Hari Terakhir (*Average Daily Sales / ADS*)**.
  - Rata-rata Harian = Total Penjualan 30 Hari Terakhir dibagi 30.
  - Titik Pesan Ulang (*Reorder Point*) = (Rata-rata Harian x Waktu Kirim) + Stok Cadangan 3 Hari.
  - Target Stok = Rata-rata Harian x 30 Hari.
  - Jika Stok Saat Ini sudah di bawah Titik Pesan Ulang, sistem menyarankan jumlah PO baru.
- **Keterbatasan Saat Ini**: Seluruh barang dipukul rata menggunakan rumus 30 hari yang sama, padahal barang musiman (seperti sirup saat Ramadhan) memiliki pola yang berbeda dengan barang pokok harian.

---

## ⚖️ 3. Bedah 6 Metode Peramalan di File `Forecast_PO.xlsx`

File `Forecast_PO.xlsx` memuat 6 pendekatan ilmiah peramalan stok:

| No | Nama Metode | Cara Kerja Sederhana | Kelebihan | Kekurangan & Risiko di Lapangan | Karakteristik Produk yang Cocok |
|:---:|:---|:---|:---|:---|:---|
| **1** | **Rata-rata Biasa (*Moving Average, n=3*)** | Merata-ratakan penjualan 3 bulan terakhir. | Sangat mudah dihitung, ringan untuk server. | Lambat merespons tren baru (*lagging*). | Barang dengan penjualan stabil dan tidak banyak lonjakan. |
| **2** | **Rata-rata Berbobot (*Weighted Moving Average, n=4*)** | Memberi bobot lebih tinggi pada bulan paling akhir (misal bobot 4, 3, 2, 1). | Lebih responsif terhadap tren penjualan terkini. | Penentuan bobot subjektif; rentan over-forecast jika bulan lalu ada promo sesaat. | Barang yang trennya dinamis dari bulan ke bulan. |
| **3** | **Koreksi Meleset (*Exponential Smoothing*)** | Mengoreksi ramalan bulan lalu berdasarkan deviasi/melesetnya penjualan nyata. Dilengkapi evaluasi nilai meleset (*MAD*). | Sangat efisien, ada metrik akurasi untuk melihat seberapa tepat ramalan. | Jika grafik penjualan naik terus-menerus, ramalan akan selalu tertinggal di bawahnya. | Produk reguler di mana kita ingin mengontrol sensitivitas ramalan. |
| **4** | **Garis Tren Naik/Turun (*Linear Trend*)** | Menggunakan garis tren regresi linier. | Sangat tepat untuk barang yang pasarnya sedang bertumbuh pesat. | Berbahaya jika tren berhenti/pasar jenuh; rumus akan terus menyuruh beli banyak (*pemicu overstock*). | Produk baru yang sedang gencar ekspansi dan promosi. |
| **5** | **Pola Musiman (*Seasonal Index*)** | Menganalisis siklus lonjakan bulanan selama 2-3 tahun ke belakang. | Sangat akurat untuk mengantisipasi masa panen/hari raya besar tanpa takut kehabisan barang. | Membutuhkan data historis bersih minimal 2–3 tahun berturut-turut. | Produk musiman (biskuit kaleng/sirup saat Lebaran, buku sekolah saat ajaran baru). |
| **6** | **Batas Merah & Plafon (*Min-Max & Safety Stock*)** | Menentukan Titik Pesan (*Min*) dan Batas Maksimal (*Max*) berbasis Standar Deviasi penjualan harian. | Menangani ketidakpastian fluktuasi harian dan mengunci plafon modal agar tidak belanja berlebihan. | Membutuhkan pencatatan riwayat penjualan harian yang rapi. | Pengendalian stok otomatis untuk seluruh barang fast-moving. |

---

## 🧮 4. Logika Perhitungan "PO Bersih" di Lapangan

Salah satu penyebab terbesar gudang kebanjiran barang (*Overstock*) di lapangan adalah **Pemesanan Ganda (*Double Order*)** akibat lupa memperhitungkan barang yang sedang dikirim supplier.

### Rumus Pengendalian Stok Lapangan:
> **Jumlah PO yang Benar = Target Maksimal Toko - (Stok di Rak + Barang yang Sedang Dikirim Supplier - Pesanan Pelanggan yang Sudah Dibooking)**

### Contoh Kasus Nyata:
- Target stok minyak goreng di toko: **100 dus**.
- Sisa stok di rak toko saat ini: **20 dus**.
- PO kemarin yang sedang dalam perjalanan truk supplier: **30 dus**.
- Pesanan pelanggan restoran yang sudah bayar (belum diambil): **5 dus**.

**Perhitungan:**
1. Stok efektif yang kita miliki = 20 dus (di rak) + 30 dus (di jalan) - 5 dus (milik pelanggan) = **45 dus**.
2. Kuantitas PO yang harus dipesan hari ini = 100 - 45 = **55 dus**.
*(Mencegah staf memesan 80 dus yang akan membuat gudang membludak menjadi 130 dus saat kiriman kemarin tiba).*

---

## 🤖 5. Pembagian Peran Kerja: Server Komputer vs Staf Purchasing

### 1. Peran Komputer / Server (Otomatis Tengah Malam)
- Setiap tengah malam saat operasional toko tutup (misal pukul 00:00), sistem secara otomatis menjalankan kalkulasi latar belakang (*background scheduler*).
- Server menyiapkan dan menyimpan data rekomendasi ke dalam memori cepat (*cache*).
- **Hasilnya**: Saat staf datang di pagi hari dan membuka menu, halaman saran order langsung terbuka secara instan (< 1 detik) tanpa waktu tunggu.

### 2. Peran Staf Purchasing (Pengambil Keputusan di Pagi Hari)
- Staf membuka menu **Saran Order**.
- Sistem menyajikan daftar barang yang stoknya menyentuh batas aman.
- **Fitur Kotak Centang (*Checkbox*)**: Staf dapat memilih/mencentang barang mana saja yang ingin di-order hari ini (misal menunda 2 barang karena menunggu info promo lusa).
- **Tombol "Buat PO Terpilih"**: Sekali klik, sistem otomatis mengelompokkan barang berdasarkan suppliernya dan langsung menerbitkan **Dokumen Draft PO resmi**. Staf tidak perlu mengetik nama barang dan harga satu per satu.

---

## 🎯 6. Strategi Pemilihan Metode Peramalan oleh Sistem

Jika 6 metode di Excel diterapkan, berikut adalah 3 opsi arsitektur bagaimana server memilih rumus untuk setiap barang:

1. **Opsi A: Pemilihan Cerdas Otomatis (*Best-Fit Selection*) [Paling Praktis]**  
   Setiap malam server menguji formula mana yang memiliki angka kesalahan (*meleset*) paling kecil terhadap data penjualan riil bulan lalu. Metode dengan akurasi tertinggi otomatis yang dipilih untuk produk tersebut.
2. **Opsi B: Pengaturan Manual di Master Produk (*Rule-Based*)**  
   Manajer dapat menyetel metode khusus pada barang tertentu (misal: Sirup disetel ke *Pola Musiman*, Barang baru disetel ke *Garis Tren*).
3. **Opsi C: Sistem Gabungan / Hybrid [Paling Direkomendasikan]**  
   Secara bawaan (*default*) sistem memilih metode paling akurat secara otomatis (Opsi A), namun manajer tetap diberi hak akses untuk mengunci metode tertentu jika ada agenda khusus toko (Opsi B).

---

## 🔗 7. Integrasi dengan Modul Laporan Kinerja Supplier

Sistem peramalan PO ini akan bekerja jauh lebih sempurna jika dihubungkan dengan data riil dari modul **Laporan Kinerja Supplier** yang baru saja dibangun:

1. **Waktu Kirim Riil (*Actual Lead Time*)**: Menggunakan data nyata berapa hari rata-rata supplier mengirim barang ke cabang tertentu, bukan angka perkiraan.
2. **Riwayat Kelengkapan Kirim (*Service Level Qty*)**: Jika supplier memiliki riwayat sering kurang kirim (misal hanya kirim 80%), sistem otomatis menyarankan stok pengaman (*buffer stock*) tambahan.
3. **Disiplin Tutup PO Expired**: Menghanguskan PO lama yang tidak pernah dikirim agar sistem tidak salah mengira bahwa "barang akan segera datang".

---

*Dokumen ini dibuat sebagai rangkuman hasil diskusi konseptual untuk dijadikan acuan dan referensi pada saat perancangan sistem tahap berikutnya.*
