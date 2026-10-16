# Buku Panduan Pengguna: Pesanan & Pengaturan E-Commerce Toko

Modul **E-Commerce** di SM Inventory ERP menghubungkan toko fisik Anda dengan saluran penjualan online, memungkinkan pelanggan memesan produk toko dari rumah via aplikasi web dan dikirimkan oleh armada kurir toko atau ekspedisi resmi.

---

## 1. Ikhtisar & Alur Pemrosesan Pesanan Online

Setiap pesanan online yang masuk diproses dengan alur kerja terstruktur:

```
[Pesanan Baru Masuk] ──> [Verifikasi Pembayaran] ──> [Packing Barang] ──> [Kirim / Pick Up] ──> [Selesai]
  (Status: PENDING)         (Status: PAID)             (Status: PROCESSING)  (Status: SHIPPED)    (Status: COMPLETED)
```

1. **PENDING (Menunggu Pembayaran)**: Pelanggan telah membuat pesanan namun belum menyelesaikan pembayaran melalui payment gateway.
2. **PAID (Sudah Dibayar)**: Dana terverifikasi masuk secara otomatis. Sistem mencadangkan stok barang dari rak toko agar tidak dibeli pembeli kasir langsung.
3. **PROCESSING (Sedang Dikemas / Packing)**: Tim toko menyiapkan fisik barang dari rak dan mengemasnya ke dalam kardus/kantong belanja.
4. **SHIPPED (Dalam Pengiriman)**: Pesanan diserahkan ke kurir toko atau agen ekspedisi (JNE/J&T/SiCepat) dengan nomor resi pelacakan.
5. **COMPLETED (Selesai)**: Barang telah diterima oleh pelanggan dengan selamat. Saldo penjualan tercatat resmi ke laporan omset e-commerce.

---

## 2. Langkah demi Langkah Pemrosesan Pesanan (SOP Toko)

1. Buka menu **E-Commerce > Pesanan E-Commerce**.
2. Filter pesanan yang berstatus **PAID (Siap Kemas)**.
3. Klik **Lihat Pesanan** untuk melihat daftar barang yang harus diambil di rak (*Picking List*).
4. Klik tombol **Cetak Surat Jalan & Label Pengiriman (Shipping Label)** untuk ditempelkan di paket kemasan.
5. Setelah barang siap, serahkan ke kurir dan klik aksi **Update Status Pengiriman (Mark as Shipped)** serta masukkan nomor resi kurir.
