<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Penghitung 1 — {{ $rack->rack_code }} | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #060b18; --surface: #0e1726; --card: #111b2e;
            --border: rgba(255,255,255,.07); --blue: #3b82f6;
            --text: #f1f5f9; --muted: #64748b; --faint: #1e293b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg); color: var(--text); min-height: 100vh;
        }

        /* ─── Sticky Header ─── */
        .header {
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
            padding: 14px 18px; position: sticky; top: 0; z-index: 20;
            box-shadow: 0 4px 20px rgba(0,0,0,.4);
        }
        .header-inner { max-width: 820px; margin: 0 auto; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .hbadge {
            background: rgba(255,255,255,.15); border-radius: 20px;
            padding: 3px 12px; font-size: 11px; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; flex-shrink: 0;
        }
        .header-titles h1 { font-size: 17px; font-weight: 800; color: white; }
        .header-titles .meta { font-size: 12px; color: rgba(255,255,255,.7); margin-top: 2px; }
        .scan-btn-header {
            margin-left: auto; flex-shrink: 0;
            display: flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2);
            color: white; border-radius: 12px; padding: 8px 16px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            transition: background .2s; text-decoration: none;
        }
        .scan-btn-header:hover { background: rgba(255,255,255,.25); }

        /* ─── Container ─── */
        .container { max-width: 820px; margin: 0 auto; padding: 20px 16px 100px; }

        /* ─── Alert ─── */
        .alert-info {
            background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.2);
            border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #93c5fd;
            margin-bottom: 18px; line-height: 1.6;
        }
        .alert-error {
            background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
            border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #fca5a5;
            margin-bottom: 18px;
        }

        /* ─── Card ─── */
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 18px; padding: 20px; margin-bottom: 16px;
        }
        .card-title {
            font-size: 11px; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 14px;
        }

        /* ─── Form inputs ─── */
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #cbd5e1; margin-bottom: 7px; }
        .form-input {
            width: 100%; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px; padding: 13px 16px; color: var(--text);
            font-size: 16px; outline: none; transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(59,130,246,.18); }

        /* ─── Search Row ─── */
        .search-row { display: flex; gap: 10px; margin-bottom: 14px; }
        .search-input {
            flex: 1; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px; padding: 11px 14px; color: var(--text);
            font-size: 15px; outline: none; transition: border-color .2s;
        }
        .search-input:focus { border-color: var(--blue); }
        .scan-btn {
            display: flex; align-items: center; gap: 6px;
            background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.25);
            color: #60a5fa; border-radius: 12px; padding: 11px 16px;
            font-size: 13px; font-weight: 700; cursor: pointer; flex-shrink: 0;
            transition: background .2s; white-space: nowrap;
        }
        .scan-btn:hover { background: rgba(59,130,246,.22); }
        .item-count { font-size: 12px; color: var(--muted); margin-bottom: 12px; }

        /* ─── Product Item ─── */
        .product-list { display: flex; flex-direction: column; gap: 8px; }
        .product-item {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 14px 16px;
            display: grid; grid-template-columns: 1fr auto;
            gap: 10px; align-items: center;
            transition: border-color .3s, box-shadow .3s;
        }
        .product-item.highlighted {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,.2);
        }
        .product-item.scan-match {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16,185,129,.2);
        }
        .product-name { font-size: 15px; font-weight: 600; color: var(--text); }
        .product-meta { font-size: 11px; color: var(--muted); font-family: 'Courier New', monospace; margin-top: 3px; }
        .qty-input {
            width: 90px; background: var(--faint); border: 2px solid rgba(59,130,246,.4);
            border-radius: 10px; padding: 10px 8px; color: var(--text);
            font-size: 20px; font-weight: 800; text-align: center; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .qty-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(59,130,246,.2);
        }

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
            transition: background .2s;
        }
        .modal-close:hover { background: var(--faint); }
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
        .manual-scan-input:focus { border-color: var(--blue); }
        .manual-scan-btn {
            background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.25);
            color: #60a5fa; border-radius: 10px; padding: 10px 14px; font-weight: 700;
            font-size: 13px; cursor: pointer;
        }

        /* ─── Submit ─── */
        .submit-area { position: fixed; bottom: 0; left: 0; right: 0; z-index: 30; padding: 14px 16px; background: rgba(6,11,24,.9); backdrop-filter: blur(12px); border-top: 1px solid var(--border); }
        .submit-inner { max-width: 820px; margin: 0 auto; display: flex; gap: 10px; align-items: center; }
        .btn-submit {
            flex: 1; background: linear-gradient(135deg,#059669,#10b981);
            color: white; border: none; border-radius: 14px; padding: 15px 20px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            transition: opacity .2s, transform .1s; letter-spacing: -.01em;
        }
        .btn-submit:hover { opacity: .9; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .45; cursor: not-allowed; }
        .submit-note { font-size: 11px; color: var(--muted); text-align: right; flex-shrink: 0; max-width: 120px; line-height: 1.4; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-inner">
        <div class="hbadge">📦 Penghitung 1</div>
        <div class="header-titles">
            <h1>{{ $session->branch?->name }}</h1>
            <div class="meta">
                {{ $session->session_number }} · Rak <strong>{{ $rack->rack_code }}</strong> — {{ $rack->rack_name }}
            </div>
        </div>
        <button class="scan-btn-header" onclick="openScanModal()">
            <span>📷</span> Scan Barcode
        </button>
    </div>
</div>

<!-- Main Content -->
<div class="container">

    <div class="alert-info">
        📋 Isi jumlah fisik yang Anda hitung untuk setiap produk di rak <strong>{{ $rack->rack_code }}</strong>.
        Setelah submit, data tidak bisa diubah dan rak akan terkunci.
        Gunakan tombol <strong>📷 Scan Barcode</strong> untuk mencari produk dengan kamera.
    </div>

    @if(session('error'))
    <div class="alert-error">⚠️ {{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="alert-error">
        <strong>⚠️ Gagal menyimpan data:</strong>
        <ul style="margin-left: 20px; margin-top: 6px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('opname.hitung.submit', $rackSession->rack_token) }}" id="count-form">
        @csrf

        <!-- Identitas -->
        <div class="card">
            <div class="card-title">Identitas Penghitung</div>
            <div class="form-group">
                <label class="form-label">Nama Anda <span style="color:#f87171">*</span></label>
                <input type="text" name="counter_name" class="form-input"
                       placeholder="Masukkan nama lengkap Anda" required
                       autocomplete="name" value="{{ old('counter_name') }}">
                @error('counter_name')
                    <p style="color:#f87171;font-size:12px;margin-top:5px;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Daftar Produk -->
        <div class="card">
            <div class="card-title">Daftar Scan Hasil Hitung</div>

            <div class="search-row">
                <input type="text" class="search-input" id="search-product"
                       placeholder="🔍 Cari nama / SKU / barcode untuk tambah..." autocomplete="off">
                <button type="button" class="scan-btn" onclick="openScanModal()">📷 Scan</button>
            </div>
            <div class="item-count" id="item-count">0 produk di-scan</div>

            <!-- Empty State Placeholder -->
            <div id="empty-cart-state" class="empty-state" style="padding: 30px 20px; text-align: center; color: var(--muted); border: 2px dashed rgba(255,255,255,.07); border-radius: 14px; margin-bottom: 10px;">
                <div style="font-size: 32px; margin-bottom: 10px;">📦</div>
                <h4 style="font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px;">Belum Ada Produk Yang Di-scan</h4>
                <p style="font-size: 12px; line-height: 1.5; color: var(--muted);">Gunakan tombol <strong>📷 Scan Barcode</strong> atau ketik pencarian di atas untuk memasukkan produk ke daftar hitung.</p>
            </div>

            <div class="product-list" id="product-list">
                @foreach($items as $item)
                <div class="product-item"
                     style="display: none;"
                     data-scanned="false"
                     data-name="{{ strtolower($item->product?->name) }}"
                     data-sku="{{ strtolower($item->product?->sku) }}"
                     data-barcode="{{ strtolower($item->product?->barcode ?? '') }}"
                     data-additional-barcodes="{{ strtolower(isset($item->product?->metadata['additional_barcodes']) && is_array($item->product?->metadata['additional_barcodes']) ? implode(',', $item->product?->metadata['additional_barcodes']) : '') }}"
                     data-item-id="{{ $item->id }}">
                    <div>
                        <div class="product-name">{{ $item->product?->name }}</div>
                        <div class="product-meta">
                            SKU: {{ $item->product?->sku }}
                            @if($item->product?->barcode)
                                &nbsp;·&nbsp;Barcode: {{ $item->product->barcode }}
                            @endif
                            @if($item->product?->category?->name)
                                &nbsp;·&nbsp;{{ $item->product->category->name }}
                            @endif
                        </div>
                    </div>
                    <input type="number" name="quantities[{{ $item->id }}]"
                           class="qty-input" id="qty-{{ $item->id }}"
                           placeholder="0" min="0" step="1"
                           inputmode="numeric">
                </div>
                @endforeach
            </div>
        </div>

    </form>
</div>

<!-- Fixed Submit Bar -->
<div class="submit-area">
    <div class="submit-inner">
        <button type="submit" form="count-form" class="btn-submit" id="submit-btn">
            ✅ Kirim Hasil Hitungan
        </button>
        <div class="submit-note">Pastikan semua qty sudah terisi</div>
    </div>
</div>

<!-- Camera Scan Modal -->
<div class="modal-overlay" id="scan-modal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">📷 Scan Barcode Produk</span>
            <button class="modal-close" onclick="closeScanModal()">✕</button>
        </div>
        <div id="qr-reader"></div>
        <div class="scan-status" id="scan-status">Arahkan kamera ke barcode produk...</div>
        <div class="manual-scan-row">
            <input type="text" id="manual-barcode" class="manual-scan-input"
                   placeholder="Ketik barcode manual lalu tekan Enter..."
                   autocomplete="off">
            <button class="manual-scan-btn" onclick="manualSearch()">Cari</button>
        </div>
    </div>
</div>

<!-- Review Unscanned Modal -->
<div class="modal-overlay" id="review-modal" style="z-index: 60;">
    <div class="modal" style="max-width: 500px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <span class="modal-title">⚠️ Barang Belum Di-scan</span>
            <button class="modal-close" onclick="closeReviewModal()" type="button">✕</button>
        </div>
        <div style="font-size: 13px; color: var(--muted); margin-bottom: 12px; line-height: 1.5;">
            Terdapat produk bawaan rak yang belum di-scan. Tentukan status barang tersebut di rak ini:
        </div>
        <!-- Quick Action Buttons -->
        <div style="display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap;">
            <button type="button" onclick="markAllReview('zero')"
                    style="flex: 1; min-width: 130px; background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.3); color: #93c5fd; border-radius: 10px; padding: 9px 12px; font-size: 12px; font-weight: 700; cursor: pointer;">
                🔵 Tandai Semua Stok 0
            </button>
            <button type="button" onclick="markAllReview('remove')"
                    style="flex: 1; min-width: 130px; background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; border-radius: 10px; padding: 9px 12px; font-size: 12px; font-weight: 700; cursor: pointer;">
                ⚪ Tandai Semua Pindah Rak
            </button>
        </div>
        <div id="review-list" style="overflow-y: auto; flex: 1; padding-right: 5px; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
            <!-- Injected via JS -->
        </div>
        <div style="margin-top: auto;">
            <button type="button" class="btn-submit" onclick="confirmReviewAndSubmit()" style="width: 100%;">
                Konfirmasi & Kirim Data
            </button>
        </div>
    </div>
</div>

<!-- JS: html5-qrcode + search + form logic -->
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
(function() {
    const storageKey = 'opname_hitung_{{ $rackSession->rack_token }}';

    function saveDraft() {
        const state = {
            checker_name: document.querySelector('input[name="counter_name"]').value,
            items: []
        };
        
        document.querySelectorAll('.product-item').forEach(el => {
            if (el.dataset.scanned === "true" || el.style.display !== 'none') {
                const qtyInput = el.querySelector('.qty-input');
                if (qtyInput && qtyInput.value !== '') {
                    state.items.push({
                        id: el.dataset.itemId || (qtyInput.name.match(/\[(.*?)\]/) ? qtyInput.name.match(/\[(.*?)\]/)[1] : null),
                        qty: qtyInput.value,
                        isNew: qtyInput.name.includes('new_quantities'),
                        name: el.dataset.name,
                        sku: el.dataset.sku,
                        barcode: el.dataset.barcode,
                        rawName: el.querySelector('.product-name').innerHTML,
                        rawMeta: el.querySelector('.product-meta').innerHTML
                    });
                }
            }
        });
        
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    function loadDraft() {
        const saved = localStorage.getItem(storageKey);
        if (!saved) return;
        
        try {
            const state = JSON.parse(saved);
            const nameInput = document.querySelector('input[name="counter_name"]');
            if (nameInput && state.checker_name) nameInput.value = state.checker_name;
            
            state.items.forEach(item => {
                if (item.isNew) {
                    let existing = document.querySelector(`.product-item input[name="new_quantities[${item.id}]"]`);
                    if (!existing) {
                        const list = document.getElementById('product-list');
                        const div = document.createElement('div');
                        div.className = 'product-item';
                        div.dataset.name = item.name;
                        div.dataset.sku = item.sku;
                        div.dataset.barcode = item.barcode;
                        div.dataset.scanned = "true";
                        div.innerHTML = `
                            <div>
                                <div class="product-name">${item.rawName}</div>
                                <div class="product-meta">${item.rawMeta}</div>
                            </div>
                            <input type="number" name="new_quantities[${item.id}]" class="qty-input" placeholder="0" min="0" step="1" inputmode="numeric" value="${item.qty}">
                        `;
                        list.insertBefore(div, list.firstChild);
                        
                        const newInput = div.querySelector('.qty-input');
                        newInput.addEventListener('keydown', e => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                const searchInp = document.getElementById('search-product');
                                searchInp.value = '';
                                filterProducts('');
                                searchInp.focus();
                            }
                        });
                        newInput.addEventListener('input', saveDraft);
                    } else {
                        existing.value = item.qty;
                        markAsScanned(existing.closest('.product-item'));
                    }
                } else {
                    const inp = document.querySelector(`input[name="quantities[${item.id}]"]`);
                    if (inp) {
                        inp.value = item.qty;
                        markAsScanned(inp.closest('.product-item'));
                    }
                }
            });
            
            productList = document.querySelectorAll('#product-list .product-item');
            filterProducts('');
        } catch(e) {
            console.error("Gagal meload draft", e);
        }
    }

    // ─── Search filter ───
    const searchInput = document.getElementById('search-product');
    let productList = document.querySelectorAll('#product-list .product-item');
    const countEl = document.getElementById('item-count');
    const emptyStateEl = document.getElementById('empty-cart-state');

    function markAsScanned(el) {
        if (!el) return;
        el.dataset.scanned = "true";
        if (emptyStateEl) emptyStateEl.style.display = 'none';
    }

    function filterProducts(q) {
        q = q.toLowerCase().trim();
        let visible = 0;
        let scannedCount = 0;
        productList = document.querySelectorAll('#product-list .product-item');

        productList.forEach(el => {
            const isScanned = el.dataset.scanned === "true";
            if (isScanned) scannedCount++;

            if (!q) {
                // If search is empty, show ONLY already scanned items
                const show = isScanned;
                el.style.display = show ? 'grid' : 'none';
                if (show) visible++;
            } else {
                // If search has query, show items matching query
                const match = el.dataset.name.includes(q)
                    || el.dataset.sku.includes(q)
                    || (el.dataset.barcode && el.dataset.barcode.includes(q))
                    || (el.dataset.additionalBarcodes && el.dataset.additionalBarcodes.includes(q));
                
                el.style.display = match ? 'grid' : 'none';
                if (match) visible++;
            }
        });

        // Update counts
        if (!q) {
            countEl.textContent = `${scannedCount} produk di-scan`;
            if (emptyStateEl) emptyStateEl.style.display = (scannedCount === 0) ? 'block' : 'none';
        } else {
            countEl.textContent = `Menampilkan ${visible} hasil pencarian`;
            if (emptyStateEl) emptyStateEl.style.display = 'none';
        }
    }
    searchInput.addEventListener('input', () => filterProducts(searchInput.value));

    // Support for physical barcode scanners typing into the search input
    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = searchInput.value.trim();
            if (val) {
                isProcessingScan = false;
                findAndFocusByBarcode(val);
            }
        }
    });

    // ─── Enter key navigation: kembali ke search bar ───
    const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
    qtyInputs.forEach((inp, idx) => {
        inp.addEventListener('input', saveDraft); // Auto-save on input
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchInp = document.getElementById('search-product');
                searchInp.value = '';
                filterProducts('');
                searchInp.focus();
            }
        });
    });

    // ─── Submit confirmation & Review Unscanned ───
    let unscannedItems = [];
    
    document.getElementById('count-form').addEventListener('submit', function(e) {
        let hasEmptyScanned = false;
        let scannedItems = document.querySelectorAll('.product-item[data-scanned="true"]');
        let unscannedDomItems = document.querySelectorAll('.product-item[data-scanned="false"]');
        unscannedItems = [];
        
        scannedItems.forEach(item => {
            const inp = item.querySelector('.qty-input');
            if (inp && (inp.value === '' || inp.value === null)) {
                hasEmptyScanned = true;
            }
        });

        if (hasEmptyScanned) {
            alert('Harap isi kuantitas untuk semua produk yang telah di-scan!');
            e.preventDefault();
            return;
        }

        unscannedDomItems.forEach(itemDiv => {
            const inp = itemDiv.querySelector('.qty-input');
            unscannedItems.push({
                id: itemDiv.dataset.itemId || (inp && inp.name.match(/\[(.*?)\]/) ? inp.name.match(/\[(.*?)\]/)[1] : null),
                name: itemDiv.querySelector('.product-name').textContent,
                input: inp
            });
        });
        
        if (unscannedItems.length > 0) {
            e.preventDefault();
            showReviewModal();
            return;
        }
        
        proceedSubmit();
    });
    
    function proceedSubmit() {
        localStorage.removeItem(storageKey); // Clear draft on submit
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Mengirim data...';
        document.getElementById('count-form').submit();
    }
    
    window.showReviewModal = function() {
        const listEl = document.getElementById('review-list');
        listEl.innerHTML = '';
        
        unscannedItems.forEach((item, index) => {
            const isNewQty = item.input && item.input.name.includes('new_quantities');
            listEl.innerHTML += `
                <div style="background: var(--surface); padding: 12px; border-radius: 12px; border: 1px solid var(--border);">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">${item.name}</div>
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="review_action_${item.id}" value="zero" checked style="accent-color: var(--blue);">
                            <span style="font-size: 13px;">Stok 0 (Habis)</span>
                        </label>
                        ${isNewQty ? '' : `
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="review_action_${item.id}" value="remove" style="accent-color: #ef4444;">
                            <span style="font-size: 13px; color: #fca5a5;">Bukan di Rak Ini (Keluarkan)</span>
                        </label>`}
                    </div>
                </div>
            `;
        });
        
        document.getElementById('review-modal').classList.add('open');
    };
    
    window.closeReviewModal = function() {
        document.getElementById('review-modal').classList.remove('open');
    };

    window.markAllReview = function(action) {
        document.querySelectorAll('#review-list input[type="radio"]').forEach(r => {
            if (r.value === action) {
                r.checked = true;
            }
        });
    };
    
    window.confirmReviewAndSubmit = function() {
        const form = document.getElementById('count-form');
        
        // Remove old hidden inputs if any
        document.querySelectorAll('.remove-rack-input').forEach(el => el.remove());
        
        unscannedItems.forEach(item => {
            const checkedRadio = document.querySelector(`input[name="review_action_${item.id}"]:checked`);
            const action = checkedRadio ? checkedRadio.value : 'zero';
            if (action === 'zero') {
                if (item.input) item.input.value = '0';
            } else if (action === 'remove') {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'remove_from_rack[]';
                hidden.value = item.id;
                hidden.className = 'remove-rack-input';
                form.appendChild(hidden);
                
                if (item.input) item.input.value = '0';
            }
        });
        
        closeReviewModal();
        proceedSubmit();
    };

    // ─── Barcode scan & highlight logic ───
    let isProcessingScan = false;

    function findAndFocusByBarcode(code) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        code = code.toLowerCase().trim();
        const status = document.getElementById('scan-status');
        let found = null;

        productList = document.querySelectorAll('#product-list .product-item');
        found = [...productList].find(el => {
            const bc = (el.dataset.barcode || '').toLowerCase();
            const sku = (el.dataset.sku || '').toLowerCase();
            const addBc = (el.dataset.additionalBarcodes || '').toLowerCase().split(',').map(s => s.trim());
            return bc === code || sku === code || addBc.includes(code);
        });

        // Reset all highlight classes
        productList.forEach(el => el.classList.remove('scan-match', 'highlighted'));

        if (found) {
            highlightAndFocus(found, status, code);
        } else {
            status.className = 'scan-status';
            status.textContent = 'Mencari produk di server...';
            
            fetch(`{{ route('opname.search-product') }}?code=${encodeURIComponent(code)}&rack_token={{ $rackSession->rack_token }}`)
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok || data.error) {
                        status.className = 'scan-status notfound';
                        const errMsg = data.message || `Produk "${code}" tidak ditemukan di server.`;
                        status.textContent = `❌ ${errMsg}`;
                        if (!document.getElementById('scan-modal').classList.contains('open')) {
                            alert(`❌ ${errMsg}`);
                            searchInput.select();
                        }
                        isProcessingScan = false;
                        return;
                    }

                    // Create new product item in DOM
                    const list = document.getElementById('product-list');
                    const div = document.createElement('div');
                    div.className = 'product-item';
                    div.dataset.name = data.name.toLowerCase();
                    div.dataset.sku = data.sku.toLowerCase();
                    div.dataset.barcode = data.barcode ? data.barcode.toLowerCase() : '';
                    div.dataset.scanned = "false";
                    
                    const meta = `SKU: ${data.sku}` + 
                                 (data.barcode ? ` &nbsp;&middot;&nbsp; Barcode: ${data.barcode}` : '') +
                                 (data.category_name ? ` &nbsp;&middot;&nbsp; ${data.category_name}` : '');
                                 
                    div.innerHTML = `
                        <div>
                            <div class="product-name">${data.name} <span style="color:#10b981;font-size:10px;">(BARU)</span></div>
                            <div class="product-meta">${meta}</div>
                        </div>
                        <input type="number" name="new_quantities[${data.id}]"
                               class="qty-input" placeholder="0" min="0" step="1" inputmode="numeric">
                    `;
                    
                    list.insertBefore(div, list.firstChild); // prepend to top
                    
                    // Update lists
                    productList = document.querySelectorAll('#product-list .product-item');
                    
                    // Bind events for new input
                    const newInput = div.querySelector('.qty-input');
                    newInput.addEventListener('input', () => { markAsScanned(div); saveDraft(); });
                    newInput.addEventListener('focus', () => markAsScanned(div));
                    newInput.addEventListener('keydown', e => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const searchInp = document.getElementById('search-product');
                            searchInp.value = '';
                            filterProducts('');
                            searchInp.focus();
                        }
                    });
                    
                    markAsScanned(div);
                    highlightAndFocus(div, status, code);
                })
                .catch(err => {
                    status.className = 'scan-status notfound';
                    status.textContent = `❌ Gagal menghubungi server.`;
                    if (!document.getElementById('scan-modal').classList.contains('open')) {
                        alert(`❌ Gagal menghubungi server.`);
                        searchInput.select();
                    }
                    isProcessingScan = false;
                });
        }
    }

    function highlightAndFocus(found, status, code) {
        markAsScanned(found);

        // Reset search view to only show scanned items including the new one
        searchInput.value = '';
        filterProducts('');

        found.classList.add('scan-match');
        found.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        closeScanModal(); // Close modal immediately so user can see the item and input qty

        // Focus qty input
        const qtyEl = found.querySelector('.qty-input');
        if (qtyEl) {
            setTimeout(() => {
                qtyEl.focus();
                qtyEl.select();
            }, 100);
        }

        status.className = 'scan-status found';
        status.textContent = `✅ Ditemukan: ${found.querySelector('.product-name').textContent.replace('(BARU)', '')}`;
        
        // Allow next scan
        setTimeout(() => { isProcessingScan = false; }, 500);
    }

    // ─── Camera scan modal ───
    let html5QrCode = null;

    window.openScanModal = function() {
        isProcessingScan = false;
        document.getElementById('scan-modal').classList.add('open');
        document.getElementById('scan-status').className = 'scan-status';
        document.getElementById('scan-status').textContent = 'Memulai kamera...';
        document.getElementById('manual-barcode').value = '';

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qr-reader');
        }

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                document.getElementById('scan-status').textContent = '⚠️ Kamera tidak ditemukan. Gunakan input manual.';
                return;
            }
            // prefer back camera
            const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
            html5QrCode.start(
                cam.id,
                { fps: 10, qrbox: { width: 250, height: 130 }, aspectRatio: 1.5 },
                (decodedText) => {
                    findAndFocusByBarcode(decodedText);
                },
                () => {}
            ).catch(err => {
                document.getElementById('scan-status').textContent = '⚠️ Gagal akses kamera: ' + err;
            });
        }).catch(err => {
            document.getElementById('scan-status').textContent = '⚠️ Tidak bisa mengakses kamera: ' + err;
        });
    };

    window.closeScanModal = function() {
        document.getElementById('scan-modal').classList.remove('open');
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(() => {});
        }
    };

    window.manualSearch = function() {
        const val = document.getElementById('manual-barcode').value;
        if (val.trim()) {
            isProcessingScan = false;
            findAndFocusByBarcode(val);
        }
    };

    document.getElementById('manual-barcode').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); window.manualSearch(); }
    });

    // Close modal on overlay click
    document.getElementById('scan-modal').addEventListener('click', function(e) {
        if (e.target === this) closeScanModal();
    });
    // Load draft when initializing
    document.querySelector('input[name="counter_name"]')?.addEventListener('input', saveDraft);
    loadDraft();
})();
</script>
</body>
</html>
