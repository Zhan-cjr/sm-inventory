# Buku Panduan Pengguna: Shift Kasir & Tutup Buku Harian (EOD)

Menu **Shift Kasir** mengelola seluruh siklus pertanggungjawaban uang kas fisik di laci kasir (*cash drawer*), mulai dari penerimaan modal awal kasir, pencatatan kas masuk/keluar operasional harian, proteksi lintas hari, hingga penutupan shift dan penghitungan selisih kas (*cash reconciliation*).

---

## 1. Ikhtisar & Arsitektur Keamanan Kasir

Sistem kasir SM Inventory menerapkan sejumlah protokol keamanan finansial tingkat enterprise:

- **Proteksi Mutex Lock (Anti Double-Click Buka Shift)**:
  - Tombol Buka Shift dilengkapi proteksi kunci mutex dan *debounce guard*. Mencegah terjadinya pembukaan 2 sesi shift ganda untuk kasir dan terminal yang sama akibat klik cepat berulang kali.
- **Proteksi Kunci Lintas Hari (Cross-Day Auto-Lock Guard)**:
  - Sesi kasir wajib ditutup pada hari yang sama. Jika kasir lupa menutup shift hingga melewati pukul 00:00 tengah malam (berganti tanggal kalender), sistem secara otomatis mengunci shift tersebut (*Locked Due to Cross-day*).
  - Kasir tidak dapat melakukan transaksi baru pada shift kadaluarsa tersebut sampai shift lama ditutup resmi atau diselesaikan oleh supervisor.
- **Isolasi Terminal & Kasir**:
  - Setiap shift mengikat kombinasi: **Nama Kasir + Terminal / Kassa (POS Device) + Cabang**.
- **Pencatatan Arus Kas Non-Penjualan (Cash In & Cash Out)**:
  - Uang tunai yang masuk atau keluar laci kasir di luar transaksi belanja (misal: tambah modal koin, bayar sampah, beli air galon toko, setoran brankas antara) wajib dicatat dengan alasan yang valid.
- **Rekonsiliasi Kas Buta (Blind Cash Count)**:
  - Saat tutup shift, kasir diminta menghitung dan menginput fisik uang di laci terlebih dahulu sebelum sistem menampilkan selisih lebih/kurang kas untuk mencegah kecurangan.

---

## 2. Siklus Alur Shift Kasir (Step-by-Step SOP)

### A. Membuka Shift Kasir (Awal Kerja)
1. Login ke aplikasi POS atau buka menu **Penjualan > Shift Kasir**.
2. Jika belum ada shift yang aktif, sistem akan menampilkan dialog **Buka Shift**.
3. Pilih **Terminal / Mesin Kasir** yang digunakan (misal: *Kassa 1*).
4. Masukkan **Kas Awal (Starting Cash / Modal Receh)**:
   - Hitung fisik uang kembalian yang diserahkan oleh bendahara toko (misal: Rp 200.000).
   - Masukkan nominal tersebut ke kolom Kas Awal.
5. Klik tombol **Buka Shift**.
6. Sistem memverifikasi mutex dan membuka sesi kasir. Laci kasir siap digunakan untuk melayani transaksi belanja pelanggan.

### B. Mencatat Kas Masuk / Kas Keluar Operasional
Selama jam operasional berjalan, jika ada pergerakan uang tunai non-transaksi:
1. Klik tombol **Kas Masuk (Cash In)**:
   - Digunakan saat kasir menerima tambahan uang modal kembalian dari brankas/supervisor.
   - Masukkan nominal uang dan keterangan (contoh: *Tambahan uang receh Rp 2.000 dan Rp 5.000 dari supervisor*).
2. Klik tombol **Kas Keluar (Cash Out)**:
   - Digunakan saat mengeluarkan uang tunai dari laci kasir untuk kebutuhan mendesak toko atau setoran parsial ke brankas (*cash drop*).
   - Masukkan nominal uang dan alasan pengeluaran (contoh: *Beli air galon toko Rp 20.000* atau *Setor brankas tengah hari Rp 1.500.000*).
3. Transaksi ini langsung memengaruhi perhitungan kas akhir laci secara *real-time*.

### C. Menutup Shift Kasir (Akhir Jam Kerja / Pergantian Kasir)
1. Setelah pelayanan transaksi selesai, klik menu/tombol **Tutup Shift (Close Shift)**.
2. Lakukan penghitungan fisik seluruh uang tunai yang ada di dalam laci kasir (*Cash Physical Count*).
3. Masukkan jumlah uang fisik riil tersebut ke kolom **Kas Akhir Fisik (Actual Cash)**.
4. Sistem akan membandingkan fisik uang dengan **Kalkulasi Kas Seharusnya (Expected Cash)**:  
   $$\text{Kas Seharusnya} = \text{Kas Awal} + \text{Penjualan Tunai} + \text{Total Cash In} - \text{Total Cash Out}$$
5. Sistem menghitung **Selisih Kas (Cash Difference)**:
   - **Rp 0 (Pas)**: Kas fisik cocok sempurna dengan sistem.
   - **Plus (+) / Lebih**: Terjadi kelebihan kas (misal: konsumen tidak mengambil kembalian receh).
   - **Minus (-) / Kurang**: Terjadi kekurangan kas fisik yang menjadi tanggung jawab kasir.
6. Berikan catatan penjelasan jika terdapat selisih kas yang tidak biasa.
7. Klik tombol **Konfirmasi Tutup Shift**.
8. Sistem akan mencetak **Struk Laporan Tutup Shift / Rekap EOD** yang memuat:
   - Rincian total penjualan per metode pembayaran (Tunai, QRIS, Kartu Debit, Transfer).
   - Total penjualan kotor (*Gross Sales*), diskon, dan retur.
   - Rekap kas masuk, kas keluar, dan selisih kas.
9. Tanda tangani struk tersebut bersama supervisor dan serahkan fisik uang ke bendahara toko.

---

## 3. Penanganan Kasus Khusus & Troubleshooting

**T: Muncul notifikasi "Shift Kemarin Belum Ditutup (Cross-day Lock)"?**  
J: Hal ini terjadi jika kasir bertugas pada hari sebelumnya tidak melakukan tutup shift hingga jam 00:00 lewat. Kasir atau supervisor harus membuka menu *Shift Kasir*, cari shift yang berstatus terkunci lintas hari tersebut, lalu lakukan proses **Tutup Shift Tertunda** dengan menginput kas fisik yang tersisa pada shift kemarin sebelum dapat membuka shift baru untuk hari ini.

**T: Apakah pembayaran QRIS atau EDC mempengaruhi uang tunai di laci?**  
J: Tidak. Pembayaran non-tunai (QRIS, EDC, Transfer) dicatat terpisah oleh sistem sebagai pembayaran digital dan tidak dihitung ke dalam saldo fisik laci kasir, sehingga kasir tidak perlu cemas saat mencocokkan uang fisik laci.
