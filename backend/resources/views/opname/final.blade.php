<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pengecek Final — Verifikasi Selisih | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #060b18; --surface: #0e1726; --card: #111b2e;
            --border: rgba(255,255,255,.07); --accent: #ef4444; --accent-glow: rgba(239,68,68,.15);
            --text: #f1f5f9; --muted: #64748b; --faint: #1e293b;
            --green: #10b981; --amber: #f59e0b; --blue: #3b82f6;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg); color: var(--text); min-height: 100vh;
        }

        /* ─── Sticky Header ─── */
        .header {
            background: linear-gradient(135deg, #7f1d1d, #991b1b, #b91c1c);
            padding: 14px 18px; position: sticky; top: 0; z-index: 20;
            box-shadow: 0 4px 20px rgba(0,0,0,.5);
        }
        .header-inner { max-width: 820px; margin: 0 auto; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .hbadge {
            background: rgba(255,255,255,.2); border-radius: 20px;
            padding: 3px 12px; font-size: 11px; font-weight: 800; letter-spacing: .08em;
            text-transform: uppercase; flex-shrink: 0; color: white;
        }
        .header-titles h1 { font-size: 17px; font-weight: 800; color: white; }
        .header-titles .meta { font-size: 12px; color: rgba(255,255,255,.8); margin-top: 2px; }
        .scan-btn-header {
            margin-left: auto; flex-shrink: 0;
            display: flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
            color: white; border-radius: 12px; padding: 8px 16px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            transition: background .2s; text-decoration: none;
        }
        .scan-btn-header:hover { background: rgba(255,255,255,.3); }

        /* ─── Container ─── */
        .container { max-width: 820px; margin: 0 auto; padding: 20px 16px 120px; }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; color: #94a3b8; text-decoration: none; margin-bottom: 16px;
        }
        .back-link:hover { color: #f1f5f9; }

        /* ─── Alert ─── */
        .alert-instruction {
            background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25);
            border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #fca5a5;
            margin-bottom: 18px; line-height: 1.6;
        }
        .alert-instruction strong { color: #fee2e2; }

        /* ─── Card ─── */
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 18px; padding: 20px; margin-bottom: 16px;
        }
        .card-title {
            font-size: 11px; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 14px;
        }

        /* ─── Form Inputs ─── */
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #cbd5e1; margin-bottom: 7px; }
        .form-input {
            width: 100%; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px; padding: 13px 16px; color: var(--text);
            font-size: 16px; outline: none; transition: border-color .2s;
        }
        .form-input:focus { border-color: var(--accent); }

        /* ─── Rack Group ─── */
        .rack-group { margin-bottom: 24px; }
        .rack-header {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 12px 16px; margin-bottom: 10px;
        }
        .rack-code-badge {
            background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.3);
            border-radius: 8px; padding: 4px 10px; font-size: 13px; font-weight: 800; font-family: monospace;
        }
        .rack-name { font-size: 13px; color: #94a3b8; margin-left: 8px; }

        /* ─── Item Card ─── */
        .final-item-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 16px; padding: 18px; margin-bottom: 12px;
            transition: border-color .3s, box-shadow .3s, transform .2s;
            position: relative;
        }
        .final-item-card.locked {
            opacity: 0.85;
            border-color: rgba(255,255,255,.07);
        }
        .final-item-card.unlocked {
            opacity: 1;
            border-color: rgba(16,185,129,.4);
            box-shadow: 0 0 0 1px rgba(16,185,129,.3), 0 8px 24px rgba(16,185,129,.1);
        }
        .final-item-card.scan-match {
            animation: pulse-border 1.2s ease-in-out;
        }
        @keyframes pulse-border {
            0% { transform: scale(1); border-color: var(--green); box-shadow: 0 0 0 4px rgba(16,185,129,.4); }
            50% { transform: scale(1.01); border-color: var(--green); box-shadow: 0 0 0 8px rgba(16,185,129,.2); }
            100% { transform: scale(1); }
        }

        .item-top {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px;
        }
        .item-product-name { font-size: 15px; font-weight: 700; color: var(--text); line-height: 1.4; }
        .item-product-sku { font-size: 12px; color: var(--muted); font-family: monospace; margin-top: 4px; }
        .item-barcode-tag {
            display: inline-block; background: rgba(255,255,255,.06); color: #cbd5e1;
            padding: 3px 8px; border-radius: 6px; font-size: 12px; font-family: monospace; font-weight: 600; margin-top: 4px;
        }

        .item-top-actions {
            display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end;
        }

        .btn-scan-item {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #ef4444, #dc2626); color: #ffffff;
            border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; padding: 7px 12px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); transition: all 0.15s ease; white-space: nowrap;
        }
        .btn-scan-item:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c); transform: translateY(-1px);
        }
        .btn-scan-item:active { transform: translateY(0); }
        .btn-scan-item.is-unlocked {
            background: rgba(16,185,129,.15); color: #34d399; border-color: rgba(16,185,129,.3); box-shadow: none;
        }

        .lock-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 20px;
            white-space: nowrap; flex-shrink: 0;
        }
        .lock-badge.is-locked {
            background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.25);
        }
        .lock-badge.is-unlocked {
            background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,.3);
        }

        .btn-camera-input {
            background: rgba(239, 68, 68, 0.18); border: 1.5px solid rgba(239, 68, 68, 0.4);
            color: #ffffff; border-radius: 10px; width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center; font-size: 19px;
            cursor: pointer; transition: all 0.15s ease; flex-shrink: 0;
        }
        .btn-camera-input:hover {
            background: rgba(239, 68, 68, 0.35); transform: scale(1.05);
        }
        .btn-camera-input.is-unlocked {
            background: rgba(16,185,129,.2); border-color: rgba(16,185,129,.4); color: #34d399; cursor: default;
        }

        /* ─── Count Comparison Pills ─── */
        .comparison-bar {
            display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap;
        }
        .comp-pill {
            flex: 1; min-width: 90px; background: var(--surface); border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 10px; text-align: center;
        }
        .comp-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--muted); }
        .comp-val { font-size: 16px; font-weight: 800; margin-top: 2px; }
        .val-p1 { color: #60a5fa; }
        .val-p2 { color: #c084fc; }
        .val-diff { color: #f87171; }

        /* ─── Input Row ─── */
        .input-row {
            display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 10px 14px;
        }
        .input-row-lbl { font-size: 13px; font-weight: 700; color: #e2e8f0; }
        .input-row-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

        .final-qty-input {
            width: 110px; background: var(--faint); border: 2px solid rgba(255,255,255,.1);
            border-radius: 10px; padding: 10px 8px; color: var(--text);
            font-size: 22px; font-weight: 800; text-align: center; outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .final-qty-input:disabled {
            background: rgba(15,23,42,.6); border-color: rgba(255,255,255,.05);
            color: #475569; cursor: not-allowed; font-size: 13px; font-weight: 600;
        }
        .final-qty-input:not(:disabled) {
            background: var(--card); border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(16,185,129,.2);
        }
        .final-qty-input:not(:disabled):focus {
            box-shadow: 0 0 0 4px rgba(16,185,129,.35);
        }

        .notes-input {
            width: 100%; background: var(--surface); border: 1px solid rgba(255,255,255,.08);
            border-radius: 10px; padding: 9px 12px; color: #cbd5e1; font-size: 12px; outline: none; margin-top: 8px;
        }
        .notes-input:focus { border-color: rgba(255,255,255,.2); }

        /* ─── Submit Area ─── */
        .submit-area {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 30;
            padding: 14px 16px; background: rgba(6,11,24,.92); backdrop-filter: blur(12px); border-top: 1px solid var(--border);
        }
        .submit-inner { max-width: 820px; margin: 0 auto; display: flex; gap: 12px; align-items: center; }
        .btn-submit {
            flex: 1; background: linear-gradient(135deg,#dc2626,#ef4444);
            color: white; border: none; border-radius: 14px; padding: 15px 20px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            transition: opacity .2s, transform .1s;
        }
        .btn-submit:hover { opacity: .9; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .45; cursor: not-allowed; }
        .submit-summary { font-size: 12px; color: var(--muted); text-align: right; flex-shrink: 0; line-height: 1.4; }
        .submit-summary strong { color: #f87171; }

        /* ─── Scan Modal ─── */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 50;
            background: rgba(0,0,0,.85); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 24px; padding: 28px; width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.6);
        }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .modal-title { font-size: 16px; font-weight: 800; }
        .modal-close {
            width: 36px; height: 36px; border-radius: 50%; background: var(--surface);
            border: 1px solid var(--border); color: var(--muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        #qr-reader { width: 100%; border-radius: 14px; overflow: hidden; }
        .scan-status {
            margin-top: 14px; font-size: 13px; text-align: center;
            color: var(--muted); min-height: 20px;
        }
        .scan-status.found { color: #10b981; font-weight: 700; }
        .scan-status.notfound { color: #f87171; }
        .manual-scan-row { display: flex; gap: 8px; margin-top: 14px; }
        .manual-scan-input {
            flex: 1; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px; padding: 10px 14px; color: var(--text); font-size: 14px; outline: none;
        }
        .manual-scan-btn {
            background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5; border-radius: 10px; padding: 10px 14px; font-weight: 700;
            font-size: 13px; cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-inner">
        <div class="hbadge">⚖️ Pengecek Final</div>
        <div class="header-titles">
            <h1>{{ $session->branch?->name }}</h1>
            <div class="meta">
                {{ $session->session_number }} &nbsp;·&nbsp; Verifikasi Selisih (Count 3)
            </div>
        </div>
        <button class="scan-btn-header" onclick="openScanModal()">
            <span>📷</span> Scan Barcode
        </button>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <a href="{{ route('opname.portal', $sessionToken) }}" class="back-link">← Kembali ke Portal Opname</a>

    <div class="alert-instruction">
        <strong>🔒 Wajib Scan Fisik:</strong> Kolom input hasil final sengaja <strong>terkunci</strong>.
        Datangi rak barang yang berselisih, lalu tekan tombol <strong>📷 Scan Barcode</strong> untuk membuka kunci input dan masukkan jumlah fisik sebenarnya di rak.
    </div>

    @if(session('error'))
    <div style="background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#fca5a5;padding:12px;border-radius:12px;margin-bottom:16px;">
        ⚠️ {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('opname.final.submit', $sessionToken) }}" id="final-form">
        @csrf

        <!-- Identitas Pengecek Final -->
        <div class="card">
            <div class="card-title">Identitas Pengecek Final</div>
            <div class="form-group">
                <label class="form-label">Nama Anda <span style="color:#f87171">*</span></label>
                <input type="text" name="checker_name" class="form-input"
                       placeholder="Masukkan nama lengkap pemeriksa final" required
                       autocomplete="name" value="{{ old('checker_name') }}">
            </div>
        </div>

        @if($discrepancyItems->isEmpty())
        <div style="padding: 40px 20px; text-align: center; color: var(--muted); border: 2px dashed rgba(255,255,255,.07); border-radius: 18px;">
            <div style="font-size: 40px; margin-bottom: 10px;">🎉</div>
            <h3 style="font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px;">Tidak Ada Item Selisih</h3>
            <p style="font-size: 13px;">Semua rak yang telah dihitung oleh P1 dan P2 cocok 100% tanpa selisih.</p>
        </div>
        @else

        <!-- Daftar Item Selisih per Rak -->
        <div id="items-container">
            @foreach($groupedByRack as $rackCode => $items)
            <div class="rack-group">
                <div class="rack-header">
                    <div>
                        <span class="rack-code-badge">{{ $rackCode }}</span>
                        <span class="rack-name">{{ $items->first()?->rackSession?->rack?->rack_name }}</span>
                    </div>
                    <span style="font-size: 12px; color: #f87171; font-weight: 700;">{{ $items->count() }} item selisih</span>
                </div>

                @foreach($items as $item)
                @php
                    $isDone = ($item->status === 'FINAL_DONE');
                    $diff = ($item->count2_quantity ?? 0) - ($item->count1_quantity ?? 0);
                @endphp
                <div class="final-item-card {{ $isDone ? 'unlocked' : 'locked' }}"
                     id="item-card-{{ $item->id }}"
                     data-item-id="{{ $item->id }}"
                     data-barcode="{{ strtolower($item->product?->barcode ?? '') }}"
                     data-sku="{{ strtolower($item->product?->sku ?? '') }}"
                     data-additional-barcodes="{{ strtolower(isset($item->product?->metadata['additional_barcodes']) && is_array($item->product?->metadata['additional_barcodes']) ? implode(',', $item->product?->metadata['additional_barcodes']) : '') }}"
                     data-unlocked="{{ $isDone ? 'true' : 'false' }}">

                    <div class="item-top">
                        <div>
                            <div class="item-product-name">{{ $item->product?->name }}</div>
                            <div class="item-product-sku">SKU: {{ $item->product?->sku }}</div>
                            @if(!empty($item->product?->barcode))
                            <div class="item-barcode-tag">Barcode: {{ $item->product->barcode }}</div>
                            @endif
                        </div>
                        <div class="item-top-actions">
                            <button type="button"
                                    class="btn-scan-item {{ $isDone ? 'is-unlocked' : '' }}"
                                    id="btn-scan-{{ $item->id }}"
                                    onclick="openScanModal('{{ $item->id }}')">
                                <span class="cam-icon">📷</span>
                                <span id="btn-scan-text-{{ $item->id }}">{{ $isDone ? 'Scan Ulang' : 'Mulai Scan' }}</span>
                            </button>
                            <span class="lock-badge {{ $isDone ? 'is-unlocked' : 'is-locked' }}" id="badge-{{ $item->id }}">
                                {{ $isDone ? '✅ Terbuka' : '🔒 Terkunci' }}
                            </span>
                        </div>
                    </div>

                    <!-- Comparison Info -->
                    <div class="comparison-bar">
                        <div class="comp-pill">
                            <div class="comp-lbl">Penghitung 1</div>
                            <div class="comp-val val-p1">{{ number_format($item->count1_quantity, 0) }}</div>
                        </div>
                        <div class="comp-pill">
                            <div class="comp-lbl">Pengecek 2</div>
                            <div class="comp-val val-p2">{{ number_format($item->count2_quantity, 0) }}</div>
                        </div>
                        <div class="comp-pill">
                            <div class="comp-lbl">Selisih P1↔P2</div>
                            <div class="comp-val val-diff">{{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0) }}</div>
                        </div>
                    </div>

                    <!-- Input Qty Final -->
                    <div class="input-row">
                        <div>
                            <div class="input-row-lbl">Hasil Fisik Aktual (Final)</div>
                            <div class="input-row-sub" id="input-sub-{{ $item->id }}">
                                {{ $isDone ? 'Kuantitas fisik aktual hasil hitung ulang' : 'Tekan tombol kamera untuk membuka kunci' }}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number"
                                   name="final_quantities[{{ $item->id }}]"
                                   id="input-qty-{{ $item->id }}"
                                   class="final-qty-input"
                                   {{ $isDone ? '' : 'disabled' }}
                                   value="{{ $isDone ? $item->final_quantity : '' }}"
                                   placeholder="{{ $isDone ? '0' : '🔒' }}"
                                   min="0" step="1" inputmode="numeric">
                            <button type="button" 
                                    class="btn-camera-input {{ $isDone ? 'is-unlocked' : '' }}" 
                                    id="btn-cam-input-{{ $item->id }}"
                                    onclick="openScanModal('{{ $item->id }}')" 
                                    title="Scan barcode produk ini">
                                <span>{{ $isDone ? '✓' : '📷' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <input type="text"
                           name="final_notes[{{ $item->id }}]"
                           class="notes-input"
                           placeholder="Catatan temuan (opsional, misal: 1 pcs kemasan pecah)..."
                           value="{{ $item->final_notes ?? '' }}">

                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        @endif

    </form>
</div>

<!-- Sticky Bottom Bar -->
@if(!$discrepancyItems->isEmpty())
<div class="submit-area">
    <div class="submit-inner">
        <button type="submit" form="final-form" class="btn-submit" id="btn-submit">
            ✅ Simpan Hasil Final Check
        </button>
        <div class="submit-summary">
            <span id="unlocked-count">0</span> / {{ $discrepancyItems->count() }} terbuka
        </div>
    </div>
</div>
@endif

<!-- Camera Scan Modal -->
<div class="modal-overlay" id="scan-modal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <span class="modal-title">📷 Scan Barcode Fisik</span>
                <div id="target-product-info" style="display:none; margin-top:4px; font-size:12px; color:#fca5a5; line-height:1.4;"></div>
            </div>
            <button class="modal-close" onclick="closeScanModal()">✕</button>
        </div>
        <div id="qr-reader"></div>
        <div class="scan-status" id="scan-status">Arahkan kamera ke barcode produk di rak...</div>
        <div class="manual-scan-row">
            <input type="text" id="manual-barcode" class="manual-scan-input"
                   placeholder="Ketik barcode manual..." autocomplete="off">
            <button class="manual-scan-btn" onclick="manualSearch()">Cari</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
(function() {
    const itemCards = document.querySelectorAll('.final-item-card');
    const unlockedCountEl = document.getElementById('unlocked-count');

    function updateUnlockedCount() {
        let count = 0;
        itemCards.forEach(card => {
            if (card.dataset.unlocked === 'true') count++;
        });
        if (unlockedCountEl) unlockedCountEl.textContent = count;
    }
    updateUnlockedCount();

    window.unlockItem = function(card) {
        if (!card) return;
        card.dataset.unlocked = 'true';
        card.classList.remove('locked');
        card.classList.add('unlocked', 'scan-match');

        const itemId = card.dataset.itemId;
        const badge = document.getElementById(`badge-${itemId}`);
        if (badge) {
            badge.className = 'lock-badge is-unlocked';
            badge.textContent = '✅ Terbuka';
        }

        const btnScan = document.getElementById(`btn-scan-${itemId}`);
        if (btnScan) {
            btnScan.classList.add('is-unlocked');
            const btnScanText = document.getElementById(`btn-scan-text-${itemId}`);
            if (btnScanText) btnScanText.textContent = 'Scan Ulang';
        }

        const camInput = document.getElementById(`btn-cam-input-${itemId}`);
        if (camInput) {
            camInput.classList.add('is-unlocked');
            camInput.innerHTML = '<span>✓</span>';
        }

        const sub = document.getElementById(`input-sub-${itemId}`);
        if (sub) {
            sub.textContent = 'Kuantitas fisik aktual hasil hitung ulang';
        }

        const input = document.getElementById(`input-qty-${itemId}`);
        if (input) {
            input.disabled = false;
            input.placeholder = '0';
            setTimeout(() => {
                input.focus();
                input.select();
            }, 150);
        }

        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        updateUnlockedCount();
    };

    // ─── Scan Match Logic ───
    let isProcessingScan = false;

    function findAndUnlockByBarcode(code) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        code = code.toLowerCase().trim();
        const status = document.getElementById('scan-status');

        let matchedCard = [...itemCards].find(card => {
            const bc = (card.dataset.barcode || '').toLowerCase();
            const sku = (card.dataset.sku || '').toLowerCase();
            const addBc = (card.dataset.additionalBarcodes || '').toLowerCase().split(',').map(s => s.trim());
            return bc === code || sku === code || addBc.includes(code);
        });

        if (matchedCard) {
            unlockItem(matchedCard);
            status.className = 'scan-status found';
            status.textContent = `✅ Barcode Cocok! Kolom input telah dibuka.`;
            setTimeout(() => {
                closeScanModal();
                isProcessingScan = false;
            }, 400);
        } else {
            status.className = 'scan-status notfound';
            status.textContent = `❌ Barcode "${code}" tidak ada dalam daftar selisih sesi ini!`;
            if (!document.getElementById('scan-modal').classList.contains('open')) {
                alert(`❌ Barcode "${code}" tidak ditemukan dalam daftar barang yang selisih.`);
            }
            setTimeout(() => { isProcessingScan = false; }, 800);
        }
    }

    // ─── Camera Scanner ───
    let html5QrCode = null;

    window.openScanModal = function(targetItemId = null) {
        const modal = document.getElementById('scan-modal');
        const status = document.getElementById('scan-status');
        const targetInfo = document.getElementById('target-product-info');
        const manualInput = document.getElementById('manual-barcode');

        modal.classList.add('open');
        status.className = 'scan-status';
        status.textContent = 'Memulai kamera...';
        manualInput.value = '';

        if (targetItemId) {
            const card = document.getElementById(`item-card-${targetItemId}`);
            if (card) {
                const prodName = card.querySelector('.item-product-name')?.textContent || '';
                const barcode = card.dataset.barcode || '';
                targetInfo.style.display = 'block';
                targetInfo.innerHTML = `Target: <strong>${prodName}</strong><br><span style="opacity:0.85;">Barcode: <code>${barcode || '-'}</code></span>`;
            }
        } else {
            targetInfo.style.display = 'none';
        }

        setTimeout(() => {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 15,
                    qrbox: { width: 260, height: 180 },
                    aspectRatio: 1.333
                },
                (decodedText) => {
                    findAndUnlockByBarcode(decodedText);
                },
                () => {}
            ).catch(err => {
                document.getElementById('scan-status').className = 'scan-status notfound';
                document.getElementById('scan-status').textContent = 'Kamera tidak dapat diakses. Silakan ketik barcode manual.';
            });
        }, 200);
    };

    window.closeScanModal = function() {
        document.getElementById('scan-modal').classList.remove('open');
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(() => {
                html5QrCode = null;
            });
        }
    };

    window.manualSearch = function() {
        const val = document.getElementById('manual-barcode').value.trim();
        if (val) {
            findAndUnlockByBarcode(val);
        }
    };

    document.getElementById('manual-barcode').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            manualSearch();
        }
    });

    // ─── Guard Submit ───
    document.getElementById('final-form').addEventListener('submit', function(e) {
        let lockedCount = 0;
        let emptyUnlockedCount = 0;

        itemCards.forEach(card => {
            if (card.dataset.unlocked !== 'true') {
                lockedCount++;
            } else {
                const inp = card.querySelector('.final-qty-input');
                if (inp && (inp.value === '' || inp.value === null)) {
                    emptyUnlockedCount++;
                }
            }
        });

        if (lockedCount > 0) {
            if (!confirm(`⚠️ Masih ada ${lockedCount} barang selisih yang belum di-scan & diverifikasi.\n\nApakah Anda yakin ingin mengirim hanya item yang sudah di-scan?`)) {
                e.preventDefault();
                return;
            }
        }

        if (emptyUnlockedCount > 0) {
            alert('Harap isi kuantitas fisik untuk semua barang yang kuncinya sudah dibuka!');
            e.preventDefault();
            return;
        }

        if (!confirm('⚖️ Konfirmasi Simpan Verifikasi Final:\n\nApakah Anda yakin ingin menyimpan seluruh hasil verifikasi fisik ini?\n\nData fisik ini akan dijadikan acuan kuantitas akhir Stok Opname.')) {
            e.preventDefault();
            return;
        }

        const btn = document.getElementById('btn-submit');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Menyimpan...';
        }
    });

})();
</script>

</body>
</html>
