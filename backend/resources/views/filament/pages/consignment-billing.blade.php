<x-filament-panels::page>
    <style>
        .pos-grid-th { background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        .pos-grid-td { border-bottom: 1px solid #f3f4f6; padding: 0.75rem; background-color: white; font-size: 0.875rem; color: #1f2937; white-space: nowrap; }
        
        .dark .pos-grid-th { background-color: #111827 !important; border-color: #374151 !important; color: #d1d5db !important; }
        .dark .pos-grid-td { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
    </style>

    <form wire:submit="calculateSellout">
        {{ $this->form }}
    </form>

    @if($supplier_id && $start_date && $end_date)
        <div style="margin-top: 1.5rem; background: white; border-radius: 0.75rem; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" class="dark:bg-gray-900 dark:border-gray-800">
            <!-- Header -->
            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;" class="dark:bg-gray-800 dark:border-gray-700">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827;" class="dark:text-white">Laporan Sellout & Rekapitulasi Tagihan</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;" class="dark:text-gray-400">Menampilkan pergerakan barang konsinyasi dalam periode terpilih, serta kuantitas yang wajib dibayar berdasarkan Cut-Off.</p>
            </div>
            
            <!-- Table Container -->
            <div style="width: 100%; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <tr>
                            <th class="pos-grid-th">SKU</th>
                            <th class="pos-grid-th">Nama Produk</th>
                            <th class="pos-grid-th" style="text-align: right;">Qty Masuk</th>
                            <th class="pos-grid-th" style="text-align: right;">Qty Retur</th>
                            <th class="pos-grid-th" style="text-align: right;" title="Penjualan murni di periode filter">Terjual (Periode Ini)</th>
                            <th class="pos-grid-th" style="text-align: right; background-color: #fefce8;" class="dark:!bg-yellow-900/20" title="Semua penjualan sejak dulu yang belum lunas (sampai tgl cut-off)">Tunggakan (Semua Unbilled)</th>
                            <th class="pos-grid-th" style="text-align: right;">HPP</th>
                            <th class="pos-grid-th" style="text-align: right; background-color: #eff6ff;" class="dark:!bg-blue-900/20">Nominal Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selloutData as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="pos-grid-td" style="font-weight: 600;" class="dark:text-white">{{ $item['sku'] }}</td>
                                <td class="pos-grid-td" style="min-width: 250px;" class="dark:text-white">{{ $item['name'] }}</td>
                                <td class="pos-grid-td" style="text-align: right; color: #059669; font-weight: 500;">{{ number_format($item['received']) }}</td>
                                <td class="pos-grid-td" style="text-align: right; color: #dc2626; font-weight: 500;">{{ number_format($item['returned']) }}</td>
                                <td class="pos-grid-td" style="text-align: right; color: #2563eb; font-weight: 500;">{{ number_format($item['sold']) }}</td>
                                <td class="pos-grid-td" style="text-align: right; font-weight: 700; color: #ca8a04; background-color: #fefce8;" class="dark:!bg-yellow-900/20 dark:text-yellow-400">{{ number_format($item['unbilled_qty']) }}</td>
                                <td class="pos-grid-td" style="text-align: right;" class="dark:text-white">Rp {{ number_format($item['cost_price'], 0, ',', '.') }}</td>
                                <td class="pos-grid-td" style="text-align: right; font-weight: 700; color: #1d4ed8; background-color: #eff6ff;" class="dark:!bg-blue-900/20 dark:text-blue-400">Rp {{ number_format($item['amount_owed'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="pos-grid-td" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;" class="dark:text-gray-400">
                                    Tidak ada pergerakan atau tagihan konsinyasi untuk rentang waktu ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            @if(count($selloutData) > 0)
                <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;" class="dark:bg-gray-800 dark:border-gray-700">
                    @if($totalTagihan > 0)
                        <button wire:click="createKontrabon" style="background-color: #10b981; color: white; padding: 0.625rem 1.25rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" class="hover:bg-emerald-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/></svg>
                            Terbitkan Kontrabon Tagihan
                        </button>
                    @else
                        <div></div> <!-- Empty div for flex spacing -->
                    @endif
                    
                    <div style="text-align: right;">
                        <div style="color: #6b7280; font-size: 0.875rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 0.25rem;" class="dark:text-gray-400">Total Tagihan (Nett)</div>
                        <div style="font-weight: 800; font-size: 1.5rem; color: #1d4ed8;" class="dark:text-blue-400">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
