@php
    $statePath = $getStatePath();
@endphp

<div 
    x-data="{
        items: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
        
        removeItem(index) {
            if (confirm('Hapus baris ini dari daftar?')) {
                this.items.splice(index, 1);
            }
        },
        
        toggleForceNew(index) {
            this.items[index].force_new = !this.items[index].force_new;
        },

        addItem() {
            if (!this.items) this.items = [];
            this.items.push({
                id: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
                name: '',
                cost_price: 0,
                selling_price: 0,
                category_id: null,
                sub_category: null,
                status: 'NEW',
                product_id: null,
                matched_name: null,
                barcode: '[Otomatis]',
                sku: '[Otomatis]',
                force_new: false,
            });
        },

        get matchedCount() {
            if (!this.items) return 0;
            return this.items.filter(i => i.status === 'MATCHED' && !i.force_new).length;
        },

        get newCount() {
            if (!this.items) return 0;
            return this.items.filter(i => i.status === 'NEW' || i.force_new).length;
        }
    }"
    class="w-full space-y-3"
>
    <style>
        .qc-table-container {
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
        .dark .qc-table-container {
            border-color: #334155;
            background: #0f172a;
        }

        .qc-table-scroll {
            width: 100%;
            overflow-x: auto;
            max-height: 520px;
        }

        .qc-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
        }

        .qc-table thead tr {
            background: #1e293b;
            color: #f8fafc;
            border-bottom: 1px solid #334155;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .qc-table th {
            padding: 0.75rem 0.85rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .qc-table td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .dark .qc-table td {
            border-bottom-color: #1e293b;
        }

        .qc-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .dark .qc-table tbody tr:nth-child(even) {
            background: #131d31;
        }

        .qc-table tbody tr:hover {
            background: #f1f5f9;
        }

        .dark .qc-table tbody tr:hover {
            background: #1e293b;
        }

        .qc-barcode-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 0.375rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .qc-barcode-matched {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .dark .qc-barcode-matched {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .qc-barcode-new {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .dark .qc-barcode-new {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .qc-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .qc-input-compact {
            width: 100%;
            padding: 0.4rem 0.6rem;
            font-size: 0.8125rem;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            outline: none;
            transition: all 0.15s ease;
        }
        .dark .qc-input-compact {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .qc-input-compact:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
        }
    </style>

    {{-- Toolbar & Summary Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                Total: <span class="font-mono text-indigo-600 dark:text-indigo-400 text-sm" x-text="items ? items.length : 0">0</span> Barang
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span x-text="matchedCount">0</span> Cocok Master Lama
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span x-text="newCount">0</span> Master Baru
            </span>
        </div>

        <button 
            type="button"
            x-on:click="addItem()"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 rounded-lg shadow-sm transition"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah 1 Baris
        </button>
    </div>

    {{-- Tabel Produk Massal --}}
    <div class="qc-table-container">
        <div class="qc-table-scroll">
            <table class="qc-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 48px;">No</th>
                        <th style="min-width: 280px;">Produk & Barcode Target</th>
                        <th style="min-width: 220px;">Status & Deteksi Master</th>
                        <th class="text-right" style="min-width: 140px;">Harga Beli / HPP (M)</th>
                        <th class="text-right" style="min-width: 140px;">Harga Jual</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="item.id || idx">
                        <tr>
                            {{-- No --}}
                            <td class="text-center font-mono text-slate-400 font-bold" x-text="idx + 1"></td>

                            {{-- Produk & Barcode Target --}}
                            <td>
                                <input 
                                    type="text" 
                                    x-model="item.name" 
                                    placeholder="Nama Produk..."
                                    class="qc-input-compact font-semibold text-slate-800 dark:text-slate-100 mb-1"
                                    required
                                >
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <template x-if="item.status === 'MATCHED' && !item.force_new">
                                        <span class="qc-barcode-tag qc-barcode-matched" title="Barcode Master yang Sudah Ada">
                                            <span x-text="item.barcode"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'NEW' || item.force_new">
                                        <span class="qc-barcode-tag qc-barcode-new" title="Barcode yang Akan Digenerate">
                                            <span x-text="item.barcode"></span>
                                        </span>
                                    </template>
                                    <span class="text-slate-400 font-mono text-[11px]" x-text="'/ ' + (item.sku || '-')"></span>
                                </div>
                            </td>

                            {{-- Status & Deteksi Master --}}
                            <td>
                                {{-- Jika Cocok Master Lama --}}
                                <template x-if="item.status === 'MATCHED' && !item.force_new">
                                    <div class="space-y-1">
                                        <span class="qc-status-badge bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            COCOK MASTER LAMA
                                        </span>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                            Tautkan ke: <strong class="text-slate-700 dark:text-slate-200" x-text="item.matched_name"></strong>
                                        </div>
                                    </div>
                                </template>

                                {{-- Jika Dipaksa Jadi Baru --}}
                                <template x-if="item.status === 'MATCHED' && item.force_new">
                                    <div class="space-y-1">
                                        <span class="qc-status-badge bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            DIPAKSA MASTER BARU
                                        </span>
                                        <div class="text-[11px] text-amber-600 dark:text-amber-400 leading-tight">
                                            Barcode & Master baru akan dibuat
                                        </div>
                                    </div>
                                </template>

                                {{-- Jika Benar-Benar Baru --}}
                                <template x-if="item.status === 'NEW'">
                                    <div class="space-y-1">
                                        <span class="qc-status-badge bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            MASTER PRODUK BARU
                                        </span>
                                        <div class="text-[11px] text-slate-400 leading-tight">
                                            Belum pernah ada di sistem
                                        </div>
                                    </div>
                                </template>
                            </td>

                            {{-- Harga Beli / HPP --}}
                            <td class="text-right">
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                                    <input 
                                        type="number" 
                                        x-model.number="item.cost_price" 
                                        step="any"
                                        class="qc-input-compact text-right pl-7 font-mono font-bold"
                                        placeholder="0"
                                        required
                                    >
                                </div>
                            </td>

                            {{-- Harga Jual --}}
                            <td class="text-right">
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-bold">Rp</span>
                                    <input 
                                        type="number" 
                                        x-model.number="item.selling_price" 
                                        step="any"
                                        class="qc-input-compact text-right pl-7 font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                        placeholder="0"
                                        required
                                    >
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Tombol Toggle Override (jika cocok master) --}}
                                    <template x-if="item.status === 'MATCHED'">
                                        <button 
                                            type="button" 
                                            x-on:click="toggleForceNew(idx)" 
                                            :title="item.force_new ? 'Kembalikan Tautkan ke Master Lama' : 'Barang Beda? Klik untuk Paksa Buat Master Baru'"
                                            class="p-1.5 rounded-lg border text-xs transition"
                                            :class="item.force_new ? 'bg-amber-50 text-amber-600 border-amber-300 hover:bg-amber-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        >
                                            <span class="text-xs" x-text="item.force_new ? '🔗' : '⚡'"></span>
                                        </button>
                                    </template>

                                    {{-- Tombol Hapus Baris --}}
                                    <button 
                                        type="button" 
                                        x-on:click="removeItem(idx)" 
                                        title="Hapus baris ini"
                                        class="p-1.5 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/60 active:scale-95 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Empty State jika belum ada item --}}
        <div x-show="!items || items.length === 0" class="p-8 text-center text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-900/50">
            <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            <p class="font-bold text-sm text-slate-600 dark:text-slate-400">Belum ada daftar produk dalam tabel</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">
                Tempelkan daftar produk di bagian <strong>"2. Import dari Copy-Paste Teks"</strong> di atas, lalu klik tombol <strong>"Proses Teks ke Tabel"</strong>.
            </p>
        </div>
    </div>
</div>
