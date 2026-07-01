<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::card>
            {{ $this->form }}
        </x-filament::card>

        @php
            $data = $this->getReportData();
            $totals = [
                'sales' => $data->sum('sales_amount'),
                'cogs' => $data->sum('cogs_amount'),
                'return' => $data->sum('return_amount'),
                'return_cogs' => $data->sum('return_cogs_amount'),
                'profit' => 0,
            ];
            $totals['profit'] = ($totals['sales'] - $totals['return']) - ($totals['cogs'] - $totals['return_cogs']);
            $totals['margin'] = $totals['sales'] > 0 ? ($totals['profit'] / $totals['sales']) * 100 : 0;
        @endphp

        <!-- Tabs -->
        <x-filament::tabs label="Tampilan Laporan">
            <x-filament::tabs.item 
                :active="$activeTab === 'item'" 
                wire:click="setActiveTab('item')"
                icon="heroicon-o-cube"
            >
                Per Item Barang
            </x-filament::tabs.item>
            <x-filament::tabs.item 
                :active="$activeTab === 'category'" 
                wire:click="setActiveTab('category')"
                icon="heroicon-o-rectangle-group"
            >
                Per Kelompok Barang
            </x-filament::tabs.item>
            <x-filament::tabs.item 
                :active="$activeTab === 'monthly'" 
                wire:click="setActiveTab('monthly')"
                icon="heroicon-o-calendar-days"
            >
                Bulanan / Harian
            </x-filament::tabs.item>
        </x-filament::tabs>

        <!-- Data Table -->
        <x-filament::card>
            <div class="overflow-x-auto">
                <table class="w-full text-left divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @if($activeTab === 'item')
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Kode/Barcode</th>
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Nama Item</th>
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Satuan</th>
                            @elseif($activeTab === 'category')
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Kode Kategori</th>
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Kelompok Barang</th>
                            @elseif($activeTab === 'monthly')
                                <th class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Tanggal</th>
                            @endif
                            
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">Penjualan</th>
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">HPP</th>
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">Retur</th>
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">HPP Retur</th>
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">Profit / Laba Kotor</th>
                            <th class="px-4 py-3 text-sm font-semibold text-right text-gray-900 dark:text-gray-100">% Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @forelse($data as $row)
                            @php
                                $netSales = $row->sales_amount - $row->return_amount;
                                $netCogs = $row->cogs_amount - $row->return_cogs_amount;
                                $profit = $netSales - $netCogs;
                                $margin = $row->sales_amount > 0 ? ($profit / $row->sales_amount) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                @if($activeTab === 'item')
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row->barcode }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->item_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->unit }}</td>
                                @elseif($activeTab === 'category')
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ str_pad($row->category_id ?? 0, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->category_name }}</td>
                                @elseif($activeTab === 'monthly')
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ \Carbon\Carbon::parse($row->tgl)->translatedFormat('l, d-F-Y') }}
                                    </td>
                                @endif
                                
                                <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ number_format($row->sales_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ number_format($row->cogs_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ number_format($row->return_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ number_format($row->return_cogs_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ number_format($profit, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ number_format($margin, 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada data penjualan pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <td colspan="{{ $activeTab === 'item' ? 3 : ($activeTab === 'category' ? 2 : 1) }}" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                TOTAL KESELURUHAN
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totals['sales'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totals['cogs'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ number_format($totals['return'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ number_format($totals['return_cogs'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totals['profit'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totals['margin'], 2, ',', '.') }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-4 text-xs text-gray-500">
                <p>* Penjualan bersih = Total Penjualan - Retur</p>
                <p>* HPP bersih = Total HPP - HPP Retur</p>
                <p>* Profit/Laba Kotor = Penjualan Bersih - HPP Bersih</p>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
