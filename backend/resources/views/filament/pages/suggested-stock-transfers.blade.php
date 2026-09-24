<x-filament-panels::page>
    @php
        $summary = $this->getFleetSummaryData();
        $routes = $summary['routes'] ?? [];
    @endphp

    {{-- Executive Header Context --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white shadow-lg border border-indigo-900/40">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                        AI Engine Aktif
                    </span>
                    <span class="text-xs text-slate-400 font-medium tracking-wide">
                        Algoritma Rebalancing Antar-Cabang (P2P Store-to-Store)
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight mt-1.5 text-white">
                    Pusat Komando Redistribusi Stok Cabang
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Menganalisa disparitas penjualan harian (ADS) & sisa hari stok (DOH). Memindahkan stok mati di cabang surplus ke cabang yang sedang kritis tanpa membeli barang baru ke supplier.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 text-right">
                    <div class="text-[10px] uppercase font-bold tracking-wider text-indigo-200">Efisiensi Kas Perusahaan</div>
                    <div class="text-base sm:text-lg font-black text-emerald-400">
                        Rp {{ number_format($summary['total_capital_freed'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Background Decorative Accent --}}
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Precision Metric KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
        
        {{-- Card 1: Produk Siap Dimutasi --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Disparitas SKU</span>
                <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 ring-1 ring-purple-500/20 group-hover:scale-105 transition-transform duration-200">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-1.5">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($summary['total_items'] ?? 0, 0, ',', '.') }}
                </span>
                <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">SKU Terdeteksi</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs">
                <span class="text-purple-700 dark:text-purple-400 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                    Laris vs Macet
                </span>
                <span class="text-slate-400 text-[11px]">Siap dialihkan</span>
            </div>
        </div>

        {{-- Card 2: Estimasi Muatan Fisik --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Kapasitas Muatan</span>
                <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 ring-1 ring-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                    <x-filament::icon icon="heroicon-o-truck" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-1.5">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($summary['total_units'] ?? 0, 0, ',', '.') }}
                </span>
                <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">PCS Barang</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs">
                <span class="text-blue-700 dark:text-blue-400 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    Armada Fleksibel
                </span>
                <span class="text-slate-400 text-[11px]">Bebas kuota min</span>
            </div>
        </div>

        {{-- Card 3: Modal Mati Diselamatkan --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Modal Diselamatkan</span>
                <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/20 group-hover:scale-105 transition-transform duration-200">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-1">
                <span class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($summary['total_capital_freed'] ?? 0, 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs">
                <span class="text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Hemat Kas
                </span>
                <span class="text-slate-400 text-[11px]">Tanpa PO Baru</span>
            </div>
        </div>

        {{-- Card 4: Rute Siap Distribusi --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Konektivitas Cabang</span>
                <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 ring-1 ring-amber-500/20 group-hover:scale-105 transition-transform duration-200">
                    <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-1.5">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ number_format($summary['total_routes'] ?? 0, 0, ',', '.') }}
                </span>
                <span class="text-xs font-semibold text-slate-500 dark:text-gray-400">Rute Aktif</span>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs">
                <span class="text-amber-700 dark:text-amber-400 font-medium flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Jaringan P2P
                </span>
                <span class="text-slate-400 text-[11px]">Store-to-Store</span>
            </div>
        </div>

    </div>

    {{-- Interactive Route Matrix Bar --}}
    @if(!empty($routes))
        <div class="rounded-2xl bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-gray-800 p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-truck" class="h-4 w-4 text-slate-500" />
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300">
                        Rute Distribusi Siap Berangkat
                    </span>
                </div>
                <span class="text-xs text-slate-500 dark:text-gray-400">
                    Kelompokkan muatan kendaraan berdasarkan rute cabang berikut:
                </span>
            </div>
            <div class="flex flex-wrap gap-2.5">
                @foreach($routes as $rKey => $r)
                    <div class="inline-flex items-center gap-2.5 px-3 py-2 rounded-xl bg-slate-50 dark:bg-gray-800/80 border border-slate-200 dark:border-gray-700 text-xs">
                        <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            {{ $r['route_label'] }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-white dark:bg-gray-700 font-semibold text-slate-700 dark:text-gray-300 shadow-2xs border border-slate-200/60 dark:border-gray-600">
                            {{ $r['item_count'] }} SKU ({{ number_format($r['total_units'], 0, ',', '.') }} Pcs)
                        </span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                            Rp {{ number_format($r['capital_freed'], 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Main Filament Table --}}
    <div class="mt-1">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
