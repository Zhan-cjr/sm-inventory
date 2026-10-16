# Buku Panduan Pengguna: Pemasok & Sub-Divisi (Supplier)

Menu **Pemasok (Supplier)** mengatur seluruh data prinsipal, distributor, agen, dan vendor pihak ketiga yang menyuplai barang ke toko SM Inventory ERP.

---

## 1. Ikhtisar & Fitur Utama

- **Data Profil Supplier**: Nama perusahaan, penanggung jawab (*contact person*), nomor telepon, email, NPWP, dan alamat kantor/gudang.
- **Sub-Divisi Supplier**: Pengelompokan divisi internal supplier (misal: *Wings Food*, *Wings Care*, *Unilever Food*, dll.) untuk pemisahan faktur dan jadwal pengiriman.
- **Skema Pembayaran (Term of Payment - TOP)**:
  - Jumlah hari tempo pembayaran (contoh: 7 hari, 14 hari, 30 hari, atau COD/Tunai).
  - Skema **Konsinyasi (`is_consignment`)**: Barang titip jual. Faktur penerimaan tidak langsung diakui sebagai hutang dagang, melainkan ditagihkan berkala sesuai barang yang laku terjual (*Consignment Billing*).
- **Aturan Penerimaan Wajib PO (`gr_requires_po`)**: Proteksi agar staf gudang tidak dapat menerima barang dari supplier ini tanpa ada Surat Pesanan (PO) yang sudah disetujui sebelumnya.
- **Tab Daftar Barang Relasi (`ProductsRelationManager`)**:
  - Menampilkan semua barang yang disuplai oleh supplier bersangkutan.
  - Membedakan barang pasokan utama (global) vs pasokan khusus cabang.
  - Penambahan barang langsung dari dalam halaman detail supplier.
  - Fitur **Pindah Supplier / Sub-Divisi** (Per baris maupun Massal/Bulk Action) dengan opsi penetapan ke cabang tertentu.
  - **Pewarisan Harga Otomatis**: Saat produk dipetakan ke cabang tertentu melalui supplier, sistem secara otomatis mewarisi harga beli (HPP), harga jual 1-3, dan margin dari data master produk.
- **Isolasi Cabang**: Pengguna dengan cabang tertentu yang terkunci hanya akan melihat produk-produk yang terdaftar dan dialokasikan untuk cabangnya saja.

---

## 2. Struktur Formulir Data Supplier

### A. Informasi Umum & Kontak
1. **Nama Supplier**: Nama legal atau nama dagang supplier (Wajib diisi).
2. **Kode Supplier**: Kode singkatan unik untuk identifikasi faktur dan PO (contoh: `SPL-WINGS`, `SPL-UNILVR`).
3. **Nomor Telepon & WhatsApp**: Kontak operasional untuk konfirmasi PO dan penagihan.
4. **Email**: Alamat email untuk pengiriman PDF PO otomatis.
5. **Alamat Lengkap**: Alamat operasional untuk penjemputan barang atau retur fisik.
6. **NPWP**: Nomor Pokok Wajib Pajak untuk keperluan penerbitan Faktur Pajak Pembelian.

### B. Aturan Pembayaran & Kontrol Operasional
1. **Term of Payment (TOP) / Jatuh Tempo (Hari)**:
   - Angka hari tempo kredit sejak tanggal faktur fisik.
   - Digunakan oleh sistem untuk otomatis menghitung *Tanggal Jatuh Tempo* pada Faktur Penerimaan Barang (GR) dan Kontrabon.
2. **Status Konsinyasi (Toggle `is_consignment`)**:
   - Jika AKTIF: Barang yang masuk dianggap titipan. Sistem tidak membuat jurnal hutang usaha saat GR, melainkan menunggu laporan rekonsiliasi penjualan konsinyasi.
3. **Penerimaan Wajib PO (Toggle `gr_requires_po`)**:
   - Jika AKTIF: Gudang dilarang menerima faktur tanpa nomor PO resmi yang berstatus `APPROVED`.
4. **Sub-Divisi Supplier (Repeater)**:
   - Daftarkan sub-divisi yang dimiliki supplier beserta nama penanggung jawab dan nomor kontaknya masing-masing.

---

## 3. Tab Daftar Barang (Products Relation Manager)

