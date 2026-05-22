<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengecek ke-2 — Rak {{ $rack->rack_code }} | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #060b18; --surface: #0e1726; --card: #111b2e;
            --border: rgba(255,255,255,.07); --purple: #a855f7;
            --text: #f1f5f9; --muted: #64748b; --faint: #1e293b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg); color: var(--text); min-height: 100vh;
        }

        /* ─── Sticky Header ─── */
        .header {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
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
            transition: background .2s;
        }
        .scan-btn-header:hover { background: rgba(255,255,255,.25); }

        /* ─── Container ─── */
        .container { max-width: 820px; margin: 0 auto; padding: 20px 16px 100px; }

        /* ─── Alerts ─── */
        .alert-warning {
            background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.2);
            border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #fcd34d;
            margin-bottom: 18px; line-height: 1.6;
        }
        .alert-error {
            background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
            border-radius: 14px; padding: 14px 16px; font-size: 13px; color: #fca5a5; margin-bottom: 18px;
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--muted); font-size: 13px; text-decoration: none; margin-bottom: 16px;
            transition: color .2s;
        }
        .back-link:hover { color: var(--text); }

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
        .form-input:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(168,85,247,.18); }

        /* ─── Search Row ─── */
        .search-row { display: flex; gap: 10px; margin-bottom: 14px; }
        .search-input {
            flex: 1; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px; padding: 11px 14px; color: var(--text);
            font-size: 15px; outline: none; transition: border-color .2s;
        }
        .search-input:focus { border-color: var(--purple); }
        .scan-btn {
            display: flex; align-items: center; gap: 6px;
            background: rgba(168,85,247,.12); border: 1px solid rgba(168,85,247,.25);
            color: #c084fc; border-radius: 12px; padding: 11px 16px;
            font-size: 13px; font-weight: 700; cursor: pointer; flex-shrink: 0;
            transition: background .2s; white-space: nowrap;
        }
        .scan-btn:hover { background: rgba(168,85,247,.22); }
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
        .product-item.highlighted { border-color: #a855f7 !important; box-shadow: 0 0 0 3px rgba(168,85,247,.2); }
        .product-item.scan-match  { border-color: #10b981 !important; box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
        .product-name { font-size: 15px; font-weight: 600; color: var(--text); }
        .product-meta { font-size: 11px; color: var(--muted); font-family: 'Courier New', monospace; margin-top: 3px; }
        .qty-input {
            width: 90px; background: var(--faint); border: 2px solid rgba(168,85,247,.4);
            border-radius: 10px; padding: 10px 8px; color: var(--text);
            font-size: 20px; font-weight: 800; text-align: center; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .qty-input:focus { border-color: var(--purple); box-shadow: 0 0 0 4px rgba(168,85,247,.2); }

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
        .modal-title  { font-size: 16px; font-weight: 800; }
        .modal-close {
            width: 36px; height: 36px; border-radius: 50%; background: var(--surface);
            border: 1px solid var(--border); color: var(--muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .modal-close:hover { background: var(--faint); }
        #qr-reader { width: 100%; border-radius: 14px; overflow: hidden; }
        .scan-status { margin-top: 14px; font-size: 13px; text-align: center; color: var(--muted); min-height: 20px; }
        .scan-status.found    { color: #10b981; font-weight: 700; }
        .scan-status.notfound { color: #f87171; }
        .manual-scan-row { display: flex; gap: 8px; margin-top: 14px; }
        .manual-scan-input {
            flex: 1; background: var(--surface); border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px; padding: 10px 14px; color: var(--text); font-size: 14px; outline: none;
        }
        .manual-scan-input:focus { border-color: var(--purple); }
        .manual-scan-btn {
            background: rgba(168,85,247,.15); border: 1px solid rgba(168,85,247,.25);
            color: #c084fc; border-radius: 10px; padding: 10px 14px;
            font-weight: 700; font-size: 13px; cursor: pointer;
        }

        /* ─── Submit ─── */
        .submit-area {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 30;
            padding: 14px 16px; background: rgba(6,11,24,.9);
            backdrop-filter: blur(12px); border-top: 1px solid var(--border);
        }
        .submit-inner { max-width: 820px; margin: 0 auto; display: flex; gap: 10px; align-items: center; }
        .btn-submit {
            flex: 1; background: linear-gradient(135deg,#7c3aed,#a855f7);
            color: white; border: none; border-radius: 14px; padding: 15px 20px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            transition: opacity .2s, transform .1s;
        }
        .btn-submit:hover { opacity: .9; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .45; cursor: not-allowed; }
        .submit-note { font-size: 11px; color: var(--muted); text-align: right; flex-shrink: 0; max-width: 130px; line-height: 1.4; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-inner">
        <div class="hbadge">🔍 Pengecek ke-2</div>
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

    <a href="{{ route('opname.cek', $sessionToken) }}" class="back-link">← Kembali ke daftar rak</a>

    <div class="alert-warning">
        ⚠️ <strong>Penting:</strong> Isi jumlah fisik yang Anda hitung sendiri secara independen.
        Hasil hitungan Penghitung 1 sengaja <strong>tidak ditampilkan</strong> agar pengecekan Anda objektif.
        Gunakan tombol <strong>📷 Scan Barcode</strong> untuk menemukan produk dengan kamera.
    </div>

    @if(session('error'))
    <div class="alert-error">⚠️ {{ session('error') }}</div>
    @endif

    <form method="POST"
          action="{{ route('opname.cek.submit', ['sessionToken' => $sessionToken, 'rackId' => $rackSession->id]) }}"
          id="check-form">
        @csrf

        <!-- Identitas Pengecek -->
        <div class="card">
            <div class="card-title">Identitas Pengecek</div>
            <div class="form-group">
                <label class="form-label">Nama Anda <span style="color:#f87171">*</span></label>
                <input type="text" name="checker_name" class="form-input"
                       placeholder="Masukkan nama lengkap Anda"
                       required autocomplete="name" value="{{ old('checker_name') }}">
                @error('checker_name')
                    <p style="color:#f87171;font-size:12px;margin-top:5px;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Daftar Produk -->
        <div class="card">
            <div class="card-title" id="list-card-title">Daftar Scan Hasil Cek</div>

            <div class="search-row">
                <input type="text" class="search-input" id="search-product"
                       placeholder="🔍 Cari nama / SKU / barcode untuk tambah..." autocomplete="off">
                <button type="button" class="scan-btn" onclick="openScanModal()">📷 Scan</button>
            </div>
            <div class="item-count" id="item-count">0 produk di-scan</div>

            <!-- Empty State Placeholder -->
            <div id="empty-cart-state" class="empty-state" style="padding: 30px 20px; text-align: center; color: var(--muted); border: 2px dashed rgba(255,255,255,.07); border-radius: 14px; margin-bottom: 10px;">
                <div style="font-size: 32px; margin-bottom: 10px;">🛒</div>
                <h4 style="font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px;">Belum Ada Produk Yang Di-scan</h4>
                <p style="font-size: 12px; line-height: 1.5; color: var(--muted);">Gunakan tombol <strong>📷 Scan Barcode</strong> atau ketik pencarian di atas untuk memasukkan produk ke keranjang hitung.</p>
            </div>

            <div class="product-list" id="product-list">
                @foreach($items as $item)
                <div class="product-item"
                     style="display: none;"
                     data-scanned="false"
                     data-name="{{ strtolower($item['product_name']) }}"
                     data-sku="{{ strtolower($item['product_sku']) }}"
                     data-barcode="{{ strtolower($item['barcode'] ?? '') }}"
                     data-item-id="{{ $item['id'] }}">
                    <div>
                        <div class="product-name">{{ $item['product_name'] }}</div>
                        <div class="product-meta">
                            SKU: {{ $item['product_sku'] }}
                            @if(!empty($item['barcode']))
                                &nbsp;·&nbsp;Barcode: {{ $item['barcode'] }}
                            @endif
                        </div>
                    </div>
                    <input type="number"
                           name="quantities[{{ $item['id'] }}]"
                           class="qty-input"
                           id="qty-{{ $item['id'] }}"
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
        <button type="submit" form="check-form" class="btn-submit" id="submit-btn">
            ✅ Kirim Hasil Pengecekan
        </button>
        <div class="submit-note">Setelah submit, rak ini terkunci dan tidak bisa diubah</div>
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
                   placeholder="Ketik barcode manual..." autocomplete="off">
            <button class="manual-scan-btn" onclick="manualSearch()">Cari</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
(function() {
    // ─── Search filter ───
    const searchInput = document.getElementById('search-product');
    const productItems = document.querySelectorAll('#product-list .product-item');
    const countEl = document.getElementById('item-count');
    const emptyStateEl = document.getElementById('empty-cart-state');

    function markAsScanned(el) {
        if (!el) return;
        el.dataset.scanned = "true";
        const qtyInput = el.querySelector('.qty-input');
        if (qtyInput) {
            qtyInput.required = true;
        }
        if (emptyStateEl) emptyStateEl.style.display = 'none';
    }

    function filterProducts(q) {
        q = q.toLowerCase().trim();
        let visible = 0;
        let scannedCount = 0;

        productItems.forEach(el => {
            const isScanned = el.dataset.scanned === "true";
            if (isScanned) scannedCount++;

            if (!q) {
                // If search is empty, show ONLY already scanned items
                const show = isScanned;
                el.style.display = show ? 'grid' : 'none';
                if (show) visible++;
            } else {
                // If search has query, show items matching the query regardless of scanned status
                const match = el.dataset.name.includes(q)
                    || el.dataset.sku.includes(q)
                    || (el.dataset.barcode && el.dataset.barcode.includes(q));
                
                el.style.display = match ? 'grid' : 'none';
                if (match) visible++;
            }
        });

        // Update counts
        if (!q) {
            countEl.textContent = `${scannedCount} produk di-scan`;
            if (scannedCount === 0) {
                if (emptyStateEl) emptyStateEl.style.display = 'block';
            } else {
                if (emptyStateEl) emptyStateEl.style.display = 'none';
            }
        } else {
            countEl.textContent = `Menampilkan ${visible} hasil pencarian`;
            if (emptyStateEl) emptyStateEl.style.display = 'none';
        }
    }
    searchInput.addEventListener('input', () => filterProducts(searchInput.value));

    // Bind quantity inputs to automatically mark as scanned on input or focus
    productItems.forEach(item => {
        const qtyInp = item.querySelector('.qty-input');
        if (qtyInp) {
            qtyInp.removeAttribute('required');
            qtyInp.addEventListener('input', () => markAsScanned(item));
            qtyInp.addEventListener('focus', () => markAsScanned(item));
        }
    });

    // ─── Enter navigation ───
    const qtyInputs = Array.from(document.querySelectorAll('.qty-input'));
    qtyInputs.forEach((inp, idx) => {
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = qtyInputs.slice(idx + 1).find(i => i.closest('.product-item').style.display !== 'none');
                if (next) next.focus();
            }
        });
    });

    // ─── Submit guard ───
    document.getElementById('check-form').addEventListener('submit', function(e) {
        const btn = document.getElementById('submit-btn');
        let hasEmpty = false;
        let scannedItems = document.querySelectorAll('.product-item[data-scanned="true"]');
        
        if (scannedItems.length === 0) {
            alert('Belum ada produk yang di-scan atau diisi kuantitasnya!');
            e.preventDefault();
            return;
        }

        scannedItems.forEach(item => {
            const inp = item.querySelector('.qty-input');
            if (inp && (inp.value === '' || inp.value === null)) {
                hasEmpty = true;
            }
        });

        if (hasEmpty) {
            alert('Harap isi kuantitas untuk semua produk yang telah di-scan!');
            e.preventDefault();
            return;
        }

        btn.disabled = true;
        btn.textContent = '⏳ Mengirim data...';
    });

    // ─── Barcode match & focus ───
    function findAndFocusByBarcode(code) {
        code = code.toLowerCase().trim();
        const status = document.getElementById('scan-status');
        let found = null;

        found = [...productItems].find(el => el.dataset.barcode === code);
        if (!found) found = [...productItems].find(el => el.dataset.sku === code);

        productItems.forEach(el => el.classList.remove('scan-match', 'highlighted'));

        if (found) {
            markAsScanned(found);
            
            // Reset search view to only show scanned items including the new one
            searchInput.value = '';
            filterProducts('');

            found.classList.add('scan-match');
            found.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            const qtyEl = found.querySelector('.qty-input');
            if (qtyEl) setTimeout(() => { qtyEl.focus(); qtyEl.select(); }, 300);

            status.className = 'scan-status found';
            status.textContent = `✅ Ditemukan: ${found.querySelector('.product-name').textContent}`;
            setTimeout(() => closeScanModal(), 1200);
        } else {
            status.className = 'scan-status notfound';
            status.textContent = `❌ Produk "${code}" tidak ditemukan di rak ini.`;
        }
    }

    // Initialize display state
    filterProducts('');

    // ─── Camera modal ───
    let html5QrCode = null;

    window.openScanModal = function() {
        document.getElementById('scan-modal').classList.add('open');
        document.getElementById('scan-status').className = 'scan-status';
        document.getElementById('scan-status').textContent = 'Memulai kamera...';
        document.getElementById('manual-barcode').value = '';

        if (!html5QrCode) html5QrCode = new Html5Qrcode('qr-reader');

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                document.getElementById('scan-status').textContent = '⚠️ Kamera tidak tersedia. Gunakan input manual.';
                return;
            }
            const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
            html5QrCode.start(
                cam.id,
                { fps: 10, qrbox: { width: 250, height: 130 }, aspectRatio: 1.5 },
                decodedText => findAndFocusByBarcode(decodedText),
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
        if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop().catch(() => {});
    };

    window.manualSearch = function() {
        const val = document.getElementById('manual-barcode').value;
        if (val.trim()) findAndFocusByBarcode(val);
    };

    document.getElementById('manual-barcode').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); window.manualSearch(); }
    });

    document.getElementById('scan-modal').addEventListener('click', function(e) {
        if (e.target === this) closeScanModal();
    });
})();
</script>
</body>
</html>
