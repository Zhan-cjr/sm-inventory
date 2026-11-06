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
    class="qc-wrapper"
>
    <style>
        .qc-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            font-family: inherit;
        }

        .qc-wrapper svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        /* Toolbar */
        .qc-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
        }
        .dark .qc-toolbar {
            background: #0f172a;
            border-color: #334155;
        }

        .qc-stats-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            font-size: 0.75rem;
        }

        .qc-stat-total {
            font-weight: 700;
            color: #334155;
        }
        .dark .qc-stat-total {
            color: #cbd5e1;
        }

        .qc-stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .qc-stat-badge.emerald {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .dark .qc-stat-badge.emerald {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .qc-stat-badge.blue {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .dark .qc-stat-badge.blue {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .qc-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .qc-dot.emerald { background: #10b981; }
        .qc-dot.blue { background: #3b82f6; }

        .qc-btn-add {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff !important;
            background: #4f46e5;
            border: 1px solid #4f46e5;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .qc-btn-add:hover {
            background: #4338ca;
        }

        /* Table Container */
        .qc-table-container {
            border: 1px solid #cbd5e1;
            border-radius: 0.65rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
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
            font-size: 0.8rem;
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
            padding: 0.6rem 0.85rem;
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
            padding: 0.12rem 0.45rem;
            border-radius: 0.35rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.72rem;
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
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .qc-input-compact {
            width: 100%;
            padding: 0.38rem 0.55rem;
            font-size: 0.8rem;
            border-radius: 0.45rem;
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

        .qc-btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 0.375rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.15s ease;
        }
        .dark .qc-btn-action {
            background: #1e293b;
            border-color: #334155;
        }
        .qc-btn-action.delete {
            border-color: #fecaca;
            background: #fef2f2;
            color: #dc2626;
        }
        .qc-btn-action.delete:hover {
            background: #fee2e2;
        }
        .dark .qc-btn-action.delete {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* Empty State */
        .qc-empty-box {
            padding: 2.5rem 1.5rem;
            text-align: center;
            background: #f8fafc;
            color: #64748b;
        }
        .dark .qc-empty-box {
            background: #0f172a;
            color: #94a3b8;
        }
        .qc-empty-icon {
            width: 44px !important;
            height: 44px !important;
            max-width: 44px !important;
            max-height: 44px !important;
            margin: 0 auto 0.75rem auto !important;
            color: #94a3b8 !important;
            display: block !important;
        }
        .qc-empty-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.25rem;
        }
        .dark .qc-empty-title {
            color: #e2e8f0;
        }
        .qc-empty-desc {
            font-size: 0.75rem;
            color: #94a3b8;
            max-width: 380px;
            margin: 0 auto;
            line-height: 1.4;
        }
    </style>

    {{-- Toolbar & Summary Bar --}}
    <div class="qc-toolbar">
        <div class="qc-stats-group">
            <span class="qc-stat-total">
                Total: <span style="font-family: monospace; font-size: 0.875rem; color: #4f46e5;" x-text="items ? items.length : 0">0</span> Barang
            </span>
            <span class="qc-stat-badge emerald">
                <span class="qc-dot emerald"></span>
                <span x-text="matchedCount">0</span> Cocok Master Lama
            </span>
            <span class="qc-stat-badge blue">
                <span class="qc-dot blue"></span>
                <span x-text="newCount">0</span> Master Baru
            </span>
        </div>

        <button 
            type="button"
            x-on:click="addItem()"
            class="qc-btn-add"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
            Tambah 1 Baris
        </button>
    </div>

    {{-- Tabel Produk Massal --}}
    <div class="qc-table-container">
        <div class="qc-table-scroll">
            <table class="qc-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th style="min-width: 250px; text-align: left;">PRODUK & BARCODE TARGET</th>
                        <th style="min-width: 210px; text-align: left;">STATUS & DETEKSI MASTER</th>
                        <th style="min-width: 130px; text-align: right;">HARGA BELI / HPP (M)</th>
                        <th style="min-width: 130px; text-align: right;">HARGA JUAL</th>
                        <th style="width: 80px; text-align: center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="item.id || idx">
                        <tr>
                            {{-- No --}}
                            <td style="text-align: center; font-family: monospace; font-weight: 700; color: #94a3b8;" x-text="idx + 1"></td>

                            {{-- Produk & Barcode Target --}}
                            <td>
                                <input 
                                    type="text" 
                                    x-model="item.name" 
                                    placeholder="Nama Produk..."
                                    class="qc-input-compact"
                                    style="font-weight: 700; margin-bottom: 4px;"
                                    required
                                >
                                <div style="display: flex; align-items: center; gap: 6px;">
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
                                    <span style="color: #94a3b8; font-family: monospace; font-size: 11px;" x-text="'/ ' + (item.sku || '-')"></span>
                                </div>
                            </td>

                            {{-- Status & Deteksi Master --}}
                            <td>
                                {{-- Jika Cocok Master Lama --}}
                                <template x-if="item.status === 'MATCHED' && !item.force_new">
                                    <div>
                                        <span class="qc-status-badge qc-stat-badge emerald">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                            COCOK MASTER LAMA
                                        </span>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 3px; line-height: 1.2;">
                                            Tautkan ke: <strong style="color: #334155;" x-text="item.matched_name"></strong>
                                        </div>
                                    </div>
                                </template>

                                {{-- Jika Dipaksa Jadi Baru --}}
                                <template x-if="item.status === 'MATCHED' && item.force_new">
                                    <div>
                                        <span class="qc-status-badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            DIPAKSA MASTER BARU
                                        </span>
                                        <div style="font-size: 11px; color: #d97706; margin-top: 3px; line-height: 1.2;">
                                            Barcode & Master baru akan dibuat
                                        </div>
                                    </div>
                                </template>

                                {{-- Jika Benar-Benar Baru --}}
                                <template x-if="item.status === 'NEW'">
                                    <div>
                                        <span class="qc-status-badge qc-stat-badge blue">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                            MASTER PRODUK BARU
                                        </span>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 3px; line-height: 1.2;">
                                            Belum pernah ada di sistem
                                        </div>
                                    </div>
                                </template>
                            </td>

                            {{-- Harga Beli / HPP --}}
                            <td style="text-align: right;">
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 8px; font-size: 11px; font-weight: 700; color: #94a3b8; pointer-events: none;">Rp</span>
                                    <input 
                                        type="number" 
                                        x-model.number="item.cost_price" 
                                        step="any"
                                        class="qc-input-compact"
                                        style="padding-left: 28px; text-align: right; font-family: monospace; font-weight: 700;"
                                        placeholder="0"
                                        required
                                    >
                                </div>
                            </td>

                            {{-- Harga Jual --}}
                            <td style="text-align: right;">
                                <div style="position: relative; display: flex; align-items: center;">
                                    <span style="position: absolute; left: 8px; font-size: 11px; font-weight: 700; color: #94a3b8; pointer-events: none;">Rp</span>
                                    <input 
                                        type="number" 
                                        x-model.number="item.selling_price" 
                                        step="any"
                                        class="qc-input-compact"
                                        style="padding-left: 28px; text-align: right; font-family: monospace; font-weight: 700; color: #059669;"
                                        placeholder="0"
                                        required
                                    >
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    {{-- Tombol Toggle Override (jika cocok master) --}}
                                    <template x-if="item.status === 'MATCHED'">
                                        <button 
                                            type="button" 
                                            x-on:click="toggleForceNew(idx)" 
                                            :title="item.force_new ? 'Kembalikan Tautkan ke Master Lama' : 'Barang Beda? Klik untuk Paksa Buat Master Baru'"
                                            class="qc-btn-action"
                                            :style="item.force_new ? 'background: #fffbeb; color: #b45309; border-color: #fde68a;' : 'background: #f1f5f9; color: #475569;'"
                                        >
                                            <span style="font-size: 11px;" x-text="item.force_new ? '🔗' : '⚡'"></span>
                                        </button>
                                    </template>

                                    {{-- Tombol Hapus Baris --}}
                                    <button 
                                        type="button" 
                                        x-on:click="removeItem(idx)" 
                                        title="Hapus baris ini"
                                        class="qc-btn-action delete"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Empty State jika belum ada item --}}
        <div x-show="!items || items.length === 0" class="qc-empty-box">
            <svg class="qc-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            <div class="qc-empty-title">Belum Ada Daftar Produk dalam Tabel</div>
            <div class="qc-empty-desc">
                Tempelkan daftar produk pada kotak teks <strong>"2. Import dari Copy-Paste Teks"</strong> di atas, lalu klik tombol <strong>"Proses Teks ke Tabel & Deteksi Master"</strong>.
            </div>
        </div>
    </div>
</div>
