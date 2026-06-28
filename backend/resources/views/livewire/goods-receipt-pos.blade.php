<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden font-sans text-sm"
     x-data="{
        focusRowQty(index) {
            setTimeout(() => {
                let el = document.getElementById('qty-' + index);
                if (el) { el.focus(); el.select(); }
            }, 100);
        },
        calcOpen: false,
        calcValue: '',
        calcTop: 0,
        calcLeft: 0,
        openCalc(event) {
            this.calcTarget = event.target;
            this.calcValue = this.calcTarget.value || '';
            
            let rect = this.calcTarget.getBoundingClientRect();
            this.calcTop = rect.bottom + 4;
            
            let calcWidth = 232;
            let leftPos = rect.right - calcWidth;
            if (leftPos < 10) leftPos = 10;
            if (this.calcTop + 320 > window.innerHeight) {
                this.calcTop = rect.top - 320 - 4;
            }
            this.calcLeft = leftPos;
            
            this.calcOpen = true;
            setTimeout(() => { 
                let el = document.getElementById('mini-calc-input');
                if(el) { el.focus(); el.select(); }
            }, 50);
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
        },
        closeCalc() {
            this.calcOpen = false;
            if (this.calcTarget) {
                this.calcTarget.focus();
                // Select the input if possible
                if (typeof this.calcTarget.select === 'function') {
                    this.calcTarget.select();
                }
            }
        },
        applyCalc() {
            try {
                let sanitized = String(this.calcValue).replace(/[^-()\d/*+.]/g, '');
                if (sanitized) {
                    let result = new Function('return ' + sanitized)();
                    if (!isNaN(result)) {
                        this.calcTarget.value = result;
                        this.calcTarget.dispatchEvent(new Event('input', { bubbles: true }));
                        this.calcTarget.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            } catch(e) { console.error('Calc error:', e); }
            this.closeCalc();
        }
     }"
     @item-added.window="focusRowQty($event.detail.index)">

    <!-- Mini Calculator Popup -->
    <div x-show="calcOpen" @click.away="closeCalc()" 
         style="display: none; position: fixed; z-index: 9999; width: 232px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border-radius: 8px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #4a4a4a; border: 1px solid #333;" 
         :style="{ top: calcTop + 'px', left: calcLeft + 'px' }" x-transition>
        
        <div style="background-color: #4a4a4a; padding: 16px 16px 8px 16px; text-align: right; border-bottom: 1px solid #333;">
            <input type="text" id="mini-calc-input" x-model="calcValue" @keydown.enter.prevent="applyCalc()" @keydown.escape.prevent="closeCalc()" style="background: transparent; border: none; color: white; width: 100%; text-align: right; outline: none; font-size: 32px; font-weight: 300; padding: 0;" autocomplete="off">
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background-color: #333;">
            <button type="button" @click="calcValue = ''" style="background-color: #646464; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 14px;">AC</button>
            <button type="button" @click="calcValue = calcValue ? (String(calcValue).startsWith('-') ? String(calcValue).substring(1) : '-' + calcValue) : '-'" style="background-color: #646464; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 14px;">+/-</button>
            <button type="button" @click="calcValue = calcValue + '/100'" style="background-color: #646464; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 14px;">%</button>
            <button type="button" @click="calcValue = calcValue + '/'" style="background-color: #ff9f0a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px;">÷</button>

            <button type="button" @click="calcValue = calcValue + '7'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">7</button>
            <button type="button" @click="calcValue = calcValue + '8'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">8</button>
            <button type="button" @click="calcValue = calcValue + '9'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">9</button>
            <button type="button" @click="calcValue = calcValue + '*'" style="background-color: #ff9f0a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px;">×</button>

            <button type="button" @click="calcValue = calcValue + '4'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">4</button>
            <button type="button" @click="calcValue = calcValue + '5'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">5</button>
            <button type="button" @click="calcValue = calcValue + '6'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">6</button>
            <button type="button" @click="calcValue = calcValue + '-'" style="background-color: #ff9f0a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px;">−</button>

            <button type="button" @click="calcValue = calcValue + '1'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">1</button>
            <button type="button" @click="calcValue = calcValue + '2'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">2</button>
            <button type="button" @click="calcValue = calcValue + '3'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">3</button>
            <button type="button" @click="calcValue = calcValue + '+'" style="background-color: #ff9f0a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px;">+</button>

            <button type="button" @click="calcValue = calcValue + '0'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500; grid-column: span 2; text-align: left; padding-left: 20px;">0</button>
            <button type="button" @click="calcValue = calcValue + '.'" style="background-color: #7a7a7a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px; font-weight: 500;">.</button>
            <button type="button" @click="applyCalc()" style="background-color: #ff9f0a; color: white; padding: 12px 0; border: none; cursor: pointer; font-size: 18px;">=</button>
        </div>
    </div>
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
                        <td><input onfocus="this.select()" type="date" class="pos-input" wire:model.live="receipt_date" @if($goodsReceipt) disabled style="background-color: #e5e7eb; cursor: not-allowed;" @endif></td>
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
                                <select class="pos-input" wire:model.live="purchase_order_id" style="border-color: #3b82f6; flex: 1;" @if($goodsReceipt) disabled style="background-color: #e5e7eb; cursor: not-allowed;" @elseif(!$supplier_id) disabled title="Pilih supplier terlebih dahulu" @endif>
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
                        <td class="pos-label">Upload Bukti Faktur</td>
                        <td>
                            <input type="file" class="pos-input" wire:model="faktur_image" accept="image/jpeg, image/png, application/pdf" multiple style="padding-top: 0.25rem;">
                            <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Bisa pilih >1 file. Maks 10MB/file. Gambar dikompres otomatis.</div>
                            @error('faktur_image.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <div wire:loading wire:target="faktur_image" class="text-xs text-blue-500 mt-1">Mengupload...</div>
                            
                            <!-- Daftar Foto Lama (Jika sedang Edit) -->
                            @if(count($existing_faktur_image) > 0)
                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 font-medium border-t border-gray-200 dark:border-gray-700 pt-2">
                                    File Tersimpan:
                                    <ul class="mt-1 space-y-1">
                                        @foreach($existing_faktur_image as $index => $path)
                                            <li class="flex items-center justify-between bg-blue-50 dark:bg-gray-800 p-1 px-2 rounded border border-blue-100 dark:border-gray-700">
                                                <span class="text-xs truncate" style="max-width: 200px;" title="{{ $path }}">File {{ $index + 1 }}</span>
                                                <button type="button" wire:click="removeExistingImage({{ $index }})" class="text-red-500 hover:text-red-700" title="Hapus file ini">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Daftar Foto Baru -->
                            @if($faktur_image && is_array($faktur_image) && count($faktur_image) > 0)
                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 font-medium border-t border-gray-200 dark:border-gray-700 pt-2">
                                    Pilihan Baru:
                                    <ul class="mt-1 space-y-1">
                                        @foreach($faktur_image as $index => $file)
                                            <li class="flex items-center justify-between bg-green-50 dark:bg-gray-800 p-1 px-2 rounded border border-green-100 dark:border-gray-700">
                                                <span class="text-xs truncate text-green-700 dark:text-green-400" style="max-width: 200px;">{{ is_string($file) ? $file : $file->getClientOriginalName() }}</span>
                                                <button type="button" wire:click="removeNewImage({{ $index }})" class="text-red-500 hover:text-red-700" title="Batal upload file ini">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </td>
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
            <div style="position: absolute; left: 0; right: 0; top: 100%; z-index: 100; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-top: 0.25rem; max-height: 300px; overflow-y: auto;" class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                @foreach($searchResults as $index => $result)
                <div wire:click="selectProduct('{{ $result->id }}')" 
                     class="search-result-item hover:bg-blue-50 dark:hover:bg-gray-700 dark:border-gray-700"
                     style="padding: 0.75rem; cursor: pointer; border-bottom: 1px solid #f9fafb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">{{ $result->sku }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $result->name }}</div>
                    </div>
                    <div style="font-weight: 600; color: #3b82f6;">Rp {{ number_format($result->cost_price, 0) }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer whitespace-nowrap bg-blue-50 dark:bg-blue-900/30 px-3 py-1.5 rounded-md border border-blue-200 dark:border-blue-800">
                <input type="checkbox" wire:model.live="enable_edit_total" class="rounded text-blue-600">
                <span class="font-medium">Aktifkan Edit Total</span>
            </label>
            <div x-data="{ open: false }" style="position: relative;">
                <button @click="open = !open" class="pos-input dark:text-gray-200 dark:bg-gray-800" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; background: transparent;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z"/></svg>
                    Pilih Kolom
                </button>
                <div x-show="open" @click.away="open = false" style="position: absolute; right: 0; top: 100%; z-index: 50; border-radius: 0.5rem; padding: 0.5rem; width: 12rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    @foreach(['sku' => 'SKU', 'barcode' => 'Barcode', 'name' => 'Nama Produk', 'qty_ordered' => 'Qty PO', 'qty_received' => 'Qty Terima', 'unit_price' => 'Harga Satuan', 'harga_jual_1' => 'Harga Jual', 'margin_gol_1' => 'Margin', 'discount_1' => 'Dis1', 'discount_2' => 'Dis2', 'discount_3' => 'Dis3'] as $key => $label)
                        <label class="flex items-center gap-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer rounded">
                            <input type="checkbox" wire:model.live="visibleColumns" value="{{ $key }}" class="rounded text-blue-600">
                            <span class="text-xs text-gray-800 dark:text-gray-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
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
                    @if(in_array('qty_ordered', $visibleColumns)) <th class="pos-grid-th" style="width: 4.5rem; text-align: right;">Qty PO</th> @endif
                    @if(in_array('qty_received', $visibleColumns)) <th class="pos-grid-th" style="width: 5.5rem; text-align: right;">Qty Terima</th> @endif
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
            <tbody @keydown="handleGridNav($event)">
                @forelse($cart as $index => $item)
                    <tr>
                        <td class="pos-grid-td" style="text-align: center;">{{ $loop->iteration }}</td>
                        @if(in_array('sku', $visibleColumns)) <td class="pos-grid-td">{{ $item['sku'] }}</td> @endif
                        @if(in_array('barcode', $visibleColumns)) <td class="pos-grid-td">{{ $item['barcode'] }}</td> @endif
                        @if(in_array('name', $visibleColumns)) 
                            <td class="pos-grid-td" style="font-weight: 500;">
                                <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; line-height: 1.2;">
                                    {{ $item['name'] }}
                                </div>
                            </td> 
                        @endif
                        @if(in_array('qty_ordered', $visibleColumns)) <td class="pos-grid-td dark:text-gray-400" style="text-align: right; color: #6b7280;">{{ $item['qty_ordered'] > 0 ? number_format($item['qty_ordered'], 0) : '-' }}</td> @endif
                        @if(in_array('qty_received', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="qty-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right; font-weight: 700; color: #2563eb;" 
                                   wire:model.lazy="cart.{{ $index }}.qty_received"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.space.prevent="openCalc($event)">
                        </td>
                        @endif
                        @if(in_array('unit_price', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <div x-data="{ raw: @entangle('cart.' . $index . '.unit_price'), focused: false, get display() { if (this.focused) return this.raw; let rawStr = (this.raw || 0).toString(); let num = parseFloat(rawStr.replace(/,/g, '')); return isNaN(num) ? '' : num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }, set display(val) { this.raw = (val || '').toString().replace(/,/g, ''); $wire.recalculateRow({{ $index }}); } }">
                                <input type="text" x-model.lazy="display" @focus="focused = true; $nextTick(() => $el.select())" @blur="focused = false" id="price-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                       x-on:keydown.space.prevent="openCalc($event)">
                            </div>
                        </td>
                        @endif

                        @if(in_array('harga_jual_1', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            @if(auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE'))
                                <div x-data="{ raw: @entangle('cart.' . $index . '.harga_jual_1'), focused: false, get display() { if (this.focused) return this.raw; let rawStr = (this.raw || 0).toString(); let num = parseFloat(rawStr.replace(/,/g, '')); return isNaN(num) ? '' : num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }, set display(val) { this.raw = (val || '').toString().replace(/,/g, ''); $wire.recalculateRow({{ $index }}); } }">
                                    <input type="text" x-model.lazy="display" @focus="focused = true; $nextTick(() => $el.select())" @blur="focused = false" class="pos-input pos-grid-input" style="text-align: right;" 
                                           x-on:keydown.space.prevent="openCalc($event)">
                                </div>
                            @else
                                <div style="text-align: right; padding: 0.375rem 0.5rem; color: #6b7280;" class="dark:text-gray-400">{{ number_format($item['harga_jual_1'], 2) }}</div>
                            @endif
                        </td>
                        @endif
                        @if(in_array('margin_gol_1', $visibleColumns))
                        <td class="pos-grid-td dark:bg-gray-800" style="padding: 0.25rem; background-color: #f9fafb;">
                            @if(auth()->user()->hasCustomAuthorization('UPDATE_SELLING_PRICE'))
                                <input type="number" step="any" class="pos-input pos-grid-input {{ $item['margin_gol_1'] < 0 ? 'text-red-500' : 'text-green-600' }} font-medium" style="text-align: right; width: 100%;" 
                                       wire:model.lazy="cart.{{ $index }}.margin_gol_1"
                                       wire:change="recalculateRow({{ $index }})"
                                       x-on:keydown.space.prevent="openCalc($event)">
                            @else
                                <div style="text-align: right; padding: 0.375rem 0.5rem;" class="{{ $item['margin_gol_1'] < 0 ? 'text-red-500' : 'text-green-600' }} font-medium">{{ number_format($item['margin_gol_1'], 2) }}</div>
                            @endif
                        </td>
                        @endif
                        @if(in_array('discount_1', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis1-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_1"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.space.prevent="openCalc($event)">
                        </td>
                        @endif
                        @if(in_array('discount_2', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" id="dis2-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_2"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.space.prevent="openCalc($event)">
                        </td>
                        @endif
                        @if(in_array('discount_3', $visibleColumns))
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input onfocus="this.select()" type="number" step="any" id="dis3-{{ $index }}" class="pos-input pos-grid-input" style="text-align: right;" 
                                   wire:model.lazy="cart.{{ $index }}.discount_3"
                                   wire:change="recalculateRow({{ $index }})"
                                   x-on:keydown.space.prevent="openCalc($event)">
                        </td>
                        @endif
                        <td class="pos-grid-td" style="text-align: right; font-weight: 600; color: #111827;" class="dark:text-gray-100">
                            @if($enable_edit_total)
                                <div x-data="{ raw: @entangle('cart.' . $index . '.subtotal'), focused: false, get display() { if (this.focused) return this.raw; let rawStr = (this.raw || 0).toString(); let num = parseFloat(rawStr.replace(/,/g, '')); return isNaN(num) ? '' : num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }, set display(val) { this.raw = (val || '').toString().replace(/,/g, ''); } }">
                                    <input type="text" x-model.lazy="display" @focus="focused = true; $nextTick(() => $el.select())" @blur="focused = false" class="pos-input font-bold" style="text-align: right;" 
                                           x-on:keydown.space.prevent="openCalc($event)">
                                </div>
                            @else
                                {{ number_format($item['subtotal'], 2) }}
                            @endif
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



