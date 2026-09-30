<x-filament-panels::page>
<div wire:poll.5s>
    <style>
        .fc-container {
            font-family: inherit;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Hero Banner */
        .fc-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #2563eb 100%);
            border-radius: 1.25rem;
            padding: 1.75rem 2rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .fc-banner::after {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }
        .fc-banner-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .fc-banner-meta {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.4rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .fc-banner-stats {
            display: flex;
            gap: 1rem;
        }
        .fc-stat-box {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(8px);
            border-radius: 0.875rem;
            padding: 0.6rem 1.2rem;
            text-align: center;
            min-width: 90px;
        }
        .fc-stat-val {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .fc-stat-lbl {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.85;
            margin-top: 0.2rem;
        }

        /* QR Portal Card */
        .fc-qr-card {
            background: #ffffff;
            border: 2px solid #e0e7ff;
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }
        .dark .fc-qr-card {
            background: #111827;
            border-color: #312e81;
        }
        @media (max-width: 768px) {
            .fc-qr-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
        .fc-qr-frame {
            background: #ffffff;
            padding: 1rem;
            border-radius: 1rem;
            border: 2px dashed #6366f1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
            width: fit-content;
            margin: 0 auto;
        }
        .fc-qr-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .dark .fc-qr-badge {
            background: #312e81;
            color: #e0e7ff;
            border-color: #4338ca;
        }
        .fc-qr-info h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dark .fc-qr-info h3 { color: #f8fafc; }
        .fc-qr-info p {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        .dark .fc-qr-info p { color: #94a3b8; }
        
        .fc-alert-locked {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            font-size: 0.825rem;
            color: #92400e;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            line-height: 1.5;
        }
        .dark .fc-alert-locked {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .fc-url-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            margin-bottom: 1.25rem;
            font-family: monospace;
            font-size: 0.8rem;
            color: #334155;
            word-break: break-all;
        }
        .dark .fc-url-box {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        .fc-btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .fc-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.6rem 1.2rem;
            border-radius: 0.75rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            border: none;
        }
        .fc-btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .fc-btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .fc-btn-secondary {
            background: #f1f5f9;
            color: #334155 !important;
            border: 1px solid #cbd5e1;
        }
        .dark .fc-btn-secondary {
            background: #1e293b;
            color: #cbd5e1 !important;
            border-color: #475569;
        }
        .fc-btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Progress Card */
        .fc-progress-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.25rem 1.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        .dark .fc-progress-card {
            background: #111827;
            border-color: #1f2937;
        }
        .fc-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .fc-progress-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dark .fc-progress-title { color: #f8fafc; }
        .fc-live-pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .fc-progress-bar-bg {
            height: 10px;
            background: #f1f5f9;
            border-radius: 9999px;
            overflow: hidden;
        }
        .dark .fc-progress-bar-bg { background: #1e293b; }
        .fc-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 9999px;
            transition: width 0.4s ease;
        }

        /* Items Monitoring */
        .fc-product-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.25rem;
        }
        .dark .fc-product-card {
            background: #111827;
            border-color: #1f2937;
        }
        .fc-product-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .dark .fc-product-header {
            background: #1e293b;
            border-color: #334155;
        }
        .fc-product-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }
        .dark .fc-product-name { color: #f8fafc; }
        .fc-product-meta {
            font-size: 0.775rem;
            color: #64748b;
            margin-top: 0.2rem;
            display: flex;
            gap: 0.75rem;
        }
        .dark .fc-product-meta { color: #94a3b8; }
        
        .fc-pill-group {
            display: flex;
            gap: 0.5rem;
        }
        .fc-pill {
            padding: 0.3rem 0.65rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }
        .fc-pill-label { font-size: 0.65rem; opacity: 0.8; text-transform: uppercase; }
        .fc-pill-system { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .fc-pill-p1 { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .fc-pill-p2 { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
        .fc-pill-diff { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .dark .fc-pill-system { background: #1e3a8a; color: #bfdbfe; border-color: #1e40af; }
        .dark .fc-pill-p1 { background: #14532d; color: #bbf7d0; border-color: #166534; }
        .dark .fc-pill-p2 { background: #713f12; color: #fef08a; border-color: #854d0e; }
        .dark .fc-pill-diff { background: #7f1d1d; color: #fecaca; border-color: #991b1b; }

        /* Table inside Product Card */
        .fc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .fc-table th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            background: #fafafa;
            font-size: 0.725rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .fc-table th {
            background: #182234;
            border-color: #334155;
            color: #94a3b8;
        }
        .fc-table td {
            padding: 0.85rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .dark .fc-table td {
            border-color: #1e293b;
            color: #cbd5e1;
        }
        .fc-table tr:last-child td {
            border-bottom: none;
        }

        .fc-badge-status-pending {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .dark .fc-badge-status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.3);
        }
        .fc-badge-status-done {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .dark .fc-badge-status-done {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        /* Bottom Sticky Action Bar */
        .fc-bottom-bar {
            position: sticky;
            bottom: 1rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid #cbd5e1;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            z-index: 30;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .dark .fc-bottom-bar {
            background: rgba(17, 24, 39, 0.95);
            border-color: #374151;
        }
        .fc-btn-complete {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }
        .fc-btn-complete:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
    </style>

    @php
        $session = $this->record;
        $summary = $this->summary;
        $grouped = $this->getDiscrepancyGrouped();
        $finalPortalUrl = $this->finalPortalUrl;
    @endphp

    <div class="fc-container">
        <!-- 1. HEADER BANNER -->
        <div class="fc-banner">
            <div>
                <h1 class="fc-banner-title">
                    <span>⚖️</span> Portal & Monitoring Final Check
                </h1>
                <div class="fc-banner-meta">
                    <span><strong>Sesi:</strong> {{ $session->session_number }}</span>
                    <span><strong>Cabang:</strong> {{ $session->branch?->name }}</span>
                    <span><strong>Tanggal:</strong> {{ $session->opname_date?->format('d M Y') }}</span>
                </div>
            </div>
            <div class="fc-banner-stats">
                <div class="fc-stat-box">
                    <div class="fc-stat-val">{{ $summary['total'] }}</div>
                    <div class="fc-stat-lbl">Item Selisih</div>
                </div>
                <div class="fc-stat-box">
                    <div class="fc-stat-val" style="color: #6ee7b7;">{{ $summary['verified'] }}</div>
                    <div class="fc-stat-lbl">Terverifikasi</div>
                </div>
                <div class="fc-stat-box">
                    <div class="fc-stat-val" style="color: #fde68a;">{{ $summary['pending'] }}</div>
                    <div class="fc-stat-lbl">Menunggu Scan</div>
                </div>
            </div>
        </div>

        <!-- 2. QR PORTAL HERO CARD -->
        <div class="fc-qr-card">
            <div class="fc-qr-frame">
                <span class="fc-qr-badge">📱 SCAN QR DENGAN HP</span>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($finalPortalUrl) }}"
                     alt="QR Portal Pengecek Final"
                     width="200"
                     height="200"
                     style="display: block; border-radius: 8px;" />
                <span style="font-size: 0.7rem; color: #64748b; font-weight: 600;">PORTAL PENGECEK FINAL</span>
            </div>

            <div class="fc-qr-info">
                <h3><span>📷</span> Portal Mobile Pengecek Final (Wajib Scan Fisik)</h3>
                <p>
                    Silakan scan QR code di samping menggunakan smartphone atau perangkat scanner mobile untuk membuka portal Pengecek Final.
                </p>

                <div class="fc-alert-locked">
                    <span style="font-size: 1.1rem; flex-shrink: 0;">🔒</span>
                    <div>
                        <strong>Pengamanan Validasi Fisik Aktif:</strong>
                        Input kuantitas final <em>tidak dapat diketik manual di layar admin ini</em> untuk mencegah manipulasi data tanpa melihat barang. 
                        Pengecek Final <strong>wajib men-scan fisik barcode barang</strong> di lokasi rak melalui portal mobile untuk membuka form input kuantitas.
                    </div>
                </div>

                <div class="fc-url-box">
                    <span style="color: #6366f1;">🔗</span>
                    <span style="flex: 1;" id="portalUrlText">{{ $finalPortalUrl }}</span>
                    <button type="button" 
                            onclick="navigator.clipboard.writeText('{{ $finalPortalUrl }}'); alert('Link portal berhasil disalin!');"
                            style="background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 700; color: #334155;">
                        Salin
                    </button>
                </div>

                <div class="fc-btn-group">
                    <a href="{{ $finalPortalUrl }}" target="_blank" class="fc-btn fc-btn-primary">
                        <span>📱 Buka Portal di Browser Ini (Tab Baru)</span>
                    </a>
                    <a href="{{ route('opname.print-final-check', ['sessionId' => $session->id]) }}" target="_blank" class="fc-btn fc-btn-secondary">
                        <span>🖨 Cetak Lembar Final Check</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. PROGRESS REAL-TIME CARD -->
        <div class="fc-progress-card">
            <div class="fc-progress-header">
                <div class="fc-progress-title">
                    <span class="fc-live-pulse"></span>
                    <span>Monitoring Verifikasi Fisik Lapangan</span>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: normal;">(Auto-update setiap 5 detik)</span>
                </div>
                <div style="font-size: 0.85rem; font-weight: 800; color: {{ $summary['is_complete'] ? '#059669' : '#4f46e5' }};">
                    {{ $summary['verified'] }} / {{ $summary['total'] }} Item Selesai ({{ $summary['percent'] }}%)
                </div>
            </div>
            <div class="fc-progress-bar-bg">
                <div class="fc-progress-bar-fill" style="width: {{ $summary['percent'] }}%;"></div>
            </div>
        </div>

        <!-- 4. DAFTAR PRODUK SELISIH & HASIL VERIFIKASI -->
        <div>
            <h2 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📋</span> Status Item Selisih Per Rak
            </h2>

            @forelse($grouped as $product)
                <div class="fc-product-card">
                    <div class="fc-product-header">
                        <div>
                            <div class="fc-product-name">{{ $product['product_name'] }}</div>
                            <div class="fc-product-meta">
                                <span>SKU: <strong>{{ $product['product_sku'] }}</strong></span>
                                <span>Barcode: <strong>{{ $product['product_barcode'] }}</strong></span>
                            </div>
                        </div>
                        <div class="fc-pill-group">
                            <div class="fc-pill fc-pill-system">
                                <span class="fc-pill-label">Sistem</span>
                                <span>{{ $product['system_qty'] }}</span>
                            </div>
                            <div class="fc-pill fc-pill-p1">
                                <span class="fc-pill-label">Total P1</span>
                                <span>{{ $product['total_count1'] }}</span>
                            </div>
                            <div class="fc-pill fc-pill-p2">
                                <span class="fc-pill-label">Total P2</span>
                                <span>{{ $product['total_count2'] }}</span>
                            </div>
                            <div class="fc-pill fc-pill-diff">
                                <span class="fc-pill-label">Selisih</span>
                                <span>{{ $product['total_count2'] - $product['total_count1'] }}</span>
                            </div>
                        </div>
                    </div>

                    <table class="fc-table">
                        <thead>
                            <tr>
                                <th>Rak</th>
                                <th style="text-align: center;">Hitung P1</th>
                                <th style="text-align: center;">Cek P2</th>
                                <th style="text-align: center;">Selisih</th>
                                <th>Status Verifikasi Fisik (Portal)</th>
                                <th>Kuantitas Final</th>
                                <th>Pemeriksa & Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product['racks'] as $rack)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: #4338ca;">{{ $rack['rack_code'] }}</div>
                                        <div style="font-size: 0.75rem; color: #64748b;">{{ $rack['rack_name'] }}</div>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; color: #166534;">
                                        {{ $rack['count1_quantity'] }}
                                    </td>
                                    <td style="text-align: center; font-weight: 700; color: #854d0e;">
                                        {{ $rack['count2_quantity'] }}
                                    </td>
                                    <td style="text-align: center; font-weight: 700; color: {{ $rack['discrepancy'] != 0 ? '#dc2626' : '#64748b' }};">
                                        {{ $rack['discrepancy'] > 0 ? '+' : '' }}{{ $rack['discrepancy'] }}
                                    </td>
                                    <td>
                                        @if($rack['status'] === 'FINAL_DONE')
                                            <span class="fc-badge-status-done">
                                                <span>✔</span> Terverifikasi Fisik
                                            </span>
                                        @else
                                            <span class="fc-badge-status-pending">
                                                <span>⏳</span> Menunggu Scan Fisik
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rack['status'] === 'FINAL_DONE')
                                            <span style="font-size: 1.1rem; font-weight: 800; color: #059669;">
                                                {{ $rack['final_quantity'] }}
                                            </span>
                                        @else
                                            <span style="color: #94a3b8; font-style: italic;">(Belum di-scan)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rack['status'] === 'FINAL_DONE')
                                            <div style="font-weight: 600; font-size: 0.8rem; color: #1e293b;">
                                                {{ $rack['final_by_name'] ?? 'Pemeriksa' }}
                                                @if($rack['final_at'])
                                                    <span style="font-size: 0.7rem; color: #64748b; font-weight: normal;">
                                                        • {{ \Carbon\Carbon::parse($rack['final_at'])->format('H:i') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($rack['final_notes'])
                                                <div style="font-size: 0.75rem; color: #475569; margin-top: 2px;">
                                                    "{{ $rack['final_notes'] }}"
                                                </div>
                                            @endif
                                        @else
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 1rem; border: 1px dashed #cbd5e1; color: #64748b;">
                    <p style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Tidak ada item selisih!</p>
                    <p style="font-size: 0.85rem;">Hasil hitung pertama dan kedua cocok untuk semua item.</p>
                </div>
            @endforelse
        </div>

        <!-- 5. BOTTOM ACTION BAR -->
        <div class="fc-bottom-bar">
            <div>
                <a href="{{ \App\Filament\Resources\StockOpname\StockOpnameSessionResource::getUrl('view', ['record' => $session]) }}" 
                   class="fc-btn fc-btn-secondary">
                    <span>⬅ Kembali ke Sesi Opname</span>
                </a>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                @if($summary['is_complete'])
                    <button type="button" 
                            wire:click="finalizeSession" 
                            wire:confirm="Semua item telah terverifikasi via scan fisik. Simpan dan selesaikan sesi stok opname sekarang?"
                            class="fc-btn fc-btn-complete">
                        <span>✅ Selesaikan Stok Opname Sekarang</span>
                    </button>
                @else
                    <div style="font-size: 0.825rem; color: #b45309; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                        <span>⚠️</span>
                        <span>Masih ada {{ $summary['pending'] }} item yang menunggu scan fisik di portal mobile.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
