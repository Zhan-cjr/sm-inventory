# Buku Panduan Pengguna: Kontrabon Hutang Supplier

Menu **Kontrabon** adalah instrumen rekonsiliasi dan pengendalian hutang usaha (*Accounts Payable / AP*) yang digunakan oleh bagian akuntansi & keuangan untuk mengelompokkan beberapa faktur Penerimaan Barang (GR) yang belum terbayar ke dalam satu bundel dokumen penagihan resmi, menetapkan jadwal rencana bayar, dan menerbitkan tanda terima penagihan kepada supplier.

---

## 1. Ikhtisar & Siklus Kontrabon

Di industri retail modern, distributor mengantarkan barang berkali-kali dalam sebulan. Alih-alih membayar per faktur secara terpisah, distributor mengumpulkan faktur-faktur fisik tersebut dan mengajukan **Tukar Faktur / Kontrabon**:

```
[Kumpulkan Faktur GR] ──> [Verifikasi & Jadwal Bayar] ──> [Approval Keuangan] ──> [Pelunasan Kas/Bank]
  (Draft Kontrabon)         (Status: VERIFIED)            (Status: APPROVED)       (Status: PAID)
```

### Keuntungan Sistem Kontrabon SM Inventory:
- **Pencegahan Double Payment**: Faktur GR yang sudah dimasukkan ke dalam suatu nomor kontrabon akan dikunci oleh sistem dan tidak dapat dimasukkan lagi ke kontrabon lain.
- **Deteksi Markup Harga Beli**: Sistem secara otomatis membandingkan harga beli pada faktur GR dengan master produk untuk mendeteksi jika supplier menaikkan harga sepihak tanpa kesepakatan.
- **Perencanaan Arus Kas (Cashflow Planning)**: Bagian keuangan dapat memfilter total hutang yang jatuh tempo pada minggu atau bulan tertentu berdasarkan *Tanggal Rencana Cair*.
- **Tanda Terima Resmi**: Cetak dokumen Tanda Terima Kontrabon sebagai bukti sah penyerahan dokumen penagihan fisik oleh kolektor/sales supplier.

---

## 2. Struktur Formulir Pembuatan Kontrabon

### A. Informasi Dokumen & Supplier
1. **Nomor Kontrabon**: Format otomatis (contoh: `KB-202610-0012`).
2. **Pilih Supplier**: Menentukan supplier yang sedang melakukan tukar faktur.
3. **Pilih Sub-Divisi (Opsional)**: Filter penagihan jika supplier memisahkan divisi faktur.
4. **Tanggal Kontrabon**: Tanggal dokumen tukar faktur diproses.
5. **Tanggal Rencana Cair (Payment Due Date)**: Tanggal yang disepakati untuk transfer pembayaran pelunasan dana.
6. **Catatan / Nomor Berkas**: Nomor tanda terima fisik dari pihak supplier.

### B. Pemilihan Faktur Penerimaan Barang (GR Items)
1. Setelah memilih nama supplier, sistem akan secara cerdas menampilkan daftar seluruh faktur Penerimaan Barang (GR) milik supplier tersebut yang **masih memiliki sisa hutang (Outstanding AP)**.
2. Setiap baris faktur menampilkan:
   - Nomor Dokumen GR & Nomor Faktur Fisik Kertas.
   - Tanggal Faktur & Tanggal Jatuh Tempo Asli.
   - Nilai Total Faktur & Sisa Hutang yang Belum Dibayar.
3. Centang kotak di samping faktur-faktur yang diserahkan oleh penagih supplier.
4. Sistem secara otomatis menjumlahkan total nominal tagihan pada bagian **Total Kontrabon**.

### C. Pemotongan Nota Retur / Deductions (Jika Ada)
- Jika ada pengembalian barang (*Purchase Return*) yang belum dipotong atau biaya penalti keterlambatan kirim (*Supplier Deduction*), nilai tersebut dapat dikurangkan langsung pada formulir kontrabon sehingga total nilai transfer menjadi netto.

---

## 3. Alur Kerja Operasional (Step-by-Step SOP)

### Langkah 1: Menerima Berkas dari Kolektor Supplier
1. Penagih/kolektor supplier datang membawa bundel faktur asli fisik dan surat jalan bertanda tangan staf gudang.
2. Buka menu **Transaksi Pembelian > Kontrabon**.
3. Klik tombol **Buat Kontrabon**.
4. Pilih **Supplier** bersangkutan.
5. Beri centang pada nomor-nomor faktur fisik yang diserahkan oleh penagih.
6. Cocokkan angka rupiah setiap faktur di kertas dengan angka di layar.
7. Masukkan **Tanggal Rencana Cair** sesuai jadwal pembayaran mingguan/bulanan toko (misal: setiap hari Kamis).
8. Klik **Simpan** dengan status `VERIFIED`.

### Langkah 2: Mencetak Tanda Terima Kontrabon
1. Pada detail kontrabon yang baru disimpan, klik tombol **Cetak Tanda Terima Kontrabon**.
2. Cetak rangkap 2:
   - **Lembar 1 (Asli)**: Diserahkan kepada penagih supplier sebagai bukti sah bahwa berkas faktur asli sudah diterima toko dan akan dicairkan pada tanggal yang tertera.
   - **Lembar 2 (Arsip)**: Ditempelkan pada bundel berkas fisik faktur dan disimpan di map ordner bagian keuangan.

### Langkah 3: Pelunasan Saat Jatuh Tempo (Status PAID)
1. Ketika tiba tanggal rencana cair, staf bendahara/keuangan membuka kontrabon tersebut.
2. Lakukan transfer bank dari rekening operasional toko ke rekening supplier.
3. Buka menu **Pelunasan Hutang Pembelian (Purchase Payment)** atau klik tombol *Bayar Kontrabon Ini*.
4. Masukkan bukti transfer bank dan selesaikan transaksi.
5. Status kontrabon otomatis berubah menjadi `PAID` (Lunas) dan seluruh faktur GR di dalamnya ditandai lunas.

---

## 4. Pertanyaan Umum & Aturan Khusus (FAQ)

**T: Kenapa faktur GR yang dicari tidak muncul di daftar pilihan kontrabon?**  
J: Ada 3 kemungkinan:
1. Faktur GR tersebut berstatus Konsinyasi (Konsinyasi ditagihkan via menu *Consignment Billing*, bukan Kontrabon biasa).
2. Faktur GR tersebut sudah pernah dimasukkan ke dalam dokumen kontrabon lain yang masih aktif/belum lunas.
3. Faktur GR tersebut sudah lunas (nilai sisa hutang Rp 0).

**T: Bisakah membatalkan kontrabon jika berkas fisik ditarik kembali oleh supplier?**  
J: Ya, selama kontrabon belum dibayar (belum berstatus `PAID`), pengguna dapat membatalkannya dengan mengubah status menjadi `CANCELLED`. Seluruh faktur GR di dalamnya akan otomatis bebas kembali dan dapat dipilih di kontrabon baru.
