<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden font-sans text-sm"
     x-data="{
        focusRowQty(index) {
            setTimeout(() =>
 {
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
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .search-result-item { border-left: 4px solid transparent; transition: all 0.1s ease-in-out; }
        .highlighted-item { background-color: #dbeafe !important; border-left: 4px solid #2563eb !important; }
        .dark .highlighted-item { background-color: #374151 !important; border-left: 4px solid #60a5fa !important; }
    </style>

    <!-- Top Section (Header) -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Pengirim Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: #111827;" class="dark:text-gray-200">Info Mutasi Stok</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">No Referensi</td>
                        <td><input type="text" class="pos-input" style="background-color: #e5e7eb; cursor: not-allowed;" readonly wire:model="reference_number"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tanggal Mutasi</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model="transfer_date"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Cabang Asal</td>
                        <td>
                            <select class="pos-input" wire:model.live="from_branch_id" @if(auth()->user()->branch_id) disabled style="background-color: #e5e7eb; cursor: not-allowed;" @endif>
                                <option value="">Pilih Cabang Asal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Penerima Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: #111827;" class="dark:text-gray-200">Tujuan & Keterangan</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Cabang Tujuan</td>
                        <td>
                            <select class="pos-input" wire:model="to_branch_id" style="border-color: #3b82f6;">
                                <option value="">Pilih Cabang Tujuan...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Catatan</td>
                        <td><textarea class="pos-input" wire:model="notes" placeholder="Catatan tambahan mutasi..." rows="3"></textarea></td>
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
                   placeholder="Scan barcode atau ketik nama produk yang ada di cabang asal... tekan Enter"
                   wire:model.live.debounce.250ms="searchQuery"
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
                    <div style="font-weight: 600; color: #10b981;">Stok Asal: {{ $result->stocks->where('branch_id', $from_branch_id)->first()->quantity_on_hand ?? 0 }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div x-data="{ open: false }" style="position: relative;">
            <button @click="open = !open" class="pos-input" style="display: flex; align-items: center; gap: 0.5rem; background: white; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/></svg>
                Pilih Kolom
            </button>
            <div x-show="open" @click.away="open = false" style="position: absolute; right: 0; top: 100%; z-index: 50;  border-radius: 0.5rem; padding: 0.5rem; width: 12rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                @foreach(['sku' => 'SKU', 'barcode' => 'Barcode', 'name' => 'Nama Produk', 'stock_available' => 'Stok Tersedia', 'qty_transfer' => 'Qty Kirim', 'unit_price' => 'Harga Mutasi', 'subtotal' => 'Subtotal', 'notes' => 'Catatan'] as $key => $label)
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
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;">No</th>
                    @if(in_array('sku', $visibleColumns)) <th class="pos-grid-th" style="width: 12%;">SKU</th> @endif
                    @if(in_array('barcode', $visibleColumns)) <th class="pos-grid-th" style="width: 12%;">Barcode</th> @endif
                    @if(in_array('name', $visibleColumns)) <th class="pos-grid-th">Nama Produk</th> @endif
                    @if(in_array('stock_available', $visibleColumns)) <th class="pos-grid-th" style="width: 8rem; text-align: right;">Stok Asal</th> @endif
                    @if(in_array('qty_transfer', $visibleColumns)) <th class="pos-grid-th" style="width: 8rem; text-align: right;">Qty Kirim</th> @endif
                    @if(in_array('unit_price', $visibleColumns)) <th class="pos-grid-th" style="width: 9rem; text-align: right;">Harga Satuan</th> @endif
                    @if(in_array('subtotal', $visibleColumns)) <th class="pos-grid-th" style="width: 10rem; text-align: right;">Subtotal</th> @endif
                    @if(in_array('notes', $visibleColumns)) <th class="pos-grid-th" style="width: 15rem;">Catatan Item</th> @endif
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
                        @if(in_array('stock_available', $visibleColumns)) <td class="pos-grid-td" style="text-align: right; color: #10b981; font-weight: 600;">{{ number_format($item['stock_available'], 0) }}</td> @endif
                        @if(in_array('qty_transfer', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="qty-{{ $index }}" class="pos-input" style="text-align: right; font-weight: 700; color: #2563eb;" 
                                   wire:model.lazy="cart.{{ $index }}.qty_transfer"
                                   wire:change="checkQty({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('price-{{ $index }}') ? document.getElementById('price-{{ $index }}').focus() : (document.getElementById('notes-{{ $index }}') ? document.getElementById('notes-{{ $index }}').focus() : document.getElementById('search-input').focus())">
                        </td>
                        @endif
                        @if(in_array('unit_price', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="price-{{ $index }}" class="pos-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.unit_price"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.enter.prevent="document.getElementById('notes-{{ $index }}') ? document.getElementById('notes-{{ $index }}').focus() : document.getElementById('search-input').focus()">
                        </td>
                        @endif
                        @if(in_array('subtotal', $visibleColumns))
                        <td class="pos-grid-td" style="text-align: right; font-weight: 600; color: #111827;" class="dark:text-gray-100">
                            {{ number_format($item['subtotal'], 2) }}
                        </td>
                        @endif
                        @if(in_array('notes', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="text" id="notes-{{ $index }}" class="pos-input" placeholder="Keterangan..."
                                   wire:model.lazy="cart.{{ $index }}.notes"
                                   x-on:keydown.enter.prevent="document.getElementById('search-input').focus()">
                        </td>
                        @endif
                        <td class="pos-grid-td" style="text-align: center;">
                            <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 transition-colors" title="Hapus Barang">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;">
                            Belum ada barang di mutasi. Silakan scan produk dari cabang pengirim.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Summary & Actions -->
    <div style="padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;" class="bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
        
        <!-- Actions Button -->
        <div style="display: flex; gap: 0.5rem;">
            <button wire:click="save" style="background-color: #2563eb; color: white; padding: 0.75rem 2rem; border-radius: 0.375rem; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm1 2h7.086L12 4.914V13H3V3z"/><path d="M4 4h5v2H4V4zm0 5h8v4H4V9z"/></svg>
                SIMPAN MUTASI
            </button>
            <a href="{{ route('filament.admin.resources.stock-transfers.index') }}" style="background-color: #fff; color: #374151; padding: 0.75rem 1.5rem; border-radius: 0.375rem; font-weight: 500; border: 1px solid #d1d5db; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
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
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Total Baris</div>
                <div style="font-weight: 600; font-size: 1rem; color: #374151;" class="dark:text-gray-200">{{ $totalLines }} Jenis Barang</div>
            </div>

            <div style="text-align: right; min-width: 8rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Total Kuantitas</div>
                <div style="font-weight: 700; font-size: 1.25rem; color: #374151;" class="dark:text-gray-200">{{ number_format($totalQty, 0) }} Pcs</div>
            </div>
            
            <div style="text-align: right; min-width: 10rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Grand Total Mutasi</div>
                <div style="font-weight: 700; font-size: 1.5rem; color: #1d4ed8;" class="dark:text-blue-400">Rp {{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<style>
    .pos-dropdown-bg { background-color: #ffffff !important; }
    .dark .pos-dropdown-bg { background-color: #1f2937 !important; border-color: #374151 !important; }
    .dark .pos-dropdown-bg .dark\:text-gray-200 { color: #e5e7eb !important; }
    .dark .pos-dropdown-bg .dark\:text-gray-400 { color: #9ca3af !important; }
</style>
</div>



