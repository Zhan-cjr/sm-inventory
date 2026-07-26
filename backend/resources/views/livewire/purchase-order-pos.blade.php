<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden font-sans text-sm"
     x-data="{
        focusRowQty(index) {
            setTimeout(() =>
 {
                let el = document.getElementById('qty-' + index);
                if (el) { el.focus(); el.select(); }
            }, 100);
        },
        handleGridNav(event) {
            let current = event.target;
            if (!current.classList.contains('pos-grid-input')) return;
            
            let key = event.key;
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(key)) {
                let tr = current.closest('tr');
                let tbody = tr.closest('tbody');
                if (!tr || !tbody) return;
                
                let rowInputs = Array.from(tr.querySelectorAll('.pos-grid-input:not([disabled])'));
                let colIndex = rowInputs.indexOf(current);
                if (colIndex === -1) return;
                
                let rows = Array.from(tbody.querySelectorAll('tr'));
                let rowIndex = rows.indexOf(tr);
                
                let target = null;
                
                if (key === 'ArrowRight' || key === 'Enter') {
                    event.preventDefault();
                    if (colIndex < rowInputs.length - 1) {
                        target = rowInputs[colIndex + 1];
                    } else if (key === 'Enter') {
                        target = document.getElementById('search-input');
                    }
                } else if (key === 'ArrowLeft') {
                    event.preventDefault();
                    if (colIndex > 0) {
                        target = rowInputs[colIndex - 1];
                    }
                } else if (key === 'ArrowUp') {
                    event.preventDefault();
                    if (rowIndex > 0) {
                        let prevRowInputs = Array.from(rows[rowIndex - 1].querySelectorAll('.pos-grid-input:not([disabled])'));
                        if (prevRowInputs.length > colIndex) target = prevRowInputs[colIndex];
                    }
                } else if (key === 'ArrowDown') {
                    event.preventDefault();
                    if (rowIndex < rows.length - 1) {
                        let nextRowInputs = Array.from(rows[rowIndex + 1].querySelectorAll('.pos-grid-input:not([disabled])'));
                        if (nextRowInputs.length > colIndex) target = nextRowInputs[colIndex];
                    }
                }
                
                if (target) {
                    target.focus();
                    if (typeof target.select === 'function') target.select();
                }
            }
        }
     }"
     @item-added.window="focusRowQty($event.detail.index)">
    <style>
        .pos-input { padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; width: 100%; border-radius: 0.375rem; background-color: #f9fafb; font-size: 0.875rem; }
        .pos-grid-input { padding: 0.35rem 0.4rem !important; font-size: 0.85rem !important; font-weight: 600 !important; width: 100% !important; }
        .pos-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; }
        .pos-label { font-weight: 500; color: #4b5563; font-size: 0.875rem; padding-right: 0.5rem; }
        .pos-grid-th { background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;}
        .pos-grid-td { border-bottom: 1px solid #f3f4f6; padding: 0.5rem; background-color: white; font-size: 0.875rem; color: #1f2937; }
        
        .dark .pos-input { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .pos-input:focus { border-color: #60a5fa !important; box-shadow: 0 0 0 1px #60a5fa !important; }
        .dark .pos-label { color: #9ca3af !important; }
        .dark .pos-grid-th { background-color: #111827 !important; border-color: #374151 !important; color: #d1d5db !important; }
        .dark .pos-grid-td { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .bg-white { background-color: #1f2937 !important; }
        .dark .empty-state { color: #9ca3af !important; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .flex-end { display: flex; justify-content: flex-end; align-items: center; }

        /* Hide number spin buttons */
        input[type=number].pos-input::-webkit-outer-spin-button,
        input[type=number].pos-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number].pos-input {
            -moz-appearance: textfield;
        }

        .search-result-item { border-left: 4px solid transparent; transition: all 0.1s ease-in-out; }
        .highlighted-item { background-color: #dbeafe !important; border-left: 4px solid #2563eb !important; }
        .dark .highlighted-item { background-color: #374151 !important; border-left: 4px solid #60a5fa !important; }
    </style>

    <!-- Top Section (Order & Supplier) -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Order Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: #111827;" class="dark:text-gray-200">Detail Pemesanan</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">No Nota</td>
                        <td><input type="text" class="pos-input" style="background-color: #e5e7eb; cursor: not-allowed;" readonly wire:model="po_number"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tanggal</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model.live="po_date"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tgl Kedaluwarsa</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model="expired_date" @if($purchaseOrder) disabled style="background-color: #e5e7eb; cursor: not-allowed;" @endif></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Faktur</td>
                        <td><input onfocus="this.select()" type="text" class="pos-input" wire:model="faktur"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Lokasi Cabang</td>
                        <td>
                            <select class="pos-input" wire:model="branch_id" @if(auth()->user()->branch_id) disabled @endif>
                                <option value="">-- Pusat / Global (Semua Cabang) --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Catatan</td>
                        <td><input onfocus="this.select()" type="text" class="pos-input" wire:model="notes" placeholder="Catatan tambahan..."></td>
                    </tr>
                </table>
            </div>

            <!-- Supplier Panel -->
            <div>
                <div class="flex-between" style="margin-bottom: 0.5rem;">
                    <div style="font-weight: 600; font-size: 1rem; color: #111827;" class="dark:text-gray-200">Detail Pemasok</div>
                    @if($supplier_id)
                    <div x-data="{ openSaran: false }" style="position: relative;">
                        <button @click="openSaran = !openSaran" 
                                class="pos-input" 
                                style="display: flex; align-items: center; gap: 0.5rem; background: #3b82f6; color: white; border: none; cursor: pointer; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3.5a.5.5 0 0 1-.5-.5v-4A.5.5 0 0 1 8 4z"/></svg>
                            Saran Order
                            <span wire:loading wire:target="applySaranOrder" class="animate-spin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </span>
                        </button>
                        <div x-show="openSaran" @click.away="openSaran = false" 
                             style="position: absolute; right: 0; top: 100%; z-index: 50; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.5rem; width: 16rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" 
                             class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                            <button wire:click="applySaranOrder('minmax')" @click="openSaran = false" class="w-full text-left p-2 hover:bg-blue-50 dark:hover:bg-gray-700 rounded transition-colors group">
                                <div class="font-bold text-gray-800 dark:text-gray-200 group-hover:text-blue-700">Metode Min/Max</div>
                                <div class="text-[10px] text-gray-500">Order jika stok < min (Qty = max - stok)</div>
                            </button>
                            <div style="height: 1px; background: #f3f4f6; margin: 0.25rem 0;" class="dark:bg-gray-700"></div>
                            <button wire:click="applySaranOrder('sales')" @click="openSaran = false" class="w-full text-left p-2 hover:bg-blue-50 dark:hover:bg-gray-700 rounded transition-colors group">
                                <div class="font-bold text-gray-800 dark:text-gray-200 group-hover:text-blue-700">Trend Penjualan (14 Hari)</div>
                                <div class="text-[10px] text-gray-500">Berdasarkan rata harian 30 hari terakhir</div>
                            </button>
                        </div>
                    </div>
                    @else
                    <button class="pos-input" style="display: flex; align-items: center; gap: 0.5rem; background: #9ca3af; color: white; border: none; cursor: not-allowed; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 600;" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3.5a.5.5 0 0 1-.5-.5v-4A.5.5 0 0 1 8 4z"/></svg>
                        Saran Order
                    </button>
                    @endif
                </div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Pemasok</td>
                        <td>
                            <div x-data="{ 
                                open: false, 
                                search: '', 
                                suppliers: @js($suppliers),
                                get filteredSuppliers() {
                                    if (this.search === '') return this.suppliers;
                                    return this.suppliers.filter(s => 
                                        (s.name || '').toLowerCase().includes(this.search.toLowerCase()) || 
                                        (s.code || '').toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                selectSupplier(id) {
                                    @this.set('supplier_id', id);
                                    this.open = false;
                                    this.search = '';
                                }
                            }" style="position: relative;">
                                <button type="button" @click="open = !open" 
                                        class="pos-input flex-between bg-white dark:bg-gray-800" 
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%; cursor: pointer;">
                                    <span>{{ $supplier_id ? (collect($suppliers)->firstWhere('id', $supplier_id)['code'] . ' - ' . collect($suppliers)->firstWhere('id', $supplier_id)['name']) : 'Pilih Supplier...' }}</span>
                                    <svg style="width: 1rem; height: 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     style="position: absolute; left: 0; z-index: 50; margin-top: 0.25rem;  border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 15rem; overflow-y: auto; width: 100%; min-width: 300px; max-width: 500px;" 
                                     class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                                    <div style="position: sticky; top: 0; padding: 0.5rem; background: white; border-bottom: 1px solid #f3f4f6;" class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                                        <input onfocus="this.select()" type="text" x-model="search" class="pos-input" placeholder="Cari supplier..." autofocus @keydown.escape="open = false">
                                    </div>
                                    <template x-for="s in filteredSuppliers" :key="s.id">
                                        <div @click="selectSupplier(s.id)" 
                                             style="padding: 0.5rem; cursor: pointer; border-bottom: 1px solid #f9fafb;"
                                             class="hover:bg-blue-50 dark:hover:bg-gray-700 dark:border-gray-700"
                                             :style="s.id == '{{ $supplier_id }}' ? 'background-color: #eff6ff;' : ''">
                                            <div style="font-weight: 700; color: #1f2937;" class="dark:text-gray-200" x-text="s.code"></div>
                                            <div style="font-size: 0.75rem; color: #6b7280;" class="dark:text-gray-400" x-text="s.name"></div>
                                        </div>
                                    </template>
                                    <div x-show="filteredSuppliers.length === 0" style="padding: 1rem; text-align: center; color: #9ca3af; font-style: italic;">
                                        Supplier tidak ditemukan.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @php
                        $selectedSupplier = collect($suppliers)->firstWhere('id', $supplier_id);
                    @endphp
                    <tr>
                        <td class="pos-label">Nama Kontak</td>
                        <td><div class="pos-input" style="background-color: transparent; border-color: transparent; padding-left: 0;">{{ $selectedSupplier ? $selectedSupplier->name : '-' }}</div></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Alamat</td>
                        <td><div class="pos-input" style="background-color: transparent; border-color: transparent; padding-left: 0;">{{ $selectedSupplier ? $selectedSupplier->address : '-' }}</div></td>
                    </tr>
                    <tr>
                        <td class="pos-label">No Telp</td>
                        <td><div class="pos-input" style="background-color: transparent; border-color: transparent; padding-left: 0;">{{ $selectedSupplier ? $selectedSupplier->phone : '-' }}</div></td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    <!-- Barcode Scanner Input -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem;" class="bg-white dark:bg-gray-900 dark:border-gray-800">
        <div style="font-weight: 600; white-space: nowrap; color: #374151;" class="dark:text-gray-300">Cari / Scan Produk</div>
        <div style="flex: 1; position: relative;"
             x-data="{ 
                highlightedIndex: -1,
                updateHighlight() {
                    let items = document.querySelectorAll('.search-result-item');
                    items.forEach((item, idx) => {
                        if (idx === this.highlightedIndex) {
                            item.classList.add('highlighted-item');
                        } else {
                            item.classList.remove('highlighted-item');
                        }
                    });
                },
                moveDown() {
                    let items = document.querySelectorAll('.search-result-item');
                    let max = items.length - 1;
                    if (this.highlightedIndex < max) {
                        this.highlightedIndex++;
                        this.updateHighlight();
                        this.scrollToHighlighted();
                    }
                },
                moveUp() {
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--;
                        this.updateHighlight();
                        this.scrollToHighlighted();
                    }
                },
                scrollToHighlighted() {
                    this.$nextTick(() => {
                        let items = document.querySelectorAll('.search-result-item');
                        if (items[this.highlightedIndex]) {
                            items[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
                        }
                    });
                },
                selectCurrent() {
                    if (this.highlightedIndex >= 0) {
                        let items = document.querySelectorAll('.search-result-item');
                        if (items[this.highlightedIndex]) {
                            items[this.highlightedIndex].click();
                        }
                    } else {
                        @this.call('searchProduct');
                    }
                    this.highlightedIndex = -1;
                }
             }">
            @php
                $isSearchDisabled = empty($branch_id);
                $searchDisabledReason = 'Pilih Lokasi Cabang terlebih dahulu.';
            @endphp
            <input onfocus="this.select()" type="text" id="search-input" class="pos-input" style="font-size: 1rem; padding: 0.5rem 1rem; {{ $isSearchDisabled ? 'background-color: #f3f4f6; cursor: not-allowed;' : '' }}"
                   placeholder="{{ $isSearchDisabled ? $searchDisabledReason : 'Mulai ketik nama produk, SKU, atau scan barcode... lalu tekan Enter' }}"
                   {{ $isSearchDisabled ? 'disabled' : '' }}
                   wire:model.live.debounce.300ms="searchQuery"
                   @input="highlightedIndex = -1; $nextTick(() => updateHighlight())"
                   @keydown.arrow-down.prevent="moveDown()"
                   @keydown.arrow-up.prevent="moveUp()"
                   @keydown.enter.prevent="selectCurrent()"
                   autofocus>

            <!-- Interactive Search Dropdown -->
            @if(count($searchResults) > 0)
            <div style="position: absolute; left: 0; right: 0; top: 100%; z-index: 100;  border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-top: 0.25rem; max-height: 300px; overflow-y: auto;" class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                @foreach($searchResults as $index => $result)
                <div wire:click="selectProduct('{{ $result->id }}')" 
                     class="search-result-item hover:bg-blue-50 dark:hover:bg-gray-700 dark:border-gray-700"
                     style="padding: 0.75rem; cursor: pointer; border-bottom: 1px solid #f9fafb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #1f2937;" class="dark:text-gray-200">{{ $result->sku }}</div>
                        <div style="font-size: 0.75rem; color: #6b7280;" class="dark:text-gray-400">{{ $result->name }}</div>
                    </div>
                    <div style="font-weight: 600; color: #3b82f6;">Rp {{ number_format($result->cost_price_tax > 0 ? $result->cost_price_tax : $result->cost_price, 0) }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div x-data="{ open: false }" style="position: relative;">
            <button @click="open = !open" class="pos-input bg-white dark:bg-gray-800" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/></svg>
                Pilih Kolom
            </button>
            <div x-show="open" @click.away="open = false" style="position: absolute; right: 0; top: 100%; z-index: 50; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.5rem; width: 12rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                @foreach(['sku' => 'SKU', 'barcode' => 'Barcode', 'name' => 'Nama Produk', 'avg_bln' => 'Sales 30 HR', 'avg_minggu' => 'Sales 7 HR', 'stock' => 'Stok', 'min_qty' => 'Min Qty', 'max_qty' => 'Max Qty', 'qty_saran' => 'Qty Saran', 'qty' => 'Qty Pesan', 'unit_cost' => 'Harga Satuan', 'discount_1' => 'Dis1', 'discount_2' => 'Dis2', 'discount_3' => 'Dis3'] as $key => $label)
                    <label class="flex items-center gap-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer rounded">
                        <input type="checkbox" wire:model.live="visibleColumns" value="{{ $key }}" class="rounded text-blue-600">
                        <span class="text-xs">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Data Grid -->
    <div style="flex: 1; overflow-y: auto;" class="bg-white dark:bg-gray-900">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <tr>
                    <th class="pos-grid-th" style="width: 2.5rem; text-align: center;">No</th>
                    @if(in_array('sku', $visibleColumns)) <th class="pos-grid-th" style="width: 8.5rem;">SKU</th> @endif
                    @if(in_array('barcode', $visibleColumns)) <th class="pos-grid-th" style="width: 9.5rem;">Barcode</th> @endif
                    @if(in_array('name', $visibleColumns)) <th class="pos-grid-th" style="min-width: 20rem; width: 32%;">Nama Produk</th> @endif
                    @if(in_array('avg_bln', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Sales 30 HR</th> @endif
                    @if(in_array('avg_minggu', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Sales 7 HR</th> @endif
                    @if(in_array('stock', $visibleColumns)) <th class="pos-grid-th" style="width: 4.5rem; text-align: right;">Stok</th> @endif
                    @if(in_array('min_qty', $visibleColumns)) <th class="pos-grid-th" style="width: 4rem; text-align: right;">Min</th> @endif
                    @if(in_array('max_qty', $visibleColumns)) <th class="pos-grid-th" style="width: 4rem; text-align: right;">Max</th> @endif
                    @if(in_array('qty_saran', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right; background-color: #e0f2fe; color: #0369a1;">Qty Saran</th> @endif
                    @if(in_array('qty', $visibleColumns)) <th class="pos-grid-th" style="width: 5.5rem; text-align: right;">Qty Pesan</th> @endif
                    @if(in_array('unit_cost', $visibleColumns)) <th class="pos-grid-th" style="width: 11rem; min-width: 11rem; text-align: right;">Harga Satuan</th> @endif
                    @if(in_array('discount_1', $visibleColumns)) <th class="pos-grid-th" style="width: 4.5rem; text-align: right;">Dis1 (%)</th> @endif
                    @if(in_array('discount_2', $visibleColumns)) <th class="pos-grid-th" style="width: 4.5rem; text-align: right;">Dis2 (%)</th> @endif
                    @if(in_array('discount_3', $visibleColumns)) <th class="pos-grid-th" style="width: 4.5rem; text-align: right;">Dis3 (%)</th> @endif
                    <th class="pos-grid-th" style="width: 11rem; min-width: 11rem; text-align: right;">Total</th>
                    <th class="pos-grid-th" style="width: 2.5rem; text-align: center;"></th>
                </tr>
            </thead>
            <tbody @keydown="handleGridNav($event)">
                @forelse($cart as $index => $item)
                    <tr>
                        <td class="pos-grid-td" style="text-align: center;">{{ $loop->iteration }}</td>
                        @if(in_array('sku', $visibleColumns)) <td class="pos-grid-td">{{ $item['sku'] }}</td> @endif
                        @if(in_array('barcode', $visibleColumns)) <td class="pos-grid-td">{{ $item['barcode'] }}</td> @endif
                        @if(in_array('name', $visibleColumns)) 
                            <td class="pos-grid-td" style="font-weight: 500; min-width: 20rem;">
                                <div style="white-space: normal; word-break: break-word; line-height: 1.35;">
                                    {{ $item['name'] }}
                                </div>
                            </td> 
                        @endif
                        @if(in_array('avg_bln', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #8b5cf6;">{{ $item['avg_bln'] }}</td> @endif
                        @if(in_array('avg_minggu', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #a855f7;">{{ $item['avg_minggu'] }}</td> @endif
                        @if(in_array('stock', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #6b7280;">{{ $item['stock'] }}</td> @endif
                        @if(in_array('min_qty', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #ef4444;">{{ $item['min_qty'] }}</td> @endif
                        @if(in_array('max_qty', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #10b981;">{{ $item['max_qty'] }}</td> @endif
                        @if(in_array('qty_saran', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; font-weight: bold; color: #0284c7; background-color: #f0f9ff;">{{ $item['qty_saran'] }}</td> @endif
                        @if(in_array('qty', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="qty-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.qty"
                                   wire:change="recalculateRow({{ $index }})">
                        </td>
                        @endif
                        @if(in_array('unit_cost', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem; min-width: 11rem;">
                            <div x-data="{ raw: @entangle('cart.' . $index . '.unit_cost'), focused: false, get display() { if (this.focused) return this.raw; let rawStr = (this.raw || 0).toString(); let num = parseFloat(rawStr.replace(/,/g, '')); return isNaN(num) ? '' : num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }, set display(val) { this.raw = (val || '').toString().replace(/,/g, ''); $wire.recalculateRow({{ $index }}); } }">
                                <input type="text" x-model.lazy="display" @focus="focused = true; $nextTick(() => $el.select())" @blur="focused = false" id="cost-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;">
                            </div>
                        </td>
                        @endif
                        @if(in_array('discount_1', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis1-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_1"
                                   wire:change="recalculateRow({{ $index }})">
                        </td>
                        @endif
                        @if(in_array('discount_2', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis2-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_2"
                                   wire:change="recalculateRow({{ $index }})">
                        </td>
                        @endif
                        @if(in_array('discount_3', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis3-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_3"
                                   wire:change="recalculateRow({{ $index }})">
                        </td>
                        @endif
                        <td class="pos-grid-td" style="text-align: right; font-weight: 600; color: #111827; min-width: 11rem; white-space: nowrap;" class="dark:text-gray-100">
                            {{ number_format($item['subtotal'], 2) }}
                        </td>
                        <td class="pos-grid-td" style="text-align: center;">
                            <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 transition-colors" title="Hapus Barang">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;">
                            Keranjang kosong. Silakan gunakan kotak pencarian di atas untuk menambahkan produk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Summary & Actions -->
    <div style="padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;" class="bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
        
        <!-- Actions Button -->
        <div style="display: flex; gap: 0.5rem; align-items: flex-end; margin-right: auto;">
            <button wire:click="save" style="background-color: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm1 2h7.086L12 4.914V13H3V3z"/><path d="M4 4h5v2H4V4zm0 5h8v4H4V9z"/></svg>
                SIMPAN
            </button>
            <a href="{{ route('filament.admin.resources.purchase-orders.index') }}" style="background-color: #fff; color: #374151; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; font-size: 0.875rem; border: 1px solid #d1d5db; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                Batal
            </a>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 ml-4 cursor-pointer">
                <input type="checkbox" wire:model="cetak_nota" class="rounded text-blue-600">
                Cetak Nota setelah simpan
            </label>
        </div>
        
        <!-- Totals -->
        <div style="display: flex; align-items: center; gap: 2rem;">

            <div style="text-align: right;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Total Baris / Qty</div>
                <div style="font-weight: 600; font-size: 1rem; color: #374151;" class="dark:text-gray-200">{{ $totalLines }} Baris / {{ number_format($totalQty, 0) }} Qty</div>
            </div>

            <div style="text-align: right; min-width: 8rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Subtotal Gross</div>
                <div style="font-weight: 600; font-size: 1rem; color: #374151;" class="dark:text-gray-200">Rp {{ number_format($subtotal, 2) }}</div>
            </div>

            <div style="text-align: right; min-width: 10rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Disc. Subtotal</div>
                <div class="flex items-center justify-end gap-1">
                    <select wire:model.live="discount_subtotal_type" class="pos-input" style="width: 4rem; padding: 0.125rem 0.25rem;">
                        <option value="nominal">Rp</option>
                        <option value="percent">%</option>
                    </select>
                    <input onfocus="this.select()" type="number" wire:model.live="discount_subtotal" class="pos-input" style="width: 6rem; padding: 0.125rem 0.25rem; text-align: right;">
                </div>
            </div>



            <div style="text-align: right; min-width: 10rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Grand Total</div>
                <div style="font-weight: 700; font-size: 1.5rem; color: #047857;" class="dark:text-emerald-400">Rp {{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
    </div>

@if($showZeroQtyModal)
    <div class="pos-modal-overlay">
        <div class="pos-modal-card">
            <div style="display: flex; align-items: flex-start; gap: 1rem; flex-shrink: 0;">
                <div class="pos-modal-icon-bg">
                    <svg style="width: 24px; height: 24px; min-width: 24px; min-height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div style="flex: 1 1 0%;">
                    <h3 class="pos-modal-title">Kuantitas masih 0</h3>
                    <p class="pos-modal-body" style="margin-top: 0.25rem;">
                        Terdapat produk dengan kuantitas 0 (kosong) di keranjang. Anda dapat menghapus semua produk tersebut atau membatalkan untuk mengedit.
                    </p>
                </div>
            </div>

            <div class="pos-modal-list-container">
                <div style="font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.5rem;" class="dark:text-gray-400">
                    Daftar Produk (Qty = 0):
                </div>
                <div class="pos-modal-list">
                    @php
                        $productsArray = array_filter(array_map('trim', explode(',', $zeroQtyProductNames)));
                    @endphp
                    <ul style="margin: 0; padding-left: 1.25rem; list-style-type: disc;">
                        @foreach($productsArray as $prodName)
                            <li style="margin-bottom: 0.25rem; word-break: break-word;">{{ $prodName }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="pos-modal-footer">
                <button wire:click="$set('showZeroQtyModal', false)" type="button" class="pos-btn-cancel">
                    Cancel
                </button>
                <button wire:click="removeZeroQtyItems" type="button" class="pos-btn-danger">
                    Hapus Semua
                </button>
            </div>
        </div>
    </div>
    @endif

<style>
    .pos-dropdown-bg { background-color: #ffffff !important; }
    .dark .pos-dropdown-bg { background-color: #1f2937 !important; border-color: #374151 !important; }
    .dark .pos-dropdown-bg .dark\:text-gray-200 { color: #e5e7eb !important; }
    .dark .pos-dropdown-bg .dark\:text-gray-400 { color: #9ca3af !important; }

    /* Modal Theme Styles */
    .pos-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 999999;
        display: flex; align-items: center; justify-content: center;
        background-color: rgba(0, 0, 0, 0.4) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        filter: none !important;
        padding: 1rem;
    }
    .pos-modal-card {
        background-color: #ffffff; border-radius: 0.75rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        border: 1px solid #e5e7eb; max-width: 32rem; width: 100%; max-height: 85vh; padding: 1.5rem; color: #1f2937;
        display: flex; flex-direction: column; gap: 1rem;
    }
    .dark .pos-modal-card {
        background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important;
    }
    .pos-modal-icon-bg {
        padding: 0.75rem; background-color: #fef3c7; color: #d97706; border-radius: 9999px;
        flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem;
    }
    .dark .pos-modal-icon-bg {
        background-color: rgba(217, 119, 6, 0.2) !important; color: #fbbf24 !important;
    }
    .pos-modal-title { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0; }
    .dark .pos-modal-title { color: #ffffff !important; }
    .pos-modal-body { margin-top: 0.5rem; font-size: 0.875rem; color: #4b5563; line-height: 1.5; }
    .dark .pos-modal-body { color: #d1d5db !important; }
    .pos-modal-highlight { font-weight: 600; color: #111827; }
    .dark .pos-modal-highlight { color: #f9fafb !important; }

    .pos-modal-list-container {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
    .pos-modal-list {
        max-height: 240px;
        overflow-y: auto;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        color: #374151;
    }
    .dark .pos-modal-list {
        background-color: #111827 !important;
        border-color: #374151 !important;
        color: #d1d5db !important;
    }
    .pos-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
        flex-shrink: 0;
    }
    .dark .pos-modal-footer {
        border-color: #374151 !important;
    }
    
    .pos-btn-cancel {
        padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #374151;
        background-color: #f3f4f6; border-radius: 0.5rem; border: 1px solid #d1d5db; cursor: pointer;
        transition: background-color 0.15s, border-color 0.15s;
    }
    .dark .pos-btn-cancel {
        background-color: #374151 !important; color: #e5e7eb !important; border-color: #4b5563 !important;
    }
    .pos-btn-cancel:hover { background-color: #e5e7eb; }
    .dark .pos-btn-cancel:hover { background-color: #4b5563 !important; }

    .pos-btn-danger {
        padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 600; color: #ffffff;
        background-color: #4f46e5; border-radius: 0.5rem; border: none; cursor: pointer;
        transition: background-color 0.15s;
    }
    .pos-btn-danger:hover { background-color: #4338ca; }
</style>
</div>



