# Buku Panduan Pengguna: Keuangan, Akuntansi & Jurnal Umum

Modul **Keuangan & Akuntansi** di SM Inventory ERP menyediakan pembukuan ganda (*Double-Entry Bookkeeping*) otomatis yang mencatat seluruh mutasi finansial bisnis mulai dari penjualan kasir, hutang piutang, biaya operasional, jurnal umum manual, hingga laporan laba rugi dan tutup buku tahunan.

---

## 1. Struktur Menu Keuangan

1. **Bagan Akun (Chart of Accounts / COA - `admin/accounts`)**:
   - Struktur hirarki kode perkiraan standar (1-Aset, 2-Kewajiban/Hutang, 3-Ekuitas/Modal, 4-Pendapatan, 5-HPP, 6-Beban Operasional).
2. **Jurnal Umum (Journal Entries - `admin/journal-entries`)**:
   - Pencatatan transaksi non-operasional (misal: setoran modal pemilik, penarikan prive, penyusutan aset tetap, atau koreksi saldo buku).
   - Memastikan prinsip keseimbangan (Total Debet = Total Kredit).
3. **Biaya Operasional (Expenses - `admin/expenses`)**:
   - Pengeluaran rutin toko di luar pembelian barang dagang (gaji karyawan, tagihan listrik PLN, PDAM, internet, sewa gedung, ATK, pemeliharaan AC/kulkas display).
4. **Rekening Bank (`admin/banks`)**:
   - Buku besar kas & bank operasional untuk rekonsiliasi transfer dan mutasi rekening koran.
5. **Tagihan Konsinyasi (Consignment Billing - `admin/consignment-billing`)**:
   - Rekonsiliasi penjualan barang titip jual dengan prinsipal konsinyasi.
6. **Tutup Buku Tahunan (`admin/tutup-buku-tahunan`)**:
   - Mengunci periode tahun fiskal lama dan memindahkan saldo laba bersih ke akun *Laba Ditahan (Retained Earnings)* untuk tahun baru.

---

## 2. Langkah demi Langkah Pencatatan Biaya Operasional (SOP)

### Cara Mencatat Biaya Operasional Toko:
1. Buka menu **Keuangan > Biaya Operasional (Expenses)**.
2. Klik tombol **Catat Biaya Baru**.
3. Pilih **Cabang** tempat biaya terjadi.
4. Tentukan **Tanggal Transaksi** dan **Kategori Biaya** (misal: *Beban Listrik Toko*).
5. Masukkan nominal rupiah pengeluaran.
6. Pilih sumber pembayaran: *Kas Kecil Toko* atau *Transfer Bank Operasional*.
7. Ketik keterangan jelas (misal: *Pembayaran tagihan listrik PLN bulan Oktober 2026 - IDPEL: 5312345678*).
8. Unggah foto nota/kuitansi fisik sebagai bukti lampiran audit.
9. Klik **Simpan**. Sistem secara otomatis mendebet akun Beban Listrik dan mengkredit Kas Toko.
