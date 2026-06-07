<div class="h-full flex flex-col bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden font-sans text-sm">
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
        
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .flex-end { display: flex; justify-content: flex-end; align-items: center; }
    </style>

    <!-- Top Section (Header Info) -->
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;" class="dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="grid-layout">
            
            <!-- Payment Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem;" class="text-gray-900 dark:text-gray-200">Info Pembayaran</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">No Pembayaran</td>
                        <td><input type="text" class="pos-input" style="background-color: #e5e7eb; cursor: not-allowed;" readonly wire:model="payment_number"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Tanggal</td>
                        <td><input type="date" class="pos-input" wire:model="payment_date"></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Cabang</td>
                        <td>
                            <select class="pos-input" wire:model="branch_id" @if(auth()->user()->branch_id) disabled @endif>
                                <option value="">Pusat</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Supplier & Payment Detail Panel -->
            <div>
                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem;" class="text-gray-900 dark:text-gray-200">Detail Supplier & Cara Bayar</div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 0 0.5rem;">
                    <tr>
                        <td style="width: 30%;" class="pos-label">Pilih Supplier</td>
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
                                        class="pos-input flex-between dark:text-gray-200 bg-white dark:bg-gray-800 cursor-pointer"
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span>{{ $supplier_id ? (collect($suppliers)->firstWhere('id', $supplier_id)['code'] . ' - ' . collect($suppliers)->firstWhere('id', $supplier_id)['name']) : 'Pilih Supplier...' }}</span>
                                    <svg style="width: 1rem; height: 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     style="position: absolute; left: 0; z-index: 50; margin-top: 0.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 15rem; overflow-y: auto; width: 100%;" 
                                     class="dark:bg-gray-800 dark:border-gray-700">
                                    <div style="position: sticky; top: 0; padding: 0.5rem; background: white; border-bottom: 1px solid #f3f4f6;" class="dark:bg-gray-800 dark:border-gray-700">
                                        <input type="text" x-model="search" class="pos-input" placeholder="Cari supplier..." autofocus @keydown.escape="open = false">
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
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">Metode Bayar</td>
                        <td>
                            <select class="pos-input" wire:model="payment_method">
                                <option value="CASH">Tunai (CASH)</option>
                                <option value="TRANSFER">Transfer Bank</option>
                                <option value="GIRO">Giro / Cek</option>
                                <option value="OTHER">Lainnya</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="pos-label">No Ref / Giro</td>
                        <td><input type="text" class="pos-input" wire:model="reference_number" placeholder="Boleh dikosongkan..."></td>
                    </tr>
                    <tr>
                        <td class="pos-label">Keterangan</td>
                        <td><input type="text" class="pos-input" wire:model="notes" placeholder="Catatan pembayaran..."></td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if (session()->has('error'))
            <div style="margin-top: 1rem; padding: 0.75rem; background-color: #fee2e2; color: #991b1b; border-radius: 0.375rem; border: 1px solid #f87171;" class="dark:bg-red-900/30 dark:text-red-400 dark:border-red-800">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Data Grid: Unpaid Invoices -->
    <div style="flex: 1; overflow-y: auto;" class="bg-white dark:bg-gray-900">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                <tr>
                    <th class="pos-grid-th" style="width: 3rem; text-align: center;">Pilih</th>
                    <th class="pos-grid-th">No Faktur (GR)</th>
                    <th class="pos-grid-th">Tgl Terima</th>
                    <th class="pos-grid-th">Jatuh Tempo</th>
                    <th class="pos-grid-th" style="text-align: right;">Total Tagihan</th>
                    <th class="pos-grid-th" style="text-align: right;">Sudah Dibayar</th>
                    <th class="pos-grid-th" style="text-align: right;">Sisa Tagihan</th>
                    <th class="pos-grid-th" style="width: 12rem; text-align: right;">Nominal Bayar (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unpaid_invoices as $index => $inv)
                    <tr class="{{ $inv['is_selected'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <td class="pos-grid-td" style="text-align: center;">
                            <input type="checkbox" wire:model.live="unpaid_invoices.{{ $index }}.is_selected" wire:change="toggleInvoice({{ $index }})" style="width: 1.25rem; height: 1.25rem; cursor: pointer; accent-color: #2563eb;">
                        </td>
                        <td class="pos-grid-td font-medium">{{ $inv['receipt_number'] }}</td>
                        <td class="pos-grid-td">{{ $inv['receipt_date'] }}</td>
                        <td class="pos-grid-td">
                            <span class="{{ \Carbon\Carbon::parse($inv['due_date'])->isPast() ? 'text-red-600 font-bold dark:text-red-400' : '' }}">
                                {{ $inv['due_date'] }}
                            </span>
                        </td>
                        <td class="pos-grid-td" style="text-align: right;">{{ number_format($inv['total_amount'], 0) }}</td>
                        <td class="pos-grid-td text-green-600 dark:text-green-400" style="text-align: right;">{{ number_format($inv['paid_amount'], 0) }}</td>
                        <td class="pos-grid-td font-bold" style="text-align: right;">{{ number_format($inv['remaining_amount'], 0) }}</td>
                        <td class="pos-grid-td" style="padding: 0.25rem;">
                            <input type="number" step="any" class="pos-input" style="text-align: right; font-weight: 700; color: #2563eb; background: {{ $inv['is_selected'] ? 'white' : '#f3f4f6' }};" 
                                   wire:model.live.debounce.300ms="unpaid_invoices.{{ $index }}.pay_amount"
                                   @if(!$inv['is_selected']) disabled @endif>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #6b7280; font-style: italic;" class="dark:text-gray-400">
                            @if($supplier_id)
                                Hore! Tidak ada tagihan yang belum lunas untuk pemasok ini.
                            @else
                                Silakan pilih Pemasok terlebih dahulu untuk melihat daftar tagihan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Bottom Action Bar -->
    <div style="background-color: white; border-top: 1px solid #e5e7eb; padding: 1rem; position: sticky; bottom: 0; z-index: 20;" class="dark:bg-gray-800 dark:border-gray-700">
        <div class="flex-between">
            <div style="font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">
                Menampilkan {{ count($unpaid_invoices) }} faktur tertunda.
            </div>
            
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <div style="text-align: right;">
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;" class="dark:text-gray-400">Total Pembayaran</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #2563eb; line-height: 1;">Rp {{ number_format($total_amount, 0) }}</div>
                </div>
                
                <button wire:click="save" wire:loading.attr="disabled"
                        style="background-color: #2563eb; color: white; border: none; border-radius: 0.5rem; padding: 0.75rem 2rem; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background-color 0.2s;"
                        class="hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Simpan Pembayaran</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
