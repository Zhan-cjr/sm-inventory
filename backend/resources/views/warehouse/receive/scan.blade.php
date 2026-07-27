@extends('warehouse.layout')

@section('title', 'Pengecekan PO: ' . $po->po_number)

@section('content')
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .wh-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--card-shadow);
    }
    
    .wh-header-info {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .wh-po-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.82rem;
        margin-bottom: 8px;
    }
    
    .wh-draft-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(99, 102, 241, 0.15);
        color: #6366f1;
        border: 1px solid rgba(99, 102, 241, 0.3);
        padding: 6px 10px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.78rem;
        margin-bottom: 8px;
        margin-left: 6px;
    }
    
    .wh-po-title {
        font-size: 1.3rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.5px;
    }
    
    .wh-po-supplier {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-top: 4px;
        font-weight: 600;
    }
    
    .wh-progress-badge {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        padding: 8px 14px;
        border-radius: 14px;
        text-align: center;
    }
    .wh-progress-val {
        font-weight: 800;
        font-size: 1.1rem;
        color: #10b981;
    }
    .wh-progress-lbl {
        font-size: 0.68rem;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
    }

    /* Segmented Controls */
    .segmented-switch {
        display: flex;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        padding: 4px;
        border-radius: 18px;
        margin-bottom: 16px;
    }
    .btn-mode {
        flex: 1;
        padding: 12px;
        text-align: center;
        background: transparent;
        border: none;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.88rem;
        cursor: pointer;
        color: var(--text-muted);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .btn-mode.active {
        background-color: var(--card-bg);
        color: var(--text-color);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    /* Scanner Container */
    #camera-section {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        border: 2px border-color;
        margin-bottom: 16px;
    }
    #reader {
        width: 100%;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        background: var(--card-bg);
    }
    #reader video {
        object-fit: cover;
        border-radius: 20px;
    }

    .input-barcode {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border-color);
        border-radius: 16px;
        font-size: 0.95rem;
        box-sizing: border-box;
        background-color: var(--card-bg);
        color: var(--text-color);
        font-weight: 600;
        transition: border-color 0.2s;
    }
    .input-barcode:focus {
        outline: none;
        border-color: #10b981;
    }

    /* Item List Cards */
    .item-list {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }
    .item-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    
    .item-card.complete {
        background-color: rgba(16, 185, 129, 0.1) !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }
    .item-card.over {
        background-color: rgba(239, 68, 68, 0.1) !important;
        border-color: rgba(239, 68, 68, 0.4) !important;
    }

    .item-info {
        flex: 1;
        padding-right: 12px;
    }
    .item-name {
        font-weight: 800;
        font-size: 0.95rem;
        line-height: 1.3;
        color: var(--text-color);
        margin-bottom: 4px;
    }
    .item-barcode {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-weight: 600;
        font-family: monospace;
    }
    
    .item-status-tag {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 8px;
        margin-top: 4px;
    }
    .item-card.complete .item-status-tag {
        background: #10b981;
        color: #ffffff;
    }
    .item-card.over .item-status-tag {
        background: #ef4444;
        color: #ffffff;
    }

    .qty-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .btn-qty-step {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-color);
        color: var(--text-color);
        font-weight: 800;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-qty-step:active {
        transform: scale(0.9);
    }
    .input-qty {
        width: 58px;
        padding: 8px 4px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-color);
        color: var(--text-color);
        text-align: center;
        font-weight: 800;
        font-size: 1rem;
    }

    .btn-submit-all {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        border: none;
        border-radius: 18px;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    .btn-submit-all:hover {
        opacity: 0.95;
        transform: translateY(-1px);
    }

    #error-msg {
        color: #ef4444;
        font-size: 0.88rem;
        margin-top: 8px;
        text-align: center;
        font-weight: 700;
    }
</style>

