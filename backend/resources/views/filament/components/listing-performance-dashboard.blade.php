@php
    $metrics = $listing->getPerformanceMetrics();
    $supplier = $listing->supplier;
    $remaining = $listing->days_remaining;
@endphp

<div class="space-y-6 text-sm">
    <!-- Header Ringkasan Perjanjian -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pemasok</div>
            <div class="text-base font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $supplier?->name ?? '-' }}</div>
            <div class="text-xs text-gray-500 mt-0.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $supplier?->is_consignment ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
                    {{ $supplier?->is_consignment ? '📦 Konsinyasi (Titip Jual)' : '🛒 Beli Putus' }}
                </span>
            </div>
        </div>

        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Masa Uji Coba (Trial)</div>
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">
                {{ $listing->trial_start_date ? \Carbon\Carbon::parse($listing->trial_start_date)->format('d M Y') : '-' }} s/d 
                {{ $listing->trial_end_date ? \Carbon\Carbon::parse($listing->trial_end_date)->format('d M Y') : '-' }}
            </div>
            <div class="mt-1">
                @if($listing->status === 'TRIAL')
                    @if($remaining !== null && $remaining < 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                            ⚠️ Jatuh Tempo (Lewat {{ abs($remaining) }} Hari)
                        </span>
                    @elseif($remaining !== null && $remaining <= 14)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            ⏳ Sisa {{ $remaining }} Hari Lagi
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            ✅ Sisa {{ $remaining }} Hari
                        </span>
                    @endif
                @elseif($listing->status === 'PASSED')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        🎉 Telah Diluluskan (Aktif Rollout)
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                        🛑 Dihentikan (Delisted)
                    </span>
                @endif
            </div>
        </div>

        <div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biaya Listing & Status Potongan</div>
            <div class="text-base font-bold text-gray-900 dark:text-gray-100 mt-1">
                Rp {{ number_format($listing->listing_fee, 0, ',', '.') }}
            </div>
            <div class="text-xs text-gray-500 mt-0.5">
                @if($listing->listing_fee > 0)
                    @php $ded = $listing->listingDeduction; @endphp
                    Status Kontrabon: <span class="font-semibold {{ ($ded && $ded->status === 'COMPLETED') ? 'text-emerald-600' : 'text-amber-600' }}">{{ $ded?->status ?? 'OPEN' }}</span>
                @else
                    <span class="text-gray-400 italic">Bebas Biaya Listing (Rp 0)</span>
                @endif
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics Card -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Produk Listing</div>
            <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">{{ $metrics['total_products'] }} <span class="text-xs font-normal text-gray-500">Item</span></div>
            <div class="text-xs text-gray-400 mt-1">Dalam 1 Perjanjian ini</div>
        </div>

        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Diterima (Masuk)</div>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($metrics['total_bought'], 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">Pcs</span></div>
            <div class="text-xs text-gray-400 mt-1">Dari faktur/penerimaan barang</div>
        </div>

        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Terjual (POS)</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($metrics['total_sold'], 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">Pcs</span></div>
            <div class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">Sell-Through: {{ $metrics['sell_through'] }}%</div>
        </div>

        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Omset Penjualan</div>
            <div class="text-xl font-black text-purple-600 dark:text-purple-400 mt-1">Rp {{ number_format($metrics['total_omset'], 0, ',', '.') }}</div>
            <div class="text-xs text-gray-400 mt-1">Sisa Stok: {{ number_format($metrics['total_stock'], 0, ',', '.') }} pcs</div>
        </div>
    </div>

    <!-- Tabel Rincian Breakdown Per Cabang & Per Produk -->
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="font-bold text-gray-900 dark:text-gray-100 text-sm">Rincian Performa per Cabang Pilot</h4>
            <span class="text-xs text-gray-500">Data bersumber dari penerimaan barang & transaksi POS</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-2.5 px-3">Cabang Pilot</th>
                        <th class="py-2.5 px-3">SKU & Produk</th>
                        <th class="py-2.5 px-3 text-right">Masuk (Pcs)</th>
                        <th class="py-2.5 px-3 text-right">Terjual (Pcs)</th>
                        <th class="py-2.5 px-3 text-right">Sisa Stok</th>
                        <th class="py-2.5 px-3 text-center">Sell-Through</th>
                        <th class="py-2.5 px-3 text-right">Omset Penjualan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                    @forelse($metrics['breakdown'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="py-2.5 px-3 font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $row['branch_name'] }}
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $row['product_name'] }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ $row['product_sku'] }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ number_format($row['qty_received'], 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($row['qty_sold'], 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-gray-500">{{ number_format($row['current_stock'], 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-center">
                                @php
                                    $st = $row['sell_through'];
                                    $badgeClass = $st >= 70 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : ($st >= 40 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300');
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $badgeClass }}">
                                    {{ $st }}%
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-semibold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                Rp {{ number_format($row['omset'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-400">Belum ada data transaksi atau barang belum ditetapkan ke cabang pilot.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
