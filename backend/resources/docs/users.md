# Buku Panduan Pengguna: Pengguna, Peran, Cabang & Hak Akses

Menu **Pengguna & Cabang** mengelola identitas seluruh karyawan yang mengoperasikan sistem SM Inventory ERP, pengaturan struktur cabang toko, serta penegakan protokol keamanan hak akses berbasis peran (*Role-Based Access Control - RBAC*).

---

## 1. Ikhtisar & Arsitektur Keamanan Multi-Cabang

SM Inventory dirancang untuk mendukung operasional multi-cabang terpusat dengan isolasi data yang ketat:

- **Pengikatan Pengguna ke Cabang (Branch Binding)**:
  - Setiap staf toko (Kasir, Admin Gudang, Supervisor) ditautkan ke cabang tempat ia bekerja secara spesifik via atribut `branch_id`.
  - **Efek Isolasi Otomatis**:
    - Kasir Cabang A hanya dapat membuka shift di Kassa Cabang A.
    - Kasir hanya melihat stok fisik dan riwayat transaksi milik Cabang A.
    - Saat staf gudang Cabang A membuka daftar produk di supplier, sistem memfilter produk yang aktif dan dialokasikan untuk Cabang A saja.
- **Pengguna Tingkat Pusat (Global / Superadmin)**:
  - Pemilik toko, manajer operasional, dan auditor kantor pusat tidak terikat pada satu cabang tertentu (`branch_id = null` atau memiliki peran Superadmin). Mereka dapat memantau seluruh cabang secara konsolidasi atau memilih cabang aktif secara dinamis.
- **Matriks Hak Akses Berbasis Peran (Filament Shield)**:
  - Setiap menu dan tombol di sistem dilindungi izin (*permissions*): *Melihat (View)*, *Membuat (Create)*, *Mengubah (Update)*, dan *Menghapus (Delete)*.

---

## 2. Struktur Peran Standar (User Roles)

Sistem telah dilengkapi dengan peran terstandarisasi untuk ritel:

| Nama Peran (Role) | Ruang Lingkup Menu | Wewenang Khusus |
| :--- | :--- | :--- |
| **Superadmin / Owner** | Seluruh Sistem (Akses Penuh) | Konfigurasi sistem, reset database, kelola user, buka tutup buku tahunan. |
| **Manager / Kepala Toko** | Laporan, Pembelian, Audit, SO | Approval Purchase Order (PO), approval penyesuaian stok, lihat laba rugi. |
| **Finance / Akuntansi** | Kontrabon, Pelunasan, Jurnal, COA | Tukar faktur supplier, pencairan hutang, rekap penerimaan kas/bank. |
| **Warehouse / Gudang** | GR, Transfer Stok, Opname, Cek Fisik | Menerima faktur fisik supplier, mutasi antar cabang, hitung fisik rak. |
| **Cashier / Kasir** | Aplikasi Kasir POS, Shift Kasir | Buka/tutup shift, transaksi penjualan, scan barcode, cetak struk kasir. |

---

## 3. Langkah demi Langkah Pengelolaan (Step-by-Step SOP)

### Cara Mendaftarkan Pengguna Baru:
1. Buka menu **Pengaturan > Pengguna (Users)**.
2. Klik tombol **Buat Pengguna Baru**.
3. Masukkan data identitas:
   - **Nama Lengkap**: Nama resmi karyawan.
   - **Email**: Alamat email yang digunakan untuk login.
   - **Password**: Kata sandi awal (minimal 8 karakter).
4. Tentukan **Cabang Penugasan (Branch)**:
   - Pilih nama cabang tempat karyawan bertugas (misal: *Cabang Sariwangi*).
   - Biarkan kosong hanya jika akun tersebut adalah staf kantor pusat yang memegang semua cabang.
5. Tentukan **Peran (Roles)**:
   - Centang peran yang sesuai dengan tugasnya (misal: centang *Cashier* untuk staf kasir).
6. Centang toggle **Status Aktif**.
7. Klik **Simpan**.

### Cara Menambah Cabang Baru (Branch Management):
1. Buka menu **Pengaturan > Cabang (Branches)**.
2. Klik tombol **Buat Cabang**.
3. Isi **Nama Cabang** (contoh: *SM Mart Cabang Sariwangi*).
4. Masukkan **Kode Cabang** unik (contoh: *SRW*).
5. Masukkan alamat lengkap, nomor telepon operasional toko, dan nama kepala toko.
6. Tentukan tipe cabang: *Cabang Satelit (Toko)* atau *Gudang Pusat (Central Warehouse)*.
7. Simpan data. Sistem secara otomatis menyiapkan relasi stok produk untuk cabang baru ini.

### Cara Menghapus Akses Karyawan yang Resign:
> [!WARNING]
> Jangan menghapus akun karyawan yang sudah resign jika akun tersebut pernah melakukan transaksi kasir atau pencatatan dokumen demi kelengkapan rekam jejak audit (*Audit Trail*).

1. Buka data pengguna yang bersangkutan di menu **Pengguna**.
2. Matikan toggle **Status Aktif (Is Active)**.
3. Ubah password akun secara acak.
4. Klik **Simpan**. Karyawan tersebut tidak akan dapat login lagi ke sistem maupun aplikasi kasir, namun seluruh riwayat transaksi lamanya tetap tersimpan aman.

---

## 4. Keamanan Akun & Tips Operasional (FAQ)

**T: Bagaimana jika kasir lupa kata sandi login?**  
J: Supervisor atau Admin dapat membuka menu *Pengguna*, klik *Edit* pada nama kasir yang bersangkutan, lalu ketikkan kata sandi baru di kolom password, dan simpan.

**T: Bisakah seorang kasir login di dua komputer mesin kassa secara bersamaan?**  
J: Tidak disarankan. Setiap mesin kassa harus memiliki akun login atau sesi shift terpisah untuk menjamin pertanggungjawaban fisik uang tunai di laci masing-masing mesin.
