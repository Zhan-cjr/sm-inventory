<x-filament-panels::page>
    @php
        $remaining = $listing?->days_remaining;

        if ($listing && $listing->trial_start_date && $listing->trial_end_date) {
            $startDate = \Carbon\Carbon::parse($listing->trial_start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($listing->trial_end_date)->endOfDay();
            $totalDuration = max(1, (int) round($startDate->diffInDays($endDate)));
            $elapsedDays = max(0, min($totalDuration, (int) round($startDate->diffInDays(now()))));
            $percentElapsed = min(100, max(0, (int) round(($elapsedDays / $totalDuration) * 100)));
        } else {
            $totalDuration = 90;
            $elapsedDays = 0;
            $percentElapsed = 0;
        }
    @endphp

    <style>
        /* ============================================================
           PERFORMA DASHBOARD ENTERPRISE DESIGN SYSTEM
           Self-contained styling for a world-class ERP presentation
           ============================================================ */
        .pf-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            font-family: inherit;
            color: #0f172a;
        }
        .dark .pf-dashboard {
            color: #f1f5f9;
        }

        /* Hero banner card */
        .pf-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid #334155;
            border-radius: 1.25rem;
            padding: 1.75rem 2rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .dark .pf-hero {
            background: linear-gradient(135deg, #0b0f19 0%, #151d2f 100%);
            border-color: #25334c;
        }
        .pf-hero-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .pf-hero-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
            min-width: 280px;
        }
        .pf-hero-title {
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
        }
        .pf-hero-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        .pf-hero-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .pf-hero-meta strong {
            color: #ffffff;
        }

        /* Hero Progress bar box */
        .pf-hero-progress-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1rem;
            padding: 1.1rem 1.35rem;
            min-width: 260px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            backdrop-filter: blur(8px);
        }
        .pf-hero-progress-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            font-weight: 700;
            color: #cbd5e1;
        }
        .pf-progress-track {
            width: 100%;
            height: 0.5rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        .pf-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.5s ease;
        }
        .pf-hero-progress-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        /* Top selector card */
        .pf-toolbar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }
        .dark .pf-toolbar-card {
            background: #131b2a;
            border-color: #232f46;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        .pf-selector-group {
            flex: 1;
            min-width: 320px;
            max-width: 650px;
        }
        .pf-selector-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .dark .pf-selector-label {
            color: #94a3b8;
        }
        .pf-select {
            width: 100%;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .dark .pf-select {
            border-color: #334155;
            background-color: #1e293b;
            color: #f1f5f9;
        }
        .pf-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background-color: #ffffff;
        }
        .dark .pf-select:focus {
            background-color: #0f172a;
        }

        /* Status Pills */
        .pf-pills-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .pf-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .pf-pill-amber {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .dark .pf-pill-amber {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.3);
        }
        .pf-pill-emerald {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .dark .pf-pill-emerald {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .pf-pill-rose {
            background: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }
        .dark .pf-pill-rose {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border-color: rgba(244, 63, 94, 0.3);
        }
        .pf-pill-blue {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .dark .pf-pill-blue {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border-color: rgba(14, 165, 233, 0.3);
        }
        .pf-pill-dark {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-family: monospace;
        }
        .pf-dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            display: inline-block;
        }

        /* 4 Executive KPI Cards Grid */
        .pf-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }
        @media (max-width: 1100px) {
            .pf-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .pf-kpi-grid {
                grid-template-columns: 1fr;
            }
        }
        .pf-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.15rem;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }
        .dark .pf-kpi-card {
            background: #131b2a;
            border-color: #232f46;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }
        .pf-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        .pf-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pf-kpi-title {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .dark .pf-kpi-title {
            color: #94a3b8;
        }
        .pf-icon-circle {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .pf-kpi-val-row {
            display: flex;
            align-items: baseline;
            gap: 0.35rem;
        }
        .pf-kpi-val {
            font-size: 1.95rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1;
            color: #0f172a;
        }
        .dark .pf-kpi-val {
            color: #ffffff;
        }
        .pf-kpi-subtext {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.3;
        }
        .dark .pf-kpi-subtext {
            color: #94a3b8;
        }

        /* Tabs Bar */
        .pf-tabs-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.25rem;
            overflow-x: auto;
        }
        .dark .pf-tabs-bar {
            border-color: #232f46;
        }
        .pf-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.15rem;
            border: none;
            background: transparent;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
            border-radius: 0.65rem 0.65rem 0 0;
        }
        .dark .pf-tab {
            color: #94a3b8;
        }
        .pf-tab:hover {
            color: #0f172a;
            background: #f1f5f9;
        }
        .dark .pf-tab:hover {
            color: #ffffff;
            background: #1e293b;
        }
        .pf-tab.is-active {
            color: #059669 !important;
            border-bottom-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.08) !important;
        }
        .dark .pf-tab.is-active {
            color: #34d399 !important;
            border-bottom-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.15) !important;
        }
        .pf-tab-counter {
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 800;
            background: #e2e8f0;
            color: #475569;
        }
        .dark .pf-tab-counter {
            background: #25334c;
            color: #cbd5e1;
        }

        /* Filter Controls Card */
        .pf-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.85rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .dark .pf-filter-card {
            background: #131b2a;
            border-color: #232f46;
        }
        .pf-input-search {
            flex: 1;
            min-width: 240px;
            padding: 0.6rem 0.9rem;
            font-size: 0.825rem;
            border-radius: 0.65rem;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
            outline: none;
        }
        .dark .pf-input-search {
            border-color: #334155;
            background-color: #1e293b;
            color: #f1f5f9;
        }
        .pf-input-search:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.15);
        }

        /* Table Card Container */
        .pf-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dark .pf-table-card {
            background: #131b2a;
            border-color: #232f46;
        }
        .pf-table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .pf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.825rem;
            text-align: left;
        }
        .pf-th {
            padding: 0.85rem 1rem;
            font-size: 0.725rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f8fafc;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .pf-th {
            background: #0f1624;
            color: #94a3b8;
            border-color: #232f46;
        }
        .pf-td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .dark .pf-td {
            border-color: #1e293b;
            color: #e2e8f0;
        }
        .pf-tr:hover .pf-td {
            background-color: #f8fafc;
        }
        .dark .pf-tr:hover .pf-td {
            background-color: #182337;
        }

        /* Detail List */
        .pf-dl {
            display: flex;
            flex-direction: column;
            divide-y: 1px solid #f1f5f9;
        }
        .pf-dl-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            font-size: 0.825rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .pf-dl-row {
            border-color: #232f46;
        }
        .pf-dl-dt {
            color: #64748b;
        }
        .dark .pf-dl-dt {
            color: #94a3b8;
        }
        .pf-dl-dd {
            font-weight: 700;
            color: #0f172a;
        }
        .dark .pf-dl-dd {
            color: #ffffff;
        }

        /* SVG Strict Dimensions */
        .pf-dashboard svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }
    </style>

    <div class="pf-dashboard">
        <!-- 1. Top Toolbar: Selector & Status Counters -->
        <div class="pf-toolbar-card">
            <div class="pf-selector-group">
                <label for="listing-select" class="pf-selector-label">
                    <span>📋</span>
                    <span>Pilih Perjanjian Listing untuk Dievaluasi</span>
                </label>
                <select
                    id="listing-select"
                    wire:change="switchListing($event.target.value)"
                    class="pf-select"
                >
                    @forelse($allListings as $item)
                        <option value="{{ $item->id }}" @selected($listing?->id === $item->id)>
                            [{{ $item->listing_number }}] {{ $item->supplier?->name ?? 'Tanpa Pemasok' }} — {{ $item->status }} ({{ count($item->products) }} Produk, {{ count((array)$item->allowed_branch_ids) }} Cabang Pilot)
                        </option>
                    @empty
                        <option value="">Belum ada perjanjian listing</option>
                    @endforelse
                </select>
            </div>

            <!-- Stats Counters -->
            <div class="pf-pills-row">
                @php
                    $trialCount = $allListings->where('status', 'TRIAL')->count();
                    $passedCount = $allListings->where('status', 'PASSED')->count();
                    $delistedCount = $allListings->where('status', 'DELISTED')->count();
                @endphp
                <div class="pf-pill pf-pill-amber">
                    <span class="pf-dot" style="background: #f59e0b;"></span>
                    <span>{{ $trialCount }} Masa Uji Coba (Trial)</span>
                </div>
                <div class="pf-pill pf-pill-emerald">
                    <span class="pf-dot" style="background: #10b981;"></span>
                    <span>{{ $passedCount }} Lulus & Rollout</span>
                </div>
                <div class="pf-pill pf-pill-rose">
                    <span class="pf-dot" style="background: #f43f5e;"></span>
                    <span>{{ $delistedCount }} Dihentikan</span>
                </div>
            </div>
        </div>

        @if(!$listing)
            <!-- Empty State Card -->
            <div class="pf-toolbar-card" style="padding: 4rem 2rem; text-align: center; flex-direction: column; justify-content: center;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🔍</div>
                <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0;">Belum Ada Perjanjian Listing</h3>
                <p style="font-size: 0.85rem; color: #64748b; margin: 0.5rem 0 1.5rem; max-width: 420px;">
                    Silakan buat perjanjian listing produk baru terlebih dahulu untuk mulai memonitor uji coba dan evaluasi performa.
                </p>
                <a href="{{ \App\Filament\Resources\ListingProduks\ListingProdukResource::getUrl('create') }}" class="pf-pill pf-pill-emerald" style="padding: 0.65rem 1.25rem; font-size: 0.85rem; text-decoration: none;">
                    ➕ Buat Listing Baru
                </a>
            </div>
        @else
            <!-- 2. Hero Card: Agreement Summary & Countdown Status -->
            <div class="pf-hero">
                <div class="pf-hero-top">
                    <div class="pf-hero-info">
                        <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                            <span class="pf-pill pf-pill-dark">
                                {{ $listing->listing_number }}
                            </span>

                            @if($listing->status === 'TRIAL')
                                @if($remaining !== null && $remaining < 0)
                                    <span class="pf-pill pf-pill-rose">
                                        ⚠️ Jatuh Tempo (Lewat {{ abs($remaining) }} Hari)
                                    </span>
                                @elseif($remaining !== null && $remaining <= 14)
                                    <span class="pf-pill pf-pill-amber">
                                        ⏳ Masa Trial: Sisa {{ $remaining }} Hari Lagi
                                    </span>
                                @else
                                    <span class="pf-pill pf-pill-emerald">
                                        ✅ Masa Trial Aktif: Sisa {{ $remaining }} Hari
                                    </span>
                                @endif
                            @elseif($listing->status === 'PASSED')
                                <span class="pf-pill pf-pill-emerald">
                                    🎉 Telah Diluluskan & Rollout (Aktif Selamanya)
                                </span>
                            @else
                                <span class="pf-pill pf-pill-rose">
                                    🛑 Dihentikan (Delisted)
                                </span>
                            @endif

                            <span class="pf-pill {{ $listing->supplier?->is_consignment ? 'pf-pill-amber' : 'pf-pill-blue' }}">
                                {{ $listing->supplier?->is_consignment ? '📦 Konsinyasi' : '🛒 Beli Putus' }}
                            </span>
                        </div>

                        <h2 class="pf-hero-title">
                            {{ $listing->supplier?->name ?? 'Pemasok Tidak Diketahui' }}
                        </h2>

                        <div class="pf-hero-meta">
                            <span class="pf-hero-meta-item">
                                <span>📅 Periode:</span>
                                <strong>{{ $listing->trial_start_date ? \Carbon\Carbon::parse($listing->trial_start_date)->format('d M Y') : '-' }} s/d {{ $listing->trial_end_date ? \Carbon\Carbon::parse($listing->trial_end_date)->format('d M Y') : '-' }}</strong>
                            </span>
                            <span class="pf-hero-meta-item">
                                <span>🏪 Cabang Pilot:</span>
                                <strong>{{ count((array) ($listing->allowed_branch_ids ?? [])) }} Cabang</strong>
                            </span>
                            <span class="pf-hero-meta-item">
                                <span>📦 Total Varian:</span>
                                <strong>{{ count($listing->products) }} SKU Produk</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar Uji Coba -->
                    @if($listing->status === 'TRIAL')
                        <div class="pf-hero-progress-box">
                            <div class="pf-hero-progress-header">
                                <span>Waktu Uji Coba Berjalan</span>
                                <span style="color: #ffffff; font-weight: 800;">{{ $percentElapsed }}%</span>
                            </div>
                            <div class="pf-progress-track">
                                <div class="pf-progress-fill" style="width: {{ $percentElapsed }}%; background: {{ $percentElapsed >= 85 ? '#f43f5e' : ($percentElapsed >= 60 ? '#f59e0b' : '#10b981') }};"></div>
                            </div>
                            <div class="pf-hero-progress-footer">
                                <span>Hari ke-{{ $elapsedDays }}</span>
                                <span>Total {{ $totalDuration }} Hari</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. 4 Executive KPI Cards -->
            <div class="pf-kpi-grid">
                <!-- Card 1: Penerimaan Barang -->
                <div class="pf-kpi-card" style="border-top: 3px solid #0284c7;">
                    <div class="pf-kpi-header">
                        <span class="pf-kpi-title">Total Masuk (Penerimaan)</span>
                        <div class="pf-icon-circle" style="background: #e0f2fe; color: #0284c7;">
                            📥
                        </div>
                    </div>
                    <div>
                        <div class="pf-kpi-val-row">
                            <span class="pf-kpi-val">{{ number_format($metrics['total_bought'], 0, ',', '.') }}</span>
                            <span class="pf-kpi-unit">Pcs</span>
                        </div>
                        <div class="pf-kpi-subtext" style="margin-top: 0.35rem;">
                            Dari faktur masuk di {{ count((array)$listing->allowed_branch_ids) }} cabang pilot
                        </div>
                    </div>
                </div>

                <!-- Card 2: Terjual & Omset -->
                <div class="pf-kpi-card" style="border-top: 3px solid #059669;">
                    <div class="pf-kpi-header">
                        <span class="pf-kpi-title">Total Terjual (POS)</span>
                        <div class="pf-icon-circle" style="background: #d1fae5; color: #059669;">
                            🛍️
                        </div>
                    </div>
                    <div>
                        <div class="pf-kpi-val-row">
                            <span class="pf-kpi-val" style="color: #059669;">{{ number_format($metrics['total_sold'], 0, ',', '.') }}</span>
                            <span class="pf-kpi-unit">Pcs</span>
                        </div>
                        <div class="pf-kpi-subtext" style="margin-top: 0.35rem; font-weight: 700; color: #059669;">
                            Omset: Rp {{ number_format($metrics['total_omset'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Card 3: Sell-Through Rate -->
                <div class="pf-kpi-card" style="border-top: 3px solid #7c3aed;">
                    @php
                        $st = $metrics['sell_through'];
                        $stColor = $st >= 70 ? '#059669' : ($st >= 40 ? '#d97706' : '#e11d48');
                        $stLabel = $st >= 70 ? '🚀 Sangat Laku (Layak Rollout)' : ($st >= 40 ? '⚖️ Moderat' : '⚠️ Lambat / Kurang Laku');
                    @endphp
                    <div class="pf-kpi-header">
                        <span class="pf-kpi-title">Sell-Through Rate</span>
                        <div class="pf-icon-circle" style="background: #ede9fe; color: #7c3aed;">
                            📊
                        </div>
                    </div>
                    <div>
                        <div class="pf-kpi-val-row">
                            <span class="pf-kpi-val" style="color: {{ $stColor }};">{{ $st }}%</span>
                        </div>
                        <!-- Mini visual bar -->
                        <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 99px; margin-top: 0.4rem; overflow: hidden;">
                            <div style="width: {{ min(100, $st) }}%; height: 100%; background: {{ $stColor }}; border-radius: 99px;"></div>
                        </div>
                        <div class="pf-kpi-subtext" style="margin-top: 0.35rem; font-weight: 700; color: {{ $stColor }};">
                            {{ $stLabel }}
                        </div>
                    </div>
                </div>

                <!-- Card 4: Sisa Stok & Valuasi -->
                <div class="pf-kpi-card" style="border-top: 3px solid #d97706;">
                    <div class="pf-kpi-header">
                        <span class="pf-kpi-title">Sisa Stok Saat Ini</span>
                        <div class="pf-icon-circle" style="background: #fef3c7; color: #d97706;">
                            📦
                        </div>
                    </div>
                    <div>
                        <div class="pf-kpi-val-row">
                            <span class="pf-kpi-val">{{ number_format($metrics['total_stock'], 0, ',', '.') }}</span>
                            <span class="pf-kpi-unit">Pcs</span>
                        </div>
                        <div class="pf-kpi-subtext" style="margin-top: 0.35rem;">
                            Valuasi Modal: <strong>Rp {{ number_format($metrics['total_stock_value'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Navigation Tabs -->
            <div class="pf-tabs-bar">
                <button
                    type="button"
                    wire:click="$set('active_tab', 'breakdown')"
                    class="pf-tab {{ $active_tab === 'breakdown' ? 'is-active' : '' }}"
                >
                    <span>📑</span>
                    <span>Rincian Cabang & SKU</span>
                    <span class="pf-tab-counter">{{ count($metrics['filtered_breakdown'] ?? []) }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('active_tab', 'product_summary')"
                    class="pf-tab {{ $active_tab === 'product_summary' ? 'is-active' : '' }}"
                >
                    <span>🏷️</span>
                    <span>Konsolidasi per SKU</span>
                    <span class="pf-tab-counter">{{ count($productsSummary) }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('active_tab', 'branch_summary')"
                    class="pf-tab {{ $active_tab === 'branch_summary' ? 'is-active' : '' }}"
                >
                    <span>🏬</span>
                    <span>Peringkat Cabang Pilot</span>
                    <span class="pf-tab-counter">{{ count($branchesSummary) }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('active_tab', 'recent_transactions')"
                    class="pf-tab {{ $active_tab === 'recent_transactions' ? 'is-active' : '' }}"
                >
                    <span>🧾</span>
                    <span>Transaksi Kasir POS</span>
                    <span class="pf-tab-counter">{{ count($recentTransactions) }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$set('active_tab', 'contract_details')"
                    class="pf-tab {{ $active_tab === 'contract_details' ? 'is-active' : '' }}"
                >
                    <span>📄</span>
                    <span>Ketentuan Kontrak & Catatan</span>
                </button>
            </div>

            <!-- Tab 1: Breakdown per Cabang Pilot & SKU -->
            @if($active_tab === 'breakdown')
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Filter Toolbar -->
                    <div class="pf-filter-card">
                        <!-- Search Box -->
                        <div style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.9rem;">🔍</span>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari SKU atau Nama Produk..."
                                class="pf-input-search"
                            />
                        </div>

                        <!-- Filter Cabang -->
                        @php
                            $pilotBranches = \App\Models\Branch::whereIn('id', (array)($listing->allowed_branch_ids ?? []))->get();
                        @endphp
                        <div style="width: 220px;">
                            <select
                                wire:model.live="selected_branch_id"
                                class="pf-select"
                                style="padding: 0.55rem 0.85rem; font-size: 0.8rem;"
                            >
                                <option value="ALL">Semua Cabang Pilot ({{ count($pilotBranches) }})</option>
                                @foreach($pilotBranches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Performa -->
                        <div style="width: 200px;">
                            <select
                                wire:model.live="performance_filter"
                                class="pf-select"
                                style="padding: 0.55rem 0.85rem; font-size: 0.8rem;"
                            >
                                <option value="ALL">Semua Performa</option>
                                <option value="HIGH">🔥 Fast Moving (≥ 70%)</option>
                                <option value="MODERATE">⚖️ Moderat (40% - 69%)</option>
                                <option value="LOW">⚠️ Slow Moving (&lt; 40%)</option>
                                <option value="OUT_OF_STOCK">🛑 Stok Habis (0 Pcs)</option>
                            </select>
                        </div>

                        @if(!empty($search) || $selected_branch_id !== 'ALL' || $performance_filter !== 'ALL')
                            <button
                                type="button"
                                wire:click="resetFilters"
                                class="pf-tab"
                                style="padding: 0.5rem 0.9rem; font-size: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;"
                            >
                                ↺ Reset
                            </button>
                        @endif
                    </div>

                    <!-- Table Data -->
                    <div class="pf-table-card">
                        <div class="pf-table-responsive">
                            <table class="pf-table">
                                <thead>
                                    <tr>
                                        <th class="pf-th">Cabang Pilot</th>
                                        <th class="pf-th">SKU & Nama Produk</th>
                                        <th class="pf-th" style="text-align: right;">Masuk (Pcs)</th>
                                        <th class="pf-th" style="text-align: right;">Terjual (Pcs)</th>
                                        <th class="pf-th" style="text-align: right;">Sisa Stok</th>
                                        <th class="pf-th" style="text-align: center;">Sell-Through</th>
                                        <th class="pf-th" style="text-align: right;">Omset Penjualan</th>
                                        <th class="pf-th" style="text-align: center;">Status Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($metrics['filtered_breakdown'] as $row)
                                        <tr class="pf-tr">
                                            <td class="pf-td" style="font-weight: 700; white-space: nowrap;">
                                                🏪 {{ $row['branch_name'] }}
                                            </td>
                                            <td class="pf-td">
                                                <div style="font-weight: 800; font-size: 0.85rem;">{{ $row['product_name'] }}</div>
                                                <div style="font-size: 0.7rem; color: #64748b; font-family: monospace; margin-top: 2px;">{{ $row['product_sku'] }}</div>
                                            </td>
                                            <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 600;">
                                                {{ number_format($row['qty_received'], 0, ',', '.') }}
                                            </td>
                                            <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #059669;">
                                                {{ number_format($row['qty_sold'], 0, ',', '.') }}
                                            </td>
                                            <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 700;">
                                                <span style="color: {{ $row['current_stock'] <= 0 ? '#e11d48' : 'inherit' }};">
                                                    {{ number_format($row['current_stock'], 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="pf-td" style="text-align: center;">
                                                @php
                                                    $stVal = $row['sell_through'];
                                                    $stPill = $stVal >= 70 ? 'pf-pill-emerald' : ($stVal >= 40 ? 'pf-pill-amber' : 'pf-pill-rose');
                                                @endphp
                                                <span class="pf-pill {{ $stPill }}" style="font-family: monospace; font-size: 0.75rem;">
                                                    {{ $stVal }}%
                                                </span>
                                            </td>
                                            <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #7c3aed; white-space: nowrap;">
                                                Rp {{ number_format($row['omset'], 0, ',', '.') }}
                                            </td>
                                            <td class="pf-td" style="text-align: center; white-space: nowrap;">
                                                @if($row['current_stock'] <= 0 && $row['qty_received'] > 0)
                                                    <span class="pf-pill pf-pill-rose" style="font-size: 0.65rem;">HABIS (0 Pcs)</span>
                                                @elseif($row['current_stock'] <= 5 && $row['current_stock'] > 0)
                                                    <span class="pf-pill pf-pill-amber" style="font-size: 0.65rem;">MENIPIS ({{ number_format($row['current_stock']) }})</span>
                                                @else
                                                    <span class="pf-pill pf-pill-emerald" style="font-size: 0.65rem;">AMAN</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📂</div>
                                                <div style="font-weight: 700; color: #64748b;">Tidak ada data yang cocok dengan kriteria filter</div>
                                                <div style="font-size: 0.75rem; margin-top: 0.25rem;">Coba sesuaikan pencarian atau reset filter di atas.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tab 2: Konsolidasi per SKU Produk -->
            @if($active_tab === 'product_summary')
                <div class="pf-table-card">
                    <div class="pf-table-responsive">
                        <table class="pf-table">
                            <thead>
                                <tr>
                                    <th class="pf-th">SKU</th>
                                    <th class="pf-th">Nama Produk</th>
                                    <th class="pf-th" style="text-align: right;">Modal HPP</th>
                                    <th class="pf-th" style="text-align: right;">Harga Jual</th>
                                    <th class="pf-th" style="text-align: right;">Total Masuk</th>
                                    <th class="pf-th" style="text-align: right;">Total Terjual</th>
                                    <th class="pf-th" style="text-align: right;">Sisa Stok</th>
                                    <th class="pf-th" style="text-align: center;">Sell-Through</th>
                                    <th class="pf-th" style="text-align: right;">Total Omset</th>
                                    <th class="pf-th">Cabang Terlaris</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productsSummary as $p)
                                    <tr class="pf-tr">
                                        <td class="pf-td" style="font-family: monospace; font-weight: 800; white-space: nowrap;">
                                            {{ $p['sku'] }}
                                        </td>
                                        <td class="pf-td" style="font-weight: 700;">
                                            {{ $p['name'] }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; color: #64748b; white-space: nowrap;">
                                            Rp {{ number_format($p['cost_price'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 700; white-space: nowrap;">
                                            Rp {{ number_format($p['selling_price'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 600;">
                                            {{ number_format($p['qty_received'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #059669;">
                                            {{ number_format($p['qty_sold'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 600;">
                                            {{ number_format($p['current_stock'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: center;">
                                            @php
                                                $stVal = $p['sell_through'];
                                                $stPill = $stVal >= 70 ? 'pf-pill-emerald' : ($stVal >= 40 ? 'pf-pill-amber' : 'pf-pill-rose');
                                            @endphp
                                            <span class="pf-pill {{ $stPill }}" style="font-family: monospace; font-size: 0.75rem;">
                                                {{ $stVal }}%
                                            </span>
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #7c3aed; white-space: nowrap;">
                                            Rp {{ number_format($p['omset'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="white-space: nowrap; font-weight: 600;">
                                            {{ $p['best_branch'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" style="padding: 2.5rem; text-align: center; color: #94a3b8;">Belum ada produk terdaftar dalam listing ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Tab 3: Peringkat Cabang Pilot -->
            @if($active_tab === 'branch_summary')
                <div class="pf-table-card">
                    <div class="pf-table-responsive">
                        <table class="pf-table">
                            <thead>
                                <tr>
                                    <th class="pf-th" style="width: 70px;">Rank</th>
                                    <th class="pf-th">Nama Cabang Pilot</th>
                                    <th class="pf-th">Kota / Wilayah</th>
                                    <th class="pf-th" style="text-align: right;">Total Masuk (Pcs)</th>
                                    <th class="pf-th" style="text-align: right;">Total Terjual (Pcs)</th>
                                    <th class="pf-th" style="text-align: right;">Sisa Stok (Pcs)</th>
                                    <th class="pf-th" style="text-align: center;">Sell-Through</th>
                                    <th class="pf-th" style="text-align: right;">Total Omset Cabang</th>
                                    <th class="pf-th">Produk Paling Laku</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branchesSummary as $index => $b)
                                    <tr class="pf-tr">
                                        <td class="pf-td" style="text-align: center;">
                                            @if($index === 0)
                                                <span class="pf-pill pf-pill-amber" style="padding: 0.2rem 0.55rem; font-size: 0.75rem;">🥇 1</span>
                                            @elseif($index === 1)
                                                <span class="pf-pill pf-pill-blue" style="padding: 0.2rem 0.55rem; font-size: 0.75rem;">🥈 2</span>
                                            @elseif($index === 2)
                                                <span class="pf-pill pf-pill-rose" style="padding: 0.2rem 0.55rem; font-size: 0.75rem;">🥉 3</span>
                                            @else
                                                <span style="font-weight: 700; color: #64748b;">#{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="pf-td" style="font-weight: 800; white-space: nowrap;">
                                            🏪 {{ $b['name'] }}
                                        </td>
                                        <td class="pf-td" style="color: #64748b; white-space: nowrap;">
                                            {{ $b['city'] }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 600;">
                                            {{ number_format($b['qty_received'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #059669;">
                                            {{ number_format($b['qty_sold'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 600;">
                                            {{ number_format($b['current_stock'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: center;">
                                            @php
                                                $stVal = $b['sell_through'];
                                                $stPill = $stVal >= 70 ? 'pf-pill-emerald' : ($stVal >= 40 ? 'pf-pill-amber' : 'pf-pill-rose');
                                            @endphp
                                            <span class="pf-pill {{ $stPill }}" style="font-family: monospace; font-size: 0.75rem;">
                                                {{ $stVal }}%
                                            </span>
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 900; color: #7c3aed; white-space: nowrap;">
                                            Rp {{ number_format($b['omset'], 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="font-weight: 600; white-space: nowrap;">
                                            {{ $b['best_product'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" style="padding: 2.5rem; text-align: center; color: #94a3b8;">Belum ada cabang pilot yang ditetapkan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Tab 4: Transaksi Kasir Terbaru -->
            @if($active_tab === 'recent_transactions')
                <div class="pf-table-card">
                    <div class="pf-table-responsive">
                        <table class="pf-table">
                            <thead>
                                <tr>
                                    <th class="pf-th">Waktu Transaksi</th>
                                    <th class="pf-th">No. Struk / Nota</th>
                                    <th class="pf-th">Cabang</th>
                                    <th class="pf-th">Kasir</th>
                                    <th class="pf-th">Produk Terjual</th>
                                    <th class="pf-th" style="text-align: right;">Harga Satuan</th>
                                    <th class="pf-th" style="text-align: right;">Qty</th>
                                    <th class="pf-th" style="text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $item)
                                    <tr class="pf-tr">
                                        <td class="pf-td" style="font-family: monospace; color: #64748b; white-space: nowrap;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="pf-td" style="font-family: monospace; font-weight: 800; white-space: nowrap;">
                                            {{ $item->transaction?->invoice_number ?? '-' }}
                                        </td>
                                        <td class="pf-td" style="white-space: nowrap; font-weight: 600;">
                                            {{ $item->transaction?->branch?->name ?? '-' }}
                                        </td>
                                        <td class="pf-td" style="color: #64748b; white-space: nowrap;">
                                            {{ $item->transaction?->cashier?->name ?? '-' }}
                                        </td>
                                        <td class="pf-td">
                                            <div style="font-weight: 700;">{{ $item->product?->name ?? '-' }}</div>
                                            <div style="font-size: 0.7rem; color: #64748b; font-family: monospace;">{{ $item->product?->sku ?? '' }}</div>
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; color: #64748b; white-space: nowrap;">
                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 800; color: #059669;">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                        </td>
                                        <td class="pf-td" style="text-align: right; font-family: monospace; font-weight: 900; color: #7c3aed; white-space: nowrap;">
                                            Rp {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: #94a3b8;">
                                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🧾</div>
                                            <div style="font-weight: 700; color: #64748b;">Belum ada transaksi kasir untuk produk dalam listing ini</div>
                                            <div style="font-size: 0.75rem; margin-top: 0.25rem;">Transaksi penjualan di cabang pilot akan otomatis tercatat di sini secara real-time.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Tab 5: Ketentuan Kontrak & Evaluasi -->
            @if($active_tab === 'contract_details')
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                    <!-- Left: Detail Kontrak -->
                    <div class="pf-toolbar-card" style="flex-direction: column; align-items: stretch;">
                        <h4 style="font-size: 0.9rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>📑</span>
                            <span>Ketentuan Perjanjian Listing</span>
                        </h4>

                        <div class="pf-dl">
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Nomor Perjanjian</span>
                                <span class="pf-dl-dd" style="font-family: monospace;">{{ $listing->listing_number }}</span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Pemasok (Supplier)</span>
                                <span class="pf-dl-dd">{{ $listing->supplier?->name ?? '-' }}</span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Divisi Pemasok</span>
                                <span class="pf-dl-dd">{{ $listing->supplierDivision?->name ?? 'Semua Divisi' }}</span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Tipe Kerjasama</span>
                                <span class="pf-pill {{ $listing->supplier?->is_consignment ? 'pf-pill-amber' : 'pf-pill-blue' }}">
                                    {{ $listing->supplier?->is_consignment ? 'Konsinyasi (Titip Jual)' : 'Beli Putus' }}
                                </span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Tanggal Mulai Uji Coba</span>
                                <span class="pf-dl-dd" style="font-family: monospace;">{{ $listing->trial_start_date ? \Carbon\Carbon::parse($listing->trial_start_date)->format('d F Y') : '-' }}</span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Tanggal Jatuh Tempo</span>
                                <span class="pf-dl-dd" style="font-family: monospace;">{{ $listing->trial_end_date ? \Carbon\Carbon::parse($listing->trial_end_date)->format('d F Y') : '-' }}</span>
                            </div>
                            <div class="pf-dl-row">
                                <span class="pf-dl-dt">Status Saat Ini</span>
                                <div>
                                    @if($listing->status === 'TRIAL')
                                        <span class="pf-pill pf-pill-amber">MASA UJI COBA (TRIAL)</span>
                                    @elseif($listing->status === 'PASSED')
                                        <span class="pf-pill pf-pill-emerald">LULUS (PASSED - ROLLOUT)</span>
                                    @else
                                        <span class="pf-pill pf-pill-rose">DIHENTIKAN (DELISTED)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Biaya Listing & Status Potongan -->
                    <div class="pf-toolbar-card" style="flex-direction: column; align-items: stretch;">
                        <h4 style="font-size: 0.9rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>💰</span>
                            <span>Biaya Listing & Potongan Kontrabon</span>
                        </h4>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.85rem; padding: 1rem 1.25rem;">
                            <div style="font-size: 0.75rem; color: #64748b;">Nilai Biaya Listing Disepakati</div>
                            <div style="font-size: 1.65rem; font-weight: 900; color: #0f172a; margin-top: 0.25rem;">
                                Rp {{ number_format($listing->listing_fee, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.35rem;">
                                {{ $listing->listing_fee > 0 ? 'Dipotong otomatis dari pembayaran kontrabon pertama.' : 'Bebas Biaya Listing (Rp 0)' }}
                            </div>
                        </div>

                        @php $ded = $listing->listingDeduction; @endphp
                        @if($ded)
                            <div class="pf-dl" style="margin-top: 0.75rem;">
                                <div class="pf-dl-row">
                                    <span class="pf-dl-dt">Status Pemotongan</span>
                                    <span class="pf-pill {{ $ded->status === 'COMPLETED' ? 'pf-pill-emerald' : 'pf-pill-amber' }}">
                                        {{ $ded->status }}
                                    </span>
                                </div>
                                <div class="pf-dl-row">
                                    <span class="pf-dl-dt">Nominal Potongan</span>
                                    <span class="pf-dl-dd" style="font-family: monospace;">Rp {{ number_format($ded->amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endif

                        <div style="margin-top: 1rem;">
                            <label style="font-size: 0.75rem; font-weight: 800; color: #64748b; display: block; margin-bottom: 0.35rem;">
                                Catatan & Riwayat Evaluasi:
                            </label>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.85rem 1rem; font-size: 0.78rem; font-family: monospace; min-height: 80px; white-space: pre-line; color: #334155;">
                                {{ $listing->notes ?: 'Belum ada catatan evaluasi khusus.' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
