<div class="prose prose-sm dark:prose-invert space-y-3">
    <p>
        <strong>Saran Order AI (Smart Restock)</strong> memprediksi kebutuhan kulakan secara otomatis berdasarkan perputaran penjualan harian aktual di kasir, waktu pengiriman supplier, dan batas stok aman.
    </p>

    <h4 class="font-bold text-base text-primary-600 dark:text-primary-400">1. Penjelasan Kolom Data</h4>
    <ul class="space-y-1.5 list-disc pl-5">
        <li>
            <strong>Stok Saat Ini:</strong> Sisa stok fisik yang tercatat aktif di cabang saat ini.
        </li>
        <li>
            <strong>ADS (Average Daily Sales):</strong> Kecepatan rata-rata barang terjual per hari. Dihitung dari total penjualan 30 hari terakhir (jika stok sempat kosong lama, AI otomatis memeriksa riwayat 90 hari agar barang laris tidak luput dipesan).
        </li>
        <li>
            <strong>Titik Pesan (ROP - Reorder Point):</strong> Batas minimal stok untuk mulai memesan barang kembali. Didapat dari <em>(ADS × Waktu Pengiriman Supplier) + Cadangan Aman (Safety Stock)</em>. Jika stok menyentuh atau di bawah angka ini, Anda berisiko kehabisan barang.
        </li>
        <li>
            <strong>Target Stok (Hari):</strong> Target ketahanan persediaan di toko (default 14–30 hari). Menentukan berapa lama stok ini diharapkan bertahan sebelum jadwal kulakan berikutnya.
        </li>
        <li>
            <strong>Saran Pesan:</strong> Estimasi jumlah unit yang direkomendasikan untuk dibeli hari ini agar persediaan toko tercukupi hingga target hari ke depan.
        </li>
    </ul>

    <h4 class="font-bold text-base text-primary-600 dark:text-primary-400">2. Arti Warna & Status</h4>
    <ul class="space-y-1.5 list-disc pl-5">
        <li>
            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-danger-100 text-danger-800 dark:bg-danger-900/50 dark:text-danger-300">HABIS (CRITICAL)</span>: 
            Stok fisik sudah 0, minus, atau berada di bawah cadangan aman darurat. Prioritaskan untuk segera di-order!
        </li>
        <li>
            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-warning-100 text-warning-800 dark:bg-warning-900/50 dark:text-warning-300">PERLU ORDER</span>: 
            Stok fisik masih ada namun sudah menyentuh Titik Pesan (ROP). Sudah saatnya membuat PO sebelum kehabisan.
        </li>
        <li>
            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-success-100 text-success-800 dark:bg-success-900/50 dark:text-success-300">AMAN</span>: 
            Stok masih cukup untuk memenuhi penjualan, atau barang tidak bergerak (ADS = 0) sehingga tidak disarankan menambah stok mati.
        </li>
    </ul>

    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-sm border-l-4 border-blue-500 text-blue-900 dark:text-blue-200">
        <strong>💡 Tips Operasional:</strong>
        <ul class="list-disc pl-5 mt-1 space-y-1">
            <li>Gunakan tombol <strong>"Segarkan Data AI"</strong> di pojok kanan atas kapan saja Anda ingin menghitung ulang data terbaru.</li>
            <li>Anda dapat mencentang beberapa produk dan klik <strong>"Buat PO Terpilih"</strong>. Sistem akan otomatis memecah draft PO sesuai Pemasok dan Sub Divisi masing-masing.</li>
        </ul>
    </div>
</div>
