<x-filament-panels::page>
@php
    $record = $this->record;
    $record->load(['items.product', 'items.rack', 'branch', 'auditor', 'adjustment']);
    
    $totalChecked = $record->items->count();
    $matchItems = $record->items->where('status', 'MATCH');
    $minusItems = $record->items->where('status', 'MINUS');
    $plusItems = $record->items->where('status', 'PLUS');
    
    $matchCount = $matchItems->count();
    $minusCount = $minusItems->count();
    $plusCount = $plusItems->count();
    $discCount = $minusCount + $plusCount;
    
    $accuracyRate = $totalChecked > 0 ? round(($matchCount / $totalChecked) * 100, 1) : 0;
    
    $totalSystemQty = $record->items->sum('system_quantity');
    $totalPhysicalQty = $record->items->sum('physical_quantity');
    $totalDiscrepancyQty = $record->items->sum('discrepancy_quantity');
@endphp

<div class="space-y-6" x-data="{
    activeFilter: 'all',
    searchQuery: '',
    matchesFilter(itemStatus, itemName, itemBarcode, itemSku) {
        const q = this.searchQuery.toLowerCase().trim();
        const matchesSearch = !q || 
            (itemName && itemName.toLowerCase().includes(q)) || 
            (itemBarcode && itemBarcode.toLowerCase().includes(q)) || 
            (itemSku && itemSku.toLowerCase().includes(q));
            
        if (!matchesSearch) return false;
        
        if (this.activeFilter === 'all') return true;
        if (this.activeFilter === 'discrepancy') return itemStatus !== 'MATCH';
        if (this.activeFilter === 'minus') return itemStatus === 'MINUS';
        if (this.activeFilter === 'plus') return itemStatus === 'PLUS';
        if (this.activeFilter === 'match') return itemStatus === 'MATCH';
        return true;
    }
}">
    {{-- 1. HEADER METADATA BANNER --}}
    <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 p-6">
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/5 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 dark:bg-primary-950/50 border border-primary-200/60 dark:border-primary-800/60 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 shadow-inner">
                    <x-heroicon-o-clipboard-document-check class="w-8 h-8" />
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-xl font-bold font-mono tracking-tight text-gray-900 dark:text-gray-100">
                            {{ $record->audit_number }}
                        </h2>
                        
                        {{-- Status Pill --}}
                        @if($record->status === 'DRAFT')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Sedang Berjalan
                            </span>
                        @elseif($record->status === 'INVESTIGATION_PENDING')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Menunggu Klarifikasi Toko
                            </span>
                        @elseif($record->status === 'ADJUSTED')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Stok Telah Disesuaikan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                Selesai / Ditutup
                            </span>
                        @endif

                        {{-- Mode Pill --}}
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            @if($record->inspection_mode === 'FREE_SCAN')
                                Mode: Spot Check (Scan Bebas)
                            @elseif($record->inspection_mode === 'RANDOM_LOTTERY')
                                Mode: Undian Acak Sistem
                            @else
                                Mode: Berbasis Risiko
                            @endif
                        </span>
                    </div>

                    <div class="mt-2.5 flex items-center gap-y-1.5 gap-x-5 flex-wrap text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-building-storefront class="w-4 h-4 text-gray-400" />
                            <span>Cabang: <strong class="text-gray-800 dark:text-gray-200">{{ $record->branch?->name ?: 'Semua Cabang' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-user class="w-4 h-4 text-gray-400" />
                            <span>Auditor: <strong class="text-gray-800 dark:text-gray-200">{{ $record->auditor?->name ?: '-' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-user-group class="w-4 h-4 text-gray-400" />
                            <span>Saksi Toko: <strong class="text-gray-800 dark:text-gray-200">{{ $record->acknowledged_by ?: '-' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-calendar class="w-4 h-4 text-gray-400" />
                            <span>Tanggal: {{ $record->inspection_date?->translatedFormat('d F Y') ?: $record->created_at->format('d/m/Y') }}</span>
                        </div>
                        @if($record->adjustment)
                            <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                                <x-heroicon-m-document-check class="w-4 h-4" />
                                <span>No. Adj: {{ $record->adjustment->adjustment_number }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($record->notes)
                <div class="bg-gray-50 dark:bg-gray-750 p-3.5 rounded-xl border border-gray-200/80 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 max-w-md">
                    <span class="font-bold text-gray-700 dark:text-gray-200">Catatan Sidak:</span>
                    <p class="mt-0.5 line-clamp-2 italic">{{ $record->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- 2. EXECUTIVE KPI CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Checked --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Total Diperiksa</div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-50 mt-1.5 font-mono">
                    {{ number_format($totalChecked, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Item / SKU produk</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-cube class="w-6 h-6" />
            </div>
        </div>

        {{-- Stock Accuracy (IRA) --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Akurasi Stok (IRA)</div>
                <div class="text-3xl font-extrabold mt-1.5 font-mono {{ $accuracyRate >= 95 ? 'text-emerald-600 dark:text-emerald-400' : ($accuracyRate >= 80 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $accuracyRate }}%
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ $matchCount }}</strong> produk cocok sempurna
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $accuracyRate >= 90 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-600' }} flex items-center justify-center">
                <x-heroicon-o-check-badge class="w-6 h-6" />
            </div>
        </div>

        {{-- Discrepancy Count --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Item Selisih</div>
                <div class="text-3xl font-extrabold mt-1.5 font-mono {{ $discCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ number_format($discCount, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5">
                    <span class="text-red-600 dark:text-red-400 font-semibold">{{ $minusCount }} Minus</span>
                    <span>&bull;</span>
                    <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $plusCount }} Plus</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $discCount > 0 ? 'bg-red-50 dark:bg-red-950/50 text-red-600' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600' }} flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
            </div>
        </div>

        {{-- Discrepancy Nominal (Loss vs Gain) --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Total Kerugian (Minus)</div>
                <div class="text-2xl font-black text-red-600 dark:text-red-400 mt-1.5 font-mono">
                    -Rp {{ number_format($record->total_loss_nominal, 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                    Lebih: +Rp {{ number_format($record->total_gain_nominal, 0, ',', '.') }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center">
                <x-heroicon-o-banknotes class="w-6 h-6" />
            </div>
        </div>
    </div>

    {{-- 3. TABEL HASIL AUDIT DENGAN FILTER INTERAKTIF --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 overflow-hidden">
        
        {{-- Filter Bar & Live Search --}}
        <div class="p-4 sm:p-5 border-b border-gray-200/80 dark:border-gray-700/80 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50">
            {{-- Filter Pills --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    type="button"
                    @click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-primary-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span>Semua Produk</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/20">{{ $totalChecked }}</span>
                </button>

                <button
                    type="button"
                    @click="activeFilter = 'discrepancy'"
                    :class="activeFilter === 'discrepancy' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>Selisih Saja</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/20">{{ $discCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeFilter = 'minus'"
                    :class="activeFilter === 'minus' ? 'bg-red-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    <span>Minus (Kurang)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/20">{{ $minusCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeFilter = 'plus'"
                    :class="activeFilter === 'plus' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    <span>Plus (Lebih)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/20">{{ $plusCount }}</span>
                </button>

                <button
                    type="button"
                    @click="activeFilter = 'match'"
                    :class="activeFilter === 'match' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Cocok (Match)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/20">{{ $matchCount }}</span>
                </button>
            </div>

            {{-- Live Search Input --}}
            <div class="relative w-full md:w-64">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Cari nama / barcode..."
                    class="w-full text-xs rounded-xl bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 pl-8 pr-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <div class="absolute left-2.5 top-2.5 text-gray-400 pointer-events-none">
                    <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                </div>
                <button
                    type="button"
                    x-show="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                >
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-slate-100 font-semibold tracking-wider uppercase text-[11px] border-b border-slate-700">
                        <th class="py-3 px-3.5 text-center w-12">No</th>
                        <th class="py-3 px-4 min-w-[240px]">Produk & Barcode</th>
                        <th class="py-3 px-3 text-center min-w-[90px]">Lokasi Rak</th>
                        <th class="py-3 px-3.5 text-right font-mono min-w-[95px]">Stok Sistem</th>
                        <th class="py-3 px-3.5 text-right font-mono min-w-[95px] bg-slate-750">Fisik Dihitung</th>
                        <th class="py-3 px-3.5 text-center font-mono min-w-[110px]">Selisih Qty</th>
                        <th class="py-3 px-3.5 text-right font-mono min-w-[95px]">HPP Satuan</th>
                        <th class="py-3 px-4 text-right font-mono min-w-[125px]">Total Selisih (Rp)</th>
                        <th class="py-3 px-4 min-w-[150px]">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-750 font-medium">
                    @forelse($record->items as $idx => $it)
                        @php
                            $prod = $it->product;
                            $pName = addslashes($prod?->name ?? 'Produk Dihapus');
                            $pBarcode = addslashes($prod?->barcode ?? '');
                            $pSku = addslashes($prod?->sku ?? '');
                            $status = $it->status;
                        @endphp
                        <tr 
                            x-show="matchesFilter('{{ $status }}', '{{ $pName }}', '{{ $pBarcode }}', '{{ $pSku }}')"
                            class="hover:bg-primary-50/40 dark:hover:bg-gray-750/70 transition-colors {{ $idx % 2 === 1 ? 'bg-gray-50/50 dark:bg-gray-800/30' : 'bg-white dark:bg-gray-800' }}"
                        >
                            {{-- No --}}
                            <td class="py-3.5 px-3.5 text-center text-gray-400 font-mono text-[11px]">{{ $idx + 1 }}</td>

                            {{-- Produk --}}
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-[13px] leading-snug">
                                    {{ $prod?->name ?? 'Produk Tidak Ditemukan' }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                    <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 font-bold">
                                        {{ $prod?->barcode ?: ($prod?->sku ?: '-') }}
                                    </span>
                                    @if($prod?->barcode && $prod?->sku && $prod->barcode !== $prod->sku)
                                        <span class="text-gray-400">/ {{ $prod->sku }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Rak --}}
                            <td class="py-3.5 px-3 text-center">
                                @if($it->rack)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/80">
                                        {{ $it->rack->rack_name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>

                            {{-- Stok Sistem Cabang --}}
                            <td class="py-3.5 px-3.5 text-right font-mono text-gray-700 dark:text-gray-300 text-[13px]">
                                {{ number_format($it->system_quantity, 0) }}
                            </td>

                            {{-- Fisik Dihitung --}}
                            <td class="py-3.5 px-3.5 text-right font-mono font-bold text-gray-900 dark:text-gray-50 text-[13px] bg-slate-50/60 dark:bg-slate-800/60">
                                {{ number_format($it->physical_quantity, 0) }}
                            </td>

                            {{-- Selisih Qty Badge --}}
                            <td class="py-3.5 px-3.5 text-center">
                                @if($it->discrepancy_quantity < 0)
                                    <span class="inline-flex items-center justify-center gap-1 min-w-[70px] px-2.5 py-1 rounded-full text-xs font-black font-mono bg-red-100 text-red-700 dark:bg-red-950/70 dark:text-red-300 border border-red-300 dark:border-red-800">
                                        {{ number_format($it->discrepancy_quantity, 0) }}
                                    </span>
                                @elseif($it->discrepancy_quantity > 0)
                                    <span class="inline-flex items-center justify-center gap-1 min-w-[70px] px-2.5 py-1 rounded-full text-xs font-black font-mono bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-300 dark:border-blue-800">
                                        +{{ number_format($it->discrepancy_quantity, 0) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 min-w-[70px] px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                        <x-heroicon-m-check class="w-3.5 h-3.5" /> 0
                                    </span>
                                @endif
                            </td>

                            {{-- HPP --}}
                            <td class="py-3.5 px-3.5 text-right font-mono text-gray-500 dark:text-gray-400 text-[11px]">
                                Rp {{ number_format($it->unit_cost, 0, ',', '.') }}
                            </td>

                            {{-- Selisih Nominal (Rp) --}}
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-[13px]">
                                @if($it->discrepancy_cost < 0)
                                    <span class="text-red-600 dark:text-red-400">
                                        -Rp {{ number_format(abs($it->discrepancy_cost), 0, ',', '.') }}
                                    </span>
                                @elseif($it->discrepancy_cost > 0)
                                    <span class="text-blue-600 dark:text-blue-400">
                                        +Rp {{ number_format($it->discrepancy_cost, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 font-normal">Rp 0</span>
                                @endif
                            </td>

                            {{-- Keterangan --}}
                            <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400 text-[11px] leading-relaxed">
                                {{ $it->clarification_notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                Belum ada item yang diperiksa dalam sesi sidak ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-900 border-t-2 border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-gray-100 font-mono text-[12px]">
                        <td colspan="3" class="py-3.5 px-4 text-right uppercase tracking-wider text-gray-500">
                            Total Rekapitulasi:
                        </td>
                        <td class="py-3.5 px-3.5 text-right">{{ number_format($totalSystemQty, 0) }}</td>
                        <td class="py-3.5 px-3.5 text-right">{{ number_format($totalPhysicalQty, 0) }}</td>
                        <td class="py-3.5 px-3.5 text-center">
                            @if($totalDiscrepancyQty < 0)
                                <span class="text-red-600">{{ number_format($totalDiscrepancyQty, 0) }}</span>
                            @elseif($totalDiscrepancyQty > 0)
                                <span class="text-blue-600">+{{ number_format($totalDiscrepancyQty, 0) }}</span>
                            @else
                                <span class="text-emerald-600">0</span>
                            @endif
                        </td>
                        <td></td>
                        <td class="py-3.5 px-4 text-right">
                            @php $netNominal = $record->total_gain_nominal - $record->total_loss_nominal; @endphp
                            @if($netNominal < 0)
                                <span class="text-red-600 dark:text-red-400">-Rp {{ number_format(abs($netNominal), 0, ',', '.') }}</span>
                            @elseif($netNominal > 0)
                                <span class="text-blue-600 dark:text-blue-400">+Rp {{ number_format($netNominal, 0, ',', '.') }}</span>
                            @else
                                <span class="text-emerald-600 dark:text-emerald-400">Rp 0</span>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>