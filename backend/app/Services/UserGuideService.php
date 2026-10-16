<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserGuideService
{
    /**
     * Daftar seluruh menu panduan terstruktur murni untuk Backend Admin Filament
     */
    public static function getMenuRegistry(): array
    {
        return [
            // ==================== 1. BERANDA ====================
            'dashboard' => [
                'title' => 'Dashboard & Indikator Utama',
                'category' => 'BERANDA',
                'icon' => 'heroicon-o-home',
                'patterns' => ['admin', 'admin/dashboard'],
                'file' => 'dashboard.md',
                'summary' => 'Ringkasan omset harian, grafik penjualan, peringatan stok menipis, dan ringkasan kas.',
            ],

            // ==================== 2. DATA MASTER ====================
            'products' => [
                'title' => 'Master Produk & Barang',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-cube',
                'patterns' => ['admin/products*'],
                'file' => 'products.md',
                'summary' => 'Pengelolaan data barang, SKU, barcode, multi-barcode, harga beli (HPP), harga jual 3 tingkat, dan stok cabang.',
            ],
            'categories' => [
                'title' => 'Kategori Produk',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-tag',
                'patterns' => ['admin/categories*'],
                'file' => 'categories.md',
                'summary' => 'Pengelompokan barang berdasarkan kategori utama dan sub-kategori untuk kemudahan laporan dan analisis.',
            ],
            'suppliers' => [
                'title' => 'Pemasok & Sub-Divisi (Supplier)',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-truck',
                'patterns' => ['admin/suppliers*'],
                'file' => 'suppliers.md',
                'summary' => 'Pendaftaran supplier, sub-divisi, syarat pembayaran (TOP/Konsinyasi), mapping barang khusus cabang, dan bulk action pindah supplier.',
            ],
            'customers' => [
                'title' => 'Pelanggan & Member',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-user-group',
                'patterns' => ['admin/customers*', 'admin/member-tiers*', 'admin/rewards*'],
                'file' => 'customers.md',
                'summary' => 'Manajemen member pelanggan, poin loyalitas belanja, tier diskon, dan riwayat belanja pelanggan.',
            ],
            'listing-produks' => [
                'title' => 'Listing Produk & Pilot Cabang',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-clipboard-document-list',
                'patterns' => ['admin/listing-produks*'],
                'file' => 'listing-produks.md',
                'summary' => 'Pendaftaran barang baru masa uji coba (trial), biaya listing, alokasi cabang pilot, dan evaluasi penjualan.',
            ],
            'product-assemblies' => [
                'title' => 'Paket Bundling & Perakitan (BOM)',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-gift',
                'patterns' => ['admin/product-assemblies*'],
                'file' => 'product-assemblies.md',
                'summary' => 'Perakitan paket sembako, parcel lebaran, resep Bill of Materials (BOM), dan pemotongan stok bahan otomatis.',
            ],
            'barcode-printer' => [
                'title' => 'Cetak Label Barcode & Price Tag Rak',
                'category' => 'DATA MASTER',
                'icon' => 'heroicon-o-qr-code',
                'patterns' => ['admin/barcode-printer*'],
                'file' => 'barcode-printer.md',
                'summary' => 'Pencetakan stiker barcode produk kemasan dan price tag rak gondola (shelving label) ukuran thermal roll atau A4.',
            ],

            // ==================== 3. PERSEDIAAN ====================
            'stock-opname' => [
                'title' => 'Stock Opname (SO)',
                'category' => 'PERSEDIAAN',
                'icon' => 'heroicon-o-calculator',
                'patterns' => ['admin/stock-opnames*', 'admin/stock-opname-sessions*', 'admin/stock-opname-racks*'],
                'file' => 'stock-opname.md',
                'summary' => 'Alur pembukaan sesi opname, penghitungan fisik per rak/area, rekonsiliasi selisih stok, dan approval penyesuaian akhir.',
            ],
            'stock-adjustments' => [
                'title' => 'Koreksi Stok (Stock Adjustment)',
                'category' => 'PERSEDIAAN',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'patterns' => ['admin/stock-adjustments*', 'admin/adjustment-reasons*'],
                'file' => 'stock-adjustments.md',
                'summary' => 'Pencatatan barang rusak, barang hilang/kadaluarsa, atau penambahan stok manual dengan kode alasan pertanggungjawaban.',
            ],
            'stock-transfers' => [
                'title' => 'Stok Transfer Antar Cabang',
                'category' => 'PERSEDIAAN',
                'icon' => 'heroicon-o-arrows-right-left',
                'patterns' => ['admin/stock-transfers*', 'admin/suggested-stock-transfers*'],
                'file' => 'stock-transfers.md',
                'summary' => 'Mutasi barang antar cabang/gudang, status pengiriman jalan (in-transit), konfirmasi terima, dan rekomendasi transfer overstock.',
            ],
            'warehouse-checks' => [
                'title' => 'Cek Fisik Gudang (Spot Check)',
                'category' => 'PERSEDIAAN',
                'icon' => 'heroicon-o-clipboard-document-check',
                'patterns' => ['admin/warehouse-checks*'],
                'file' => 'warehouse-checks.md',
                'summary' => 'Audit cepat fisik barang gudang, verifikasi kondisi kemasan dan expired date sebelum penerimaan atau mutasi.',
            ],
            'inventory-logs' => [
                'title' => 'Kartu Stok & Riwayat Mutasi',
                'category' => 'PERSEDIAAN',
                'icon' => 'heroicon-o-queue-list',
                'patterns' => ['admin/inventory-logs*'],
                'file' => 'inventory-logs.md',
                'summary' => 'Buku audit kartu stok lengkap: mencatat setiap detik perubahan saldo fisik barang, tipe dokumen mutasi, dan staf pelaksana.',
            ],

            // ==================== 4. TRANSAKSI ====================
            'purchase-orders' => [
                'title' => 'Pesanan Pembelian (PO)',
                'category' => 'TRANSAKSI',
                'icon' => 'heroicon-o-document-plus',
                'patterns' => ['admin/purchase-orders*', 'admin/suggested-orders*'],
                'file' => 'purchase-orders.md',
                'summary' => 'Penerbitan PO resmi ke supplier, approval manajer, saran order ADS x lead time, cetak PO, dan pelacakan barang datang.',
            ],
            'goods-receipts' => [
                'title' => 'Penerimaan Barang (Goods Receipt / GR)',
                'category' => 'TRANSAKSI',
                'icon' => 'heroicon-o-inbox-arrow-down',
                'patterns' => ['admin/goods-receipts*'],
                'file' => 'goods-receipts.md',
                'summary' => 'Pencatatan faktur masuk fisik dari supplier, verifikasi surat jalan, perhitungan DPP & PPN 11%, dan penambahan stok otomatis.',
            ],
            'kontrabons' => [
                'title' => 'Kontrabon Hutang Supplier',
                'category' => 'TRANSAKSI',
                'icon' => 'heroicon-o-document-text',
                'patterns' => ['admin/kontrabons*'],
                'file' => 'kontrabons.md',
                'summary' => 'Pengelompokan faktur-faktur jatuh tempo menjadi satu berkas penagihan (nota kontrabon), verifikasi harga beli, dan jadwal pembayaran.',
            ],
            'purchase-payments' => [
                'title' => 'Pelunasan Hutang Pembelian',
                'category' => 'TRANSAKSI',
                'icon' => 'heroicon-o-banknotes',
                'patterns' => ['admin/purchase-payments*'],
                'file' => 'purchase-payments.md',
                'summary' => 'Pencatatan pengeluaran kas/bank untuk pelunasan faktur supplier dan cetak voucher tanda bukti pembayaran.',
            ],
            'purchase-returns' => [
                'title' => 'Retur Pembelian ke Supplier',
                'category' => 'TRANSAKSI',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'patterns' => ['admin/purchase-returns*'],
                'file' => 'purchase-returns.md',
                'summary' => 'Pengembalian barang rusak atau tidak sesuai ke supplier, pemotongan nilai hutang (Nota Debet), dan cetak surat jalan retur.',
            ],

            // ==================== 5. LAPORAN/ARSIP ====================
            'transactions' => [
                'title' => 'Arsip Transaksi & Riwayat Penjualan',
                'category' => 'LAPORAN/ARSIP',
                'icon' => 'heroicon-o-receipt-percent',
                'patterns' => ['admin/transactions*', 'admin/arsip-transaksis*', 'admin/arsip-retur-penjualans*'],
                'file' => 'transactions.md',
                'summary' => 'Daftar struk penjualan, cetak ulang struk, pelacakan kasir pelaksana, dan arsip riwayat transaksi lama.',
            ],
            'laporan' => [
                'title' => 'Laporan Penjualan, HPP & Persediaan',
                'category' => 'LAPORAN/ARSIP',
                'icon' => 'heroicon-o-chart-bar-square',
                'patterns' => ['admin/laporan*', 'admin/rekapitulasi-transaksi*', 'admin/analisis-pergerakan-produk*', 'admin/performa-listing*', 'admin/arsip-laporan-eod*', 'admin/rekap-tipe-supplier*'],
                'file' => 'laporan.md',
                'summary' => 'Laporan omset kasir, laba kotor, HPP terjual, rekapitulasi pembayaran (QRIS/EDC/Tunai), dan valuasi aset persediaan.',
            ],

            // ==================== 6. PENGATURAN ====================
            'shifts' => [
                'title' => 'Manajemen Shift Kasir',
                'category' => 'PENGATURAN',
                'icon' => 'heroicon-o-clock',
                'patterns' => ['admin/shifts*'],
                'file' => 'shifts.md',
                'summary' => 'Monitoring buka/tutup shift kasir, kas awal, penjualan tunai, uang fisik, selisih kas, dan status shift.',
            ],
            'pos-settings' => [
                'title' => 'Pengaturan POS (Tombol & Struk)',
                'category' => 'PENGATURAN',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'patterns' => ['admin/pos-settings*'],
                'file' => 'pos-settings.md',
                'summary' => 'Konfigurasi umum kasir, kustomisasi 23 tombol pintasan keyboard dinamis, tata letak struk belanja thermal, dan integrasi timbangan.',
            ],
            'terminals' => [
                'title' => 'Terminal POS & Otorisasi Device',
                'category' => 'PENGATURAN',
                'icon' => 'heroicon-o-computer-desktop',
                'patterns' => ['admin/terminals*', 'admin/pos-devices*'],
                'file' => 'terminals.md',
                'summary' => 'Pendaftaran mesin kassa per cabang, printer thermal, dan otorisasi izin perangkat kasir.',
            ],
            'promotions' => [
                'title' => 'Promosi, Diskon & Voucher',
                'category' => 'PENGATURAN',
                'icon' => 'heroicon-o-sparkles',
                'patterns' => ['admin/promotions*', 'admin/vouchers*', 'admin/market-basket-rules*'],
                'file' => 'promotions.md',
                'summary' => 'Pengaturan promo harga coret, diskon persentase/nominal, syarat minimum belanja, masa berlaku promo, dan voucher kupon.',
            ],
            'users' => [
                'title' => 'Pengguna, Peran & Cabang',
                'category' => 'PENGATURAN',
                'icon' => 'heroicon-o-shield-check',
                'patterns' => ['admin/users*', 'admin/branches*'],
                'file' => 'users.md',
                'summary' => 'Pengaturan akun staf, penetapan cabang kerja kasir/admin, hak akses menu (Shield), dan keamanan akun.',
            ],

            // ==================== 7. KEUANGAN & AKUNTANSI ====================
            'finance-accounting' => [
                'title' => 'Keuangan, Jurnal & Bagan Akun (COA)',
                'category' => 'KEUANGAN & AKUNTANSI',
                'icon' => 'heroicon-o-banknotes',
                'patterns' => ['admin/accounts*', 'admin/journal-entries*', 'admin/expenses*', 'admin/banks*', 'admin/consignment-billing*', 'admin/tutup-buku-tahunan*', 'admin/setup-saldo-awal*'],
                'file' => 'finance-accounting.md',
                'summary' => 'Pencatatan biaya operasional toko, bagan akun (COA), jurnal umum manual, rekening bank, tagihan konsinyasi, dan tutup buku tahunan.',
            ],

            // ==================== 8. E-COMMERCE ====================
            'ecommerce' => [
                'title' => 'Pesanan & Pengaturan E-Commerce',
                'category' => 'E-COMMERCE',
                'icon' => 'heroicon-o-globe-alt',
                'patterns' => ['admin/ecommerce-orders*', 'admin/ecommerce-settings*'],
                'file' => 'ecommerce.md',
                'summary' => 'Alur pesanan belanja online pelanggan, proses picking-packing barang rak, cetak label pengiriman, dan pelacakan kurir toko.',
            ],
        ];
    }

    /**
     * Deteksi menu saat ini berdasarkan URL atau path
     */
    public static function detectMenuFromUrl(?string $url = null): string
    {
        if (!empty($url)) {
            $path = parse_url($url, PHP_URL_PATH);
        } else {
            // Jika request adalah Livewire request (livewire/update), ambil URL asal dari Referer
            if (request()->is('livewire/*') && request()->header('Referer')) {
                $path = parse_url(request()->header('Referer'), PHP_URL_PATH);
            } else {
                $path = request()->path();
            }
        }
        $path = ltrim((string) $path, '/');

        $registry = static::getMenuRegistry();

        foreach ($registry as $key => $meta) {
            foreach ($meta['patterns'] as $pattern) {
                if (Str::is($pattern, $path)) {
                    return $key;
                }
            }
        }

        return 'dashboard';
    }

    /**
     * Ambil konten panduan terformat (HTML)
     */
    public static function getGuideContent(string $key): array
    {
        $registry = static::getMenuRegistry();
        $meta = $registry[$key] ?? $registry['dashboard'];

        $docPath = resource_path('docs/' . $meta['file']);

        if (!File::exists($docPath)) {
            $markdown = "# " . $meta['title'] . "\n\nPanduan untuk menu ini sedang disusun.";
        } else {
            $markdown = File::get($docPath);
        }

        // Render markdown ke HTML
        $html = Str::markdown($markdown);

        return [
            'key' => $key,
            'title' => $meta['title'],
            'category' => $meta['category'],
            'icon' => $meta['icon'],
            'summary' => $meta['summary'],
            'html' => $html,
            'raw_markdown' => $markdown,
        ];
    }
}
