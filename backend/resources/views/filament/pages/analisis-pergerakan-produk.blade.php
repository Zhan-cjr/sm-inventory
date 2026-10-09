<x-filament-panels::page>
    @php
        $report = $this->velocity_report;
        $kpi = $report['kpi'];
        $items = $report['items'];
        $daysCount = $report['days_count'];
    @endphp

    <style>
        .vel-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .dark .vel-card {
            background: #111827;
            border-color: #1f2937;
        }
        
        .vel-quadrant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        
        .vel-quad-card {
            border-radius: 0.75rem;
            padding: 1.1rem 1.25rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .vel-quad-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .vel-quad-card.active-quad {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        
        .vel-quad-red {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .dark .vel-quad-red {
            background: rgba(127, 29, 29, 0.25);
            border-color: #991b1b;
        }
        
        .vel-quad-amber {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .dark .vel-quad-amber {
            background: rgba(120, 53, 15, 0.25);
            border-color: #92400e;
        }
        
        .vel-quad-dark {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .dark .vel-quad-dark {
            background: rgba(31, 41, 55, 0.6);
            border-color: #374151;
        }
        
        .vel-quad-green {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .dark .vel-quad-green {
            background: rgba(20, 83, 45, 0.25);
            border-color: #166534;
        }

        .vel-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .vel-btn {
            background: #1f2937;
            border-color: #374151;
            color: #9ca3af;
        }
        .vel-btn:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }
        .vel-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        .vel-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8rem;
        }
        .vel-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.03em;
            padding: 0.75rem 0.85rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .vel-table th {
            background: #1f2937;
            color: #9ca3af;
            border-color: #374151;
        }
        .vel-table td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dark .vel-table td {
            border-color: #1f2937;
        }
        .vel-table tbody tr:hover {
            background: rgba(241, 245, 249, 0.6);
        }
        .dark .vel-table tbody tr:hover {
            background: rgba(31, 41, 55, 0.4);
        }

        .vel-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 800;
            white-space: nowrap;
        }
        .vel-badge-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
        .dark .vel-badge-danger {
            background: rgba(220, 38, 38, 0.2);
            color: #f87171;
            border-color: #991b1b;
        }
        .vel-badge-warning {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fcd34d;
        }
        .dark .vel-badge-warning {
            background: rgba(217, 119, 6, 0.2);
            color: #fbbf24;
            border-color: #92400e;
        }
        .vel-badge-dark {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .dark .vel-badge-dark {
            background: rgba(75, 85, 99, 0.2);
            color: #9ca3af;
            border-color: #4b5563;
        }
        .vel-badge-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
        }
        .dark .vel-badge-success {
            background: rgba(22, 163, 74, 0.2);
            color: #4ade80;
            border-color: #166534;
        }
    </style>

    <div class="space-y-6">

        {{-- 1. TOOLBAR FILTER HORIZON WAKTU & LINGKUP DATA --}}
        <div class="vel-card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider dark:text-gray-400">Periode Analisis:</span>
                    <button wire:click="applyDatePreset('TODAY')" type="button" class="vel-btn {{ $date_preset === 'TODAY' ? 'active' : '' }}">
                        Hari Ini
                    </button>
                    <button wire:click="applyDatePreset('7_DAYS')" type="button" class="vel-btn {{ $date_preset === '7_DAYS' ? 'active' : '' }}">
                        7 Hari Terakhir
                    </button>
                    <button wire:click="applyDatePreset('30_DAYS')" type="button" class="vel-btn {{ $date_preset === '30_DAYS' ? 'active' : '' }}">
                        30 Hari Terakhir (Default)
                    </button>
                    <button wire:click="applyDatePreset('90_DAYS')" type="button" class="vel-btn {{ $date_preset === '90_DAYS' ? 'active' : '' }}">
                        90 Hari (Kuartal)
                    </button>
                    <button wire:click="applyDatePreset('365_DAYS')" type="button" class="vel-btn {{ $date_preset === '365_DAYS' ? 'active' : '' }}">
                        1 Tahun
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button wire:click="resetFilters" type="button" class="vel-btn" title="Reset semua filter">
                        <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                        Reset Filter
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Tanggal Mulai --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Dari Tanggal:</label>
                    <input wire:model.live="start_date" type="date" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>

                {{-- Tanggal Akhir --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Sampai Tanggal:</label>
                    <input wire:model.live="end_date" type="date" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                </div>

                {{-- Cabang --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Cabang / Toko:</label>
                    <select wire:model.live="branch_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @if(!auth()->user()->branch_id)
                            <option value="ALL">Semua Cabang</option>
                        @endif
                        @foreach($this->branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Mitra Supplier:</label>
                    <select wire:model.live="supplier_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="ALL">Semua Supplier</option>
                        @foreach($this->suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kategori Barang:</label>
                    <select wire:model.live="category_id" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="ALL">Semua Kategori</option>
                        @foreach($this->categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. MATRIKS 4 KUADRAN KEPUTUSAN CEPAT (1 KALI PANDANGAN) --}}
        <div class="vel-quadrant-grid">
            {{-- Kuadran 1: FAST MOVING & KRITIS --}}
            <div wire:click="setQuadrant('CRITICAL_FAST')" class="vel-quad-card vel-quad-red {{ $quadrant === 'CRITICAL_FAST' ? 'active-quad' : '' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="vel-badge vel-badge-danger">🔴 FAST MOVING (KRITIS)</span>
                    <span class="text-xs font-bold text-red-600 dark:text-red-400">DOH &le; 7 Hari</span>
                </div>
                <div class="text-2xl font-black text-red-700 dark:text-red-300">
                    {{ number_format($kpi['critical_fast']['count']) }} <span class="text-xs font-normal">SKU</span>
                </div>
                <div class="text-xs text-red-600/90 dark:text-red-400/90 mt-1">
                    Potensi Omset Terancam: <b>Rp {{ number_format($kpi['critical_fast']['potential_revenue_risk']) }}</b>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between border-t border-red-200/60 pt-2 dark:border-red-900/40">
                    <span>Sisa Stok: {{ number_format($kpi['critical_fast']['total_stock']) }} pcs</span>
                    <span class="font-bold text-red-700 dark:text-red-300">Harus Reorder &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 2: SLOW MOVING & OVERSTOCK --}}
            <div wire:click="setQuadrant('OVERSTOCK_SLOW')" class="vel-quad-card vel-quad-amber {{ $quadrant === 'OVERSTOCK_SLOW' ? 'active-quad' : '' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="vel-badge vel-badge-warning">🟡 SLOW MOVING (OVERSTOCK)</span>
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400">DOH &ge; 60 Hari</span>
                </div>
                <div class="text-2xl font-black text-amber-700 dark:text-amber-300">
                    {{ number_format($kpi['overstock_slow']['count']) }} <span class="text-xs font-normal">SKU</span>
                </div>
                <div class="text-xs text-amber-600/90 dark:text-amber-400/90 mt-1">
                    Modal Tertahan (Macet): <b>Rp {{ number_format($kpi['overstock_slow']['capital_tied']) }}</b>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between border-t border-amber-200/60 pt-2 dark:border-amber-900/40">
                    <span>Sisa Stok: {{ number_format($kpi['overstock_slow']['total_stock']) }} pcs</span>
                    <span class="font-bold text-amber-700 dark:text-amber-300">Kunci Order &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 3: DEAD STOCK --}}
            <div wire:click="setQuadrant('DEAD_STOCK')" class="vel-quad-card vel-quad-dark {{ $quadrant === 'DEAD_STOCK' ? 'active-quad' : '' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="vel-badge vel-badge-dark">⚫ DEAD STOCK (STOK MATI)</span>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">0 Penjualan</span>
                </div>
                <div class="text-2xl font-black text-gray-800 dark:text-gray-200">
                    {{ number_format($kpi['dead_stock']['count']) }} <span class="text-xs font-normal">SKU</span>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Total Aset Mati: <b>Rp {{ number_format($kpi['dead_stock']['capital_tied']) }}</b>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between border-t border-gray-300 pt-2 dark:border-gray-700">
                    <span>Sisa Stok: {{ number_format($kpi['dead_stock']['total_stock']) }} pcs</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">Retur / Obral &rarr;</span>
                </div>
            </div>

            {{-- Kuadran 4: OPTIMAL --}}
            <div wire:click="setQuadrant('OPTIMAL')" class="vel-quad-card vel-quad-green {{ $quadrant === 'OPTIMAL' ? 'active-quad' : '' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="vel-badge vel-badge-success">🟢 OPTIMAL & SEHAT</span>
                    <span class="text-xs font-bold text-green-600 dark:text-green-400">DOH 8 - 30 Hari</span>
                </div>
                <div class="text-2xl font-black text-green-700 dark:text-green-300">
                    {{ number_format($kpi['optimal']['count']) }} <span class="text-xs font-normal">SKU</span>
                </div>
                <div class="text-xs text-green-600/90 dark:text-green-400/90 mt-1">
                    Aset Produktif: <b>Rp {{ number_format($kpi['optimal']['capital_healthy']) }}</b>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 flex items-center justify-between border-t border-green-200/60 pt-2 dark:border-green-900/40">
                    <span>Sisa Stok: {{ number_format($kpi['optimal']['total_stock']) }} pcs</span>
                    <span class="font-bold text-green-700 dark:text-green-300">Pola Normal &rarr;</span>
                </div>
            </div>
        </div>

        {{-- 3. TABEL ANALITIK DATA & SORTING --}}
        <div class="vel-card space-y-3">
            {{-- Toolbar Pencarian & Urutan --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1 min-w-[260px]">
                    <div class="relative w-full max-w-md">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari SKU, Barcode, atau Nama Produk..." class="w-full text-xs pl-8 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" />
                    </div>

                    @if($quadrant !== 'ALL')
                        <button wire:click="setQuadrant('ALL')" type="button" class="vel-btn" title="Tampilkan semua kuadran">
                            Tampilkan Semua Kuadran &times;
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 dark:text-gray-400">Urutkan Berdasarkan:</label>
                    <select wire:model.live="sort_by" class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="doh_asc">⚡ Sisa Hari Tercepat Habis (DOH Rendah)</option>
                        <option value="doh_desc">⏳ Sisa Hari Terlama (DOH Tinggi / Slow)</option>
                        <option value="revenue_desc">💰 Omset Penjualan Terbesar (Rp)</option>
                        <option value="qty_desc">📦 Volume Penjualan Terbanyak (Pcs)</option>
                        <option value="capital_desc">💼 Modal Tertahan Terbesar (Rp Stok)</option>
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

            {{-- Table --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="vel-table">
                    <thead>
                        <tr>
                            <th style="width: 35px; text-align: center;">No</th>
                            <th>SKU & Barcode</th>
                            <th>Nama Barang & Kategori</th>
                            <th>Supplier</th>
                            <th style="text-align: right;">Terjual ({{ $daysCount }} Hari)</th>
                            <th style="text-align: right;">Laju Jual / Hari (ADS)</th>
                            <th style="text-align: right;">Total Omset (Rp)</th>
                            <th style="text-align: right;">Sisa Stok Fisik</th>
                            <th style="text-align: right;">Modal Mengendap (Rp)</th>
                            <th style="text-align: center;">Sisa Hari Stok (DOH)</th>
                            <th style="text-align: center;">Status Kuadran</th>
                            <th style="text-align: left;">Rekomendasi Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $it)
                            <tr>
                                <td style="text-align: center; color: #94a3b8;">{{ $report['from'] + $idx }}</td>
                                <td style="font-family: monospace; font-size: 0.72rem;">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $it['sku'] }}</div>
                                    <div class="text-gray-500">{{ $it['barcode'] }}</div>
                                    @if(!empty($it['additional_barcodes']))
                                        <div class="text-[10px] text-blue-600 dark:text-blue-400 font-sans" title="Multi-Barcode Terhubung">
                                            +{{ count($it['additional_barcodes']) }} Multi-Barcode
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $it['product_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $it['category_name'] }}</div>
                                </td>
                                <td class="text-xs text-gray-700 dark:text-gray-300">
                                    {{ $it['supplier_name'] }}
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0284c7;">
                                    {{ number_format($it['qty_sold'], 0, ',', '.') }} pcs
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #2563eb;">
                                    {{ number_format($it['ads'], 1, ',', '.') }} /hr
                                </td>
                                <td style="text-align: right; font-weight: 700;">
                                    Rp {{ number_format($it['revenue'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 800; color: {{ $it['current_stock'] <= 5 ? '#dc2626' : '#15803d' }};">
                                    {{ number_format($it['current_stock'], 0, ',', '.') }} pcs
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #b45309;">
                                    Rp {{ number_format($it['capital_tied'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: center; font-weight: 900; font-size: 0.85rem;">
                                    <span class="{{ $it['doh'] <= 7 ? 'text-red-600' : ($it['doh'] >= 60 ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ $it['doh_display'] }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="vel-badge vel-badge-{{ $it['quadrant_badge'] }}">
                                        {{ $it['quadrant_label'] }}
                                    </span>
                                </td>
                                <td style="font-size: 0.72rem; font-weight: 600; color: #475569;" class="dark:text-gray-300">
                                    {{ $it['recommended_action'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 2.5rem;" class="text-gray-400">
                                    Tidak ditemukan produk yang sesuai dengan kriteria filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Toolbar --}}
            @if($report['total_items'] > 0)
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 text-xs text-gray-500 dark:text-gray-400">
                    <div>
                        Menampilkan <b>{{ number_format($report['from']) }}</b> s/d <b>{{ number_format($report['to']) }}</b> dari total <b>{{ number_format($report['total_items']) }}</b> produk
                    </div>

                    <div class="flex items-center gap-1">
                        <button wire:click="prevPage" type="button" class="vel-btn" {{ $report['current_page'] <= 1 ? 'disabled' : '' }}>
                            &larr; Sebelumnya
                        </button>
                        <span class="font-bold px-2">Hal. {{ $report['current_page'] }} / {{ $report['total_pages'] }}</span>
                        <button wire:click="nextPage" type="button" class="vel-btn" {{ $report['current_page'] >= $report['total_pages'] ? 'disabled' : '' }}>
                            Berikutnya &rarr;
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
