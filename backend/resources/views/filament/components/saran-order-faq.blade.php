<div class="prose prose-sm dark:prose-invert">
    <p>
        AI memprediksi kebutuhan restock dengan mempelajari riwayat kecepatan penjualan (velocity) setiap produk. Berikut adalah penjelasan setiap kolom:
    </p>
    <ul>
        <li>
            <strong>Stok Saat Ini</strong>: Sisa stok aktual di gudang cabang saat ini.
        </li>
        <li>
            <strong>ADS (Average Daily Sales)</strong>: Rata-rata barang terjual per hari (dihitung dari total penjualan 30 hari terakhir dibagi 30).
        </li>
        <li>
            <strong>Titik Pesan (ROP)</strong>: Titik batas aman terendah. Didapat dari <strong>ADS × Lead Time</strong> (waktu pengiriman supplier). Jika stok menyentuh atau turun di bawah angka ini, Anda berisiko kehabisan barang saat menunggu kiriman datang.
        </li>
        <li>
            <strong>Target Stok (Hari)</strong>: Diambil dari settingan <em>Target Inventori</em> pada menu stok cabang. Default adalah 30 hari. Ini menentukan seberapa lama Anda ingin stok ini bertahan di gudang sebelum restock berikutnya.
        </li>
        <li>
            <strong>Saran Pesan</strong>: Jumlah ideal yang harus dipesan hari ini untuk memenuhi target hari penjualan ke depan. <br>
            <em>Rumus: ((ADS × Target Stok) + Titik Pesan) - Stok Saat Ini</em>
        </li>
    </ul>
    
    <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm border-l-4 border-primary-500">
        <strong>💡 Tips:</strong> Anda dapat mengubah "Target Stok (Hari)" per produk di menu <strong>Products -> Edit -> Daftar Stok Cabang</strong>. AI akan otomatis menyesuaikan hitungannya esok hari!
    </div>
</div>