<div>
    <!-- Header Info Card -->
    <div class="wh-card">
        <div class="wh-header-info">
            <div>
                <div style="display: flex; align-items: center; flex-wrap: wrap;">
                    <div class="wh-po-badge">
                        <span>📋 PO RECEIVING</span>
                    </div>
                    <div class="wh-draft-badge" id="draft-saved-indicator" style="display: none;">
                        <span>💾 Draft Otomatis</span>
                    </div>
                </div>
                <h2 class="wh-po-title">{{ $po->po_number }}</h2>
                <div class="wh-po-supplier">Supplier: {{ $po->supplier->name ?? 'Umum' }}</div>
            </div>
            <div class="wh-progress-badge">
                <div class="wh-progress-val" id="completed-count-badge">0 / {{ count($items) }}</div>
                <div class="wh-progress-lbl">SELESAI</div>
            </div>
        </div>
    </div>

    <!-- Mode Switcher -->
    <div class="segmented-switch">
        <button id="btn-camera" class="btn-mode active" onclick="setMode('camera')">
            <span>📷 Kamera HP</span>
        </button>
        <button id="btn-usb" class="btn-mode" onclick="setMode('usb')">
            <span>🔌 Scanner USB/BT</span>
        </button>
    </div>

    <!-- Camera Viewfinder Section -->
    <div id="camera-section">
        <div id="reader"></div>
    </div>

    <!-- USB Scanner Section -->
    <div id="usb-section" style="display: none;" class="wh-card">
        <form onsubmit="event.preventDefault(); submitBarcode();" style="margin:0;">
            <input type="text" inputmode="numeric" pattern="[0-9]*" id="manual-barcode" class="input-barcode" placeholder="Arahkan kursor kesini lalu scan barcode..." autocomplete="off">
        </form>
        <div id="error-msg"></div>
    </div>

    <!-- Items Audit List -->
    <ul class="item-list" id="item-list">
        <!-- Rendered dynamically by JS -->
    </ul>

    <!-- Form Submit Section -->
    <div class="wh-card">
        <form id="submit-form" action="{{ route('warehouse.receive.submit', $po->id) }}" method="POST">
            @csrf
            <input type="hidden" name="scanned_items" id="scanned_items_input">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">CATATAN PENGECEKAN (OPSIONAL)</label>
                <textarea name="notes" oninput="saveLocalDraft()" placeholder="Tuliskan catatan kondisi fisik barang jika ada..." style="width: 100%; padding: 12px; border-radius: 14px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-color); font-family: inherit; resize: none; box-sizing: border-box;" rows="2"></textarea>
            </div>
            <button type="button" class="btn-submit-all" onclick="submitCheck()">SIMPAN RESULT PENGECEKAN</button>
        </form>
    </div>
</div>

