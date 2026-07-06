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
            <div class="card-title">Daftar Produk — {{ $items->count() }} item</div>

            <div class="search-row">
                <input type="text" class="search-input" id="search-product"
                       placeholder="🔍 Cari nama / SKU / barcode..." autocomplete="off">
                <button type="button" class="scan-btn" onclick="openScanModal()">📷 Scan</button>
            </div>
            <div class="item-count" id="item-count">Menampilkan {{ $items->count() }} produk</div>

            <div class="product-list" id="product-list">
                @foreach($items as $item)
                <div class="product-item"
                     data-name="{{ strtolower($item->product?->name) }}"
                     data-sku="{{ strtolower($item->product?->sku) }}"
                     data-barcode="{{ strtolower($item->product?->barcode ?? '') }}"
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
        <div style="font-size: 13px; color: var(--muted); margin-bottom: 16px; line-height: 1.5;">
            Terdapat produk yang dibiarkan kosong (tidak di-scan). Tentukan status barang tersebut di rak ini:
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
    // ─── Search filter ───
    const searchInput = document.getElementById('search-product');
    let productList = document.querySelectorAll('#product-list .product-item');
    const countEl     = document.getElementById('item-count');

    function filterProducts(q) {
        q = q.toLowerCase().trim();
        let visible = 0;
        productList.forEach(el => {
            const match = !q
                || el.dataset.name.includes(q)
                || el.dataset.sku.includes(q)
                || el.dataset.barcode.includes(q);
            el.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        countEl.textContent = `Menampilkan ${visible} produk`;
    }
    searchInput.addEventListener('input', () => filterProducts(searchInput.value));

    // ─── Enter key navigation: kembali ke search bar ───
    const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
    qtyInputs.forEach((inp, idx) => {
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('search-product').focus();
            }
        });
    });

    // ─── Submit confirmation & Review Unscanned ───
    let unscannedItems = [];
    
    document.getElementById('count-form').addEventListener('submit', function(e) {
        const allInputs = document.querySelectorAll('.qty-input');
        unscannedItems = [];
        
        allInputs.forEach(inp => {
            const itemDiv = inp.closest('.product-item');
            if (itemDiv.style.display !== 'none' && inp.value === '') {
                unscannedItems.push({
                    id: itemDiv.dataset.itemId || inp.name.match(/\[(.*?)\]/)[1],
                    name: itemDiv.querySelector('.product-name').textContent,
                    input: inp
                });
            }
        });
        
        if (unscannedItems.length > 0) {
            e.preventDefault();
            showReviewModal();
            return;
        }
        
        proceedSubmit();
    });
    
    function proceedSubmit() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Mengirim data...';
        document.getElementById('count-form').submit();
    }
    
    window.showReviewModal = function() {
        const listEl = document.getElementById('review-list');
        listEl.innerHTML = '';
        
        unscannedItems.forEach((item, index) => {
            const isNewQty = item.input.name.includes('new_quantities');
            const hiddenInputs = isNewQty 
                ? '' 
                : `<input type="radio" name="review_action_${item.id}" value="remove" id="ra_rem_${item.id}" style="accent-color: #ef4444;">
                   <label for="ra_rem_${item.id}" style="font-size: 13px; cursor: pointer; color: #fca5a5;">Keluarkan dari Rak</label>`;
                   
            listEl.innerHTML += `
                <div style="background: var(--surface); padding: 12px; border-radius: 12px; border: 1px solid var(--border);">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">${item.name}</div>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="review_action_${item.id}" value="zero" checked style="accent-color: var(--blue);">
                            <span style="font-size: 13px;">Stok 0 (Habis)</span>
                        </label>
                        ${isNewQty ? '' : `
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="review_action_${item.id}" value="remove" style="accent-color: #ef4444;">
                            <span style="font-size: 13px; color: #fca5a5;">Bukan di Rak Ini (Hapus)</span>
                        </label>`}
                    </div>
                </div>
            `;
        });
        
        document.getElementById('review-modal').classList.add('open');
    }
    
    window.closeReviewModal = function() {
        document.getElementById('review-modal').classList.remove('open');
    }
    
    window.confirmReviewAndSubmit = function() {
        const form = document.getElementById('count-form');
        
        // Remove old hidden inputs if any
        document.querySelectorAll('.remove-rack-input').forEach(el => el.remove());
        
        unscannedItems.forEach(item => {
            const action = document.querySelector(`input[name="review_action_${item.id}"]:checked`).value;
            if (action === 'zero') {
                item.input.value = '0';
            } else if (action === 'remove') {
                // We add a hidden input array remove_from_rack[]
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'remove_from_rack[]';
                hidden.value = item.id;
                hidden.className = 'remove-rack-input';
                form.appendChild(hidden);
                
                // Set qty to 0 just in case to pass validation
                item.input.value = '0';
            }
        });
        
        closeReviewModal();
        proceedSubmit();
    }

    // ─── Barcode scan & highlight logic ───
    let isProcessingScan = false;

    function findAndFocusByBarcode(code) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        code = code.toLowerCase().trim();
        const status = document.getElementById('scan-status');
        let found = null;

        // try exact barcode match first, then SKU, then name contains
        found = [...productList].find(el =>
            el.style.display !== 'none' &&
            el.dataset.barcode === code
        );
        if (!found) found = [...productList].find(el =>
            el.style.display !== 'none' &&
            el.dataset.sku === code
        );

        // Reset all highlight classes
        productList.forEach(el => el.classList.remove('scan-match', 'highlighted'));

        if (found) {
            highlightAndFocus(found, status, code);
        } else {
            status.className = 'scan-status';
            status.textContent = 'Mencari produk di server...';
            
            fetch(`{{ route('opname.search-product') }}?code=${encodeURIComponent(code)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        status.className = 'scan-status notfound';
                        status.textContent = `❌ Produk "${code}" tidak ditemukan di server.`;
                        isProcessingScan = false;
                    } else {
                        // Create new product item in DOM
                        const list = document.getElementById('product-list');
                        const div = document.createElement('div');
                        div.className = 'product-item';
                        div.dataset.name = data.name.toLowerCase();
                        div.dataset.sku = data.sku.toLowerCase();
                        div.dataset.barcode = data.barcode ? data.barcode.toLowerCase() : '';
                        
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
                        const countEl = document.getElementById('item-count');
                        let visibleCount = Array.from(productList).filter(e => e.style.display !== 'none').length;
                        countEl.textContent = `Menampilkan ${visibleCount} produk`;
                        
                        // Handle enter key for new input
                        const newInput = div.querySelector('.qty-input');
                        newInput.addEventListener('keydown', e => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                document.getElementById('search-product').focus();
                            }
                        });
                        
                        highlightAndFocus(div, status, code);
                    }
                })
                .catch(err => {
                    status.className = 'scan-status notfound';
                    status.textContent = `❌ Gagal menghubungi server.`;
                    isProcessingScan = false;
                });
        }
    }

    function highlightAndFocus(found, status, code) {
        // Reset all highlight classes
        productList = document.querySelectorAll('#product-list .product-item');
        productList.forEach(el => el.classList.remove('scan-match', 'highlighted'));

        // Scroll into view & highlight
        found.classList.add('scan-match');
        found.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Focus qty input
        const qtyEl = found.querySelector('.qty-input');
        if (qtyEl) {
            setTimeout(() => {
                qtyEl.focus();
                qtyEl.select();
            }, 300);
        }

        status.className = 'scan-status found';
        status.textContent = `✅ Ditemukan: ${found.querySelector('.product-name').textContent.replace('(BARU)', '')}`;

        // Auto-close modal after 1.2s if scanning was done by camera
        setTimeout(() => closeScanModal(), 1200);
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
})();
</script>
</body>
</html>
