# Buku Panduan Pengguna: Pelanggan, Member & Program Loyalitas Poin

Menu **Pelanggan & Member** mengelola seluruh data konsumen setia toko, kartu keanggotaan (*Membership*), sistem akumulasi poin loyalitas belanja, tingkatan level member (*Member Tiers*), serta program penukaran hadiah (*Reward Redemption*).

---

## 1. Ikhtisar & Fitur Loyalitas Pelanggan

- **Identifikasi Cepat di Kasir**: Kasir dapat memanggil profil pelanggan dengan memindai barcode kartu member atau cukup mengetikkan **Nomor WhatsApp / HP**.
- **Akumulasi Poin Belanja Otomatis**:
  - Setiap kelipatan nominal belanja tertentu (misal: setiap Rp 10.000), sistem secara otomatis menambahkan poin ke saldo member.
- **Tingkatan Level Member (Member Tiers)**:
  - Pelanggan diklasifikasikan ke dalam tingkatan (contoh: *Silver*, *Gold*, *Platinum*) berdasarkan total akumulasi belanja dalam periode tertentu.
  - Setiap tier dapat memiliki hak istimewa berbeda, seperti diskon belanja otomatis (misal: diskon 2% untuk Gold, diskon 5% untuk Platinum) atau kelipatan poin lebih tinggi.
- **Katalog Hadiah & Penukaran Poin (Rewards)**:
  - Poin yang terkumpul dapat ditukarkan langsung di kasir dengan barang gratis (minyak goreng, tumbler, voucher belanja) atau potongan harga.
- **Pelacakan Nilai Seumur Hidup (Customer Lifetime Value - CLV)**:
  - Melihat total riwayat transaksi, frekuensi kunjungan belanja toko, dan produk-produk favorit yang sering dibeli oleh pelanggan bersangkutan.

---

## 2. Struktur Formulir Data Pelanggan

1. **Nama Lengkap**: Nama pelanggan (Wajib diisi).
2. **Nomor HP / WhatsApp**: Nomor aktif konsumen (kunci identifikasi utama di kasir POS).
3. **Nomor Kartu Member**: Barcode cetak pada kartu fisik plastik (jika toko menerbitkan kartu fisik).
4. **Alamat & Kota**: Alamat domisili untuk pengiriman pesanan e-commerce.
5. **Tanggal Lahir**: Digunakan untuk program ucapan selamat dan promo diskon hari ulang tahun member (*Birthday Voucher*).
6. **Tier Member**: Level keanggotaan saat ini (*Silver / Gold / Platinum*).
7. **Saldo Poin Aktif**: Jumlah poin yang siap ditukarkan saat ini (*read-only* dipengaruhi mutasi transaksi).

---

## 3. Langkah demi Langkah Pelaksanaan (SOP)

### Cara Mendaftarkan Member Baru di Kasir:
1. Saat kasir melayani konsumen, tekan tombol **F4 (Input Member)**.
2. Ketik nomor HP konsumen. Jika belum terdaftar, klik tombol **+ Daftar Member Baru**.
3. Masukkan **Nama Pelanggan** dan **Nomor WhatsApp**.
4. Scan barcode kartu fisik jika ada kartu baru yang diserahkan.
5. Klik **Simpan & Sambungkan**. Transaksi langsung terhubung dengan member baru tersebut dan poin transaksi langsung masuk ke saldonya.

### Cara Melakukan Penukaran Poin (Redeem Reward):
1. Buka sub-menu **Penukaran Hadiah (Reward Redemptions)** atau langsung di kasir.
2. Cari nama/nomor HP member.
3. Pilih hadiah dari katalog reward yang poinnya mencukupi.
4. Klik **Tukarkan Hadiah**. Saldo poin member otomatis berkurang sesuai nilai poin hadiah dan bukti penukaran dicetak oleh printer kasir.
