@extends('warehouse.layout')

@section('title', 'Pengecekan PO: ' . $po->po_number)

@section('content')
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 15px;
        background: var(--bg-color);
        min-height: 100vh;
        box-sizing: border-box;
    }
    .header-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .header-card h2 {
        color: var(--text-color);
        margin: 0 0 5px 0;
        font-size: 1.2rem;
    }
    .header-card p {
        color: var(--text-color);
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.8;
    }
    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    .alert-danger {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #f87171;
    }
    .alert-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }
    .mode-switch {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    .btn-mode {
        flex: 1;
        padding: 10px;
        text-align: center;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        color: var(--text-color);
    }
    .btn-mode.active {
        background-color: var(--header-bg);
        color: var(--header-text);
        border-color: var(--header-bg);
    }
    #reader {
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        background: var(--card-bg);
    }
    #reader video {
        object-fit: cover;
    }
    .input-section {
        margin-bottom: 15px;
    }
    .input-barcode {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        box-sizing: border-box;
        background-color: var(--card-bg);
        color: var(--text-color);
    }
    .item-list {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }
    .item-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
        color: var(--text-color);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .item-card.complete {
        background-color: #d1fae5;
        border-color: #34d399;
        color: #065f46;
    }
    .item-card.over {
        background-color: #fee2e2;
        border-color: #f87171;
        color: #991b1b;
    }
    html.dark .item-card.complete {
        background-color: rgba(16, 185, 129, 0.2);
        border-color: #059669;
        color: #34d399;
    }
    html.dark .item-card.over {
        background-color: rgba(239, 68, 68, 0.2);
        border-color: #dc2626;
        color: #f87171;
    }
    .item-info {
        flex: 1;
    }
    .item-name {
        font-weight: bold;
        font-size: 0.95rem;
    }
    .item-barcode {
        font-size: 0.8rem;
        color: #6b7280;
    }
    .item-qty {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .btn-submit {
        width: 100%;
        padding: 15px;
        background-color: #059669;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-submit:hover {
        opacity: 0.9;
    }
    .controls {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .btn-toggle {
        flex: 1;
        padding: 10px;
        background: #e5e7eb;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        color: #4b5563;
    }
    .btn-toggle.active {
        background: #3b82f6;
        color: #fff;
    }
    #error-msg {
        color: #dc2626;
        font-size: 0.9rem;
        margin-top: 5px;
        text-align: center;
        font-weight: bold;
    }
    html.dark textarea {
        background-color: var(--card-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }
    html.dark input[type="number"] {
        background-color: var(--card-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }
</style>

<div class="container">
    <div class="header-card">
        <h2>Pengecekan Gudang</h2>
        <p>No PO: {{ $po->po_number }}</p>
    </div>

    <div class="controls">
        <button id="btn-camera" class="btn-toggle active" onclick="setMode('camera')">Kamera HP</button>
        <button id="btn-usb" class="btn-toggle" onclick="setMode('usb')">Scanner USB/BT</button>
    </div>

    <div id="camera-section">
        <div id="reader"></div>
    </div>

    <div id="usb-section" style="display: none;" class="input-section">
        <input type="text" id="manual-barcode" class="input-barcode" placeholder="Arahkan kursor kesini, lalu scan barcode..." autocomplete="off">
        <div id="error-msg"></div>
    </div>

    <ul class="item-list" id="item-list">
        <!-- Rendered by JS -->
    </ul>

    <form id="submit-form" action="{{ route('warehouse.receive.submit', $po->id) }}" method="POST">
        @csrf
        <input type="hidden" name="scanned_items" id="scanned_items_input">
        <div class="input-section">
            <textarea name="notes" placeholder="Catatan tambahan (opsional)" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-color); font-family: inherit; resize: none; box-sizing: border-box; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" rows="3"></textarea>
        </div>
        <button type="button" class="btn-submit" onclick="submitCheck()">Simpan Pengecekan</button>
    </form>
</div>

<!-- Audio for scan feedback -->
<audio id="beep-ok" src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU..." preload="auto"></audio>
<audio id="beep-err" src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU..." preload="auto"></audio>

<script>
    const itemsData = @json($items);
    const scannedState = {};
    const isItemVisible = {};
    const rejectedCheck = @json($rejectedCheck ?? null);

    itemsData.forEach(item => {
        scannedState[item.product_id] = item.qty_scanned || 0;
        isItemVisible[item.product_id] = item.qty_scanned > 0;
    });

    const beepOk = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'); // short pip
    const beepErr = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3'); // error buzz

    function initList() {
        const list = document.getElementById('item-list');
        list.innerHTML = '';

        itemsData.forEach(item => {
            const target = item.qty_po;
            const li = document.createElement('li');
            li.id = 'item-' + item.product_id;
            li.className = 'item-card';
            li.style.display = 'none'; // Sembunyikan secara default
            
            let inputAttrs = '';
            if (rejectedCheck) {
                // If rejected, lock items that did not exceed PO qty
                if (item.qty_scanned <= target) {
                    inputAttrs = 'readonly style="background-color: #f3f4f6; cursor: not-allowed; width: 60px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; text-align: right;"';
                } else {
                    inputAttrs = 'style="width: 60px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; text-align: right;"';
                }
            } else {
                inputAttrs = 'style="width: 60px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; text-align: right;"';
            }

            li.innerHTML = `
                <div class="item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-barcode">${item.barcode || '-'}</div>
                </div>
                <div class="item-qty" style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" id="input-${item.product_id}" value="${item.qty_scanned > 0 ? item.qty_scanned : ''}" placeholder="0" min="0" ${inputAttrs} onchange="handleManualInput('${item.product_id}', this.value)" onkeydown="handleInputKeydown(event, '${item.product_id}')">
                    <span style="font-size: 0.9rem; color: #6b7280; font-weight: bold;">/ ${target}</span>
                </div>
            `;
            list.appendChild(li);
            updateItemUI(item.product_id);
        });
        
        if (rejectedCheck) {
            document.getElementById('error-msg').innerHTML = `<div class="alert alert-warning">Pengecekan ini sebelumnya ditolak. Silakan perbaiki kuantitas yang melebihi PO.</div>`;
        }
    }

    function handleInputKeydown(event, productId) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const inputField = document.getElementById('input-' + productId);
            if (inputField) inputField.blur();

            if (document.getElementById('btn-usb').classList.contains('active')) {
                document.getElementById('manual-barcode').focus();
            }
        }
    }

    function handleManualInput(productId, value) {
        let val = value === '' ? 0 : parseInt(value);
        if (isNaN(val) || val < 0) val = 0;
        scannedState[productId] = val;
        updateItemUI(productId);
    }

    function updateItemUI(productId) {
        const item = itemsData.find(i => i.product_id === productId);
        if (!item) return;

        const scanned = scannedState[productId];
        const target = item.qty_po;
        const li = document.getElementById('item-' + productId);

        if (li) {
            li.classList.remove('complete', 'over');
            if (scanned === target && target > 0) li.classList.add('complete');
            if (scanned > target) li.classList.add('over');
            
            if (isItemVisible[productId]) {
                li.style.display = 'flex';
            } else {
                li.style.display = 'none';
            }
        }
    }

    function handleScan(barcodeStr) {
        document.getElementById('error-msg').innerText = '';
        const barcode = barcodeStr.trim();
        if (!barcode) return;

        // Find item
        const item = itemsData.find(i => i.barcode === barcode);
        if (item) {
            isItemVisible[item.product_id] = true;
            
            // Auto increment quantity on scan
            let currentVal = scannedState[item.product_id] || 0;
            scannedState[item.product_id] = currentVal + 1;
            
            const inputField = document.getElementById('input-' + item.product_id);
            if (inputField) {
                inputField.value = scannedState[item.product_id];
            }

            updateItemUI(item.product_id);
            
            // Move item to the top of the list so the latest scan is always visible
            const li = document.getElementById('item-' + item.product_id);
            if (li) {
                li.parentNode.prepend(li);
            }

            beepOk.play().catch(e => {});

            // Jika mode USB/BT, pastikan fokus kembali ke kotak pencarian barcode, BUKAN ke input qty
            if (document.getElementById('btn-usb').classList.contains('active')) {
                setTimeout(() => {
                    const manualInput = document.getElementById('manual-barcode');
                    if (manualInput) {
                        manualInput.focus();
                    }
                }, 50);
            }
        } else {
            document.getElementById('error-msg').innerText = 'Barcode tidak ditemukan di PO ini!';
            beepErr.play().catch(e => {});
            setTimeout(() => {
                document.getElementById('error-msg').innerText = '';
            }, 3000);
        }
    }

    // USB Scanner Mode
    const manualInput = document.getElementById('manual-barcode');
    manualInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleScan(this.value);
            this.value = '';
        }
    });

    // Camera Scanner
    let html5QrcodeScanner;
    function initCamera() {
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 10, 
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdgeSize * 0.75); // 75% of screen
                    return { width: qrboxSize, height: qrboxSize };
                },
                aspectRatio: 1.0,
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
            }, false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function stopCamera() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }
    }

    let lastScanCode = '';
    let lastScanTime = 0;
    function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();
        // Prevent duplicate scans within 2 seconds
        if (decodedText === lastScanCode && (now - lastScanTime) < 2000) {
            return;
        }
        lastScanCode = decodedText;
        lastScanTime = now;
        handleScan(decodedText);
    }

    function onScanFailure(error) {
        // ignore
    }

    function setMode(mode) {
        document.getElementById('btn-camera').className = 'btn-toggle' + (mode === 'camera' ? ' active' : '');
        document.getElementById('btn-usb').className = 'btn-toggle' + (mode === 'usb' ? ' active' : '');
        
        if (mode === 'camera') {
            document.getElementById('camera-section').style.display = 'block';
            document.getElementById('usb-section').style.display = 'none';
            initCamera();
        } else {
            document.getElementById('camera-section').style.display = 'none';
            document.getElementById('usb-section').style.display = 'block';
            stopCamera();
            manualInput.focus();
        }
    }

    function submitCheck() {
        if (confirm('Yakin ingin menyimpan hasil pengecekan gudang ini?')) {
            document.getElementById('scanned_items_input').value = JSON.stringify(scannedState);
            document.getElementById('submit-form').submit();
        }
    }

    // Init
    initList();
    initCamera(); // default mode
</script>
@endsection
