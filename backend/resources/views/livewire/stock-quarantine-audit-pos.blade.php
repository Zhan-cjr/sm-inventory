<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 font-sans text-sm"
     x-data="{
        highlightedIndex: -1,
        get searchItems() {
            return document.querySelectorAll('.search-result-item');
        },
        moveDown() {
            let items = this.searchItems;
            if (items.length === 0) return;
            if (this.highlightedIndex < items.length - 1) {
                this.highlightedIndex++;
            } else {
                this.highlightedIndex = 0;
            }
            this.scrollToHighlighted();
        },
        moveUp() {
            let items = this.searchItems;
            if (items.length === 0) return;
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            } else {
                this.highlightedIndex = items.length - 1;
            }
            this.scrollToHighlighted();
        },
        selectHighlighted() {
            if (this.highlightedIndex >= 0) {
                let el = document.getElementById('search-item-' + this.highlightedIndex);
                if (el) {
                    el.click();
                    this.highlightedIndex = -1;
                    return;
                }
            }
            $wire.handleScanEnter();
            this.highlightedIndex = -1;
        },
        scrollToHighlighted() {
            this.$nextTick(() => {
                let el = document.getElementById('search-item-' + this.highlightedIndex);
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        focusSearch() {
            this.$nextTick(() => {
                let searchInput = document.getElementById('searchQueryInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }
     }"
     @focus-row-actual.window="
        setTimeout(() => {
            let el = document.getElementById('actual-' + $event.detail.index);
            if (el) {
                el.focus();
                el.select();
            }
        }, 80);
     "
     @open-print-tab.window="window.open($event.detail.url, '_blank')"
     x-init="
        $watch('$wire.searchResults', () => {
            highlightedIndex = -1;
        });
     ">

    <style>
        .pos-input { padding: 0.45rem 0.6rem; border: 1px solid #d1d5db; width: 100%; border-radius: 0.375rem; background-color: #f9fafb; font-size: 0.875rem; }
        .pos-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 1px #2563eb; }
        .pos-label { font-weight: 500; color: #4b5563; font-size: 0.875rem; padding-right: 0.5rem; }
        .pos-grid-th { background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 0.5rem 0.65rem; text-align: left; font-weight: 600; color: #374151; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;}
        .pos-grid-td { border-bottom: 1px solid #f3f4f6; padding: 0.45rem 0.65rem; background-color: white; font-size: 0.875rem; color: #1f2937; vertical-align: middle; }
        
        .dark .pos-input { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .pos-input:focus { border-color: #60a5fa !important; box-shadow: 0 0 0 1px #60a5fa !important; }
        .dark .pos-label { color: #9ca3af !important; }
        .dark .pos-grid-th { background-color: #111827 !important; border-color: #374151 !important; color: #d1d5db !important; }
        .dark .pos-grid-td { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .dark .bg-white { background-color: #1f2937 !important; }
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 768px) {
            .grid-layout { grid-template-columns: 1fr; gap: 0.75rem; }
        }

        .search-result-item { border-left: 4px solid transparent; transition: all 0.1s ease-in-out; background-color: #ffffff; cursor: pointer; }
        .search-result-item:hover, .search-result-item.is-selected { background-color: #eff6ff !important; border-left: 4px solid #2563eb !important; }
        .dark .search-result-item { background-color: #1f2937 !important; }
        .dark .search-result-item:hover, .dark .search-result-item.is-selected { background-color: #1e3a8a !important; border-left: 4px solid #3b82f6 !important; }
    </style>

    <!-- Top Section (Header) -->
    <div style="padding: 1.25rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Left Panel: Info Audit -->
            <div>
                <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 6px; color: #1e40af;" class="dark:text-blue-400">
                    <x-heroicon-o-clipboard-document-check style="width: 22px; height: 22px;" />
                    Audit & Pemeriksaan Keranjang Karantina BS
                </div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 32%;" class="pos-label">No. Audit</td>
                        <td>
                            <input type="text" class="pos-input" style="font-family: monospace; font-weight: 700; background-color: #eff6ff; color: #1e40af; cursor: not-allowed;" readonly wire:model="audit_number">
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tanggal Audit</td>
                        <td>
                            <input type="date" class="pos-input" wire:model="audit_date">
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Cabang Toko</td>
                        <td>
                            <select class="pos-input font-medium" wire:model.live="branch_id" @if(auth()->user()->branch_id !== null) disabled @endif>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Right Panel: Statistik & Catatan -->
            <div style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <label class="pos-label" style="display: block; margin-bottom: 4px;">Catatan Audit / Temuan Lapangan</label>
                    <textarea class="pos-input" rows="2" placeholder="Misal: Audit mingguan keranjang BS sebelum sales retur datang..." wire:model="notes"></textarea>
                </div>

                <!-- Badges Ringkasan -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 10px;">
                    <div style="background: #f3f4f6; border: 1px solid #e5e7eb; padding: 8px; border-radius: 8px; text-align: center;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="font-size: 11px; color: #6b7280;">Item Dihitung</div>
                        <div style="font-weight: 700; font-size: 14px; color: #111827;" class="dark:text-white">
                            {{ $totalItemsCount }} <span style="font-size: 11px; font-weight: normal; color: #6b7280;">/ {{ $totalExpectedBsItems }}</span>
                        </div>
                    </div>
                    <div style="background: #f3f4f6; border: 1px solid #e5e7eb; padding: 8px; border-radius: 8px; text-align: center;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="font-size: 11px; color: #6b7280;">Sistem BS</div>
                        <div style="font-weight: 700; font-size: 14px; color: #4b5563;" class="dark:text-gray-300">{{ number_format($totalSystem, 0) }}</div>
                    </div>
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px; border-radius: 8px; text-align: center;" class="dark:bg-blue-900/30 dark:border-blue-800">
                        <div style="font-size: 11px; color: #1e40af;" class="dark:text-blue-300">Fisik Riil</div>
                        <div style="font-weight: 700; font-size: 14px; color: #1d4ed8;" class="dark:text-blue-400">{{ number_format($totalActual, 0) }}</div>
                    </div>
                    <div style="background: {{ $totalDiscrepancy < 0 ? '#fef2f2' : ($totalDiscrepancy > 0 ? '#fefce8' : '#ecfdf5') }}; border: 1px solid {{ $totalDiscrepancy < 0 ? '#fecaca' : ($totalDiscrepancy > 0 ? '#fef08a' : '#a7f3d0') }}; padding: 8px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: {{ $totalDiscrepancy < 0 ? '#991b1b' : ($totalDiscrepancy > 0 ? '#854d0e' : '#065f46') }};">Selisih Fisik</div>
                        <div style="font-weight: 700; font-size: 14px; color: {{ $totalDiscrepancy < 0 ? '#dc2626' : ($totalDiscrepancy > 0 ? '#ca8a04' : '#059669') }};">
                            {{ $totalDiscrepancy > 0 ? '+' : '' }}{{ number_format($totalDiscrepancy, 0) }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Search / Scan Barcode -->
        <div style="margin-top: 14px; position: relative;">
            <div style="display: flex; gap: 8px; align-items: center;">
                <div style="position: relative; flex: 1;">
                    <input 
                        id="searchQueryInput"
                        type="text" 
                        class="pos-input" 
                        style="padding-left: 36px; padding-right: 36px; background-color: #ffffff; font-size: 13.5px;"
                        placeholder="Scan Barcode / Ketik nama barang (Gunakan ↑ ↓ dan Enter untuk memilih)..."
                        wire:model.live.debounce.200ms="searchQuery"
                        @keydown.arrow-down.prevent="moveDown()"
                        @keydown.arrow-up.prevent="moveUp()"
                        @keydown.enter.prevent="selectHighlighted()"
                        @keydown.escape="highlightedIndex = -1"
                        autocomplete="off"
                        autofocus
                    >
                    <div style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af;">
                        <x-heroicon-o-magnifying-glass style="width: 18px; height: 18px;" />
                    </div>
                    @if(!empty($searchQuery))
                        <button type="button" 
                                wire:click="$set('searchQuery', '')" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; background: none; border: none; cursor: pointer;"
                                title="Hapus pencarian">
                            <x-heroicon-m-x-circle style="width: 18px; height: 18px;" />
                        </button>
                    @endif
                </div>

                @if(!empty($items))
                    <button type="button" wire:click="markAllMatch" 
                            style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 7px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #374151; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 4px;"
                            title="Isi fisik riil sama persis dengan saldo sistem">
                        <x-heroicon-m-check-badge style="width: 16px; height: 16px; color: #059669;" />
                        Samakan Fisik = Sistem
                    </button>
                    <button type="button" wire:click="clearTable" 
                            style="background: #fef2f2; border: 1px solid #fecaca; padding: 7px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #dc2626; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 4px;"
                            title="Kosongkan tabel audit untuk mulai ulang">
                        <x-heroicon-o-trash style="width: 15px; height: 15px;" />
                        Kosongkan
                    </button>
                @else
                    @if($totalExpectedBsItems > 0)
                        <button type="button" wire:click="loadAllBranchBsItems" 
                                style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #1e40af; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 4px;"
                                title="Tampilkan seluruh item BS yang tercatat di sistem">
                            <x-heroicon-o-arrow-down-tray style="width: 16px; height: 16px; color: #2563eb;" />
                            Muat Seluruh BS Sistem ({{ $totalExpectedBsItems }} Item)
                        </button>
                    @endif
                @endif
            </div>

            <!-- Search Dropdown Results -->
            @if(!empty($searchResults))
                <div style="position: absolute; left: 0; right: 0; top: 100%; z-index: 50; max-height: 280px; overflow-y: auto; background: white; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); margin-top: 4px;" class="dark:bg-gray-800 dark:border-gray-700">
                    @foreach($searchResults as $idx => $res)
                        <div id="search-item-{{ $idx }}"
                             class="search-result-item" 
                             :class="{ 'is-selected': highlightedIndex === {{ $idx }} }"
                             @mouseenter="highlightedIndex = {{ $idx }}"
                             style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;"
                             wire:click="selectProduct('{{ $res['id'] }}')">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 13px; color: #1e293b;" class="dark:text-white">
                                    {{ $res['name'] }}
                                </div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                    <span style="font-family: monospace; font-weight: 600; color: #334155;" class="dark:text-gray-300">
                                        {{ $res['barcode'] ?: $res['sku'] }}
                                    </span>
                                    &nbsp;&bull;&nbsp; Satuan: {{ $res['unit_of_measure'] }}
                                    &nbsp;&bull;&nbsp; Sistem BS: <strong style="color: {{ $res['quantity_damaged'] > 0 ? '#b45309' : '#64748b' }};">{{ number_format($res['quantity_damaged'], 0) }}</strong>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 5px; font-weight: 600; border: 1px solid #bfdbfe;">
                                    Pilih (Enter ↵)
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Table Grid -->
    <div style="flex: 1; overflow-y: auto; padding: 0.5rem 1.25rem;">
        @if(empty($items))
            <div style="text-align: center; padding: 3.5rem 1rem; color: #6b7280; max-width: 520px; margin: 0 auto;">
                <div style="width: 56px; height: 56px; background-color: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; border: 1px solid #dbeafe;">
                    <x-heroicon-o-qr-code style="width: 30px; height: 30px; color: #2563eb;" />
                </div>
                <div style="font-weight: 700; font-size: 16px; color: #1f2937;" class="dark:text-gray-100">
                    Keranjang Audit Masih Kosong
                </div>
                <p style="font-size: 13px; margin-top: 6px; line-height: 1.5; color: #4b5563;" class="dark:text-gray-400">
                    Silakan <strong>scan barcode</strong> pada kemasan produk atau <strong>ketik nama barang</strong> di kolom pencarian di atas untuk memulai audit fisik keranjang BS.
                </p>
                <div style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; background-color: #f3f4f6; padding: 6px 14px; border-radius: 20px; color: #374151;" class="dark:bg-gray-800 dark:text-gray-300">
                    <span>📦 Total tercatat di sistem BS cabang ini: <strong>{{ $totalExpectedBsItems }} item</strong></span>
                </div>
                @if($totalExpectedBsItems > 0)
                    <div style="margin-top: 16px;">
                        <button type="button" wire:click="loadAllBranchBsItems" 
                                style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #2563eb; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            Atau klik di sini untuk memuat seluruh {{ $totalExpectedBsItems }} item BS dari sistem
                        </button>
                    </div>
                @endif
            </div>
        @else
            <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                <thead>
                    <tr>
                        <th class="pos-grid-th" style="width: 35px; text-align: center;">No</th>
                        <th class="pos-grid-th" style="width: 140px;">Barcode / SKU</th>
                        <th class="pos-grid-th">Nama Produk</th>
                        <th class="pos-grid-th" style="width: 90px; text-align: right;">Sistem BS</th>
                        <th class="pos-grid-th" style="width: 110px; text-align: right;">Fisik Riil</th>
                        <th class="pos-grid-th" style="width: 85px; text-align: right;">Selisih</th>
                        <th class="pos-grid-th" style="width: 200px;">Tindakan Selisih</th>
                        <th class="pos-grid-th" style="width: 170px;">Catatan</th>
                        <th class="pos-grid-th" style="width: 35px; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        @php 
                            $diff = (float)$item['discrepancy']; 
                        @endphp
                        <tr>
                            <td class="pos-grid-td text-center" style="color: #6b7280; font-size: 12px;">{{ $index + 1 }}</td>
                            <td class="pos-grid-td" style="font-family: monospace; font-size: 12px;">
                                {{ $item['barcode'] ?: $item['sku'] }}
                            </td>
                            <td class="pos-grid-td">
                                <div style="font-weight: 600;">{{ $item['name'] }}</div>
                                <div style="font-size: 11px; color: #6b7280;">HPP: Rp {{ number_format($item['cost_price'], 0, ',', '.') }} / {{ $item['unit'] }}</div>
                            </td>
                            <td class="pos-grid-td text-right font-medium" style="color: #4b5563;">
                                {{ number_format($item['system_damaged'], 0) }}
                            </td>
                            <td class="pos-grid-td text-right">
                                <input 
                                    id="actual-{{ $index }}"
                                    type="number" 
                                    step="1"
                                    min="0"
                                    class="pos-input text-right font-bold" 
                                    style="width: 85px; display: inline-block; padding: 4px 6px; font-size: 14px; {{ $diff != 0 ? 'border-color: #f59e0b; background-color: #fffbeb;' : '' }}"
                                    value="{{ $item['actual_damaged'] }}"
                                    wire:change="updateActual({{ $index }}, $event.target.value)"
                                    @keydown.enter.prevent="$wire.updateActual({{ $index }}, $event.target.value); focusSearch();"
                                >
                            </td>
                            <td class="pos-grid-td text-right font-bold" style="font-size: 14px; color: {{ $diff < 0 ? '#dc2626' : ($diff > 0 ? '#16a34a' : '#4b5563') }};">
                                {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0) }}
                            </td>
                            <td class="pos-grid-td">
                                <select class="pos-input" style="font-size: 12px; padding: 3px 6px;" 
                                        wire:change="setAction({{ $index }}, $event.target.value)">
                                    <option value="NONE" @if($item['action'] === 'NONE') selected @endif>✅ Sesuai (Klop)</option>
                                    <option value="WRITE_OFF_DISPOSAL" @if($item['action'] === 'WRITE_OFF_DISPOSAL') selected @endif>🗑️ Pemusnahan (Hilang/Dibuang)</option>
                                    <option value="RESTORE_TO_SALABLE" @if($item['action'] === 'RESTORE_TO_SALABLE') selected @endif>🔄 Kembalikan ke Rak Display</option>
                                    <option value="ADJUST_INCREASE" @if($item['action'] === 'ADJUST_INCREASE') selected @endif>⚠️ Tambah ke Karantina BS</option>
                                </select>
                            </td>
                            <td class="pos-grid-td">
                                <input 
                                    type="text" 
                                    class="pos-input" 
                                    style="padding: 3px 6px; font-size: 12px;"
                                    placeholder="Alasan selisih..." 
                                    wire:model="items.{{ $index }}.notes"
                                    @keydown.enter.prevent="focusSearch()"
                                >
                            </td>
                            <td class="pos-grid-td text-center">
                                <button type="button" wire:click="removeItem({{ $index }})" 
                                        style="color: #ef4444; padding: 4px; border-radius: 4px; border: none; background: none; cursor: pointer;"
                                        title="Hapus baris ini dari audit">
                                    <x-heroicon-o-trash style="width: 16px; height: 16px;" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Bottom Footer (Actions) -->
    <div style="padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #fafafa;" class="dark:border-gray-700 dark:bg-gray-800">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: #374151;" class="dark:text-gray-300">
            <input type="checkbox" wire:model="cetak_bap" style="width: 16px; height: 16px; accent-color: #2563eb; border-radius: 4px;">
            <span>Cetak Berita Acara (BAP Audit BS) setelah simpan</span>
        </label>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="/admin/stock-quarantine-audits" 
               style="background: #f3f4f6; color: #374151; padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; border: 1px solid #d1d5db; font-size: 13px;">
                Batal
            </a>
            <button type="button" wire:click="saveAudit"
                    wire:loading.attr="disabled"
                    style="background: #2563eb; color: white; padding: 8px 20px; border-radius: 6px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 13px; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                <x-heroicon-m-check style="width: 18px; height: 18px;" />
                <span wire:loading.remove>Simpan & Terapkan Hasil Audit BS</span>
                <span wire:loading>Memproses...</span>
            </button>
        </div>
    </div>
</div>
