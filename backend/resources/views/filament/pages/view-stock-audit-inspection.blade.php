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

<div class="so-audit-container space-y-6" x-data="{
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
    {{-- SCOPED CSS TO PREVENT ANY JUMBO ICONS ACROSS ALL BROWSERS & THEMES --}}
    <style>
        .so-audit-container svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            max-width: 1.5rem !important;
            max-height: 1.5rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }
        .so-audit-container svg.icon-xs {
            width: 0.875rem !important;
            height: 0.875rem !important;
            max-width: 0.875rem !important;
            max-height: 0.875rem !important;
        }
        .so-audit-container svg.icon-sm {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
        }
        .so-audit-container svg.icon-md {
            width: 1.25rem !important;
            height: 1.25rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
        }
        .so-audit-container svg.icon-lg {
            width: 1.5rem !important;
            height: 1.5rem !important;
            max-width: 1.5rem !important;
            max-height: 1.5rem !important;
        }
    </style>

    {{-- 1. INFORMASI DOKUMEN & PELAKSANAAN DALAM CARD-CARD --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        {{-- Card 1: Identitas Dokumen --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-primary-500/5 rounded-full blur-2xl -mr-6 -mt-6 pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <div style="width: 34px; height: 34px; min-width: 34px; min-height: 34px;" class="rounded-xl bg-primary-50 dark:bg-primary-950/50 border border-primary-200/60 dark:border-primary-800/60 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0">
                            <x-heroicon-o-document-text class="icon-sm" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" />
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Dokumen Sidak</span>
                    </div>
                    
                    {{-- Status Badge --}}
                    @if($record->status === 'DRAFT')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Berjalan
                        </span>
                    @elseif($record->status === 'INVESTIGATION_PENDING')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Klarifikasi
                        </span>
                    @elseif($record->status === 'ADJUSTED')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai (Adjusted)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            Ditutup
                        </span>
                    @endif
                </div>

                <div class="text-xl font-bold font-mono tracking-tight text-gray-900 dark:text-gray-100">
                    {{ $record->audit_number }}
                </div>

                <div class="mt-3 space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Tanggal:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $record->inspection_date?->translatedFormat('d F Y') ?: $record->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Metode:</span>
                        <span class="font-semibold text-primary-600 dark:text-primary-400">
                            @if($record->inspection_mode === 'FREE_SCAN')
                                Spot Check (Bebas)
                            @elseif($record->inspection_mode === 'RANDOM_LOTTERY')
                                Undian Acak Sistem
                            @else
                                Berbasis Risiko
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Lokasi & Tim Pemeriksa --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div style="width: 34px; height: 34px; min-width: 34px; min-height: 34px;" class="rounded-xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200/60 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0">
                        <x-heroicon-o-building-storefront class="icon-sm" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" />
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cabang & Tim Pelaksana</span>
                </div>

                <div class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                    {{ $record->branch?->name ?: 'Semua Cabang' }}
                </div>

                <div class="mt-3 space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Auditor:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $record->auditor?->name ?: '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Saksi Toko:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $record->acknowledged_by ?: '-' }}</span>
                    </div>
                    @if($record->adjustment)
                        <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-semibold pt-0.5">
                            <span class="text-gray-400">No. Koreksi:</span>
                            <span class="font-mono">{{ $record->adjustment->adjustment_number }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Catatan & Ringkasan Hasil --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div style="width: 34px; height: 34px; min-width: 34px; min-height: 34px;" class="rounded-xl bg-purple-50 dark:bg-purple-950/50 border border-purple-200/60 dark:border-purple-800/60 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                        <x-heroicon-o-chat-bubble-bottom-center-text class="icon-sm" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" />
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan Sidak</span>
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 p-2.5 rounded-xl border border-gray-200/60 dark:border-gray-700 min-h-[46px] line-clamp-2 italic">
                    {{ $record->notes ?: 'Tidak ada catatan tambahan yang dicantumkan saat inspeksi.' }}
                </div>

                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Akurasi Stok (IRA):</span>
                    <span class="font-mono font-bold {{ $accuracyRate >= 95 ? 'text-emerald-600 dark:text-emerald-400' : ($accuracyRate >= 80 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $accuracyRate }}% ({{ $matchCount }}/{{ $totalChecked }})
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- 2. KPI / METRIC CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Card Metrik: Total Checked --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Jumlah Item Dicek</div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-50 mt-1 font-mono">
                    {{ number_format($totalChecked, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Fisik: <strong class="text-gray-700 dark:text-gray-300">{{ number_format($totalPhysicalQty, 0) }}</strong> | Sistem: {{ number_format($totalSystemQty, 0) }}
                </div>
            </div>
            <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px;" class="rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 shrink-0">
                <x-heroicon-o-cube class="icon-lg" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px;" />
            </div>
        </div>

        {{-- Card Metrik: Stock Accuracy (Match) --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Item Cocok (Match)</div>
                <div class="text-3xl font-extrabold mt-1 font-mono text-emerald-600 dark:text-emerald-400">
                    {{ number_format($matchCount, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ $accuracyRate }}%</strong> dari total item diperiksa
                </div>
            </div>
            <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px;" class="rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <x-heroicon-o-check-badge class="icon-lg" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px;" />
            </div>
        </div>

        {{-- Card Metrik: Discrepancy Count --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Jumlah Selisih</div>
                <div class="text-3xl font-extrabold mt-1 font-mono {{ $discCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ number_format($discCount, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5">
                    <span class="text-red-600 dark:text-red-400 font-semibold">{{ $minusCount }} Minus</span>
                    <span>&bull;</span>
                    <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $plusCount }} Plus</span>
                </div>
            </div>
            <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px;" class="rounded-xl {{ $discCount > 0 ? 'bg-red-50 dark:bg-red-950/50 text-red-600' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600' }} flex items-center justify-center shrink-0">
                <x-heroicon-o-exclamation-triangle class="icon-lg" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px;" />
            </div>
        </div>

        {{-- Card Metrik: Total Nominal Selisih --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Total Nominal Kerugian</div>
                <div class="text-2xl font-black text-red-600 dark:text-red-400 mt-1 font-mono">
                    -Rp {{ number_format($record->total_loss_nominal, 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                    Lebih: +Rp {{ number_format($record->total_gain_nominal, 0, ',', '.') }}
                </div>
            </div>
            <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px;" class="rounded-xl bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                <x-heroicon-o-banknotes class="icon-lg" style="width: 22px; height: 22px; min-width: 22px; min-height: 22px;" />
            </div>
        </div>

    </div>

    {{-- 3. TABEL ITEM YANG DICEK DENGAN FILTER & SEARCH --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 overflow-hidden">
        
        {{-- Card Header Tabel --}}
        <div class="p-4 sm:p-5 border-b border-gray-200/80 dark:border-gray-700/80 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50">
            
            {{-- Filter Pills --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <button
                    type="button"
                    @click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-primary-600 text-white shadow-sm font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all border border-gray-200/70 dark:border-gray-600 flex items-center gap-1.5"
                >
                    <span>Semua Item</span>
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
            <div class="relative w-full md:w-72">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Cari nama produk / barcode / SKU..."
                    class="w-full text-xs rounded-xl bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 pl-8 pr-8 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-gray-800 dark:text-gray-100"
                />
                <div class="absolute left-2.5 top-2.5 text-gray-400 pointer-events-none flex items-center justify-center">
                    <x-heroicon-m-magnifying-glass class="icon-xs text-gray-400" style="width: 15px; height: 15px; min-width: 15px; min-height: 15px;" />
                </div>
                <button
                    type="button"
                    x-show="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex items-center justify-center"
                >
                    <x-heroicon-m-x-mark class="icon-xs" style="width: 15px; height: 15px; min-width: 15px; min-height: 15px;" />
                </button>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-slate-100 font-semibold tracking-wider uppercase text-[11px] border-b border-slate-700">
                        <th class="py-3.5 px-3.5 text-center w-12">No</th>
                        <th class="py-3.5 px-4 min-w-[260px]">Produk & Barcode</th>
                        <th class="py-3.5 px-3 text-center min-w-[95px]">Lokasi Rak</th>
                        <th class="py-3.5 px-3.5 text-right font-mono min-w-[100px]">Stok Sistem</th>
                        <th class="py-3.5 px-3.5 text-right font-mono min-w-[100px] bg-slate-750">Fisik Dihitung</th>
                        <th class="py-3.5 px-3.5 text-center font-mono min-w-[110px]">Selisih Qty</th>
                        <th class="py-3.5 px-3.5 text-right font-mono min-w-[100px]">HPP Satuan</th>
                        <th class="py-3.5 px-4 text-right font-mono min-w-[130px]">Total Selisih (Rp)</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Keterangan</th>
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

                            {{-- Lokasi Rak --}}
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
                                        <x-heroicon-m-check class="icon-xs" style="width: 14px; height: 14px; min-width: 14px; min-height: 14px;" /> 0
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
