@extends('warehouse.layout')

@section('title', 'Pengecekan Penerimaan Gudang')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<style>
    .wh-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--card-shadow);
    }
    .wh-hero-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #10b981;
        margin-bottom: 16px;
    }
    .wh-hero-title {
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0 0 6px 0;
        letter-spacing: -0.5px;
    }
    .wh-hero-sub {
        color: var(--text-muted);
        font-size: 0.88rem;
        margin: 0;
        line-height: 1.5;
    }
    .form-group {
        margin-bottom: 18px;
    }
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--text-color);
        letter-spacing: 0.2px;
    }

    /* Locked Branch Card Styling */
    .wh-branch-locked-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 16px;
        color: var(--text-color);
    }

    .wh-checkbox-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: rgba(16, 185, 129, 0.06);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 16px;
        margin-bottom: 20px;
        cursor: pointer;
    }
    .wh-checkbox-card input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #10b981;
        cursor: pointer;
    }
    .wh-checkbox-label {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-color);
        cursor: pointer;
    }
    .btn-action-submit {
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-action-submit:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 12px 25px rgba(16, 185, 129, 0.4);
    }
    .btn-action-submit:active {
        transform: scale(0.98);
    }
    .alert-banner {
        padding: 14px 18px;
        border-radius: 16px;
        margin-bottom: 20px;
        font-size: 0.88rem;
        font-weight: 600;
    }
    .alert-danger {
        background-color: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .alert-success {
        background-color: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .alert-warning {
        background-color: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* TomSelect Custom Dark / Light Overrides */
    .ts-control {
        border-radius: 14px !important;
        padding: 12px 16px !important;
        background-color: var(--card-bg) !important;
        color: var(--text-color) !important;
        border-color: var(--border-color) !important;
        font-family: inherit !important;
        font-size: 0.95rem !important;
    }
    .ts-dropdown {
        border-radius: 16px !important;
        background-color: var(--card-bg) !important;
        color: var(--text-color) !important;
        border-color: var(--border-color) !important;
        overflow: hidden !important;
        box-shadow: var(--card-shadow) !important;
    }
    .ts-dropdown .option {
        padding: 12px 16px !important;
    }
    .ts-dropdown .active {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #10b981 !important;
    }
    html.dark .ts-control input {
        color: #ffffff !important;
    }
</style>

<div>
    <!-- Hero Card Header -->
    <div class="wh-card">
        <div class="wh-hero-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <h1 class="wh-hero-title">Cek Penerimaan Barang (PO)</h1>
        <p class="wh-hero-sub">Pilih lokasi cabang, supplier, dan nomor PO untuk melakukan pengecekan fisik barang di gudang secara akurat.</p>
    </div>

    <!-- Alert Notifications -->
    @if(session('error'))
        <div class="alert-banner alert-danger">⚠️ {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert-banner alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert-banner alert-warning">🔔 {{ session('warning') }}</div>
    @endif

    <!-- Form Container -->
    <div class="wh-card">
        <form action="{{ route('warehouse.receive.search') }}" method="POST" id="search-form">
            @csrf

            <!-- User Assigned Branch vs Superadmin Branch Selector -->
            @if(auth()->user() && auth()->user()->branch_id)
                <div class="form-group">
                    <label class="form-label">CABANG GUDANG (TERSETEL)</label>
                    <div class="wh-branch-locked-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#10b981">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0v-4a1 1 0 011-1h2a1 1 0 011 1v4m-4 0h4" />
                        </svg>
                        <span style="font-weight: 800; font-size: 1rem;">{{ auth()->user()->branch->name ?? 'Cabang Terdaftar' }}</span>
                        <span style="margin-left: auto; background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.72rem; font-weight: 800; padding: 4px 10px; border-radius: 99px;">OTOMATIS</span>
                    </div>
                </div>
            @elseif(isset($branches) && count($branches) > 0)
                <div class="form-group">
                    <label for="branch_id" class="form-label">CABANG GUDANG</label>
                    <select id="branch_id" name="branch_id" class="form-control">
                        <option value="">-- Semua Cabang --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label for="supplier_id" class="form-label">SUPPLIER / VENDOR</label>
                <select id="supplier_id" name="supplier_id" class="form-control">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quick Auto-Select Checkbox -->
            <label class="wh-checkbox-card">
                <input type="checkbox" id="last_po_check" onchange="handleLastPOCheck()">
                <span class="wh-checkbox-label">Otomatis Pilih PO Terakhir dari Supplier Ini</span>
            </label>

            <div class="form-group">
                <label for="po_number" class="form-label">NOMOR PURCHASE ORDER (PO)</label>
                <select id="po_number" name="po_number" class="form-control" required>
                    <option value="">-- Pilih PO --</option>
                </select>
            </div>

            <button type="submit" class="btn-action-submit">
                <span>LANJUT PENGECEKAN FISIK</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    const purchaseOrders = @json($purchaseOrders);
    const lastPoCheck = document.getElementById('last_po_check');
    
    // Init TomSelect for Branch if present
    const branchElem = document.getElementById('branch_id');
    if (branchElem) {
        let branchTomSelect = new TomSelect("#branch_id", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        branchTomSelect.on('change', function(value) {
            window.location.href = '?branch_id=' + value;
        });
    }

    let supplierTomSelect = new TomSelect("#supplier_id", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    let poTomSelect = new TomSelect("#po_number", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    supplierTomSelect.on('change', function(value) {
        filterPOs(value);
    });

    function filterPOs(supplierId) {
        poTomSelect.clear();
        poTomSelect.clearOptions();
        poTomSelect.addOption({value: '', text: '-- Pilih PO --'});

        if (!supplierId) {
            lastPoCheck.checked = false;
            return;
        }

        const filteredPOs = purchaseOrders.filter(po => po.supplier_id === supplierId);
        
        filteredPOs.forEach(po => {
            poTomSelect.addOption({value: po.po_number, text: po.po_number});
        });

        handleLastPOCheck();
    }

    function handleLastPOCheck() {
        if (lastPoCheck.checked) {
            const supplierId = supplierTomSelect.getValue();
            if (!supplierId) {
                alert('Pilih supplier terlebih dahulu!');
                lastPoCheck.checked = false;
                return;
            }
            
            const filteredPOs = purchaseOrders.filter(po => po.supplier_id === supplierId);
            if (filteredPOs.length > 0) {
                poTomSelect.setValue(filteredPOs[0].po_number);
            }
        }
    }
</script>
@endsection
