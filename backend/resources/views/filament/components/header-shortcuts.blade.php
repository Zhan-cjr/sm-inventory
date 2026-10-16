<div style="display: flex; align-items: center; gap: 6px; margin-left: 16px; margin-right: 16px; margin-top: auto; margin-bottom: auto;" class="hidden md:flex flex-wrap items-center">
    <!-- 1. Produk -->
    <a href="/admin/products" 
       title="Data Produk / Barang"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/products*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <span style="white-space: nowrap;">Produk</span>
    </a>

    <!-- 2. Pesanan Pembelian -->
    <a href="/admin/purchase-orders" 
       title="Pesanan Pembelian (PO)"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/purchase-orders*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span style="white-space: nowrap;">Pesanan Pembelian</span>
    </a>

    <!-- 3. Pengecekan Gudang -->
    <a href="/admin/warehouse-checks" 
       title="Pengecekan Gudang / Stock Audit"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/warehouse-checks*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
        <span style="white-space: nowrap;">Cek Gudang</span>
    </a>

    <!-- 4. Penerimaan Barang -->
    <a href="/admin/goods-receipts" 
       title="Penerimaan Barang (Goods Receipt)"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/goods-receipts*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <span style="white-space: nowrap;">Penerimaan Barang</span>
    </a>

    <!-- 5. Koreksi Stok -->
    <a href="/admin/stock-adjustments" 
       title="Koreksi Stok / Stock Adjustment"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/stock-adjustments*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
        </svg>
        <span style="white-space: nowrap;">Koreksi Stok</span>
    </a>

    <!-- 6. Stok Transfer -->
    <a href="/admin/stock-transfers" 
       title="Stok Transfer Antar Gudang"
       style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;"
       class="{{ request()->is('admin/stock-transfers*') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-emerald-600' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; flex-shrink: 0;">
            <path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <span style="white-space: nowrap;">Stok Transfer</span>
    </a>

    <!-- 7. Petunjuk Penggunaan & SOP (F1) -->
    <button type="button" 
            onclick="Livewire.dispatch('open-user-guide', { menu: null, url: window.location.pathname })" 
            title="Buku Panduan & Petunjuk Menu Ini (F1)"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; font-size: 12px; font-weight: 700; border-radius: 8px; text-decoration: none; transition: all 0.15s ease; border: 1px solid rgba(16, 185, 129, 0.4); cursor: pointer;"
            class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 shadow-sm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <span style="white-space: nowrap;">Petunjuk (F1)</span>
    </button>
</div>
