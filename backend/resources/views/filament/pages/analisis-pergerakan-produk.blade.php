<x-filament-panels::page>
    @php
        $report = $this->velocity_report;
        $kpi = $report['kpi'];
        $items = $report['items'];
        $daysCount = $report['days_count'];
    @endphp

    <div class="space-y-5">
        {{-- 1. TOOLBAR FILTER --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider dark:text-gray-400 mr-1">Periode:</span>
                    @foreach(['TODAY' => 'Hari Ini', '7_DAYS' => '7 Hari', '30_DAYS' => '30 Hari (Default)', '90_DAYS' => '90 Hari (Kuartal)', '365_DAYS' => '1 Tahun'] as $key => $lbl)
                        <button wire:click="applyDatePreset('{{ $key }}')" type="button" 
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition border {{ $date_preset === $key ? 'bg-primary-600 border-primary-600 text-white shadow-sm' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-primary-500' }}">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

                <button wire:click="resetFilters" type="button" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:text-primary-600 border border-gray-200 dark:border-gray-700 flex items-center gap-1">
                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                    Reset Filter
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Dari Tanggal:</label>
                    <input wire:model.live="start_date" type="date" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Sampai Tanggal:</label>
                    <input wire:model.live="end_date" type="date" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Cabang / Toko:</label>
                    <select wire:model.live="branch_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @if(!auth()->user()->branch_id) <option value="ALL">Semua Cabang</option> @endif
                        @foreach($this->branches as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Supplier:</label>
                    <select wire:model.live="supplier_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="ALL">Semua Supplier</option>
                        @foreach($this->suppliers as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kategori:</label>
                    <select wire:model.live="category_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="ALL">Semua Kategori</option>
                        @foreach($this->categories as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. MATRIKS 4 KUADRAN KEPUTUSAN INSTAN --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            {{-- Kuadran 1: Fast Kritis --}}
            <div wire:click="setQuadrant('CRITICAL_FAST')" class="cursor-pointer rounded-xl p-4 border-2 transition shadow-sm {{ $quadrant === 'CRITICAL_FAST' ? 'border-red-500 bg-red-50 dark:bg-red-950/40 ring-2 ring-red-400/30' : 'bg-white dark:bg-gray-900 border-red-200 dark:border-red-900/50 hover:border-red-400' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-black uppercase tracking-wider text-red-600 dark:text-red-400">🔴 Fast Moving Kritis</span>
                    <span class="text-[11px] font-extrabold text-red-500">&le; 7 Hari</span>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpi['critical_fast']['count']) }} <span class="text-xs font-normal text-gray-500">SKU</span></div>
                <div class="text-xs text-red-600 dark:text-red-400 mt-1">Risiko Omset: <b>Rp {{ number_format($kpi['critical_fast']['potential_revenue_risk']) }}</b></div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 border-t border-red-100 dark:border-red-900/40 pt-1.5 flex justify-between font-semibold">
                    <span>Stok: {{ number_format($kpi['critical_fast']['total_stock']) }} pcs</span>
                    <span class="text-red-600 dark:text-red-400 font-bold">Harus Reorder &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 2: Slow Overstock --}}
            <div wire:click="setQuadrant('OVERSTOCK_SLOW')" class="cursor-pointer rounded-xl p-4 border-2 transition shadow-sm {{ $quadrant === 'OVERSTOCK_SLOW' ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/40 ring-2 ring-amber-400/30' : 'bg-white dark:bg-gray-900 border-amber-200 dark:border-amber-900/50 hover:border-amber-400' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">🟡 Slow Overstock</span>
                    <span class="text-[11px] font-extrabold text-amber-500">&ge; 60 Hari</span>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpi['overstock_slow']['count']) }} <span class="text-xs font-normal text-gray-500">SKU</span></div>
                <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">Modal Tertahan: <b>Rp {{ number_format($kpi['overstock_slow']['capital_tied']) }}</b></div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 border-t border-amber-100 dark:border-amber-900/40 pt-1.5 flex justify-between font-semibold">
                    <span>Stok: {{ number_format($kpi['overstock_slow']['total_stock']) }} pcs</span>
                    <span class="text-amber-600 dark:text-amber-400 font-bold">Kunci Order &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 3: Dead Stock --}}
            <div wire:click="setQuadrant('DEAD_STOCK')" class="cursor-pointer rounded-xl p-4 border-2 transition shadow-sm {{ $quadrant === 'DEAD_STOCK' ? 'border-gray-500 bg-gray-100 dark:bg-gray-800 ring-2 ring-gray-400/30' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 hover:border-gray-400' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-700 dark:text-gray-300">⚫ Dead Stock (Mati)</span>
                    <span class="text-[11px] font-extrabold text-gray-500">0 Laku</span>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpi['dead_stock']['count']) }} <span class="text-xs font-normal text-gray-500">SKU</span></div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Uang Mati: <b>Rp {{ number_format($kpi['dead_stock']['capital_tied']) }}</b></div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 border-t border-gray-200 dark:border-gray-800 pt-1.5 flex justify-between font-semibold">
                    <span>Stok: {{ number_format($kpi['dead_stock']['total_stock']) }} pcs</span>
                    <span class="text-gray-700 dark:text-gray-300 font-bold">Retur/Obral &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 4: Optimal --}}
            <div wire:click="setQuadrant('OPTIMAL')" class="cursor-pointer rounded-xl p-4 border-2 transition shadow-sm {{ $quadrant === 'OPTIMAL' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 ring-2 ring-emerald-400/30' : 'bg-white dark:bg-gray-900 border-emerald-200 dark:border-emerald-900/50 hover:border-emerald-400' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">🟢 Sehat & Optimal</span>
                    <span class="text-[11px] font-extrabold text-emerald-500">8 - 30 Hari</span>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpi['optimal']['count']) }} <span class="text-xs font-normal text-gray-500">SKU</span></div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Aset Produktif: <b>Rp {{ number_format($kpi['optimal']['capital_healthy']) }}</b></div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 border-t border-emerald-100 dark:border-emerald-900/40 pt-1.5 flex justify-between font-semibold">
                    <span>Stok: {{ number_format($kpi['optimal']['total_stock']) }} pcs</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">Pola Aman &rarr;</span>
                </div>
            </div>
        </div>

        {{-- 3. TABEL ANALITIK DATA --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1 min-w-[260px]">
                    <div class="relative w-full max-w-sm">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari SKU, Barcode, Produk..." class="w-full text-xs pl-8 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" />
                    </div>
                    @if($quadrant !== 'ALL')
                        <button wire:click="setQuadrant('ALL')" type="button" class="text-xs font-bold px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                            Semua Kuadran &times;
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 dark:text-gray-400">Urutkan:</label>
                    <select wire:model.live="sort_by" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="doh_asc">⚡ Cepat Habis (DOH Rendah)</option>
                        <option value="doh_desc">⏳ Paling Lambat (DOH Tinggi)</option>
                        <option value="revenue_desc">💰 Omset Terbesar (Rp)</option>
                        <option value="qty_desc">📦 Volume Terbanyak (Pcs)</option>
                        <option value="capital_desc">💼 Modal Macet Terbesar (Rp)</option>
                        <option value="name_asc">🔤 Nama Produk (A-Z)</option>
                    </select>

                    <select wire:model.live="per_page" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="15">15 Baris</option>
                        <option value="25">25 Baris</option>
                        <option value="50">50 Baris</option>
                        <option value="100">100 Baris</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-[10px] font-extrabold border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-2.5 text-center">No</th>
                            <th class="p-2.5">SKU & Barcode</th>
                            <th class="p-2.5">Nama Barang & Kategori</th>
                            <th class="p-2.5">Supplier</th>
                            <th class="p-2.5 text-right">Laku ({{ $daysCount }} Hari)</th>
                            <th class="p-2.5 text-right">Laju/Hari (ADS)</th>
                            <th class="p-2.5 text-right">Omset (Rp)</th>
                            <th class="p-2.5 text-right">Stok Fisik</th>
                            <th class="p-2.5 text-right">Modal Tertahan</th>
                            <th class="p-2.5 text-center">Sisa Hari (DOH)</th>
                            <th class="p-2.5 text-center">Status</th>
                            <th class="p-2.5">Rekomendasi Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($items as $idx => $it)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                                <td class="p-2.5 text-center text-gray-400">{{ $report['from'] + $idx }}</td>
                                <td class="p-2.5 font-mono text-[11px]">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $it['sku'] }}</div>
                                    <div class="text-gray-500">{{ $it['barcode'] }}</div>
                                    @if(!empty($it['additional_barcodes']))
                                        <div class="text-[10px] text-primary-600 font-sans">+{{ count($it['additional_barcodes']) }} Multi-Barcode</div>
                                    @endif
                                </td>
                                <td class="p-2.5">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $it['product_name'] }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $it['category_name'] }}</div>
                                </td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $it['supplier_name'] }}</td>
                                <td class="p-2.5 text-right font-bold text-sky-600">{{ number_format($it['qty_sold'], 0, ',', '.') }} pcs</td>
                                <td class="p-2.5 text-right font-black text-primary-600">{{ number_format($it['ads'], 1, ',', '.') }} /hr</td>
                                <td class="p-2.5 text-right font-bold">Rp {{ number_format($it['revenue'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-right font-extrabold {{ $it['current_stock'] <= 5 ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ number_format($it['current_stock'], 0, ',', '.') }} pcs
                                </td>
                                <td class="p-2.5 text-right font-bold text-amber-700 dark:text-amber-400">Rp {{ number_format($it['capital_tied'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-center font-black text-[13px] {{ $it['doh'] <= 7 ? 'text-red-600' : ($it['doh'] >= 60 ? 'text-amber-600' : 'text-emerald-600') }}">
                                    {{ $it['doh_display'] }}
                                </td>
                                <td class="p-2.5 text-center">
                                    @php
                                        $badgeColors = [
                                            'danger' => 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border-red-300',
                                            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300',
                                            'dark' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-300',
                                            'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300',
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border {{ $badgeColors[$it['quadrant_badge']] ?? '' }}">
                                        {{ $it['quadrant_label'] }}
                                    </span>
                                </td>
                                <td class="p-2.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ $it['recommended_action'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="p-8 text-center text-gray-400">Tidak ada data produk yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($report['total_items'] > 0)
                <div class="flex flex-wrap items-center justify-between gap-3 pt-1 text-xs text-gray-500">
                    <div>Menampilkan <b>{{ number_format($report['from']) }}</b> s/d <b>{{ number_format($report['to']) }}</b> dari <b>{{ number_format($report['total_items']) }}</b> produk</div>
                    <div class="flex items-center gap-1">
                        <button wire:click="prevPage" type="button" class="px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-800 font-bold hover:bg-gray-200" {{ $report['current_page'] <= 1 ? 'disabled' : '' }}>&larr; Sebelumnya</button>
                        <span class="px-2 font-bold">Hal. {{ $report['current_page'] }} / {{ $report['total_pages'] }}</span>
                        <button wire:click="nextPage" type="button" class="px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-800 font-bold hover:bg-gray-200" {{ $report['current_page'] >= $report['total_pages'] ? 'disabled' : '' }}>Berikutnya &rarr;</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