Saat membuka halaman **Edit Supplier**, di bagian bawah terdapat tab relasi **Daftar Barang**. Tab ini memberikan kendali penuh terhadap katalog barang supplier tersebut:

### Fitur & Kolom pada Tabel Daftar Barang:
1. **Cakupan Pasokan**:
   - Menampilkan apakah barang dipasok ke **Semua Cabang** (Utama) atau **Khusus Cabang Tertentu** (Override).
2. **Informasi Harga & Margin**:
   - Menampilkan Barcode, Nama Produk, Kategori, Harga Beli Master, Margin, dan Harga Jual (Gol 1 s/d 3).
3. **Tombol "Tambah Barang ke Supplier Ini" (Header Action)**:
   - Memilih produk master yang sudah ada untuk langsung dihubungkan ke supplier ini.
   - Dapat menentukan apakah pasokan ini berlaku untuk Semua Cabang atau Khusus Cabang tertentu.

### Fitur Pindah Supplier / Sub-Divisi:
Jika terjadi perubahan distributor atau pengalihan rute pasokan barang:
1. **Pindah Per Baris (Row Action)**:
   - Klik tombol titik tiga / icon panah pada baris produk yang diinginkan.
   - Pilih opsi **Pindah Supplier / Sub Divisi**.
   - Pilih Supplier Baru dan Sub-Divisi target.
   - Tentukan lingkup pemindahan:
     - *Semua Cabang (Global)*: Mengubah supplier master dari produk tersebut.
     - *Cabang Tertentu*: Memindahkan pasokan hanya untuk cabang yang dipilih (misal: Cabang Sariwangi beralih ke agen lokal, sedangkan cabang lain tetap ke distributor pusat).
2. **Pindah Massal (Bulk Action)**:
   - Centang beberapa produk sekaligus pada tabel daftar barang.
   - Klik dropdown **Aksi Massal** di atas tabel dan pilih **Pindah Supplier Massal**.
   - Tentukan supplier target dan cabang penerima.
   - Sistem akan memproses pemindahan seluruh produk yang dipilih sekaligus.

> [!TIP]
> **Sinkronisasi Harga Otomatis**: Saat produk dipindahkan ke supplier cabang tertentu, sistem secara otomatis memastikan stok cabang dibuatkan catatan harga jual default yang sinkron dengan master produk, sehingga kasir cabang tidak mengalami kendala harga Rp 0.

---

## 4. Panduan Langkah demi Langkah (Step-by-Step SOP)

### Cara Mendaftarkan Supplier Baru
1. Buka menu **Data Master > Pemasok**.
2. Klik tombol **Buat Pemasok** di kanan atas.
3. Masukkan **Nama Supplier** dan **Kode Supplier**.
4. Isi kontak telepon, penanggung jawab, dan alamat.
5. Tentukan **Jatuh Tempo (Hari)** (misal: `14` untuk tempo 2 minggu, atau `0` untuk tunai/COD).
6. Jika memiliki divisi khusus, tambahkan di bagian **Sub Divisi**.
7. Klik **Simpan**.

### Cara Menghubungkan Produk ke Supplier
1. Buka menu **Pemasok**, lalu klik **Edit** pada supplier yang dituju.
2. Gulir ke bawah menuju tab **Daftar Barang**.
3. Klik tombol **Tambah Barang**.
4. Cari produk berdasarkan nama atau barcode, pilih sub-divisi terkait, dan simpan.

---

## 5. Pertanyaan Umum & Aturan Sistem (FAQ)

**T: Kenapa user kasir cabang saya tidak bisa melihat seluruh produk di tab barang supplier?**  
J: SM Inventory menerapkan prinsip keamanan data berbasis cabang. Jika staf kasir/gudang telah ditetapkan ke cabang tertentu (misal: Cabang Sariwangi), maka mereka hanya dapat melihat barang-barang yang aktif dan terdaftar untuk cabang mereka sendiri.

**T: Apa yang terjadi jika supplier dihapus sementara produknya masih memiliki stok?**  
J: Sistem melindungi integritas audit transaksi. Supplier yang memiliki riwayat transaksi PO, GR, atau produk aktif tidak dapat dihapus permanen (*Protected by Foreign Key Constraint*), melainkan harus dinonaktifkan statusnya.
