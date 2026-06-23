@extends('warehouse.layout')

@section('title', 'Pengecekan Penerimaan Gudang')

@section('content')
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f4f6;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        background: var(--card-bg);
        min-height: 100vh;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    h1 {
        font-size: 1.5rem;
        color: var(--text-color);
        margin-bottom: 20px;
        text-align: center;
    }
    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    .alert-danger {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #f87171;
    }
    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #34d399;
    }
    .alert-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--text-color);
    }
    button[type="submit"] {
        width: 100%;
        padding: 12px;
        background-color: var(--header-bg);
        color: var(--header-text);
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        margin-top: 10px;
        transition: background-color 0.2s;
    }
    button:hover {
        background-color: #1d4ed8;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<div class="container">
    <h1>Cek Penerimaan Gudang</h1>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <form action="{{ route('warehouse.receive.search') }}" method="POST" id="search-form">
        @csrf
        @if(isset($branches) && count($branches) > 0)
        <div class="form-group">
            <label for="branch_id">Cabang</label>
            <select id="branch_id" name="branch_id" class="form-control" onchange="window.location.href='?branch_id='+this.value">
                <option value="">-- Semua Cabang --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id" class="form-control">
                <option value="">-- Pilih Supplier --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="display: flex; align-items: center; margin-bottom: 15px;">
            <input type="checkbox" id="last_po_check" style="margin-right: 8px;" onchange="handleLastPOCheck()">
            <label for="last_po_check" style="margin-bottom: 0; font-weight: normal; font-size: 0.9rem;">Gunakan PO Terakhir dari Supplier ini</label>
        </div>

        <div class="form-group">
            <label for="po_number">Nomor Purchase Order (PO)</label>
            <select id="po_number" name="po_number" class="form-control" required>
                <option value="">-- Pilih PO --</option>
            </select>
        </div>
        <button type="submit">Lanjut Pengecekan</button>
    </form>
</div>

<style>
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        box-sizing: border-box;
        background-color: var(--card-bg);
        color: var(--text-color);
    }
</style>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        const purchaseOrders = @json($purchaseOrders);
        const lastPoCheck = document.getElementById('last_po_check');
        
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
                    // Since ordered by created_at desc, the first is the latest
                    poTomSelect.setValue(filteredPOs[0].po_number);
                }
            }
        }
    </script>
@endsection
