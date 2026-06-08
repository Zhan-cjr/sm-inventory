<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden font-sans text-sm"
     x-data="{
        focusRowQty(index) {
            setTimeout(() => {
                let el = document.getElementById('qty-' + index);
                if (el) { el.focus(); el.select(); }
            }, 100);
        }
     }"
     @item-added.window="focusRowQty($event.detail.index)">
    <style>
        .pos-input { padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; width: 100%; border-radius: 0.375rem; background-color: #f9fafb; font-size: 0.875rem; }
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

        .search-result-item { border-left: 4px solid transparent; transition: all 0.1s ease-in-out; }
        .highlighted-item { background-color: #dbeafe !important; border-left: 4px solid #2563eb !important; }
        .dark .highlighted-item { background-color: #374151 !important; border-left: 4px solid #60a5fa !important; }
    </style>

    <!-- Top Section (Receipt & Supplier) -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Receipt Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem;" class="text-gray-900 dark:text-gray-200">Detail Penerimaan</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">No Terima</td>
                        <td><input type="text" class="pos-input" style="background-color: #e5e7eb; cursor: not-allowed;" readonly wire:model="receipt_number"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tgl Terima</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model.live="receipt_date"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Metode Bayar</td>
                        <td>
                            <select class="pos-input font-semibold text-blue-600 dark:bg-gray-800" wire:model.live="payment_method">
                                <option value="tempo">Kontrabon (Hutang / Tempo)</option>
                                <option value="cash">Lunas Tunai (Cash Toko)</option>
                                <option value="transfer">Lunas Transfer (Bank)</option>
                            </select>
                        </td>
                    </tr>
                    <tr x-show="$wire.payment_method === 'tempo'">
                        <td class="pos-label">Jatuh Tempo</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model="due_date" placeholder="Otomatis dari Pemasok"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Pilih PO (Opsional)</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <select class="pos-input" wire:model.live="purchase_order_id" style="border-color: #3b82f6; flex: 1;" @if(!$supplier_id) disabled title="Pilih supplier terlebih dahulu" @endif>
                                    <option value="">-- Penerimaan Tanpa PO --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }}</option>
                                    @endforeach
                                </select>
                                <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; white-space: nowrap; cursor: pointer;" class="text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" wire:model.live="only_latest_po" style="border-radius: 0.25rem; color: #2563eb;">
                                    PO Terbaru Saja
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">No Faktur Supplier</td>
                        <td><input onfocus="this.select()" type="text" class="pos-input" wire:model="faktur_supplier" placeholder="Masukkan No Surat Jalan / Faktur..."></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Lokasi Cabang</td>
                        <td>
                            <select class="pos-input" wire:model="branch_id" @if(auth()->user()->branch_id) disabled @endif>
                                <option value="">Pusat / Global (Master Produk)</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Supplier Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem;" class="text-gray-900 dark:text-gray-200">Detail Pemasok</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Pemasok</td>
                        <td>
                            <div x-data="{ 
                                open: false, 
                                search: '', 
                                disabled: @js($purchase_order_id ? true : false),
                                suppliers: @js($suppliers),
                                get filteredSuppliers() {
                                    if (this.search === '') return this.suppliers;
                                    return this.suppliers.filter(s => 
                                        (s.name || '').toLowerCase().includes(this.search.toLowerCase()) || 
                                        (s.code || '').toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                selectSupplier(id) {
                                    if (this.disabled) return;
                                    @this.set('supplier_id', id);
                                    this.open = false;
                                    this.search = '';
                                }
                            }" style="position: relative;">
                                <button type="button" @click="!disabled && (open = !open)" 
                                        class="pos-input flex-between dark:text-gray-200"
                                        :class="disabled ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-75' : 'bg-white dark:bg-gray-800 cursor-pointer'"
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span>{{ $supplier_id ? (collect($suppliers)->firstWhere('id', $supplier_id)['code'] . ' - ' . collect($suppliers)->firstWhere('id', $supplier_id)['name']) : 'Pilih Supplier...' }}</span>
                                    <svg style="width: 1rem; height: 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     style="position: absolute; left: 0; z-index: 50; margin-top: 0.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 15rem; overflow-y: auto; width: 100%; min-width: 300px; max-width: 500px;" 
                                     class="dark:bg-gray-800 dark:border-gray-700">
                                    <div style="position: sticky; top: 0; padding: 0.5rem; background: white; border-bottom: 1px solid #f3f4f6;" class="dark:bg-gray-800 dark:border-gray-700">
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
                        <td><div class="pos-input dark:text-gray-200" style="background-color: transparent; border-color: transparent; padding-left: 0;">{{ $selectedSupplier ? $selectedSupplier->name : '-' }}</div></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Alamat</td>
                        <td><div class="pos-input dark:text-gray-200" style="background-color: transparent; border-color: transparent; padding-left: 0; line-height: 1.2;">{{ $selectedSupplier ? $selectedSupplier->address : '-' }}</div></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Catatan</td>
                        <td><input onfocus="this.select()" type="text" class="pos-input" wire:model="notes" placeholder="Catatan tambahan..."></td>
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
            <input onfocus="this.select()" type="text" id="search-input" class="pos-input" style="font-size: 1rem; padding: 0.5rem 1rem;"
                   placeholder="Scan barcode atau ketik nama produk... tekan Enter"
                   wire:model.live.debounce.250ms="searchQuery"
                   @input="highlightedIndex = -1; $nextTick(() => updateHighlight())"
                   @keydown.arrow-down.prevent="moveDown()"
                   @keydown.arrow-up.prevent="moveUp()"
                   @keydown.enter.prevent="selectCurrent()"
                   autofocus>

            <!-- Interactive Search Dropdown -->
            @if(count($searchResults) > 0)
            <div style="position: absolute; left: 0; right: 0; top: 100%; z-index: 100; background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-top: 0.25rem; max-height: 300px; overflow-y: auto;" class="dark:bg-gray-800 dark:border-gray-700">
                @foreach($searchResults as $index => $result)
                <div wire:click="selectProduct('{{ $result->id }}')" 
                     class="search-result-item hover:bg-blue-50 dark:hover:bg-gray-700 dark:border-gray-700"
                     style="padding: 0.75rem; cursor: pointer; border-bottom: 1px solid #f9fafb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #1f2937;" class="dark:text-gray-200">{{ $result->sku }}</div>
                        <div style="font-size: 0.75rem; color: #6b7280;" class="dark:text-gray-400">{{ $result->name }}</div>
                    </div>
                    <div style="font-weight: 600; color: #3b82f6;">Rp {{ number_format($result->cost_price, 0) }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div x-data="{ open: false }" style="position: relative;">
            <button @click="open = !open" class="pos-input dark:text-gray-200" style="display: flex; align-items: center; gap: 0.5rem; background: white; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/></svg>
                Pilih Kolom
            </button>
            <div x-show="open" @click.away="open = false" style="position: absolute; right: 0; top: 100%; z-index: 50; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.5rem; width: 12rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="dark:bg-gray-800 dark:border-gray-700">
                @foreach(['sku' => 'SKU', 'barcode' => 'Barcode', 'name' => 'Nama Produk', 'qty_ordered' => 'Qty PO', 'qty_received' => 'Qty Terima', 'unit_price' => 'Harga Satuan', 'harga_jual_1' => 'Harga Jual', 'margin_gol_1' => 'Margin', 'discount_1' => 'Dis1', 'discount_2' => 'Dis2', 'discount_3' => 'Dis3'] as $key => $label)
                    <label class="flex items-center gap-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer rounded">
                        <input type="checkbox" wire:model.live="visibleColumns" value="{{ $key }}" class="rounded text-blue-600">
                        <span class="text-xs dark:text-gray-200">{{ $label }}</span>
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
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;">No</th>
                    @if(in_array('sku', $visibleColumns)) <th class="pos-grid-th" style="width: 12%;">SKU</th> @endif
                    @if(in_array('barcode', $visibleColumns)) <th class="pos-grid-th" style="width: 12%;">Barcode</th> @endif
                    @if(in_array('name', $visibleColumns)) <th class="pos-grid-th">Nama Produk</th> @endif
                    @if(in_array('qty_ordered', $visibleColumns)) <th class="pos-grid-th" style="width: 7rem; text-align: right;">Qty PO</th> @endif
                    @if(in_array('qty_received', $visibleColumns)) <th class="pos-grid-th" style="width: 7rem; text-align: right;">Qty Terima</th> @endif
                    @if(in_array('unit_price', $visibleColumns)) <th class="pos-grid-th" style="width: 9rem; text-align: right;">Harga Satuan</th> @endif

                    @if(in_array('harga_jual_1', $visibleColumns)) <th class="pos-grid-th" style="width: 8rem; text-align: right;">Harga Jual</th> @endif
                    @if(in_array('margin_gol_1', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Margin (%)</th> @endif
                    @if(in_array('discount_1', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Dis1 (%)</th> @endif
                    @if(in_array('discount_2', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Dis2 (%)</th> @endif
                    @if(in_array('discount_3', $visibleColumns)) <th class="pos-grid-th" style="width: 5rem; text-align: right;">Dis3 (%)</th> @endif
                    <th class="pos-grid-th" style="width: 10rem; text-align: right;">Total</th>
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cart as $index => $item)
                    <tr>
                        <td class="pos-grid-td" style="text-align: center;">{{ $loop->iteration }}</td>
                        @if(in_array('sku', $visibleColumns)) <td class="pos-grid-td">{{ $item['sku'] }}</td> @endif
                        @if(in_array('barcode', $visibleColumns)) <td class="pos-grid-td">{{ $item['barcode'] }}</td> @endif
                        @if(in_array('name', $visibleColumns)) <td class="pos-grid-td" style="font-weight: 500;">{{ $item['name'] }}</td> @endif
                        @if(in_array('qty_ordered', $visibleColumns)) <td class="pos-grid-td dark:text-gray-400" style="text-align: right; color: #6b7280;">{{ $item['qty_ordered'] > 0 ? number_format($item['qty_ordered'], 0) : '-' }}</td> @endif
                        @if(in_array('qty_received', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="qty-{{ $index }}" class="pos-input" style="text-align: right; font-weight: 700; color: #2563eb;" 
                                   wire:model.lazy="cart.{{ $index }}.qty_received"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('price-{{ $index }}').focus()">
                        </td>
                        @endif
                        @if(in_array('unit_price', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="price-{{ $index }}" class="pos-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.unit_price"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('dis1-{{ $index }}').focus()">
                        </td>
                        @endif

                        @if(in_array('harga_jual_1', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            @if(auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE'))
                                <input onfocus="this.select()" type="number" step="any" class="pos-input" style="text-align: right;" 
                                       wire:model.lazy="cart.{{ $index }}.harga_jual_1"
                                       wire:change="recalculateRow({{ $index }})">
                            @else
                                <div style="text-align: right; padding: 0.375rem 0.5rem; color: #6b7280;" class="dark:text-gray-400">{{ number_format($item['harga_jual_1'], 2) }}</div>
                            @endif
                        </td>
                        @endif
                        @if(in_array('margin_gol_1', $visibleColumns))
                        <td class="pos-grid-td dark:bg-gray-800" style="padding: 0.25rem; background-color: #f9fafb;">
                            @if(auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE'))
                                <input type="number" step="any" class="pos-input {{ $item['margin_gol_1'] < 0 ? 'text-red-500' : 'text-green-600' }} font-medium" style="text-align: right; width: 100%;" 
                                       wire:model.lazy="cart.{{ $index }}.margin_gol_1"
                                       wire:change="recalculateRow({{ $index }})">
                            @else
                                <div style="text-align: right; padding: 0.375rem 0.5rem;" class="{{ $item['margin_gol_1'] < 0 ? 'text-red-500' : 'text-green-600' }} font-medium">{{ number_format($item['margin_gol_1'], 2) }}</div>
                            @endif
                        </td>
                        @endif
                        @if(in_array('discount_1', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis1-{{ $index }}" class="pos-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_1"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('dis2-{{ $index }}').focus()">
                        </td>
                        @endif
                        @if(in_array('discount_2', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis2-{{ $index }}" class="pos-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_2"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('dis3-{{ $index }}').focus()">
                        </td>
                        @endif
                        @if(in_array('discount_3', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="dis3-{{ $index }}" class="pos-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_3"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('search-input').focus()">
                        </td>
                        @endif
                        <td class="pos-grid-td" style="text-align: right; font-weight: 600; color: #111827;" class="dark:text-gray-100">
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
                        <td colspan="10" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;" class="dark:text-gray-400">
                            Belum ada barang. Silakan pilih PO atau scan produk untuk penerimaan langsung.
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
            <a href="{{ route('filament.admin.resources.goods-receipts.index') }}" style="background-color: #fff; color: #374151; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; font-size: 0.875rem; border: 1px solid #d1d5db; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                Batal
            </a>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 ml-4 cursor-pointer">
                <input type="checkbox" wire:model="cetak_nota" class="rounded text-blue-600">
                Cetak Nota setelah simpan
            </label>
        </div>
        
        <!-- Totals -->
        <div style="display: flex; align-items: center; gap: 2rem;">
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem; margin-right: 1rem;">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="include_tax" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Include PPN ({{ (float)(\App\Models\Organization::first()->tax_rate ?? 11) }}%)</span>
                </label>
            </div>

            <div style="text-align: right;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Total Baris / Qty</div>
                <div style="font-weight: 600; font-size: 1rem; color: #374151;" class="dark:text-gray-200">{{ $totalLines }} Items / {{ number_format($totalQty, 0) }} Pcs</div>
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

            <div style="text-align: right; min-width: 8rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">PPN ({{ (float)(\App\Models\Organization::first()->tax_rate ?? 11) }}%)</div>
                <div class="flex items-center justify-end gap-1">
                    <span style="font-size: 0.75rem; color: #6b7280;">Rp</span>
                    <input onfocus="this.select()" type="number" wire:model.live="tax_amount" class="pos-input" style="width: 7rem; padding: 0.125rem 0.25rem; text-align: right; border-style: dashed; background: transparent;">
                </div>
            </div>

            <div style="text-align: right; min-width: 10rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Grand Total</div>
                <div style="font-weight: 700; font-size: 1.5rem; color: #1d4ed8;" class="dark:text-blue-400">Rp {{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>



