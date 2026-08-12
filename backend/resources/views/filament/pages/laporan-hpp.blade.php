<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <x-filament::card>
            <form wire:submit="processReport">
                {{ $this->form }}
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" color="primary" wire:loading.attr="disabled" wire:target="processReport">
                        <x-filament::loading-indicator class="h-5 w-5 mr-2 inline-block" wire:loading wire:target="processReport" />
                        <span wire:loading.remove wire:target="processReport">Proses Laporan</span>
                        <span wire:loading wire:target="processReport">Memproses...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @if($isReportReady)
            @php
                $data = $this->getReportData();
                $totals = $this->getGrandTotals();
            @endphp

            <div class="relative">
                <!-- Loading Overlay -->
                <div wire:loading.delay wire:target="processReport, setActiveTab, previousPage, nextPage, gotoPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 rounded-xl backdrop-blur-sm">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg flex items-center gap-3 border border-gray-200 dark:border-gray-700">
                        <x-filament::loading-indicator class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                        <span class="font-medium text-gray-700 dark:text-gray-200">Memuat data laporan...</span>
                    </div>
                </div>

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
                    :active="$activeTab === 'subcategory'" 
                    wire:click="setActiveTab('subcategory')"
                    icon="heroicon-o-bars-3-bottom-left">
                    Per Sub Kategori
                </x-filament::tabs.item>
                <x-filament::tabs.item 
                    :active="$activeTab === 'monthly'" 
                    wire:click="setActiveTab('monthly')"
                    icon="heroicon-o-calendar-days">
                    Bulanan / Harian
                </x-filament::tabs.item>
                <x-filament::tabs.item 
                    :active="$activeTab === 'yearly'" 
                    wire:click="setActiveTab('yearly')"
                    icon="heroicon-o-calendar">
                    Tahunan
                </x-filament::tabs.item>
            </x-filament::tabs>

            <script>
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('print-window', (event) => {
                        setTimeout(() => { window.print(); }, 500);
                    });
                });
            </script>

            <style>
                .hpp-table { width: 100%; border-collapse: collapse; text-align: left; }
                .hpp-table th { padding: 12px 16px; background-color: rgba(243, 244, 246, 0.5); font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
                .hpp-table td { padding: 12px 16px; font-size: 0.875rem; border-bottom: 1px solid #e5e7eb; }
                .hpp-table tr:hover { background-color: #f9fafb; }
                .dark .hpp-table th { background-color: rgba(31, 41, 55, 0.5); border-color: #374151; color: #f3f4f6; }
                .dark .hpp-table td { border-color: #374151; color: #d1d5db; }
                .dark .hpp-table tr:hover { background-color: #1f2937; }
                .text-right { text-align: right !important; }
                .text-red { color: #dc2626 !important; }
                .text-green { color: #16a34a !important; }
                .dark .text-red { color: #ef4444 !important; }
                .dark .text-green { color: #22c55e !important; }
                .font-bold { font-weight: bold !important; }
                
                .hpp-tfoot { background-color: rgba(243, 244, 246, 0.8); border-top: 2px solid #d1d5db; }
                .dark .hpp-tfoot { background-color: rgba(31, 41, 55, 0.8); border-top: 2px solid #374151; color: #f3f4f6; }

                @media print {
                    body { background-color: #fff !important; color: #000 !important; }
                    .fi-topbar, .fi-sidebar, .fi-header-heading, .fi-tabs, form, .fi-page-header-actions { display: none !important; }
                    .fi-main { padding: 0 !important; margin: 0 !important; }
                    .hpp-table { width: 100% !important; }
                    .hpp-table th, .hpp-table td { color: #000 !important; border: 1px solid #ddd; padding: 8px; }
                    .dark .hpp-tfoot { background-color: #f3f4f6 !important; color: #000 !important; }
                    .text-green, .text-red { color: #000 !important; }
                    @page { size: landscape; margin: 10mm; }
                }
            </style>

            <!-- Data Table -->
            <x-filament::card>
                <div style="overflow-x: auto;">
                    <table class="hpp-table">
                        <thead>
                            <tr>
                                @if($activeTab === 'item')
                                    <th>SKU</th>
                                    <th>Barcode</th>
                                    <th>Nama Item</th>
                                    <th>Satuan</th>
                                @elseif($activeTab === 'category')
                                    <th>Kode Kategori</th>
                                    <th>Kelompok Barang</th>
                                @elseif($activeTab === 'subcategory')
                                    <th>Sub Kategori</th>
                                    <th>Kategori Induk</th>
                                @elseif($activeTab === 'monthly')
                                    <th>Tanggal</th>
                                @elseif($activeTab === 'yearly')
                                    <th>Bulan</th>
                                @endif
                                
                                <th class="text-right">Penjualan</th>
                                <th class="text-right">HPP</th>
                                <th class="text-right">Retur</th>
                                <th class="text-right">HPP Retur</th>
                                <th class="text-right">Profit / Laba Kotor</th>
                                <th class="text-right">% Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                @php
                                    $netSales = $row->sales_amount - $row->return_amount;
                                    $netCogs = $row->cogs_amount - $row->return_cogs_amount;
                                    $profit = $netSales - $netCogs;
                                    $margin = $row->sales_amount > 0 ? ($profit / $row->sales_amount) * 100 : 0;
                                @endphp
                                <tr>
                                    @if($activeTab === 'item')
                                        <td>{{ $row->barcode }}</td>
                                        <td>{{ $row->product_barcode }}</td>
                                        <td class="font-bold">{{ $row->item_name }}</td>
                                        <td>{{ $row->unit }}</td>
                                    @elseif($activeTab === 'category')
                                        <td>{{ str_pad($row->category_id ?? 0, 3, '0', STR_PAD_LEFT) }}</td>
                                        <td class="font-bold">{{ $row->category_name }}</td>
                                    @elseif($activeTab === 'subcategory')
                                        <td>{{ $row->sub_category ?: '-' }}</td>
                                        <td class="font-bold">{{ $row->category_name }}</td>
                                    @elseif($activeTab === 'monthly')
                                        <td class="font-bold">
                                            {{ \Carbon\Carbon::parse($row->tgl)->translatedFormat('l, d-F-Y') }}
                                        </td>
                                    @elseif($activeTab === 'yearly')
                                        <td class="font-bold">
                                            {{ \Carbon\Carbon::parse($row->bulan . '-01')->translatedFormat('F Y') }}
                                        </td>
                                    @endif
                                    
                                    <td class="text-right">{{ number_format($row->sales_amount, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row->cogs_amount, 0, ',', '.') }}</td>
                                    <td class="text-right text-red">{{ number_format($row->return_amount, 0, ',', '.') }}</td>
                                    <td class="text-right text-red">{{ number_format($row->return_cogs_amount, 0, ',', '.') }}</td>
                                    <td class="text-right font-bold {{ $profit >= 0 ? 'text-green' : 'text-red' }}">
                                        {{ number_format($profit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right">{{ number_format($margin, 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        Tidak ada data penjualan pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="hpp-tfoot">
                            <tr>
                                <td colspan="{{ $activeTab === 'item' ? 4 : ($activeTab === 'category' || $activeTab === 'subcategory' ? 2 : 1) }}" class="text-right font-bold" style="text-align: right; padding-right: 1rem;">
                                    TOTAL KESELURUHAN
                                </td>
                                <td class="text-right font-bold">{{ number_format($totals['sales'], 0, ',', '.') }}</td>
                                <td class="text-right font-bold">{{ number_format($totals['cogs'], 0, ',', '.') }}</td>
                                <td class="text-right font-bold text-red">{{ number_format($totals['return'], 0, ',', '.') }}</td>
                                <td class="text-right font-bold text-red">{{ number_format($totals['return_cogs'], 0, ',', '.') }}</td>
                                <td class="text-right font-bold">{{ number_format($totals['profit'], 0, ',', '.') }}</td>
                                <td class="text-right font-bold">{{ number_format($totals['margin'], 2, ',', '.') }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mt-4 border-t border-gray-200 dark:border-white/10">
                    <x-filament::pagination :paginator="$data" :page-options="[]" class="px-3 py-3" />
                </div>
                
                <div class="mt-4 text-xs text-gray-500">
                    <p>* Penjualan bersih = Total Penjualan - Retur</p>
                    <p>* HPP bersih = Total HPP - HPP Retur</p>
                    <p>* Profit/Laba Kotor = Penjualan Bersih - HPP Bersih</p>
                </div>
            </x-filament::card>
        </div>
        @else
            <x-filament::card>
                <div class="py-8 text-center text-gray-500">
                    <x-filament::icon
                        icon="heroicon-o-document-text"
                        class="mx-auto h-12 w-12 text-gray-400 mb-4"
                    />
                    <p class="text-lg">Silakan atur filter dan klik tombol <strong>Proses Laporan</strong> untuk menampilkan data.</p>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
