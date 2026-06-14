<x-filament-panels::page>
    <div class="fi-ta-content bg-white shadow-sm ring-1 ring-gray-950/5 rounded-xl dark:bg-gray-900 dark:ring-white/10 p-6 text-center">
        <h2 class="text-2xl font-bold text-danger-600 dark:text-danger-400 mb-4">Peringatan: Zona Bahaya!</h2>
        
        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto" style="line-height: 1.6;">
            Fitur ini akan menghapus <strong>seluruh data operasional</strong> dari database secara permanen. 
            Data yang akan dihapus meliputi semua Transaksi Penjualan, Transaksi Pembelian, Stok Produk, Data Master Produk, Jurnal Keuangan, Cabang, Terminal Kasir, Supplier, dan Pelanggan/Member.
        </p>

        <p class="text-gray-800 font-semibold dark:text-gray-200 mb-8 max-w-2xl mx-auto bg-warning-50 dark:bg-warning-500/10 p-4 rounded-lg border border-warning-200 dark:border-warning-500/20" style="line-height: 1.6;">
            Data yang akan <strong>DIPERTAHANKAN</strong>: Data Pengguna (Users), Hak Akses (Roles), Daftar Akun COA (Chart of Accounts), Daftar Bank, Member Tiers, dan Adjustment Reasons.
        </p>

        <div class="mt-8">
            <p class="text-sm text-gray-500">
                Silakan klik tombol <strong>"RESET ALL DATA"</strong> di pojok kanan atas halaman ini untuk memulai proses reset.
            </p>
        </div>
    </div>
</x-filament-panels::page>
