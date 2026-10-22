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
                    <div style="font-weight: 600; font-size: 1rem; color: #111827;" class="dark:text-gray-200">Detail Supplier</div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        {{-- Tombol Promo Supplier --}}
                        <button type="button" 
                                wire:click="openPromoModal" 
                                class="pos-input" 
                                style="display: flex; align-items: center; gap: 0.4rem; background: #059669; color: white; border: none; cursor: pointer; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-weight: 600;"
                                title="Upload Excel atau Paste Teks WA Promo Supplier">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h6.086a1.5 1.5 0 0 0 1.06-.44l4.915-4.914A1.5 1.5 0 0 0 15 8.586V2.5A1.5 1.5 0 0 0 13.5 1h-11zM2 2.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5V8H9.5A1.5 1.5 0 0 0 8 9.5V14H2.5a.5.5 0 0 1-.5-.5v-11zm7 11.293V9.5a.5.5 0 0 1 .5-.5h4.293L9 13.793z"/></svg>
                            Promo Supplier
                        </button>

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
                </div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Supplier</td>
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
                        <td class="pos-label">Sub Divisi</td>
                        <td>
                            <select wire:model.live="supplier_division_id" class="pos-input dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700" style="width: 100%;">
                                <option value="">-- Semua Sub Divisi --</option>
                                @if($selectedSupplier && $selectedSupplier->divisions)
                                    @foreach($selectedSupplier->divisions as $div)
                                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </td>
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
                   wire:model.live.debounce.150ms="searchQuery"
                   @input="highlightedIndex = -1; $nextTick(() => updateHighlight())"
                   @keydown.arrow-down.prevent="moveDown()"
                   @keydown.arrow-up.prevent="moveUp()"
                   @keydown.enter.prevent="selectCurrent()"
                   @keydown.escape="$wire.set('searchQuery', '')"
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
                @foreach(['sku' => 'SKU', 'barcode' => 'Barcode', 'name' => 'Nama Produk', 'avg_bln' => 'Sales 30 HR', 'avg_minggu' => 'Sales 7 HR', 'stock' => 'Stok', 'last_return' => 'Retur Terakhir', 'min_qty' => 'Min Qty', 'max_qty' => 'Max Qty', 'qty_saran' => 'Qty Saran', 'qty' => 'Qty Pesan', 'unit_cost' => 'Harga Satuan', 'discount_1' => 'Dis1', 'discount_2' => 'Dis2', 'discount_3' => 'Dis3'] as $key => $label)
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
                    @if(in_array('last_return', $visibleColumns)) <th class="pos-grid-th" style="width: 8rem; text-align: right;" title="Transaksi Retur Pembelian & Koreksi Retur (90 Hari Terakhir)">Retur Terakhir</th> @endif
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

                                {{-- Promo Supplier Badge --}}
                                @if(!empty($item['promo_note']))
                                    <div style="margin-top: 0.25rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;"
                                              title="Promo On-Faktur dari Supplier">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/></svg>
                                            Promo: {{ $item['promo_note'] }}
                                        </span>
                                    </div>
                                @endif
                                
                                {{-- Smart Badge 1: Habis via Retur --}}
                                @if(!empty($item['is_empty_due_to_return']))
                                    <div style="margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background-color: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3;"
                                              title="Stok kosong karena diretur {{ $item['last_return']['qty'] ?? 0 }} pcs ({{ $item['last_return']['days_ago'] ?? 0 }} hari lalu), belum ada barang masuk lagi, dan penjualan 90 hari sangat kecil ({{ (int)($item['sales_90d'] ?? 0) }} pcs). Disarankan discontinue.">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                                            Habis via Retur ({{ $item['last_return']['days_ago'] ?? 0 }} hr lalu) — Disarankan Discontinue
                                        </span>
                                        <button type="button" 
                                                wire:click="discontinueBranchProduct('{{ $item['product_id'] }}')"
                                                wire:confirm="Yakin ingin mendiscontinue produk ini khusus di cabang ini? Produk akan dinonaktifkan di cabang ini dan tidak muncul lagi di saran order."
                                                style="cursor: pointer; font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 0.25rem; background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; transition: background 0.15s;"
                                                onmouseover="this.style.backgroundColor='#dc2626'; this.style.color='#ffffff';"
                                                onmouseout="this.style.backgroundColor='#fee2e2'; this.style.color='#b91c1c';"
                                                title="Klik untuk menonaktifkan produk ini khusus di cabang ini">
                                            Discontinue Cabang
                                        </button>
                                    </div>
                                @elseif(!empty($item['is_oos_with_history']))
                                    {{-- Smart Badge 3: Pasokan Sempat Kosong / OOS with history --}}
                                    <div style="margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;"
                                              title="Stok kosong karena pasokan macet. Penjualan 30 hari = 0, namun riwayat penjualan 90 hari tercatat {{ (int)$item['sales_90d'] }} pcs (rata-rata ~{{ $item['avg_sales_active'] }} pcs/bln saat aktif). Aman untuk diorder.">
                                            📦 Pasokan Sempat Kosong (Riwayat 90 HR: {{ (int)$item['sales_90d'] }} pcs)
                                        </span>
                                    </div>
                                @elseif(!empty($item['is_slow_moving_alert']))
                                    {{-- Smart Badge 2: Slow Moving / Penjualan Lesu --}}
                                    <div style="margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 0.375rem; background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;"
                                              title="Produk slow-moving: Penjualan 30 hari hanya {{ $item['avg_bln'] }} pcs sementara sisa stok masih {{ $item['stock'] }} pcs. Disarankan tidak order untuk menjaga arus kas.">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                            @if($item['avg_bln'] <= 0)
                                                Slow Moving (Sales 90 HR: {{ (int)($item['sales_90d'] ?? 0) }} pcs) — Disarankan Tinjau Ulang
                                            @else
                                                Slow Moving ({{ !empty($item['is_sales_from_history']) ? 'Histori' : 'Sales' }} 30 HR: {{ $item['avg_bln'] }} pcs) — Disarankan Tinjau Ulang
                                            @endif
                                        </span>
                                        <button type="button" 
                                                wire:click="updateRow({{ $index }}, 'qty', 0)"
                                                style="cursor: pointer; font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 0.25rem; background-color: #fffbeb; color: #b45309; border: 1px solid #fcd34d; transition: background 0.15s;"
                                                onmouseover="this.style.backgroundColor='#d97706'; this.style.color='#ffffff';"
                                                onmouseout="this.style.backgroundColor='#fffbeb'; this.style.color='#b45309';"
                                                title="Klik untuk mengubah Qty Pesan menjadi 0">
                                            Set Qty 0
                                        </button>
                                    </div>
                                @endif
                            </td> 
                        @endif
                        @if(in_array('avg_bln', $visibleColumns)) 
                            <td class="pos-grid-td" style="text-align: right; color: #8b5cf6;">
                                <div>{{ $item['avg_bln'] }}</div>
                                @if(!empty($item['is_sales_from_history']) && (float)($item['avg_bln'] ?? 0) > 0)
                                    <div style="margin-top: 1px;">
                                        <span style="display: inline-block; font-size: 0.58rem; font-weight: 700; padding: 1px 4px; border-radius: 3px; background-color: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; white-space: nowrap;"
                                              title="Rata-rata penjualan diambil dari histori import aplikasi lama">
                                            Histori
                                        </span>
                                    </div>
                                @elseif(!empty($item['is_oos_with_history']))
                                    <div style="margin-top: 2px;">
                                        <span style="display: inline-block; font-size: 0.62rem; font-weight: 700; padding: 1px 4px; border-radius: 4px; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; white-space: nowrap;"
                                              title="Pasokan sempat kosong. Total jual 90 hari: {{ (int)$item['sales_90d'] }} pcs (~{{ $item['avg_sales_active'] }} pcs/bln)">
                                            90hr: {{ (int)$item['sales_90d'] }}
                                        </span>
                                    </div>
                                @endif
                            </td> 
                        @endif
                        @if(in_array('avg_minggu', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #a855f7;">{{ $item['avg_minggu'] }}</td> @endif
                        @if(in_array('stock', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #6b7280;">{{ $item['stock'] }}</td> @endif
                        @if(in_array('last_return', $visibleColumns))
                            <td class="pos-grid-td" style="text-align: right;">
                                @if(!empty($item['last_return']))
                                    <div title="No Dok: {{ $item['last_return']['doc_number'] }}&#10;Tipe: {{ $item['last_return']['type_label'] }}&#10;Tgl: {{ $item['last_return']['date_formatted'] }}&#10;Alasan: {{ $item['last_return']['reason'] }}"
                                         style="cursor: help; line-height: 1.2;">
                                        <span style="font-weight: 700; color: #e11d48;">{{ number_format($item['last_return']['qty'], 0) }} pcs</span>
                                        <div style="font-size: 0.7rem; color: #6b7280;">({{ $item['last_return']['days_ago'] == 0 ? 'hari ini' : $item['last_return']['days_ago'] . ' hr lalu' }})</div>
                                    </div>
                                @else
                                    <span style="color: #9ca3af;">-</span>
                                @endif
                            </td>
                        @endif
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

    {{-- MODAL IMPORT PROMO SUPPLIER --}}
    @if($showPromoModal)
    <div class="pos-modal-overlay" style="z-index: 9999999;">
        <div class="pos-modal-card" style="max-width: 64rem; width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
            {{-- Modal Header --}}
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;" class="dark:border-gray-700">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background-color: #d1fae5; color: #059669; padding: 0.5rem; border-radius: 0.5rem;" class="dark:bg-emerald-950 dark:text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.5 1A1.5 1.5 0 0 0 1 2.5v11A1.5 1.5 0 0 0 2.5 15h6.086a1.5 1.5 0 0 0 1.06-.44l4.915-4.914A1.5 1.5 0 0 0 15 8.586V2.5A1.5 1.5 0 0 0 13.5 1h-11zM2 2.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5V8H9.5A1.5 1.5 0 0 0 8 9.5V14H2.5a.5.5 0 0 1-.5-.5v-11zm7 11.293V9.5a.5.5 0 0 1 .5-.5h4.293L9 13.793z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="pos-modal-title" style="font-size: 1.15rem;">Muat Promo On-Faktur Supplier</h3>
                        <p style="font-size: 0.8rem; color: #6b7280; margin: 0;">Upload file Excel (.xlsx/.xls) atau tempel chat WhatsApp promo dari supplier.</p>
                    </div>
                </div>
                <button type="button" wire:click="closePromoModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" style="cursor: pointer; background: transparent; border: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div style="flex: 1 1 auto; overflow-y: auto; padding: 0.75rem 0; display: flex; flex-direction: column; gap: 1rem;">
                {{-- Source Selector Tabs --}}
                <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid #e5e7eb;" class="dark:border-gray-700">
                    <button type="button" 
                            wire:click="$set('promoSource', 'excel')"
                            style="padding: 0.5rem 1rem; font-weight: 600; font-size: 0.85rem; border-bottom: 2px solid {{ $promoSource === 'excel' ? '#059669' : 'transparent' }}; color: {{ $promoSource === 'excel' ? '#059669' : '#6b7280' }}; margin-bottom: -2px; cursor: pointer; background: transparent; border-top: none; border-left: none; border-right: none;">
                        📊 File Excel Promo
                    </button>
                    <button type="button" 
                            wire:click="$set('promoSource', 'text')"
                            style="padding: 0.5rem 1rem; font-weight: 600; font-size: 0.85rem; border-bottom: 2px solid {{ $promoSource === 'text' ? '#059669' : 'transparent' }}; color: {{ $promoSource === 'text' ? '#059669' : '#6b7280' }}; margin-bottom: -2px; cursor: pointer; background: transparent; border-top: none; border-left: none; border-right: none;">
                        💬 Teks Chat WhatsApp
                    </button>
                </div>

                {{-- Tab 1: Upload Excel --}}
                @if($promoSource === 'excel')
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; background: #f9fafb; padding: 1rem; border-radius: 0.5rem; border: 1px dashed #d1d5db;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <label style="font-weight: 600; font-size: 0.85rem; color: #374151;" class="dark:text-gray-200">Pilih File Excel:</label>
                                <input type="file" wire:model="promoFile" accept=".xlsx,.xls,.csv" class="pos-input" style="margin-top: 0.25rem;">
                            </div>
                            <div wire:loading wire:target="promoFile" style="font-size: 0.8rem; color: #059669; font-weight: 600;">
                                ⏳ Membaca struktur file Excel...
                            </div>
                        </div>

                        {{-- Options if Sheet/Columns detected --}}
                        @if(count($excelSheets) > 0)
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;" class="dark:border-gray-700">
                                <div>
                                    <label class="pos-label" style="font-size: 0.75rem;">Sheet Excel:</label>
                                    <select wire:model.live="selectedSheet" class="pos-input" style="font-size: 0.8rem;">
                                        @foreach($excelSheets as $sh)
                                            <option value="{{ $sh }}">{{ $sh }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="pos-label" style="font-size: 0.75rem;">Kolom Barcode:</label>
                                    <select wire:model="selectedBarcodeCol" class="pos-input" style="font-size: 0.8rem;">
                                        @foreach($excelColumns as $colLetter => $colName)
                                            <option value="{{ $colLetter }}">Kolom {{ $colLetter }} ({{ $colName }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="pos-label" style="font-size: 0.75rem;">Kolom Promo / Diskon:</label>
                                    <select wire:model="selectedPromoCol" class="pos-input" style="font-size: 0.8rem;">
                                        @foreach($excelColumns as $colLetter => $colName)
                                            <option value="{{ $colLetter }}">Kolom {{ $colLetter }} ({{ $colName }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="pos-label" style="font-size: 0.75rem;">Mulai Baris:</label>
                                    <input type="number" min="1" wire:model="startRow" class="pos-input" style="font-size: 0.8rem;">
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                {{-- Tab 2: WhatsApp Text --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label style="font-weight: 600; font-size: 0.85rem; color: #374151;" class="dark:text-gray-200">
                            Tempel (Paste) Salinan Chat WhatsApp Sales:
                        </label>
                        <textarea wire:model="promoText" 
                                  rows="6" 
                                  class="pos-input" 
                                  style="font-family: monospace; font-size: 0.8rem; resize: vertical;"
                                  placeholder="Contoh format chat WA:&#10;- 8998866608923 SOKLIN SOFTENER TWILIGHT Disc. 10%&#10;- 8998866803892 SOKLIN ALL IN1 SCT Pot. Rp 2.500/Box&#10;- 8998866632553 Sedaap Kecap Pch 550Gr Disc. 7,3%"></textarea>
                    </div>
                @endif

                {{-- Action Button: Parse / Analyze --}}
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" 
                            wire:click="processPromoData" 
                            class="pos-input" 
                            style="background-color: #2563eb; color: white; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 1rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        <span>Baca & Cocokkan dengan Master Barang</span>
                        <span wire:loading wire:target="processPromoData" class="animate-spin">⏳</span>
                    </button>
                </div>

                {{-- Error Alert --}}
                @if(!empty($promoErrorMessage))
                    <div style="padding: 0.75rem 1rem; background-color: #fee2e2; color: #b91c1c; border-radius: 0.375rem; border: 1px solid #fca5a5; font-size: 0.85rem;">
                        ⚠️ {{ $promoErrorMessage }}
                    </div>
                @endif

                {{-- Parsed Preview Results --}}
                @if(count($parsedPromoItems) > 0)
                    @php
                        $matchedCount = collect($parsedPromoItems)->where('is_matched', true)->count();
                        $needOrderCount = collect($parsedPromoItems)->where('recommendation', 'need_order')->count();
                        $inCartCount = collect($parsedPromoItems)->where('in_cart', true)->count();
                        $slowCount = collect($parsedPromoItems)->where('recommendation', 'slow_moving')->count();
                        $selectedCount = collect($parsedPromoItems)->where('selected', true)->count();
                    @endphp

                    <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;" class="dark:border-gray-700">
                        {{-- Summary Cards --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.5rem;">
                            <div style="padding: 0.5rem; background-color: #f3f4f6; border-radius: 0.375rem; text-align: center;" class="dark:bg-gray-800">
                                <div style="font-size: 0.7rem; color: #6b7280;">Total Promo Dibaca</div>
                                <div style="font-size: 1.15rem; font-weight: 700; color: #111827;" class="dark:text-gray-100">{{ count($parsedPromoItems) }}</div>
                            </div>
                            <div style="padding: 0.5rem; background-color: #ecfdf5; border-radius: 0.375rem; text-align: center; border: 1px solid #a7f3d0;" class="dark:bg-emerald-950 dark:border-emerald-800">
                                <div style="font-size: 0.7rem; color: #047857;">Cocok di Master</div>
                                <div style="font-size: 1.15rem; font-weight: 700; color: #065f46;" class="dark:text-emerald-300">{{ $matchedCount }}</div>
                            </div>
                            <div style="padding: 0.5rem; background-color: #eff6ff; border-radius: 0.375rem; text-align: center; border: 1px solid #bfdbfe;" class="dark:bg-blue-950 dark:border-blue-800">
                                <div style="font-size: 0.7rem; color: #1d4ed8;">🟢 Butuh Order (Saran > 0)</div>
                                <div style="font-size: 1.15rem; font-weight: 700; color: #1e40af;" class="dark:text-blue-300">{{ $needOrderCount }}</div>
                            </div>
                            <div style="padding: 0.5rem; background-color: #fef3c7; border-radius: 0.375rem; text-align: center; border: 1px solid #fde68a;" class="dark:bg-amber-950 dark:border-amber-800">
                                <div style="font-size: 0.7rem; color: #b45309;">Di Keranjang Saat Ini</div>
                                <div style="font-size: 1.15rem; font-weight: 700; color: #92400e;" class="dark:text-amber-300">{{ $inCartCount }}</div>
                            </div>
                        </div>

                        {{-- Action Controls & Filters --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                <button type="button" wire:click="selectOnlyNeedOrderPromo" class="pos-input" style="font-size: 0.75rem; background-color: #dbeafe; color: #1e40af; border-color: #bfdbfe; font-weight: 600; cursor: pointer; padding: 0.2rem 0.5rem;">
                                    🟢 Pilih yang Butuh Order Saja
                                </button>
                                <button type="button" wire:click="setAllPromoSelection(true)" class="pos-input" style="font-size: 0.75rem; font-weight: 500; cursor: pointer; padding: 0.2rem 0.5rem;">
                                    Pilih Semua
                                </button>
                                <button type="button" wire:click="setAllPromoSelection(false)" class="pos-input" style="font-size: 0.75rem; font-weight: 500; cursor: pointer; padding: 0.2rem 0.5rem;">
                                    Kosongkan
                                </button>
                            </div>

                            <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; cursor: pointer; color: #374151;" class="dark:text-gray-300">
                                <input type="checkbox" wire:model="updateExistingCartOnly" class="rounded text-emerald-600">
                                <span>Hanya perbarui diskon barang yang <b>sudah ada di keranjang</b></span>
                            </label>
                        </div>

                        {{-- Table of Parsed Promo Items --}}
                        <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.375rem;" class="dark:border-gray-700">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem;">
                                <thead style="position: sticky; top: 0; background-color: #f3f4f6; z-index: 5;" class="dark:bg-gray-800">
                                    <tr>
                                        <th style="padding: 0.4rem; text-align: center; width: 2.5rem;">Pilih</th>
                                        <th style="padding: 0.4rem; text-align: left;">Barcode</th>
                                        <th style="padding: 0.4rem; text-align: left;">Nama Produk (Database)</th>
                                        <th style="padding: 0.4rem; text-align: left;">Promo Terbaca</th>
                                        <th style="padding: 0.4rem; text-align: right;">Stok</th>
                                        <th style="padding: 0.4rem; text-align: right;">Jual/Bln</th>
                                        <th style="padding: 0.4rem; text-align: right;">Qty Saran</th>
                                        <th style="padding: 0.4rem; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($parsedPromoItems as $idx => $pItem)
                                    <tr style="border-bottom: 1px solid #f3f4f6;" class="hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700">
                                        <td style="padding: 0.4rem; text-align: center;">
                                            @if($pItem['is_matched'])
                                                <input type="checkbox" 
                                                       wire:click="togglePromoItemSelection({{ $idx }})" 
                                                       {{ $pItem['selected'] ? 'checked' : '' }} 
                                                       class="rounded text-emerald-600 cursor-pointer">
                                            @else
                                                <span style="color: #9ca3af;">-</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.4rem; font-family: monospace;">{{ $pItem['barcode'] }}</td>
                                        <td style="padding: 0.4rem; font-weight: 600;">
                                            @if($pItem['is_matched'])
                                                {{ $pItem['product_name'] }}
                                                @if($pItem['in_cart'])
                                                    <span style="font-size: 0.65rem; color: #2563eb; background: #dbeafe; padding: 1px 4px; border-radius: 3px; margin-left: 4px;">Di Keranjang</span>
                                                @endif
                                            @else
                                                <span style="color: #ef4444; font-style: italic;">Tidak ditemukan: {{ $pItem['file_name'] }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.4rem;">
                                            <span style="font-weight: 700; color: #047857; background: #ecfdf5; padding: 1px 6px; border-radius: 4px; border: 1px solid #a7f3d0;">
                                                {{ $pItem['promo_raw'] }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.4rem; text-align: right; color: #4b5563;" class="dark:text-gray-300">
                                            {{ $pItem['is_matched'] ? number_format($pItem['current_stock'], 0) : '-' }}
                                        </td>
                                        <td style="padding: 0.4rem; text-align: right; color: #8b5cf6;">
                                            {{ $pItem['is_matched'] ? number_format($pItem['avg_bln'], 0) : '-' }}
                                        </td>
                                        <td style="padding: 0.4rem; text-align: right; font-weight: 700; color: {{ $pItem['qty_saran'] > 0 ? '#0284c7' : '#9ca3af' }};">
                                            {{ $pItem['is_matched'] ? number_format($pItem['qty_saran'], 0) : '-' }}
                                        </td>
                                        <td style="padding: 0.4rem; text-align: center;">
                                            @if(!$pItem['is_matched'])
                                                <span style="font-size: 0.65rem; color: #dc2626; background: #fee2e2; padding: 1px 5px; border-radius: 3px;">Belum Terdaftar</span>
                                            @elseif($pItem['recommendation'] === 'need_order')
                                                <span style="font-size: 0.65rem; color: #15803d; background: #dcfce7; padding: 1px 5px; border-radius: 3px; font-weight: 700;">🟢 Butuh Order</span>
                                            @elseif($pItem['recommendation'] === 'slow_moving')
                                                <span style="font-size: 0.65rem; color: #b91c1c; background: #fee2e2; padding: 1px 5px; border-radius: 3px;">🔴 Slow Moving</span>
                                            @else
                                                <span style="font-size: 0.65rem; color: #b45309; background: #fef3c7; padding: 1px 5px; border-radius: 3px;">Stok Aman</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Modal Footer --}}
            <div class="pos-modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.85rem; color: #4b5563;" class="dark:text-gray-300">
                    @if(count($parsedPromoItems) > 0)
                        Terpilih: <b>{{ collect($parsedPromoItems)->where('selected', true)->count() }}</b> item promo
                    @endif
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" wire:click="closePromoModal" class="pos-btn-cancel">
                        Tutup
                    </button>
                    @if(count($parsedPromoItems) > 0)
                    <button type="button" 
                            wire:click="applyPromoToCart" 
                            class="pos-btn-danger" 
                            style="background-color: #059669; display: flex; align-items: center; gap: 0.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.736 3.97a.75.75 0 0 1 1.028 1.09l-6.5 7.5a.75.75 0 0 1-1.08.02L2.736 8.53a.75.75 0 0 1 1.028-1.09L7.25 10.45l5.486-6.48z"/></svg>
                        <span>Terapkan ke Keranjang PO</span>
                    </button>
                    @endif
                </div>
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