<script>
    const itemsData = @json($items);
    const scannedState = {};
    const isItemVisible = {};
    const rejectedCheck = @json($rejectedCheck ?? null);
    const DRAFT_KEY = 'wh_scan_draft_po_{{ $po->id }}';

    itemsData.forEach(item => {
        scannedState[item.product_id] = item.qty_scanned || 0;
        isItemVisible[item.product_id] = item.qty_scanned > 0;
    });

    const beepOk = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
    const beepErr = new Audio('https://assets.mixkit.co/active_storage/sfx/2955/2955-preview.mp3');

    // LOCAL STORAGE DRAFT MANAGEMENT
    function saveLocalDraft() {
        try {
            const notesInput = document.querySelector('textarea[name="notes"]');
            const draftData = {
                scannedState: scannedState,
                isItemVisible: isItemVisible,
                notes: notesInput ? notesInput.value : '',
                timestamp: Date.now()
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));
            
            const badge = document.getElementById('draft-saved-indicator');
            if (badge) badge.style.display = 'inline-flex';
        } catch (e) {
            console.error('Failed to save local draft', e);
        }
    }

    function clearLocalDraft() {
        try {
            localStorage.removeItem(DRAFT_KEY);
            const badge = document.getElementById('draft-saved-indicator');
            if (badge) badge.style.display = 'none';
        } catch (e) {
            console.error('Failed to clear local draft', e);
        }
    }

    function restoreLocalDraft() {
        try {
            const rawDraft = localStorage.getItem(DRAFT_KEY);
            if (rawDraft) {
                const draft = JSON.parse(rawDraft);
                if (draft && draft.scannedState) {
                    let hasRestored = false;
                    Object.keys(draft.scannedState).forEach(pid => {
                        const numericPid = isNaN(pid) ? pid : Number(pid);
                        const val = draft.scannedState[pid];
                        if (val > 0) {
                            scannedState[numericPid] = val;
                            isItemVisible[numericPid] = true;
                            hasRestored = true;
                        }
                    });

                    if (draft.notes) {
                        const notesInput = document.querySelector('textarea[name="notes"]');
                        if (notesInput) notesInput.value = draft.notes;
                    }

                    if (hasRestored) {
                        const badge = document.getElementById('draft-saved-indicator');
                        if (badge) badge.style.display = 'inline-flex';

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Draft Pengecekan Dipulihkan',
                            text: 'Hasil scan sebelum halaman terefresh telah dimuat kembali.',
                            showConfirmButton: false,
                            timer: 3500
                        });
                    }
                }
            }
        } catch (e) {
            console.error('Failed to restore local draft', e);
        }
    }

    function updateCompletedProgress() {
        let completed = 0;
        itemsData.forEach(item => {
            const scanned = scannedState[item.product_id] || 0;
            if (scanned === item.qty_po && item.qty_po > 0) {
                completed++;
            }
        });
        const badge = document.getElementById('completed-count-badge');
        if (badge) badge.innerText = `${completed} / ${itemsData.length}`;
    }

    function initList() {
        const list = document.getElementById('item-list');
        list.innerHTML = '';

        itemsData.forEach(item => {
            const target = item.qty_po;
            const li = document.createElement('li');
            li.id = 'item-' + item.product_id;
            li.className = 'item-card';
            li.style.display = 'none';
            
            let inputAttrs = '';
            if (rejectedCheck) {
                if (item.qty_scanned === target && target > 0) {
                    inputAttrs = 'readonly style="opacity: 0.6; cursor: not-allowed;"';
                }
            }

            li.innerHTML = `
                <div class="item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-barcode">Barcode: ${item.barcode || '-'}</div>
                    <div class="item-status-tag" id="tag-${item.product_id}">PENDING</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="qty-controls">
                        <button type="button" class="btn-qty-step" onclick="changeQtyStep('${item.product_id}', -1)">-</button>
                        <form onsubmit="event.preventDefault(); returnQtyToBarcode('${item.product_id}');" style="margin:0;">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="input-qty" id="input-${item.product_id}" value="${item.qty_scanned > 0 ? item.qty_scanned : ''}" placeholder="0" min="0" ${inputAttrs} onchange="handleManualInput('${item.product_id}', this.value)" onkeydown="handleInputKeydown(event, '${item.product_id}')">
                        </form>
                        <button type="button" class="btn-qty-step" onclick="changeQtyStep('${item.product_id}', 1)">+</button>
                    </div>
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 800;">/ ${target}</span>
                    <button type="button" onclick="confirmDeleteItem('${item.product_id}')" style="background: none; border: none; cursor: pointer; padding: 4px; color: #ef4444;" title="Hapus Item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            `;
            list.appendChild(li);
            updateItemUI(item.product_id);
        });
        
        if (rejectedCheck) {
            document.getElementById('error-msg').innerHTML = `<div style="color: #f59e0b; padding: 10px; font-weight: 700;">⚠️ Pengecekan ini sebelumnya ditolak. Silakan perbaiki kuantitas yang melebihi PO.</div>`;
        }
        updateCompletedProgress();
    }

    function changeQtyStep(productId, delta) {
        let current = scannedState[productId] || 0;
        let next = current + delta;
        if (next < 0) next = 0;
        scannedState[productId] = next;
        
        const inputField = document.getElementById('input-' + productId);
        if (inputField) inputField.value = next > 0 ? next : '';
        
        updateItemUI(productId);
        saveLocalDraft();
    }

    function returnQtyToBarcode(productId) {
        const inputField = document.getElementById('input-' + productId);
        if (inputField) inputField.blur();

        if (document.getElementById('btn-usb').classList.contains('active')) {
            document.getElementById('manual-barcode').focus();
        }
    }

    function handleInputKeydown(event, productId) {
        if (event.key === 'Enter' || event.keyCode === 13) {
            event.preventDefault();
            returnQtyToBarcode(productId);
        }
    }

    function handleManualInput(productId, value) {
        let val = value === '' ? 0 : parseInt(value);
        if (isNaN(val) || val < 0) val = 0;
        scannedState[productId] = val;
        updateItemUI(productId);
        saveLocalDraft();
    }

    window.confirmDeleteItem = function(productId) {
        Swal.fire({
            title: 'Hapus Item?',
            text: "Item ini akan dikembalikan ke status belum discan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                scannedState[productId] = 0;
                isItemVisible[productId] = false;
                
                const inputField = document.getElementById('input-' + productId);
                if (inputField) inputField.value = '';
                
                updateItemUI(productId);
                saveLocalDraft();
                
                if (document.getElementById('btn-usb').classList.contains('active')) {
                    document.getElementById('manual-barcode').focus();
                }
            }
        });
    };

    function updateItemUI(productId) {
        const item = itemsData.find(i => i.product_id === productId || String(i.product_id) === String(productId));
        if (!item) return;

        const scanned = scannedState[productId] || 0;
        const target = item.qty_po;
        const li = document.getElementById('item-' + item.product_id);
        const tag = document.getElementById('tag-' + item.product_id);

        if (li) {
            li.classList.remove('complete', 'over');
            if (tag) {
                if (scanned === target && target > 0) {
                    tag.innerText = '✅ SESUAI ORDER';
                    tag.style.background = '#10b981';
                    tag.style.color = '#ffffff';
                } else if (scanned > target) {
                    tag.innerText = '⚠️ MELEBIHI ORDER';
                    tag.style.background = '#ef4444';
                    tag.style.color = '#ffffff';
                } else {
                    tag.innerText = '⏳ BELUM LENGKAP';
                    tag.style.background = 'rgba(245, 158, 11, 0.2)';
                    tag.style.color = '#f59e0b';
                }
            }

            if (scanned === target && target > 0) li.classList.add('complete');
            if (scanned > target) li.classList.add('over');
            
            if (isItemVisible[item.product_id]) {
                li.style.display = 'flex';
                const inputElem = document.getElementById('input-' + item.product_id);
                if (inputElem && scanned > 0) {
                    inputElem.value = scanned;
                }
            } else {
                li.style.display = 'none';
            }
        }
        updateCompletedProgress();
    }

    function handleScan(barcodeStr) {
        document.getElementById('error-msg').innerText = '';
        const barcode = barcodeStr.trim().toLowerCase();
        if (!barcode) return;

        const item = itemsData.find(i => {
            const bc = (i.barcode || '').toLowerCase();
            const sku = (i.sku || '').toLowerCase();
            let addBc = [];
            if (Array.isArray(i.additional_barcodes)) {
                addBc = i.additional_barcodes.map(b => String(b).trim().toLowerCase());
            } else if (typeof i.additional_barcodes === 'string') {
                addBc = i.additional_barcodes.split(',').map(b => b.trim().toLowerCase());
            }
            return bc === barcode || sku === barcode || addBc.includes(barcode);
        });
        if (item) {
            if (rejectedCheck && item.qty_scanned === item.qty_po && item.qty_po > 0) {
                document.getElementById('error-msg').innerText = 'Barang ini sudah sesuai pada pengecekan sebelumnya!';
                beepErr.play().catch(e => {});
                setTimeout(() => {
                    document.getElementById('error-msg').innerText = '';
                }, 3000);
                return;
            }

            isItemVisible[item.product_id] = true;
            
            const li = document.getElementById('item-' + item.product_id);
            if (li) {
                li.parentNode.prepend(li);
            }

            if (!scannedState[item.product_id] || scannedState[item.product_id] === 0) {
                scannedState[item.product_id] = 1;
            }

            updateItemUI(item.product_id);
            saveLocalDraft();
            beepOk.play().catch(e => {});

            setTimeout(() => {
                const inputField = document.getElementById('input-' + item.product_id);
                if (inputField) {
                    inputField.value = scannedState[item.product_id];
                    inputField.focus();
                    inputField.select();
                }
            }, 50);
        } else {
            document.getElementById('error-msg').innerText = 'Barcode tidak ditemukan di PO ini!';
            beepErr.play().catch(e => {});
            setTimeout(() => {
                document.getElementById('error-msg').innerText = '';
            }, 3000);
        }
    }

    function submitBarcode() {
        const manualInput = document.getElementById('manual-barcode');
        handleScan(manualInput.value);
        manualInput.value = '';
    }
    
    const manualInput = document.getElementById('manual-barcode');
    manualInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            submitBarcode();
        }
    });

    let html5QrcodeScanner;
    function initCamera() {
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
                fps: 10, 
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdgeSize * 0.75);
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
        document.getElementById('btn-camera').className = 'btn-mode' + (mode === 'camera' ? ' active' : '');
        document.getElementById('btn-usb').className = 'btn-mode' + (mode === 'usb' ? ' active' : '');
        
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
        itemsData.forEach(item => {
            const inputField = document.getElementById('input-' + item.product_id);
            if (inputField && isItemVisible[item.product_id]) {
                let val = inputField.value === '' ? 0 : parseInt(inputField.value);
                if (isNaN(val) || val < 0) val = 0;
                scannedState[item.product_id] = val;
            }
        });

        Swal.fire({
            title: 'Simpan Pengecekan?',
            text: "Pastikan semua kuantitas barang sudah sesuai dengan fisik di gudang.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                // Clear draft on successful submit
                clearLocalDraft();
                document.getElementById('scanned_items_input').value = JSON.stringify(scannedState);
                document.getElementById('submit-form').submit();
            }
        });
    }

    // Init Sequence
    initList();
    restoreLocalDraft();
    itemsData.forEach(item => updateItemUI(item.product_id));
    initCamera();
</script>
@endsection
