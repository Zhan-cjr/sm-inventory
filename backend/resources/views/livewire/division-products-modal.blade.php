<div class="div-modal-wrapper">
    <style>
        .div-modal-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-family: inherit;
        }

        .div-modal-wrapper svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        /* Top Toolbar */
        .div-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
        }
        .dark .div-toolbar {
            background: #0f172a;
            border-color: #334155;
        }

        .div-search-box {
            position: relative;
            min-width: 260px;
            flex: 1;
            max-width: 360px;
        }
        .div-search-input {
            width: 100%;
            padding: 0.45rem 0.75rem 0.45rem 2.2rem;
            font-size: 0.8125rem;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            outline: none;
            transition: all 0.15s ease;
        }
        .dark .div-search-input {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }
        .div-search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
        }
        .div-search-icon {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .div-actions-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .div-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.775rem;
            font-weight: 700;
            color: #ffffff !important;
            background: #4f46e5;
            border: 1px solid #4f46e5;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .div-btn-primary:hover {
            background: #4338ca;
        }

        .div-btn-move {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.775rem;
            font-weight: 700;
            color: #92400e !important;
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .div-btn-move:hover {
            background: #fde68a;
        }
        .dark .div-btn-move {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.3);
        }

        /* Flash Message */
        .div-flash-success {
            padding: 0.65rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .div-flash-error {
            padding: 0.65rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Table */
        .div-table-card {
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .dark .div-table-card {
            border-color: #334155;
            background: #0f172a;
        }

        .div-table-scroll {
            width: 100%;
            overflow-x: auto;
            max-height: 520px;
        }

        .div-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
        }

        .div-table thead tr {
            background: #1e293b;
            color: #f8fafc;
            border-bottom: 1px solid #334155;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .div-table th {
            padding: 0.75rem 0.85rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .div-table td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dark .div-table td {
            border-bottom-color: #1e293b;
        }

        .div-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .dark .div-table tbody tr:nth-child(even) {
            background: #131d31;
        }
        .div-table tbody tr:hover {
            background: #f1f5f9;
        }
        .dark .div-table tbody tr:hover {
            background: #1e293b;
        }

        .div-barcode-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.45rem;
            background: #f1f5f9;
            color: #0f172a;
            border-radius: 0.35rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .dark .div-barcode-pill {
            background: #1e293b;
            color: #cbd5e1;
        }

        .div-cat-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }
        .dark .div-cat-badge {
            background: #1e293b;
            color: #94a3b8;
        }

        .div-stok-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            font-family: ui-monospace, monospace;
        }
        .div-stok-badge.green {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .dark .div-stok-badge.green {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .div-stok-badge.gray {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        /* Modal Overlay */
        .div-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            padding: 1rem;
        }
        .div-modal-content {
            background: #ffffff;
            border-radius: 1rem;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
        }
        .dark .div-modal-content {
            background: #0f172a;
            border: 1px solid #334155;
        }

        .div-modal-header {
            padding: 1.1rem 1.35rem;
            background: #1e293b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .div-modal-body {
            padding: 1.25rem;
            overflow-y: auto;
            flex: 1;
        }
        .div-modal-footer {
            padding: 0.85rem 1.25rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
        }
        .dark .div-modal-footer {
            background: #131d31;
            border-top-color: #1e293b;
        }
    </style>

    {{-- Notification Messages --}}
    @if (session()->has('success'))
        <div class="div-flash-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="div-flash-error">
            {{ session('error') }}
        </div>
    @endif

    {{-- Info Card Sub Divisi --}}
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem;" class="dark:bg-slate-900 dark:border-slate-800">
        <div>
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700;">Sub-Divisi</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;" class="dark:text-slate-100">
                {{ $division->name }}
            </div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                Supplier: <strong style="color: #334155;" class="dark:text-slate-300">{{ $division->supplier?->name ?? '-' }}</strong>
                @if($division->branch)
                    <span style="margin-left: 8px; padding: 1px 6px; border-radius: 4px; background: #eff6ff; color: #2563eb; font-weight: 600; font-size: 11px;">
                        Cabang: {{ $division->branch->name }}
                    </span>
                @else
                    <span style="margin-left: 8px; padding: 1px 6px; border-radius: 4px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 11px;">
                        Lingkup: Pusat (Semua Cabang)
                    </span>
                @endif
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <div style="text-align: right;">
                <div style="font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Barang</div>
                <div style="font-size: 1.35rem; font-weight: 900; color: #4f46e5; font-family: ui-monospace, monospace;">
                    {{ $products->count() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="div-toolbar">
        <div class="div-search-box">
            <svg class="div-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari nama, barcode, atau SKU..." 
                class="div-search-input"
            >
        </div>

        <div class="div-actions-group">
            {{-- Tombol Tambah Barang --}}
            <button 
                type="button" 
                wire:click="openAddModal" 
                class="div-btn-primary"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                + Tambah Barang ke Sub-Divisi
            </button>

            {{-- Tombol Pindah Supplier / Sub Divisi --}}
            @if(count($selectedProducts) > 0)
                <button 
                    type="button" 
                    wire:click="openMoveModal" 
                    class="div-btn-move"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    ⇄ Pindah Supplier / Sub-Divisi ({{ count($selectedProducts) }})
                </button>
            @endif
        </div>
    </div>

    {{-- Data Table --}}
    <div class="div-table-card">
        <div class="div-table-scroll">
            <table class="div-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input 
                                type="checkbox" 
                                wire:model.live="selectAll"
                                style="width: 15px; height: 15px; cursor: pointer; accent-color: #4f46e5;"
                            >
                        </th>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th style="min-width: 250px; text-align: left;">PRODUK & BARCODE</th>
                        <th style="min-width: 140px; text-align: left;">KATEGORI</th>
                        <th style="min-width: 110px; text-align: right;">HPP (M)</th>
                        <th style="min-width: 110px; text-align: right;">HARGA JUAL</th>
                        <th style="min-width: 100px; text-align: center;">TOTAL STOK</th>
                        <th style="width: 90px; text-align: center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $idx => $prod)
                        @php
                            $totalQty = $prod->stocks->sum('quantity_on_hand');
                        @endphp
                        <tr wire:key="prod-row-{{ $prod->id }}">
                            {{-- Checkbox --}}
                            <td style="text-align: center;">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="selectedProducts" 
                                    value="{{ (string) $prod->id }}"
                                    style="width: 15px; height: 15px; cursor: pointer; accent-color: #4f46e5;"
                                >
                            </td>

                            {{-- No --}}
                            <td style="text-align: center; font-family: monospace; font-weight: 700; color: #94a3b8;">
                                {{ $idx + 1 }}
                            </td>

                            {{-- Produk & Barcode --}}
                            <td>
                                <div style="font-weight: 700; color: #0f172a;" class="dark:text-slate-100">
                                    {{ $prod->name }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px;">
                                    <span class="div-barcode-pill">
                                        {{ $prod->barcode ?: '-' }}
                                    </span>
                                    <span style="font-family: monospace; font-size: 11px; color: #94a3b8;">
                                        / {{ $prod->sku }}
                                    </span>
                                </div>
                            </td>

                            {{-- Kategori / Sub --}}
                            <td>
                                <div class="div-cat-badge">
                                    {{ $prod->category?->name ?? '-' }}
                                </div>
                                @if($prod->sub_category)
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        {{ $prod->sub_category }}
                                    </div>
                                @endif
                            </td>

                            {{-- HPP --}}
                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: #475569;" class="dark:text-slate-300">
                                Rp {{ number_format($prod->cost_price, 0, ',', '.') }}
                            </td>

                            {{-- Harga Jual --}}
                            <td style="text-align: right; font-family: monospace; font-weight: 700; color: #059669;" class="dark:text-emerald-400">
                                Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                            </td>

                            {{-- Total Stok --}}
                            <td style="text-align: center;">
                                <span class="div-stok-badge {{ $totalQty > 0 ? 'green' : 'gray' }}">
                                    {{ number_format($totalQty, 0, ',', '.') }} pcs
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    {{-- Tombol Pindah Satu Barang --}}
                                    <button 
                                        type="button" 
                                        wire:click="openMoveModal('{{ $prod->id }}')" 
                                        title="Pindah Supplier / Sub-Divisi"
                                        style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; border: 1px solid #fde68a; background: #fffbeb; color: #d97706; cursor: pointer;"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </button>

                                    {{-- Tombol Lepas dari Sub-Divisi --}}
                                    <button 
                                        type="button" 
                                        wire:confirm="Lepaskan barang ini dari sub-divisi {{ $division->name }}?"
                                        wire:click="detachProduct('{{ $prod->id }}')" 
                                        title="Lepas dari Sub-Divisi ini"
                                        style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; color: #64748b; cursor: pointer;"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 2.5rem; text-align: center; color: #94a3b8;">
                                <svg style="width: 38px !important; height: 38px !important; margin: 0 auto 8px auto; display: block; color: #cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <div style="font-weight: 700; font-size: 0.875rem; color: #475569;">Belum Ada Barang di Sub-Divisi Ini</div>
                                <div style="font-size: 0.75rem; margin-top: 4px;">Klik tombol <strong>"+ Tambah Barang ke Sub-Divisi"</strong> di atas untuk memasukkan produk.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL 1: Tambah Barang ke Sub-Divisi --}}
    @if ($showAddModal)
        <div class="div-modal-overlay">
            <div class="div-modal-content">
                <div class="div-modal-header">
                    <div style="font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Barang ke Sub-Divisi: {{ $division->name }}
                    </div>
                    <button type="button" wire:click="closeAddModal" style="background: none; border: none; color: #ffffff; cursor: pointer; font-size: 1.25rem;">&times;</button>
                </div>

                <div class="div-modal-body">
                    <div style="margin-bottom: 0.85rem;">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="addSearch" 
                            placeholder="Cari barang supplier {{ $division->supplier?->name }}..." 
                            class="div-search-input"
                            style="padding-left: 0.85rem;"
                        >
                    </div>

                    <div style="border: 1px solid #e2e8f0; border-radius: 0.5rem; max-height: 340px; overflow-y: auto;" class="dark:border-slate-800">
                        <table class="div-table">
                            <thead>
                                <tr>
                                    <th style="width: 36px; text-align: center;">PILIH</th>
                                    <th>NAMA BARANG & BARCODE</th>
                                    <th style="text-align: right; width: 100px;">HPP</th>
                                    <th style="text-align: left; width: 130px;">SUB-DIVISI SAAT INI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($addCandidates as $cand)
                                    <tr wire:key="cand-{{ $cand->id }}">
                                        <td style="text-align: center;">
                                            <input 
                                                type="checkbox" 
                                                wire:model.live="addSelectedProducts" 
                                                value="{{ (string) $cand->id }}"
                                                style="width: 15px; height: 15px; cursor: pointer; accent-color: #4f46e5;"
                                            >
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a;" class="dark:text-slate-100">{{ $cand->name }}</div>
                                            <div style="font-family: monospace; font-size: 11px; color: #94a3b8;">{{ $cand->barcode ?: $cand->sku }}</div>
                                        </td>
                                        <td style="text-align: right; font-family: monospace; font-size: 12px;">
                                            Rp {{ number_format($cand->cost_price, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @if($cand->supplierDivision)
                                                <span style="font-size: 11px; background: #fffbeb; color: #b45309; padding: 2px 6px; border-radius: 4px;">
                                                    {{ $cand->supplierDivision->name }}
                                                </span>
                                            @else
                                                <span style="font-size: 11px; color: #94a3b8;">- Tanpa Sub-Divisi -</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 1.5rem; color: #94a3b8; font-size: 0.8125rem;">
                                            Tidak ada barang lain dari supplier ini yang dapat ditambahkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="div-modal-footer">
                    <button type="button" wire:click="closeAddModal" style="padding: 0.45rem 0.9rem; font-size: 0.775rem; font-weight: 600; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #ffffff; cursor: pointer;">
                        Batal
                    </button>
                    <button type="button" wire:click="addProductsToDivision" class="div-btn-primary" style="padding: 0.45rem 1rem;">
                        Simpan ke Sub-Divisi ({{ count($addSelectedProducts) }})
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL 2: Pindah Supplier / Sub-Divisi --}}
    @if ($showMoveModal)
        <div class="div-modal-overlay">
            <div class="div-modal-content">
                <div class="div-modal-header" style="background: #b45309;">
                    <div style="font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        Pindah Supplier / Sub-Divisi ({{ count($selectedProducts) }} Barang)
                    </div>
                    <button type="button" wire:click="closeMoveModal" style="background: none; border: none; color: #ffffff; cursor: pointer; font-size: 1.25rem;">&times;</button>
                </div>

                <div class="div-modal-body" style="display: flex; flex-direction: column; gap: 1rem;">
                    <p style="font-size: 0.8125rem; color: #64748b;">
                        Pilih supplier dan sub-divisi tujuan. Produk yang dipilih akan otomatis diperbarui kepemilikan supplier dan sub-divisinya.
                    </p>

                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;" class="dark:text-slate-300">
                            Supplier Tujuan:
                        </label>
                        <select 
                            wire:model.live="targetSupplierId" 
                            style="width: 100%; padding: 0.5rem 0.75rem; font-size: 0.8125rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #ffffff;"
                            class="dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100"
                        >
                            <option value="">-- Pilih Supplier Tujuan --</option>
                            @foreach ($allSuppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;" class="dark:text-slate-300">
                            Sub-Divisi Tujuan (Opsional):
                        </label>
                        <select 
                            wire:model="targetDivisionId" 
                            style="width: 100%; padding: 0.5rem 0.75rem; font-size: 0.8125rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #ffffff;"
                            class="dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100"
                        >
                            <option value="">-- Tanpa Sub-Divisi / Divisi Utama --</option>
                            @foreach ($targetDivisions as $td)
                                <option value="{{ $td->id }}">{{ $td->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="div-modal-footer">
                    <button type="button" wire:click="closeMoveModal" style="padding: 0.45rem 0.9rem; font-size: 0.775rem; font-weight: 600; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #ffffff; cursor: pointer;">
                        Batal
                    </button>
                    <button type="button" wire:click="executeMove" class="div-btn-primary" style="background: #b45309; border-color: #b45309; padding: 0.45rem 1rem;">
                        Konfirmasi Pindah
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
