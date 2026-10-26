<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 font-sans text-sm"
     x-data="{
        focusRowQty(index) {
            setTimeout(() => {
                let el = document.getElementById('qty-' + index);
                if (el) { el.focus(); el.select(); }
            }, 100);
        }
     }"
     @item-added.window="focusRowQty($event.detail.index)"
     @open-print-tab.window="window.open($event.detail.url, '_blank')">

    <style>
        .pos-input { padding: 0.45rem 0.6rem; border: 1px solid #d1d5db; width: 100%; border-radius: 0.375rem; background-color: #f9fafb; font-size: 0.875rem; }
        .pos-input:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 1px #dc2626; }
        .pos-label { font-weight: 500; color: #4b5563; font-size: 0.875rem; padding-right: 0.5rem; }
        .pos-grid-th { background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 0.5rem 0.65rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;}
        .pos-grid-td { border-bottom: 1px solid #f3f4f6; padding: 0.45rem 0.65rem; background-color: white; font-size: 0.875rem; color: #1f2937; }
        
        .dark .pos-input { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .pos-input:focus { border-color: #f87171 !important; box-shadow: 0 0 0 1px #f87171 !important; }
        .dark .pos-label { color: #9ca3af !important; }
        .dark .pos-grid-th { background-color: #111827 !important; border-color: #374151 !important; color: #d1d5db !important; }
        .dark .pos-grid-td { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .bg-white { background-color: #1f2937 !important; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 768px) {
            .grid-layout { grid-template-columns: 1fr; gap: 0.75rem; }
        }

        .search-result-item { border-left: 4px solid transparent; transition: all 0.1s ease-in-out; background-color: #ffffff; cursor: pointer; }
        .search-result-item:hover { background-color: #fee2e2 !important; border-left: 4px solid #dc2626 !important; }
        .highlighted-item { background-color: #fee2e2 !important; border-left: 4px solid #dc2626 !important; }
        
        .dark .search-result-item { background-color: #1f2937 !important; }
        .dark .search-result-item:hover { background-color: #374151 !important; border-left: 4px solid #f87171 !important; }
        .dark .highlighted-item { background-color: #374151 !important; border-left: 4px solid #f87171 !important; }
    </style>

    <!-- Top Section (Header) -->
    <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Left Panel: Info Dokumen -->
            <div>
                <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 6px; color: #b91c1c;" class="dark:text-red-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Pencatatan Barang Rusak ke Karantina BS
                </div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 32%;" class="pos-label">No. Dokumen</td>
                        <td>
                            <input type="text" class="pos-input" style="font-family: monospace; font-weight: 700; background-color: #fee2e2; color: #991b1b; cursor: not-allowed;" readonly wire:model="reference_number">
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tanggal Mutasi</td>
                        <td>
                            <input type="date" class="pos-input" wire:model="movement_date">
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Cabang Toko</td>
                        <td>
                            <select class="pos-input font-medium" wire:model.live="branch_id" @if(auth()->user()->branch_id) disabled style="background-color: #e5e7eb; cursor: not-allowed;" @endif>
                                <option value="">Pilih Cabang...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Right Panel: Catatan & Opsi -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.65rem; color: #374151;" class="dark:text-gray-200">Keterangan Transaksi</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 28%; vertical-align: top;" class="pos-label">Catatan Global</td>
                        <td>
                            <textarea class="pos-input" rows="2" placeholder="Misal: Penyisiran barang pecah dan expired di lorong bumbu & sabun" wire:model="notes"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Opsi Cetak</td>
                        <td>
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                                <input type="checkbox" wire:model="cetak_bukti" style="width: 18px; height: 18px; border-radius: 4px; accent-color: #dc2626;">
                                <span style="font-weight: 600; color: #1f2937;" class="dark:text-gray-200">Cetak Bukti Pemindahan BS setelah disimpan</span>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>

    <!-- Product Search Bar & Interactive Dropdown -->
    <div style="padding: 0.85rem 1.25rem; border-bottom: 1px solid #e5e7eb; position: relative; z-index: 40;" class="dark:border-gray-700 bg-white dark:bg-gray-900">
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <div style="position: relative; flex: 1;"
                 x-data="{
                    highlightedIndex: -1,
                    updateHighlight() {
                        let items = document.querySelectorAll('.search-result-item');
                        items.forEach((item, idx) => {
                            if (idx === this.highlightedIndex) {
                                item.classList.add('highlighted-item');
                                item.scrollIntoView({ block: 'nearest' });
                            } else {
                                item.classList.remove('highlighted-item');
                            }
                        });
                    },
                    moveDown() {
                        let items = document.querySelectorAll('.search-result-item');
                        let max = items.length - 1;
                        if (max >= 0 && this.highlightedIndex < max) {
                            this.highlightedIndex++;
                            this.updateHighlight();
                        }
                    },
                    moveUp() {
                        if (this.highlightedIndex > 0) {
                            this.highlightedIndex--;
                            this.updateHighlight();
                        }
                    },
                    selectCurrent() {
                        if (this.highlightedIndex >= 0) {
                            let items = document.querySelectorAll('.search-result-item');
                            if (items[this.highlightedIndex]) {
                                items[this.highlightedIndex].click();
                            }
                        } else {
                            $wire.call('handleScanEnter');
                        }
                        this.highlightedIndex = -1;
                    }
                 }">
                <div style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; z-index: 10;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                </div>
                <input 
                    type="text" 
                    id="searchQueryInput"
                    placeholder="Scan Barcode Scanner atau Ketik Nama Produk / SKU... (Gunakan panah ↑ ↓ lalu Enter)"
                    class="pos-input" 
                    style="padding-left: 2.75rem; font-size: 0.95rem; height: 44px; border-width: 2px;"
                    wire:model.live.debounce.150ms="searchQuery"
                    @input="highlightedIndex = -1"
                    @keydown.arrow-down.prevent="moveDown()"
                    @keydown.arrow-up.prevent="moveUp()"
                    @keydown.enter.prevent="selectCurrent()"
                    @keydown.escape="$wire.set('searchQuery', ''); highlightedIndex = -1;"
                    autofocus
                    autocomplete="off"
                >
                @if(!empty($searchQuery))
                    <button wire:click="$set('searchQuery', ''); $set('searchResults', []);" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; background: none; border: none; cursor: pointer; font-size: 1.25rem; z-index: 10;">
                        &times;
                    </button>
                @endif

                <!-- Search Results Dropdown -->
                @if(!empty($searchResults))
                    <div 
                        id="searchResultsDropdown"
                        style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 9999; max-height: 380px; overflow-y: auto; background-color: #ffffff; border: 2px solid #dc2626; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);" 
                        class="dark:bg-gray-800 dark:border-red-600"
                    >
                        <div style="padding: 0.4rem 0.85rem; background-color: #fee2e2; border-bottom: 1px solid #fecaca; display: flex; justify-content: space-between; font-size: 0.75rem; color: #991b1b; font-weight: 700;">
                            <span>HASIL PENCARIAN ({{ count($searchResults) }} PRODUK) — Tekan Panah ↑ ↓ lalu Enter</span>
                            <span>ESC untuk tutup</span>
                        </div>
                        @foreach($searchResults as $index => $res)
                            <div 
                                wire:click="selectProduct('{{ $res['id'] }}')"
                                @mouseenter="highlightedIndex = {{ $index }}; updateHighlight();"
                                class="search-result-item"
                                style="padding: 0.65rem 1rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;"
                            >
                                <div>
                                    <div style="font-weight: 700; color: #111827; font-size: 0.92rem;" class="dark:text-white">{{ $res['name'] }}</div>
                                    <div style="font-size: 0.75rem; color: #6b7280; font-family: monospace;">
                                        Barcode: <strong style="color: #1e40af;" class="dark:text-blue-400">{{ $res['barcode'] ?: '-' }}</strong> | SKU: {{ $res['sku'] }}
                                    </div>
                                </div>
                                <div style="text-align: right; font-size: 0.8rem; display: flex; gap: 8px; align-items: center;">
                                    @if((float)$res['salable_stock'] > 0)
                                        <span style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                            Layak Jual: {{ (float)$res['salable_stock'] }} {{ $res['unit_name'] }}
                                        </span>
                                    @else
                                        <span style="background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                            Stok Rak: 0 {{ $res['unit_name'] }}
                                        </span>
                                    @endif
                                    @if((float)$res['damaged_stock'] > 0)
                                        <span style="background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                            Di BS: {{ (float)$res['damaged_stock'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if(count($cart) > 0)
                <button wire:click="clearCart" wire:confirm="Yakin ingin mengosongkan seluruh keranjang barang karantina?" style="padding: 0.6rem 0.9rem; background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 0.375rem; font-weight: 600; font-size: 0.825rem; cursor: pointer; white-space: nowrap;">
                    Kosongkan Daftar
                </button>
            @endif
        </div>
    </div>

    <!-- Items Table (Cart) -->
    <div style="flex: 1; overflow-y: auto; min-height: 280px; position: relative; z-index: 10;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="pos-grid-th" style="width: 45px; text-align: center;">No</th>
                    <th class="pos-grid-th" style="width: 140px;">Barcode / SKU</th>
                    <th class="pos-grid-th">Nama Produk</th>
                    <th class="pos-grid-th" style="width: 110px; text-align: right;">Layak Jual</th>
                    <th class="pos-grid-th" style="width: 90px; text-align: right;">Di BS</th>
                    <th class="pos-grid-th" style="width: 110px; text-align: center;">Qty Rusak</th>
                    <th class="pos-grid-th" style="width: 200px;">Alasan Kerusakan</th>
                    <th class="pos-grid-th" style="width: 220px;">Catatan Kerusakan</th>
                    <th class="pos-grid-th" style="width: 60px; text-align: center;">Hapus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cart as $index => $item)
                    <tr wire:key="cart-row-{{ $item['product_id'] }}">
                        <td class="pos-grid-td" style="text-align: center; color: #6b7280; font-weight: 600;">
                            {{ $index + 1 }}
                        </td>
                        <td class="pos-grid-td">
                            <span style="font-family: monospace; font-weight: 700; color: #1e40af;" class="dark:text-blue-400">{{ $item['barcode'] ?: $item['sku'] }}</span>
                        </td>
                        <td class="pos-grid-td">
                            <div style="font-weight: 600;">{{ $item['name'] }}</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">Satuan: {{ $item['unit_name'] }}</div>
                        </td>
                        <td class="pos-grid-td" style="text-align: right;">
                            <span style="background-color: #ecfdf5; color: #047857; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                                {{ (float)$item['salable_stock'] }}
                            </span>
                        </td>
                        <td class="pos-grid-td" style="text-align: right; color: #dc2626; font-weight: 600;">
                            {{ (float)$item['damaged_stock'] }}
                        </td>
                        <td class="pos-grid-td" style="text-align: center;">
                            <input 
                                type="number" 
                                id="qty-{{ $index }}"
                                step="any"
                                min="0.01"
                                max="{{ $item['salable_stock'] }}"
                                class="pos-input" 
                                style="text-align: center; font-weight: 700; font-size: 0.95rem; width: 85px; margin: 0 auto; {{ (float)$item['qty'] > (float)$item['salable_stock'] ? 'border-color: #dc2626; background-color: #fee2e2;' : '' }}"
                                wire:model.live.debounce.300ms="cart.{{ $index }}.qty"
                                wire:change="updateQty({{ $index }}, $event.target.value)"
                            >
                        </td>
                        <td class="pos-grid-td">
                            <select class="pos-input" style="font-size: 0.8rem;" wire:model="cart.{{ $index }}.reason">
                                @foreach($reasonOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="pos-grid-td">
                            <input 
                                type="text" 
                                class="pos-input" 
                                style="font-size: 0.8rem;"
                                placeholder="Misal: Rembes di kardus"
                                wire:model="cart.{{ $index }}.notes"
                            >
                        </td>
                        <td class="pos-grid-td" style="text-align: center;">
                            <button 
                                wire:click="removeItem({{ $index }})" 
                                style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px;"
                                title="Hapus dari daftar"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem 1rem; color: #9ca3af;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto; height: 3.5rem; width: 3.5rem; margin-bottom: 0.75rem; color: #d1d5db;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <div style="font-weight: 600; font-size: 0.95rem; color: #4b5563;" class="dark:text-gray-300">Belum ada barang di keranjang karantina</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">Gunakan scanner barcode atau ketik nama produk pada kolom pencarian di atas untuk memasukkan barang.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Bottom Section: Summary & Action Buttons -->
    <div style="padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background-color: #f9fafb; position: relative; z-index: 10;" class="dark:bg-gray-800 dark:border-gray-700 flex-wrap gap-4">
        
        <!-- Summary Counters -->
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div style="background-color: white; border: 1px solid #e5e7eb; padding: 0.45rem 1rem; border-radius: 0.5rem;" class="dark:bg-gray-900 dark:border-gray-700">
                <span style="color: #6b7280; font-size: 0.8rem; font-weight: 500;">Total Varian Item:</span>
                <strong style="color: #111827; font-size: 1rem; margin-left: 6px;" class="dark:text-white">{{ $totalLines }}</strong>
            </div>
            <div style="background-color: #fee2e2; border: 1px solid #fecaca; padding: 0.45rem 1rem; border-radius: 0.5rem;">
                <span style="color: #991b1b; font-size: 0.8rem; font-weight: 500;">Total Qty Rusak / BS:</span>
                <strong style="color: #dc2626; font-size: 1.1rem; margin-left: 6px;">{{ $totalQty }} Pcs</strong>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a 
                href="/admin/stock-quarantines" 
                style="padding: 0.6rem 1.2rem; background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 0.5rem; font-weight: 600; text-decoration: none; font-size: 0.875rem;"
                class="dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 hover:bg-gray-200"
            >
                Kembali
            </a>
            <button 
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                style="padding: 0.6rem 1.4rem; background-color: #dc2626; color: white; border: none; border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);"
                class="hover:bg-red-700"
                @if(count($cart) === 0) disabled style="opacity: 0.5; cursor: not-allowed;" @endif
            >
                <span wire:loading.remove wire:target="save" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Simpan Pemindahan ke BS
                </span>
                <span wire:loading wire:target="save">
                    Menyimpan...
                </span>
            </button>
        </div>

    </div>

</div>
