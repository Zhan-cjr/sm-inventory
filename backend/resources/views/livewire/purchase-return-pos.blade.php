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
        .dark .empty-state { color: #9ca3af !important; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .flex-end { display: flex; justify-content: flex-end; align-items: center; }
    </style>

    <!-- Top Section -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Return Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: #111827;" class="dark:text-gray-200">Detail Retur Pembelian</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">No Retur</td>
                        <td><input type="text" class="pos-input" style="background-color: #e5e7eb; cursor: not-allowed;" readonly wire:model="return_number"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tgl Retur</td>
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model="return_date"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Supplier</td>
                        <td>
                            <div x-data="{ 
                                open: false, 
                                search: '', 
                                disabled: false,
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
                                        class="pos-input flex-between"
                                        :class="disabled ? 'bg-gray-100 cursor-not-allowed opacity-75' : 'bg-white dark:bg-gray-800 cursor-pointer'"
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-color: #3b82f6;">
                                    <span>{{ $supplier_id ? (collect($suppliers)->firstWhere('id', $supplier_id)['code'] . ' - ' . collect($suppliers)->firstWhere('id', $supplier_id)['name']) : '-- Pilih Supplier --' }}</span>
                                    <svg style="width: 1rem; height: 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     style="position: absolute; left: 0; z-index: 50; margin-top: 0.25rem;  border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 15rem; overflow-y: auto; width: 100%; min-width: 300px; max-width: 500px;" 
                                     class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                                    <div style="position: sticky; top: 0; padding: 0.5rem; background: white; border-bottom: 1px solid #f3f4f6;" class="pos-dropdown-bg border border-gray-200 dark:border-gray-700">
                                        <input onfocus="this.select()" type="text" x-model="search" class="pos-input" placeholder="Cari kode/nama supplier..." autofocus @keydown.escape="open = false">
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
                    <tr>
                        <td class="pos-label">Penerimaan Barang (GR)</td>
                        <td>
                            <select class="pos-input" wire:model.live="goods_receipt_id" style="border-color: #3b82f6;" @if(!$supplier_id) disabled title="Pilih supplier terlebih dahulu" @endif>
                                <option value="">-- Pilih No Penerimaan (GR) --</option>
                                @foreach($goodsReceipts as $gr)
                                    <option value="{{ $gr->id }}">{{ $gr->receipt_number }} ({{ \Carbon\Carbon::parse($gr->receipt_date)->format('d/m/Y') }})</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Branch & Notes Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: #111827;" class="dark:text-gray-200">Lokasi & Catatan</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Lokasi Cabang</td>
                        <td>
                            <select class="pos-input" wire:model="branch_id" @if(auth()->user()->branch_id) disabled @endif>
                                <option value="">Pusat / Global (Master Produk)</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Catatan Retur</td>
                        <td><textarea class="pos-input" wire:model="notes" placeholder="Catatan tambahan (mis. alasan umum)..." rows="4"></textarea></td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    <!-- Data Grid -->
    <div style="flex: 1; overflow-y: auto;" class="bg-white dark:bg-gray-900">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <tr>
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;">No</th>
                    <th class="pos-grid-th" style="width: 12%;">Barcode</th>
                    <th class="pos-grid-th">Nama Produk</th>
                    <th class="pos-grid-th" style="width: 7rem; text-align: right;">Sisa (Max)</th>
                    <th class="pos-grid-th" style="width: 8rem; text-align: right; background-color: #fee2e2; color: #991b1b;">Qty Retur</th>
                    <th class="pos-grid-th" style="width: 10rem; text-align: right;">Harga Beli (+PPN)</th>
                    <th class="pos-grid-th" style="width: 10rem; text-align: right;">Total</th>
                    <th class="pos-grid-th" style="width: 12rem;">Alasan Spesifik</th>
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cart as $index => $item)
                    <tr>
                        <td class="pos-grid-td" style="text-align: center;">{{ $loop->iteration }}</td>
                        <td class="pos-grid-td">{{ $item['barcode'] }}</td>
                        <td class="pos-grid-td" style="font-weight: 500;">{{ $item['name'] }}</td>
                        <td class="pos-grid-td" style="text-align: right; color: #6b7280;">{{ number_format($item['max_qty'], 0) }}</td>
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="qty-{{ $index }}" class="pos-input" style="text-align: right; font-weight: 700; color: #dc2626;" 
                                   wire:model.lazy="cart.{{ $index }}.qty_returned"
                                   wire:change="recalculateRow({{ $index }})"
                                   placeholder="0">
                        </td>
                        <td class="pos-grid-td" style="text-align: right; color: #6b7280;">
                            {{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="pos-grid-td" style="text-align: right; font-weight: 600; color: #111827;" class="dark:text-gray-100">
                            {{ number_format($item['subtotal'], 2) }}
                        </td>
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="text" class="pos-input" wire:model.lazy="cart.{{ $index }}.reason" placeholder="Ketik alasan...">
                        </td>
                        <td class="pos-grid-td" style="text-align: center;">
                            <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 transition-colors" title="Hapus Barang">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;">
                            Pilih No Penerimaan Barang (GR) untuk memuat barang yang bisa diretur.
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
            <button wire:click="save" style="background-color: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-red-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm1 2h7.086L12 4.914V13H3V3z"/><path d="M4 4h5v2H4V4zm0 5h8v4H4V9z"/></svg>
                SIMPAN RETUR
            </button>
            <a href="{{ route('filament.admin.resources.purchase-returns.index') }}" style="background-color: #fff; color: #374151; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; font-size: 0.875rem; border: 1px solid #d1d5db; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;" class="hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                Batal
            </a>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 ml-4 cursor-pointer">
                <input type="checkbox" wire:model="cetak_nota" class="rounded text-red-600">
                Cetak Bukti setelah simpan
            </label>
        </div>
        
        <!-- Totals -->
        <div style="display: flex; align-items: center; gap: 2rem;">
            <div style="text-align: right;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Total Baris / Qty Retur</div>
                <div style="font-weight: 600; font-size: 1rem; color: #374151;" class="dark:text-gray-200">{{ $totalLines }} Items / {{ number_format($totalQty, 0) }} Pcs</div>
            </div>

            <div style="text-align: right; min-width: 10rem;">
                <div style="color: #6b7280; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;" class="dark:text-gray-400">Grand Total Retur</div>
                <div style="font-weight: 700; font-size: 1.5rem; color: #dc2626;" class="dark:text-red-400">Rp {{ number_format($grandTotal, 2) }}</div>
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



