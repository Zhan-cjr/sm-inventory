<x-filament-panels::page>
    <style>
        /* Premium Custom Cards & Grid for Stock Opname Dashboard */
        .opname-grid-3 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 640px) {
            .opname-grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .opname-grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .opname-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 1024px) {
            .opname-grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .opname-card {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .opname-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            border-color: #4f46e5;
        }
        .dark .opname-card {
            border-color: #1e293b;
            background-color: #0f172a;
        }
        .dark .opname-card:hover {
            border-color: #6366f1;
        }

        .opname-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .opname-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            flex-shrink: 0;
        }
        
        .opname-icon-indigo { background-color: #eff6ff; color: #3b82f6; }
        .dark .opname-icon-indigo { background-color: rgba(30, 41, 59, 0.5); color: #60a5fa; }
        
        .opname-icon-blue { background-color: #f0fdf4; color: #10b981; }
        .dark .opname-icon-blue { background-color: rgba(6, 78, 59, 0.2); color: #34d399; }
        
        .opname-icon-amber { background-color: #fffbeb; color: #f59e0b; }
        .dark .opname-icon-amber { background-color: rgba(120, 53, 4, 0.2); color: #fbbf24; }

        .opname-card-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin: 0;
        }
        .opname-card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.25rem;
        }
        .dark .opname-card-value {
            color: #ffffff;
        }

        .opname-container {
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .opname-container {
            border-color: #1e293b;
            background-color: #0f172a;
        }

        .opname-progress-row {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        @media (min-width: 768px) {
            .opname-progress-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .opname-progress-card {
            padding: 1.25rem;
            border-radius: 1rem;
            border: 1px solid #f1f5f9;
        }
        .dark .opname-progress-card {
            border-color: #1e293b;
        }
        .opname-progress-card.sky { background-color: rgba(240, 249, 255, 0.5); border-color: #e0f2fe; }
        .dark .opname-progress-card.sky { background-color: rgba(8, 47, 73, 0.1); border-color: rgba(14, 165, 233, 0.15); }
        .opname-progress-card.amber { background-color: rgba(255, 251, 235, 0.5); border-color: #fef3c7; }
        .dark .opname-progress-card.amber { background-color: rgba(120, 53, 4, 0.05); border-color: rgba(245, 158, 11, 0.15); }
        .opname-progress-card.rose { background-color: rgba(255, 241, 242, 0.5); border-color: #ffe4e6; }
        .dark .opname-progress-card.rose { background-color: rgba(136, 19, 55, 0.05); border-color: rgba(244, 63, 94, 0.15); }
        .opname-progress-card.emerald { background-color: rgba(240, 253, 244, 0.5); border-color: #dcfce7; }
        .dark .opname-progress-card.emerald { background-color: rgba(6, 78, 59, 0.05); border-color: rgba(16, 185, 129, 0.15); }

        .opname-progress-bar-bg {
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 9999px;
            height: 0.625rem;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .dark .opname-progress-bar-bg {
            background-color: #334155;
        }
        .opname-progress-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.5s ease;
        }
        .opname-progress-bar-fill.sky { background-color: #0ea5e9; }
        .opname-progress-bar-fill.amber { background-color: #f59e0b; }

        /* Custom badges */
        .opname-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
        }
        .opname-badge-success { background-color: #d1fae5; color: #065f46; }
        .dark .opname-badge-success { background-color: rgba(6, 78, 59, 0.4); color: #34d399; }
        .opname-badge-warning { background-color: #fef3c7; color: #92400e; }
        .dark .opname-badge-warning { background-color: rgba(120, 53, 4, 0.4); color: #fbbf24; }
        .opname-badge-danger { background-color: #fee2e2; color: #991b1b; }
        .dark .opname-badge-danger { background-color: rgba(153, 27, 27, 0.4); color: #f87171; }
        .opname-badge-info { background-color: #e0f2fe; color: #0369a1; }
        .dark .opname-badge-info { background-color: rgba(3, 105, 161, 0.4); color: #38bdf8; }

        /* Rack cards & listings */
        .opname-rack-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-top: 1.25rem;
        }
        @media (min-width: 640px) {
            .opname-rack-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .opname-rack-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .opname-rack-card {
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s;
        }
        .opname-rack-card:hover {
            border-color: #6366f1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dark .opname-rack-card {
            border-color: #1e293b;
            background-color: #0f172a;
        }
        .dark .opname-rack-card:hover {
            border-color: #818cf8;
        }

        /* Scroll container for P1 QR list */
        .opname-scroll-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 20rem;
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        .opname-scroll-item {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
        }
        .dark .opname-scroll-item {
            background-color: #020617;
            border-color: #1e293b;
        }
        @media (min-width: 640px) {
            .opname-scroll-item {
                flex-direction: row;
                align-items: center;
            }
        }

        /* Drawer elements */
        .opname-drawer-backdrop {
            position: absolute;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
        .opname-drawer-container {
            position: fixed;
            top: 0;
            bottom: 0;
            right: 0;
            display: flex;
            max-width: 100%;
            padding-left: 2.5rem;
            pointer-events: none;
            z-index: 999;
        }
        .opname-drawer-content {
            pointer-events: auto;
            width: 100vw;
            max-width: 42rem;
            height: 100%;
            background-color: #ffffff;
            box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.1), -10px 0 10px -5px rgba(0, 0, 0, 0.04);
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .dark .opname-drawer-content {
            background-color: #0f172a;
            border-color: #1e293b;
            box-shadow: -10px 0 25px -5px rgba(0, 0, 0, 0.5);
        }
        .opname-drawer-header {
            padding: 1.25rem 1.5rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .opname-drawer-header {
            background-color: #020617;
            border-color: #1e293b;
        }
        .opname-drawer-body {
            flex: 1 1 0%;
            padding: 1.5rem;
            overflow-y: auto;
        }
        .opname-drawer-footer {
            padding: 1rem 1.5rem;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
        }
        .dark .opname-drawer-footer {
            background-color: #020617;
            border-color: #1e293b;
        }

        /* Flex & typography utilities */
        .opname-flex-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .opname-flex-gap-3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .opname-text-title {
            font-weight: 750;
            color: #0f172a;
        }
        .dark .opname-text-title {
            color: #f8fafc;
        }
        .opname-text-muted {
            color: #64748b;
        }
        .dark .opname-text-muted {
            color: #94a3b8;
        }
    </style>

    <div class="space-y-6">

        {{-- 3D/Glassmorphic Header Stats Cards --}}
        <div class="opname-grid-3 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- No Sesi -->
            <div class="opname-card relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                <div class="opname-card-header flex items-center justify-between">
                    <div>
                        <p class="opname-card-title text-xs font-semibold tracking-wider text-gray-400 uppercase">Nomor Sesi</p>
                        <p class="opname-card-value mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $this->record->session_number }}</p>
                    </div>
                    <div class="opname-icon-wrapper opname-icon-indigo rounded-xl bg-indigo-50 dark:bg-indigo-950/50 p-3 text-indigo-600 dark:text-indigo-400">
                        <svg width="24" height="24" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Cabang -->
            <div class="opname-card relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                <div class="opname-card-header flex items-center justify-between">
                    <div>
                        <p class="opname-card-title text-xs font-semibold tracking-wider text-gray-400 uppercase">Cabang</p>
                        <p class="opname-card-value mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $this->record->branch?->name }}</p>
                    </div>
                    <div class="opname-icon-wrapper opname-icon-blue rounded-xl bg-blue-50 dark:bg-blue-950/50 p-3 text-blue-600 dark:text-blue-400">
                        <svg width="24" height="24" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tanggal Opname -->
            <div class="opname-card relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm transition-all duration-300 hover:shadow-md sm:col-span-2 lg:col-span-1">
                <div class="opname-card-header flex items-center justify-between">
                    <div>
                        <p class="opname-card-title text-xs font-semibold tracking-wider text-gray-400 uppercase">Tanggal Opname</p>
                        <p class="opname-card-value mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $this->record->opname_date?->format('d M Y') }}</p>
                    </div>
                    <div class="opname-icon-wrapper opname-icon-amber rounded-xl bg-amber-50 dark:bg-amber-950/50 p-3 text-amber-600 dark:text-amber-400">
                        <svg width="24" height="24" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Sesi & Quick Stats --}}
        <div class="opname-container rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="opname-flex-between flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="opname-text-title text-lg font-bold text-gray-900 dark:text-white">Progress Real-time</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pemantauan laju hitung dan cek barang</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full tracking-wide shadow-sm
                    @if($this->record->status === 'COMPLETED') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 ring-1 ring-emerald-600/20
                    @elseif($this->record->status === 'FINAL_CHECK') bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 ring-1 ring-rose-600/20
                    @elseif($this->record->status === 'CHECKING') bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 ring-1 ring-amber-600/20
                    @elseif($this->record->status === 'COUNTING') bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-400 ring-1 ring-sky-600/20
                    @else bg-gray-50 text-gray-700 dark:bg-gray-850 dark:text-gray-400 ring-1 ring-gray-600/20 @endif">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5
                        @if($this->record->status === 'COMPLETED') bg-emerald-500
                        @elseif($this->record->status === 'FINAL_CHECK') bg-rose-500
                        @elseif($this->record->status === 'CHECKING') bg-amber-500
                        @elseif($this->record->status === 'COUNTING') bg-sky-500
                        @else bg-gray-400 @endif"></span>
                    {{ match($this->record->status) {
                        'DRAFT'       => 'Draft',
                        'COUNTING'    => 'Sedang Dihitung',
                        'CHECKING'    => 'Sedang Dicek',
                        'FINAL_CHECK' => 'Final Check SPV',
                        'COMPLETED'   => 'Selesai',
                        default       => $this->record->status
                     } }}
                </span>
            </div>

            @php
                $c1Progress = $this->record->count1_progress;
                $c2Progress = $this->record->count2_progress;
                $discCount  = $this->record->discrepancy_count;
                
                $c1Percent = $c1Progress['total'] > 0 ? round(($c1Progress['done'] / $c1Progress['total']) * 100) : 0;
                $c2Percent = $c2Progress['total'] > 0 ? round(($c2Progress['done'] / $c2Progress['total']) * 100) : 0;
            @endphp

            <div class="opname-progress-row grid grid-cols-1 gap-5 md:grid-cols-3">
                <!-- Penghitung 1 Progress -->
                <div class="opname-progress-card sky p-5 bg-sky-50/50 dark:bg-sky-950/10 border border-sky-100 dark:border-sky-900/30 rounded-2xl">
                    <div class="opname-flex-between flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-sky-800 dark:text-sky-400">Rak Dihitung (P1)</span>
                        <span class="text-xs font-bold text-sky-700 dark:text-sky-300">{{ $c1Progress['done'] }}/{{ $c1Progress['total'] }} Rak</span>
                    </div>
                    <div class="opname-progress-bar-bg w-full bg-sky-100 dark:bg-sky-950 rounded-full h-2.5 overflow-hidden">
                        <div class="opname-progress-bar-fill sky bg-sky-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $c1Percent }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2">Persentase selesai: {{ $c1Percent }}%</p>
                </div>

                <!-- Pengecek 2 Progress -->
                <div class="opname-progress-card amber p-5 bg-amber-50/50 dark:bg-amber-950/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl">
                    <div class="opname-flex-between flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-amber-800 dark:text-amber-400">Rak Dicek (P2)</span>
                        <span class="text-xs font-bold text-amber-700 dark:text-amber-300">{{ $c2Progress['done'] }}/{{ $c2Progress['total'] }} Rak</span>
                    </div>
                    <div class="opname-progress-bar-bg w-full bg-amber-100 dark:bg-amber-950 rounded-full h-2.5 overflow-hidden">
                        <div class="opname-progress-bar-fill amber bg-amber-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $c2Percent }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2">Persentase selesai: {{ $c2Percent }}%</p>
                </div>

                <!-- Item Selisih -->
                <div class="opname-progress-card rose p-5 bg-rose-50/50 dark:bg-rose-950/10 border border-rose-100 dark:border-rose-900/30 rounded-2xl">
                    <div class="opname-flex-between flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-rose-800 dark:text-rose-400">Item Selisih</span>
                        <span class="px-2 py-0.5 text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400 rounded-full">DISCREPANCY</span>
                    </div>
                    <p class="text-3xl font-extrabold text-rose-600 mt-1">{{ $discCount }}</p>
                    <p class="text-[11px] text-gray-500 mt-2">Perlu verifikasi atau penyesuaian oleh Supervisor</p>
                </div>
            </div>
        </div>

        {{-- QR CODE SECTION --}}
        @if(in_array($this->record->status, ['COUNTING', 'CHECKING', 'FINAL_CHECK', 'COMPLETED']))
        <div class="opname-container rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="opname-flex-between flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="opname-text-title text-lg font-bold text-gray-900 dark:text-white">📱 QR Code Akses Sesi</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Bagikan QR portal atau cetak stiker QR untuk ditempel di fisik rak</p>
                </div>
                <a href="{{ route('opname.print-qr', $this->record->id) }}" target="_blank"
                   class="opname-btn opname-btn-primary">
                    <svg width="16" height="16" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Cetak Semua QR & Rak
                </a>
            </div>

            <div class="opname-grid-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- QR PORTAL SESI (Pengecek ke-2) -->
                <div class="opname-progress-card sky border border-blue-100 dark:border-blue-900/40 rounded-2xl p-5 bg-blue-50/20 dark:bg-blue-950/10">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0 animate-ping"></span>
                        <p class="text-sm font-bold text-blue-800 dark:text-blue-400">QR Portal Sesi (Auditor P2)</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                        <div class="p-3 bg-white border border-blue-100 rounded-2xl shadow-sm flex-shrink-0" style="display: flex; align-items: center; justify-content: center; width: 144px; height: 144px;">
                            <!-- Instant Server-side QR Code Rendering -->
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data={{ urlencode(url('/opname/' . $this->record->session_token)) }}"
                                 alt="QR Code Session"
                                 width="128"
                                 height="128"
                                 class="rounded-lg shadow-sm" />
                        </div>
                        <div class="flex-1 min-w-0 w-full">
                            <p class="text-xs text-gray-500 mb-1.5">URL Portal Sesi:</p>
                            <div class="flex items-center gap-2">
                                <p class="font-mono text-xs bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 px-3 py-2 rounded-xl break-all text-gray-700 dark:text-gray-300 flex-1">
                                    {{ url('/opname/' . $this->record->session_token) }}
                                </p>
                            </div>
                            <div class="mt-4 text-[11px] text-gray-500 space-y-1.5">
                                <p>📌 Bagikan QR ini ke <strong>Pengecek ke-2 (Independent Auditor)</strong></p>
                                <p>🔒 Selama pengecekan, hasil input P1 disembunyikan agar audit objektif.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR PER RAK (Penghitung 1) -->
                <div class="opname-progress-card emerald border border-emerald-100 dark:border-emerald-900/40 rounded-2xl p-5 bg-emerald-50/20 dark:bg-emerald-950/10">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-400">QR per Rak (Penghitung 1)</p>
                    </div>

                    <div class="opname-scroll-container space-y-3.5 max-h-80 overflow-y-auto pr-1">
                        @foreach($this->record->rackSessions()->with('rack')->get() as $rs)
                        <div class="opname-scroll-item flex flex-col sm:flex-row sm:items-center gap-4 bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                            <!-- Instant Server-side QR Code Rendering -->
                            <div class="flex-shrink-0 p-1.5 bg-white border border-gray-100 rounded-xl" style="display: flex; align-items: center; justify-content: center; width: 72px; height: 72px;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=64x64&data={{ urlencode(url('/opname/hitung/' . $rs->rack_token)) }}"
                                     alt="QR Code {{ $rs->rack?->rack_code }}"
                                     width="64"
                                     height="64"
                                     class="rounded-lg" />
                            </div>
                            <!-- Info Rak -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono font-bold text-sm text-gray-950 dark:text-white">{{ $rs->rack?->rack_code }}</span>
                                    @if($rs->count1_status === 'DONE')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">P1 Selesai</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">P1 Belum</span>
                                    @endif
                                    @if($rs->count2_status === 'DONE')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">P2 Selesai</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ $rs->rack?->rack_name }}</p>
                            </div>
                            <!-- Aksi -->
                            <div class="flex sm:flex-col gap-2 flex-shrink-0 w-full sm:w-auto">
                                <a href="{{ url('/opname/hitung/' . $rs->rack_token) }}" target="_blank"
                                   class="opname-btn opname-btn-xs opname-btn-info flex-1 text-center">
                                    Buka Portal
                                </a>
                                <a href="{{ route('opname.print-qr-single', $rs->id) }}" target="_blank"
                                   class="opname-btn opname-btn-xs opname-btn-outline flex-1 text-center">
                                    Cetak Sticker
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Daftar Rak --}}
        <div class="opname-container rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <h3 class="opname-text-title text-lg font-bold text-gray-900 dark:text-white mb-1">Daftar Rak dalam Sesi</h3>
            <p class="text-xs text-gray-500 mb-6">Klik tombol detail pada masing-masing rak untuk melihat tabel item dan kuantitas hasil opname.</p>

            <div x-data="{ activeRack: null }" class="relative">
                <div class="opname-rack-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($this->record->rackSessions()->with('rack')->get() as $rs)
                    @php
                        $isCountingActive = $rs->active_count_at && $rs->active_count_at->gt(now()->subMinutes(5)) && $rs->count1_status !== 'DONE';
                        $isCheckingActive = $rs->active_check_at && $rs->active_check_at->gt(now()->subMinutes(5)) && $rs->count2_status !== 'DONE';
                        $items = $rs->items()->with('product.category')->get()->sortBy('product.name');
                    @endphp
                    <!-- Card Rak -->
                    <div class="opname-rack-card relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/40 p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-800 flex flex-col justify-between">
                        
                        <div>
                            <!-- Header Card -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 flex items-center justify-center border border-gray-100 dark:border-gray-800 text-lg">
                                        📦
                                    </div>
                                    <div>
                                        <p class="font-mono font-bold text-base text-gray-900 dark:text-white leading-tight">{{ $rs->rack?->rack_code }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $rs->rack?->rack_name }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1.5">
                                    @if($isCountingActive)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 ring-1 ring-emerald-600/20 animate-pulse">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span> P1 Hitung
                                    </span>
                                    @endif
                                    @if($isCheckingActive)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 ring-1 ring-purple-600/20 animate-pulse">
                                        <span class="w-1 h-1 rounded-full bg-purple-500"></span> P2 Cek
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Status User Count & Check -->
                            <div class="space-y-2.5 my-4 text-xs">
                                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-900/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
                                    <span class="text-gray-400 font-medium">Penghitung 1 (P1):</span>
                                    @if($rs->count1_status === 'DONE')
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400 text-[11px] truncate max-w-[140px]" title="{{ $rs->count1_by_name }} ({{ $rs->count1_at?->format('H:i') }})">
                                            ✅ {{ $rs->count1_by_name }}
                                        </span>
                                    @else
                                        <span class="font-semibold text-gray-400 dark:text-gray-600 bg-gray-100/50 dark:bg-gray-950 px-2 py-0.5 rounded-md">Belum</span>
                                    @endif
                                </div>

                                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-900/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
                                    <span class="text-gray-400 font-medium">Pengecek 2 (P2):</span>
                                    @if($rs->count2_status === 'DONE')
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 text-[11px] truncate max-w-[140px]" title="{{ $rs->count2_by_name }} ({{ $rs->count2_at?->format('H:i') }})">
                                            ✅ {{ $rs->count2_by_name }}
                                        </span>
                                    @else
                                        <span class="font-semibold text-gray-400 dark:text-gray-600 bg-gray-100/50 dark:bg-gray-950 px-2 py-0.5 rounded-md">Belum</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Footer Card Action -->
                        <div class="mt-2 flex items-center justify-between border-t border-gray-100 dark:border-gray-800 pt-3">
                            <div class="text-left">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Item Barang</span>
                                <p class="text-sm font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $items->count() }}</p>
                            </div>
                            <button type="button" @click="activeRack = '{{ $rs->id }}'"
                                    class="opname-btn opname-btn-sm opname-btn-success">
                                📊 Detail Item & Qty
                            </button>
                        </div>

                    </div>

                    <!-- Slide-over Drawer Component for Rack -->
                    <div x-show="activeRack === '{{ $rs->id }}'" 
                         class="fixed inset-0 z-50 overflow-hidden" 
                         aria-labelledby="modal-title" role="dialog" aria-modal="true"
                         style="display: none;">
                        <div class="absolute inset-0">
                            <!-- Backdrop overlay -->
                            <div @click="activeRack = null"
                                 class="opname-drawer-backdrop absolute inset-0 bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity"></div>

                            <div class="opname-drawer-container pointer-events-none fixed top-0 bottom-0 right-0 flex max-w-full pl-10">
                                <div class="pointer-events-auto w-screen max-w-2xl">
                                    
                                    <div class="opname-drawer-content flex h-full flex-col bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-800">
                                        <!-- Drawer Header -->
                                        <div class="opname-drawer-header px-6 py-5 bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                                            <div>
                                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                                    <span>📦 Rak {{ $rs->rack?->rack_code }}</span>
                                                    <span class="text-sm font-normal text-gray-400">({{ $rs->rack?->rack_name }})</span>
                                                </h2>
                                                <p class="text-xs text-gray-500 mt-1">Sesi: {{ $this->record->session_number }}</p>
                                            </div>
                                            <button type="button" @click="activeRack = null"
                                                    style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:0.5rem;color:#94a3b8;background:transparent;border:none;cursor:pointer;transition:background-color 0.15s,color 0.15s;"
                                                    onmouseover="this.style.backgroundColor='#f1f5f9';this.style.color='#475569'"
                                                    onmouseout="this.style.backgroundColor='transparent';this.style.color='#94a3b8'">
                                                <span class="sr-only">Tutup detail</span>
                                                <svg style="width:1.125rem;height:1.125rem;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Drawer Body -->
                                        <div class="opname-drawer-body flex-1 p-6 space-y-6">
                                            <!-- Info Counters -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-850">
                                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Penghitung 1 (P1)</p>
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1">
                                                        @if($rs->count1_status === 'DONE')
                                                            ✅ {{ $rs->count1_by_name }}
                                                            <span class="block text-xs font-normal text-gray-400 mt-0.5">{{ $rs->count1_at?->format('d M Y, H:i') }}</span>
                                                        @else
                                                            <span class="text-gray-400 italic">Belum dihitung</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-850">
                                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pengecek 2 (P2)</p>
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1">
                                                        @if($rs->count2_status === 'DONE')
                                                            ✅ {{ $rs->count2_by_name }}
                                                            <span class="block text-xs font-normal text-gray-400 mt-0.5">{{ $rs->count2_at?->format('d M Y, H:i') }}</span>
                                                        @else
                                                            <span class="text-gray-400 italic">Belum dicek</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Table of Items -->
                                            <div>
                                                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Daftar Item Barang</h3>
                                                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
                                                    <table class="opname-table w-full text-sm text-left border-collapse">
                                                        <thead>
                                                            <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                                                <th class="py-3 px-4">Nama Produk</th>
                                                                <th class="py-3 px-4 text-right">System Qty</th>
                                                                <th class="py-3 px-4 text-right">Hitung P1</th>
                                                                <th class="py-3 px-4 text-right">Cek P2</th>
                                                                <th class="py-3 px-4 text-right">Selisih</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                                            @forelse($items as $item)
                                                            <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-900/40 transition-colors">
                                                                <td class="py-3 px-4">
                                                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $item->product?->name }}</div>
                                                                    <div class="flex items-center gap-2 mt-0.5 text-[10px] font-mono text-gray-400">
                                                                        <span>SKU: {{ $item->product?->sku }}</span>
                                                                        @if($item->product?->barcode)
                                                                        <span>·</span>
                                                                        <span>Barcode: {{ $item->product?->barcode }}</span>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="py-3 px-4 text-right font-mono text-gray-650 dark:text-gray-400">
                                                                    {{ number_format($item->system_quantity, 0) }}
                                                                </td>
                                                                <td class="py-3 px-4 text-right font-mono font-semibold text-gray-900 dark:text-gray-200">
                                                                    {{ $item->count1_quantity !== null ? number_format($item->count1_quantity, 0) : '-' }}
                                                                </td>
                                                                <td class="py-3 px-4 text-right font-mono font-semibold text-gray-900 dark:text-gray-200">
                                                                    {{ $item->count2_quantity !== null ? number_format($item->count2_quantity, 0) : '-' }}
                                                                </td>
                                                                <td class="py-3 px-4 text-right font-mono font-bold">
                                                                    @if($item->count2_quantity !== null)
                                                                        @php
                                                                            $diff = $item->count2_quantity - $item->count1_quantity;
                                                                        @endphp
                                                                        @if($diff > 0)
                                                                            <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($diff, 0) }}</span>
                                                                        @elseif($diff < 0)
                                                                            <span class="text-rose-600 dark:text-rose-400">{{ number_format($diff, 0) }}</span>
                                                                        @else
                                                                            <span class="text-gray-400">0</span>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-gray-400">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @empty
                                                            <tr>
                                                                <td colspan="5" class="py-6 text-center text-gray-450 italic">
                                                                    Tidak ada produk.
                                                                </td>
                                                            </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Drawer Footer -->
                                        <div class="opname-drawer-footer px-6 py-4 bg-gray-50 dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                                            <button type="button" @click="activeRack = null"
                                                    class="opname-btn opname-btn-sm opname-btn-gray">
                                                Tutup Detail
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Summary Produk (tampil sejak COUNTING untuk memantau progress) --}}
        @if(in_array($this->record->status, ['COUNTING', 'CHECKING', 'FINAL_CHECK', 'COMPLETED']))
        <div class="opname-container rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <h3 class="opname-text-title text-lg font-bold text-gray-900 dark:text-white mb-1">Rekap Produk Lintas Rak</h3>
            <p class="text-xs text-gray-500 mb-6">Akumulasi seluruh hitungan fisik produk di semua rak dibandingkan dengan sistem</p>

            <div class="overflow-x-auto">
                <table class="opname-table w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th>SKU</th>
                            <th>Produk</th>
                            <th class="text-right">Sistem</th>
                            <th class="text-right">Total P1</th>
                            <th class="text-right">Total P2</th>
                            <th class="text-right">Final SPV</th>
                            <th class="text-right">Selisih Final</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        @foreach($this->record->getProductSummary() as $summary)
                        <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-950/40 transition-colors {{ $summary['is_discrepancy'] ? 'bg-rose-50/15 dark:bg-rose-950/5' : '' }}">
                            <td class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $summary['sku'] }}</td>
                            <td class="font-semibold text-gray-900 dark:text-white">{{ $summary['name'] }}</td>
                            <td class="text-right font-mono text-gray-600 dark:text-gray-400">{{ number_format($summary['system_qty'], 0) }}</td>
                            <td class="text-right font-mono text-gray-650 dark:text-gray-450">{{ number_format($summary['total_count1'], 0) }}</td>
                            <td class="text-right font-mono text-gray-650 dark:text-gray-450">{{ number_format($summary['total_count2'], 0) }}</td>
                            <td class="text-right font-mono font-bold text-gray-950 dark:text-white">
                                {{ $summary['total_final'] > 0 ? number_format($summary['total_final'], 0) : '-' }}
                            </td>
                            <td class="text-right font-mono font-extrabold
                                {{ $summary['final_disc'] < 0 ? 'text-rose-600' : ($summary['final_disc'] > 0 ? 'text-emerald-600' : 'text-gray-400') }}">
                                {{ $summary['final_disc'] != 0 ? ($summary['final_disc'] > 0 ? '+' : '') . number_format($summary['final_disc'], 0, ',', '.') : '0' }}
                            </td>
                            <td class="text-center">
                                @if($summary['is_discrepancy'])
                                    <span class="opname-badge opname-badge-danger">Selisih</span>
                                @else
                                    <span class="opname-badge opname-badge-success">Cocok</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
