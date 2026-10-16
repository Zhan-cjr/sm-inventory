# Buku Panduan Pengguna: Penerimaan Barang (Goods Receipt / GR)

Menu **Penerimaan Barang (Goods Receipt / GR)** digunakan oleh staf gudang dan admin inventori untuk mencatat kedatangan fisik barang dari supplier, memeriksa kesesuaian dengan Surat Pesanan (PO), menghitung nilai faktur pembelian riil (diskon bertingkat & PPN), serta menambah saldo stok fisik secara otomatis.

---

## 1. Ikhtisar & Fitur Utama

- **Penerimaan Berbasis PO vs Non-PO**:
  - Penerimaan dari PO resmi: Memuat otomatis daftar barang yang dipesan, harga kesepakatan, dan melacak kuantitas yang belum terkirim (*partial delivery*).
  - Penerimaan Langsung (Non-PO): Untuk pembelian darurat, barang lokal harian, atau supplier yang tidak mewajibkan PO.
- **Validasi Anti-Duplikasi Faktur**: Sistem secara ketat memeriksa kombinasi **Nomor Faktur Fisik + Supplier**. Staf tidak dapat menginput nomor faktur yang sama dari supplier yang sama untuk mencegah pembayaran ganda (*double payment*).
- **Perhitungan Diskon Bertingkat & Pajak (PPN 11%)**:
  - Dukungan Diskon 1 (%), Diskon 2 (%), dan Diskon Tunai/Nominal (Rp).
  - Pilihan status PPN: *Non-PPN*, *PPN Exclude* (ditambahkan ke DPP), atau *PPN Include* (sudah termasuk dalam DPP).
- **Penambahan Stok Fisik Instan**: Saat dokumen GR disimpan dan berstatus diterima, stok fisik di cabang terkait langsung bertambah pada kartu stok (*inventory log*).
- **Pembentukan Hutang Dagang Otomatis**: Jika pembelian bersifat kredit/tempo, nilai faktur otomatis masuk ke buku pembantu hutang supplier untuk kemudian ditagihkan via **Kontrabon** atau **Pelunasan Hutang**.

---

## 2. Struktur Formulir Penerimaan Barang

### A. Informasi Dokumen & Supplier
1. **Nomor GR**: Dibuat otomatis oleh sistem dengan format standar (contoh: `GR-202610-0001`).
2. **Pilih Supplier**: Menentukan supplier pengirim barang.
3. **Pilih Nomor PO (Opsional)**:
   - Jika dipilih: Seluruh baris produk dari PO bersangkutan akan otomatis terisi ke tabel item barang.
   - Sistem akan memvalidasi apakah kuantitas yang diterima melebihi sisa kuantitas PO (*over-delivery protection*).
4. **Nomor Faktur Fisik**: Nomor cetak faktur kertas / surat jalan resmi dari supplier (Wajib diisi).
5. **Tanggal Faktur**: Tanggal yang tertera pada faktur kertas fisik.
6. **Tanggal Diterima**: Tanggal barang fisik masuk dan dibongkar di gudang.
7. **Jatuh Tempo**: Dihitung otomatis berdasarkan setting *Term of Payment (TOP)* supplier, namun dapat disesuaikan manual jika ada kesepakatan tempo khusus.
8. **Cabang Penerima**: Cabang tempat barang fisik disimpan.

### B. Tabel Rincian Barang yang Diterima
Setiap baris barang mencakup data mendalam:
1. **Produk**: Pencarian cepat via nama produk atau barcode.
2. **Kuantitas Diterima (Qty)**: Jumlah fisik barang yang tiba dalam kondisi baik.
3. **Harga Satuan (Harga Beli / DPP)**: Harga beli per unit.
4. **Diskon 1 (%)**: Persentase diskon promosi distributor (misal: 5%).
5. **Diskon 2 (%)**: Persentase diskon tambahan (misal: 2%).
6. **Diskon Nominal (Rp)**: Potongan tunai per unit jika ada.
7. **Subtotal Baris**: Dihitung otomatis:  
   $$\text{Subtotal} = (\text{Qty} \times \text{Harga Beli}) - \text{Total Diskon}$$

### C. Ringkasan Finansial & Pajak (Footer Form)
1. **Total DPP**: Akumulasi nilai bersih barang sebelum PPN.
2. **Diskon Global Faktur**: Diskon ekstra yang diberikan supplier pada tingkat keseluruhan faktur.
3. **Pengaturan PPN**:
   - *Tanpa PPN (0%)*: Untuk barang non-BKP atau supplier non-PKP.
   - *PPN 11% Exclude*: Nilai PPN 11% ditambahkan di atas DPP sehingga total hutang meningkat.
   - *PPN 11% Include*: Nilai PPN dihitung dari dalam DPP tanpa menambah total tagihan akhir.
4. **Biaya Kirim / Ekstra**: Ongkos bongkar muat atau ekspedisi jika dibebankan ke faktur.
5. **Total Akhir Faktur (Grand Total)**: Nilai riil yang wajib dibayarkan ke supplier.

---

## 3. Alur Kerja Operasional (Step-by-Step SOP)

### Langkah 1: Memeriksa Surat Jalan & Fisik Barang
1. Terima dokumen fisik (Surat Jalan & Faktur Asli) dari sopir/sales distributor.
2. Lakukan penghitungan fisik barang dan periksa tanggal kadaluarsa (*expired date*). Pastikan tidak ada kemasan yang bocor, rusak, atau penyok.

### Langkah 2: Menginput GR di Sistem
1. Buka menu **Transaksi Pembelian > Penerimaan Barang (GR)**.
2. Klik tombol **Buat Penerimaan Barang**.
3. Pilih nama **Supplier**.
4. Jika sebelumnya ada PO, pilih nomor **Purchase Order (PO)**. Sistem akan memuat item pesanan.
5. Ketik **Nomor Faktur Fisik** yang ada di kertas faktur supplier.
6. Masukkan jumlah fisik riil yang diterima di kolom **Qty Diterima**. Jika barang yang datang kurang dari pesanan PO, masukkan sesuai fisik yang ada (*Partial Receipt*).
7. Periksa kesesuaian harga beli per unit dan diskon dengan dokumen kertas.
8. Pilih status **PPN** (Non-PPN atau 11%).
9. Pastikan nilai **Grand Total** di sistem cocok persis dengan total rupiah di faktur fisik.
10. Klik **Simpan**.

### Langkah 3: Dampak Pasca-Penyimpanan
- Stok fisik di cabang penerima bertambah seketika.
- Status PO (jika ada) berubah menjadi `PARTIAL` (jika baru sebagian diterima) atau `COMPLETED` (jika seluruh pesanan telah tiba).
- Terbentuk catatan tagihan hutang baru pada modul **Kontrabon** dan **Laporan Hutang**.

---

## 4. Troubleshooting & Aturan Penting

**T: Muncul peringatan "Nomor faktur fisik sudah pernah diinput untuk supplier ini"?**  
J: Faktur tersebut kemungkinan sudah pernah dicatat oleh staf lain atau pada sesi penerimaan sebelumnya. Cari nomor faktur tersebut di kolom pencarian tabel Penerimaan Barang untuk memeriksa statusnya dan mencegah input ganda.

**T: Apa yang harus dilakukan jika ada barang yang rusak/cacat saat diterima?**  
J: Barang yang rusak jangan dimasukkan ke Qty Diterima, catat kuantitas hanya yang layak jual. Atau jika faktur supplier sudah terlanjur dicetak global, input sesuai faktur lalu segera buat dokumen **Retur Pembelian (Purchase Return)** untuk memotong nota hutang tersebut.
