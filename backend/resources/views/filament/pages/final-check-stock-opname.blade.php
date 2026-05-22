<x-filament-panels::page>
<style>
    /* ===== Final Check Page Styles ===== */
    .fc-page-wrapper {
        font-family: 'Inter', sans-serif;
        max-width: 1100px;
        margin: 0 auto;
        padding-bottom: 2rem;
    }

    /* Header Banner */
    .fc-header {
        background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 50%, #2563eb 100%);
        border-radius: 1.25rem;
        padding: 1.75rem 2rem;
        color: white;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
        position: relative;
        overflow: hidden;
    }
    .fc-header::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 160px; height: 160px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
    }
    .fc-header::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 100px;
        width: 120px; height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .fc-header-meta { font-size: 0.85rem; opacity: 0.85; margin-top: 0.35rem; }
    .fc-header-badge {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 9999px;
        padding: 0.35rem 1rem;
        font-size: 0.8rem;
        font-weight: 700;
        white-space: nowrap;
        backdrop-filter: blur(4px);
    }
    .fc-header-stats {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    .fc-stat {
        text-align: center;
    }
    .fc-stat-val { font-size: 1.75rem; font-weight: 800; line-height: 1; }
    .fc-stat-label { font-size: 0.7rem; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.2rem; }

    /* Instruction Box */
    .fc-instruction {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1px solid #f59e0b;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .dark .fc-instruction {
        background: linear-gradient(135deg, rgba(120,53,15,0.25), rgba(120,53,15,0.15));
        border-color: rgba(245,158,11,0.4);
    }
    .fc-instruction-icon { font-size: 1.25rem; flex-shrink: 0; margin-top: 0.1rem; }
    .fc-instruction-text { font-size: 0.875rem; color: #78350f; line-height: 1.6; }
    .dark .fc-instruction-text { color: #fcd34d; }

    /* Product Card */
    .fc-product-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        margin-bottom: 1.5rem;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }
    .dark .fc-product-card {
        background: #0f172a;
        border-color: #1e293b;
    }

    /* Product Card Header */
    .fc-product-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        gap: 1rem;
    }
    .dark .fc-product-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border-color: #334155;
    }
    .fc-product-name { font-size: 1.0625rem; font-weight: 800; color: #0f172a; }
    .dark .fc-product-name { color: #f8fafc; }
    .fc-product-sku { font-size: 0.75rem; color: #64748b; font-family: monospace; margin-top: 0.2rem; }

    /* Summary Pills */
    .fc-summary-pills {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .fc-pill {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.5rem 0.875rem;
        border-radius: 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        min-width: 68px;
    }
    .fc-pill-val { font-size: 1.25rem; font-weight: 800; line-height: 1.1; }
    .fc-pill-sistem   { background: #eff6ff; color: #1d4ed8; }
    .fc-pill-p1       { background: #f0fdf4; color: #16a34a; }
    .fc-pill-p2       { background: #fef3c7; color: #d97706; }
    .fc-pill-selisih  { background: #fff1f2; color: #be123c; }
    .dark .fc-pill-sistem  { background: rgba(37,99,235,0.15); color: #93c5fd; }
    .dark .fc-pill-p1      { background: rgba(22,163,74,0.15); color: #86efac; }
    .dark .fc-pill-p2      { background: rgba(217,119,6,0.15); color: #fcd34d; }
    .dark .fc-pill-selisih { background: rgba(190,18,60,0.15); color: #fda4af; }

    /* Items Table */
    .fc-table-wrap { overflow-x: auto; }
    .fc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .fc-table thead tr {
        background: #f8fafc;
        border-bottom: 2px solid #cbd5e1;
    }
    .dark .fc-table thead tr {
        background: #1e293b;
        border-color: #334155;
    }
    .fc-table th {
        padding: 0.75rem 1rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        white-space: nowrap;
    }
    .fc-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .dark .fc-table td {
        border-color: #1e293b;
    }
    .fc-table tbody tr:last-child td { border-bottom: none; }
    .fc-table tbody tr:hover { background: rgba(79,70,229,0.025); }
    .dark .fc-table tbody tr:hover { background: rgba(79,70,229,0.06); }

    /* Input Final Qty */
    .fc-input-qty {
        width: 120px;
        padding: 0.5rem 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.625rem;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: right;
        color: #0f172a;
        background: white;
        transition: border-color 0.15s, box-shadow 0.15s;
        -moz-appearance: textfield;
    }
    .fc-input-qty:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    .dark .fc-input-qty {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    .fc-input-note {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.625rem;
        font-size: 0.8rem;
        color: #475569;
        background: white;
        transition: border-color 0.15s;
    }
    .fc-input-note:focus {
        outline: none;
        border-color: #6366f1;
    }
    .dark .fc-input-note {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }

    /* Action Bar */
    .fc-action-bar {
        position: sticky;
        bottom: 1rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.1);
        z-index: 20;
        flex-wrap: wrap;
    }
    .dark .fc-action-bar {
        background: #0f172a;
        border-color: #1e293b;
    }

    .fc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.625rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 700;
        border-radius: 0.75rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .fc-btn-primary {
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
        box-shadow: 0 4px 12px rgba(5,150,105,0.3);
    }
    .fc-btn-primary:hover { background: linear-gradient(135deg, #047857, #059669); transform: translateY(-1px); }
    .fc-btn-secondary {
        background: #f1f5f9;
        color: #334155;
        border: 1.5px solid #e2e8f0;
    }
    .dark .fc-btn-secondary {
        background: #1e293b;
        color: #e2e8f0;
        border-color: #334155;
    }
    .fc-btn-secondary:hover { background: #e2e8f0; }
    .dark .fc-btn-secondary:hover { background: #334155; }
    .fc-btn-print {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(14,165,233,0.3);
    }
    .fc-btn-print:hover { background: linear-gradient(135deg, #0284c7, #1d4ed8); transform: translateY(-1px); }

    /* Empty State */
    .fc-empty {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 1.25rem;
        border: 1px dashed #cbd5e1;
    }
    .dark .fc-empty {
        background: #0f172a;
        border-color: #334155;
    }

    /* Print Styles */
    @media print {
        .fc-action-bar, .fc-no-print { display: none !important; }
        .fc-page-wrapper { max-width: 100%; }
        .fc-product-card { page-break-inside: avoid; }
        .fc-header { background: #4f46e5 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .fc-input-qty, .fc-input-note { border: 1px solid #ccc !important; }
    }
</style>

<div class="fc-page-wrapper">

    @php
        $groups      = $this->getDiscrepancyGrouped();
        $totalItems  = collect($groups)->sum(fn($g) => count($g['racks']));
        $totalProds  = count($groups);
        $session     = $this->record;
    @endphp

    {{-- ===== Header Banner ===== --}}
    <div class="fc-header">
        <div>
            <div style="font-size:0.75rem;opacity:0.7;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Final Check SPV — Stock Opname</div>
            <h1 style="font-size:1.5rem;font-weight:900;margin:0;line-height:1.2;">{{ $session->session_number }}</h1>
            <p class="fc-header-meta">
                🏢 {{ $session->branch?->name }} &nbsp;|&nbsp; 📅 {{ $session->opname_date?->format('d M Y') }}
            </p>
            <div class="fc-header-stats">
                <div class="fc-stat">
                    <div class="fc-stat-val">{{ $totalProds }}</div>
                    <div class="fc-stat-label">Produk Selisih</div>
                </div>
                <div class="fc-stat">
                    <div class="fc-stat-val">{{ $totalItems }}</div>
                    <div class="fc-stat-label">Item di Rak</div>
                </div>
            </div>
        </div>
        <div>
            <span class="fc-header-badge">⚠️ Menunggu Verifikasi SPV</span>
        </div>
    </div>

    {{-- ===== Instruction Box ===== --}}
    <div class="fc-instruction">
        <span class="fc-instruction-icon">📋</span>
        <div class="fc-instruction-text">
            <strong>Instruksi SPV:</strong> Di bawah ini adalah produk yang ditemukan <strong>selisih</strong> antara Penghitung 1 dan Pengecek 2.
            Masukkan <strong>Final Qty (SPV)</strong> — jumlah fisik aktual hasil pengecekan ulang Anda per rak.
            Setelah semua diisi, klik <strong>"Simpan Final Check"</strong>.
        </div>
    </div>

    {{-- ===== Discrepancy Groups ===== --}}
    @if(count($groups) === 0)
        <div class="fc-empty">
            <div style="font-size:2.5rem;margin-bottom:0.75rem;">✅</div>
            <h3 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">Tidak Ada Selisih</h3>
            <p style="color:#64748b;font-size:0.875rem;">Semua item antara Penghitung 1 dan Pengecek 2 sudah sesuai.</p>
            <a href="{{ \App\Filament\Resources\StockOpname\StockOpnameSessionResource::getUrl('view', ['record' => $session]) }}"
               class="fc-btn fc-btn-secondary" style="margin-top:1.25rem;display:inline-flex;">← Kembali ke Sesi</a>
        </div>
    @else
        @foreach($groups as $group)
        @php $selisihP1P2 = $group['total_count2'] - $group['total_count1']; @endphp
        <div class="fc-product-card">

            {{-- Product Header --}}
            <div class="fc-product-header">
                <div>
                    <div class="fc-product-name">{{ $group['product_name'] }}</div>
                    <div class="fc-product-sku">SKU: {{ $group['product_sku'] }}</div>
                </div>
                <div class="fc-summary-pills">
                    <div class="fc-pill fc-pill-sistem">
                        <span class="fc-pill-val">{{ number_format($group['system_qty'], 0) }}</span>
                        <span>Sistem</span>
                    </div>
                    <div class="fc-pill fc-pill-p1">
                        <span class="fc-pill-val">{{ number_format($group['total_count1'], 0) }}</span>
                        <span>Total P1</span>
                    </div>
                    <div class="fc-pill fc-pill-p2">
                        <span class="fc-pill-val">{{ number_format($group['total_count2'], 0) }}</span>
                        <span>Total P2</span>
                    </div>
                    <div class="fc-pill fc-pill-selisih">
                        <span class="fc-pill-val">{{ $selisihP1P2 > 0 ? '+' : '' }}{{ number_format($selisihP1P2, 0) }}</span>
                        <span>Selisih P1↔P2</span>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="fc-table-wrap">
                <table class="fc-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Rak</th>
                            <th style="text-align:right;">Hitung P1</th>
                            <th style="text-align:right;">Cek P2</th>
                            <th style="text-align:right;">Selisih P1↔P2</th>
                            <th style="text-align:right;">Final Qty (SPV) *</th>
                            <th style="text-align:left;min-width:180px;">Catatan (Opsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['racks'] as $rack)
                        @php $disc = $rack['discrepancy'] ?? ($rack['count2_quantity'] - $rack['count1_quantity']); @endphp
                        <tr>
                            <td>
                                <span style="font-weight:800;font-size:0.8rem;color:#4f46e5;font-family:monospace;">{{ $rack['rack_code'] }}</span>
                                @if(!empty($rack['rack_name']))
                                <br><span style="font-size:0.75rem;color:#94a3b8;">{{ $rack['rack_name'] }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:700;color:#16a34a;">{{ number_format($rack['count1_quantity'], 0) }}</td>
                            <td style="text-align:right;font-weight:700;color:#d97706;">{{ number_format($rack['count2_quantity'], 0) }}</td>
                            <td style="text-align:right;font-weight:800;{{ $disc != 0 ? 'color:#be123c;' : 'color:#94a3b8;' }}">
                                {{ $disc > 0 ? '+' : '' }}{{ number_format($disc, 0) }}
                            </td>
                            <td style="text-align:right;">
                                <input
                                    type="number"
                                    class="fc-input-qty"
                                    wire:model.defer="finalQuantities.{{ $rack['item_id'] }}"
                                    placeholder="{{ number_format($rack['count2_quantity'], 0) }}"
                                    min="0"
                                    step="1"
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="fc-input-note"
                                    wire:model.defer="finalNotes.{{ $rack['item_id'] }}"
                                    placeholder="Opsional..."
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
        @endforeach

        {{-- ===== Sticky Action Bar ===== --}}
        <div class="fc-action-bar fc-no-print">
            <div style="font-size:0.8rem;color:#64748b;">
                <strong style="color:#0f172a;" class="dark:text-white">{{ $totalProds }} produk</strong> dengan selisih perlu diverifikasi
            </div>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
                <a href="{{ route('opname.print-final-check', ['sessionId' => $session->id]) }}"
                   target="_blank"
                   class="fc-btn fc-btn-print">
                    🖨️ Cetak Laporan
                </a>
                <a href="{{ \App\Filament\Resources\StockOpname\StockOpnameSessionResource::getUrl('view', ['record' => $session]) }}"
                   class="fc-btn fc-btn-secondary">
                    ← Kembali
                </a>
                <button type="button" wire:click="saveFinalCheck" class="fc-btn fc-btn-primary">
                    ✅ Simpan Final Check
                </button>
            </div>
        </div>

        {{-- Print Header (only visible when printing) --}}
        <div style="display:none;" id="print-footer">
            <p style="font-size:0.75rem;color:#64748b;text-align:center;margin-top:2rem;">
                Dicetak dari SM Inventory — {{ $session->session_number }} — {{ now()->format('d M Y H:i') }}
            </p>
        </div>

    @endif
</div>
</x-filament-panels::page>
