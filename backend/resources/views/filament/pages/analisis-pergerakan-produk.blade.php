<x-filament-panels::page>
    <style>
        .pv-root { display: flex; flex-direction: column; gap: 1.25rem; font-family: inherit; }
        .pv-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .dark .pv-card { background: #111827; border-color: #1f2937; color: #f3f4f6; }
        .pv-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .pv-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; }
        .pv-q-card { cursor: pointer; border-radius: 0.875rem; padding: 1rem 1.125rem; border: 2px solid transparent; transition: all 0.2s; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .pv-q-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .dark .pv-q-card { background: #111827; }
        .pv-q-fast { border-color: #fecaca; background: #fff5f5; } .dark .pv-q-fast { border-color: #7f1d1d; background: #450a0a25; } .pv-q-fast.active { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.25); }
        .pv-q-slow { border-color: #fde68a; background: #fffbeb; } .dark .pv-q-slow { border-color: #78350f; background: #451a0325; } .pv-q-slow.active { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.25); }
        .pv-q-dead { border-color: #cbd5e1; background: #f8fafc; } .dark .pv-q-dead { border-color: #334155; background: #0f172a50; } .pv-q-dead.active { border-color: #475569; box-shadow: 0 0 0 3px rgba(71,85,105,0.25); }
        .pv-q-opt { border-color: #a7f3d0; background: #f0fdf4; } .dark .pv-q-opt { border-color: #064e3b; background: #022c2225; } .pv-q-opt.active { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.25); }
        .pv-pill { padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; border: 1px solid #cbd5e1; background: #f1f5f9; color: #334155; cursor: pointer; transition: all 0.15s; }
        .pv-pill:hover { border-color: #3b82f6; color: #1d4ed8; } .pv-pill.active { background: #2563eb; color: #fff; border-color: #2563eb; box-shadow: 0 2px 4px rgba(37,99,235,0.3); }
        .pv-btn-success { background: #059669; color: #fff; border-color: #059669; } .pv-btn-success:hover { background: #047857; color: #fff; }
        .dark .pv-pill { background: #1e293b; border-color: #334155; color: #cbd5e1; } .dark .pv-pill.active { background: #2563eb; color: #fff; }
        .pv-input { width: 100%; font-size: 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; padding: 0.4rem 0.6rem; background: #fff; color: #0f172a; }
        .dark .pv-input { background: #1f2937; border-color: #374151; color: #f9fafb; }
        .pv-dropdown-menu { position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: #fff; border: 1px solid #cbd5e1; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 0.25rem; max-height: 240px; display: flex; flex-direction: column; }
        .dark .pv-dropdown-menu { background: #1f2937; border-color: #374151; color: #f9fafb; }
        .pv-drop-item { padding: 0.45rem 0.65rem; font-size: 0.75rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
        .pv-drop-item:hover { background: #f1f5f9; color: #2563eb; font-weight: 700; }
        .dark .pv-drop-item { border-color: #374151; } .dark .pv-drop-item:hover { background: #374151; color: #60a5fa; }
        .pv-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 0.75rem; } .dark .pv-table-wrap { border-color: #1f2937; }
        .pv-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; text-align: left; }
        .pv-table th { background: #f8fafc; color: #475569; font-weight: 800; font-size: 0.6875rem; text-transform: uppercase; padding: 0.65rem 0.75rem; border-bottom: 1px solid #e2e8f0; }
        .dark .pv-table th { background: #1e293b; color: #94a3b8; border-color: #334155; }
        .pv-table td { padding: 0.65rem 0.75rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .dark .pv-table td { border-color: #1f2937; } .pv-table tr:hover td { background: #f8fafc80; } .dark .pv-table tr:hover td { background: #1e293b50; }
        .pv-badge { display: inline-block; padding: 0.2rem 0.55rem; font-size: 0.6875rem; font-weight: 800; border-radius: 9999px; border: 1px solid; }
        .pv-badge-danger { background: #fef2f2; color: #991b1b; border-color: #fecaca; } .pv-badge-warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .pv-badge-dark { background: #f1f5f9; color: #334155; border-color: #cbd5e1; } .pv-badge-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .pv-badge-secondary { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
        .dark .pv-badge-danger { background: #450a0a; color: #fca5a5; border-color: #7f1d1d; } .dark .pv-badge-warning { background: #451a03; color: #fcd34d; border-color: #78350f; }
        .dark .pv-badge-dark { background: #0f172a; color: #cbd5e1; border-color: #334155; } .dark .pv-badge-success { background: #022c22; color: #6ee7b7; border-color: #064e3b; }
    </style>

    @php
        $report = $this->velocity_report;
        $kpi = $report['kpi'];
        $items = $report['items'];
        $daysCount = $report['days_count'];
    @endphp

    <div class="pv-root">
        {{-- 1. TOOLBAR & PRESETS --}}
        <div class="pv-card">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Periode:</span>
                    @foreach(['TODAY' => 'Hari Ini', '7_DAYS' => '7 Hari', '30_DAYS' => '30 Hari', '90_DAYS' => '90 Hari', '365_DAYS' => '1 Thn'] as $k => $lbl)
                        <button wire:click="applyDatePreset('{{ $k }}')" type="button" class="pv-pill {{ $date_preset === $k ? 'active' : '' }}">{{ $lbl }}</button>
                    @endforeach
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button wire:click="exportExcel" wire:loading.attr="disabled" type="button" class="pv-pill pv-btn-success" style="display: flex; align-items: center; gap: 0.35rem;">
                        <x-heroicon-o-arrow-down-tray style="width: 14px; height: 14px;" /><span wire:loading.remove wire:target="exportExcel">Export Excel</span><span wire:loading wire:target="exportExcel">Mengunduh...</span>
                    </button>
                    <button wire:click="resetFilters" type="button" class="pv-pill" style="display: flex; align-items: center; gap: 0.35rem;">
                        <x-heroicon-o-arrow-path style="width: 14px; height: 14px;" /> Reset
                    </button>
                </div>
            </div>

            <div class="pv-filter-grid" style="margin-top: 0.75rem;">
                <div><label style="font-size: 0.6875rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.25rem;">Dari:</label><input wire:model.live="start_date" type="date" class="pv-input" /></div>
                <div><label style="font-size: 0.6875rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.25rem;">Sampai:</label><input wire:model.live="end_date" type="date" class="pv-input" /></div>
                <div><label style="font-size: 0.6875rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.25rem;">Cabang:</label>
                    <select wire:model.live="branch_id" class="pv-input">
                        @if(!auth()->user()->branch_id) <option value="ALL">Semua Cabang</option> @endif
                        @foreach($this->branches as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                    </select>
                </div>

                {{-- SEARCHABLE SUPPLIER COMBOBOX WITH INSTANT LIVEWIRE TRIGGER --}}
                <div x-data="{ open: false, search: '' }" @click.outside="open = false" style="position: relative;">
                    <label style="font-size: 0.6875rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.25rem;">Supplier (Ketik Cari):</label>
                    <div @click="open = !open" class="pv-input" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; color: {{ $supplier_id !== 'ALL' ? '#2563eb' : 'inherit' }};">
                            {{ $this->selected_supplier_name }}
                        </span>
                        <span style="font-size: 0.625rem; color: #94a3b8; margin-left: 4px;">▼</span>
                    </div>
                    <div x-show="open" x-cloak class="pv-dropdown-menu">
                        <div style="padding: 0.4rem; border-bottom: 1px solid #e2e8f0;">
                            <input x-model="search" type="text" placeholder="Ketik nama supplier..." class="pv-input" style="padding: 0.3rem 0.5rem;" @click.stop autofocus />
                        </div>
                        <div style="overflow-y: auto; max-height: 180px;">
                            <div wire:click="selectSupplier('ALL')" @click="open = false; search = '';" class="pv-drop-item" style="font-weight: 800; color: #2563eb; background: {{ $supplier_id === 'ALL' ? '#eff6ff' : 'transparent' }};">
                                ✨ Semua Supplier
                            </div>
                            @foreach($this->suppliers as $s)
                                <div wire:click="selectSupplier('{{ $s->id }}')"
                                     @click="open = false; search = '';"
                                     x-show="!search || '{{ strtolower(addslashes($s->name)) }}'.includes(search.toLowerCase())"
                                     class="pv-drop-item"
                                     style="background: {{ $supplier_id === $s->id ? '#eff6ff' : 'transparent' }}; font-weight: {{ $supplier_id === $s->id ? '800' : 'normal' }};">
                                    {{ $s->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div><label style="font-size: 0.6875rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.25rem;">Kategori:</label>
                    <select wire:model.live="category_id" class="pv-input">
                        <option value="ALL">Semua Kategori</option>
                        @foreach($this->categories as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. EXECUTIVE 4-QUADRANT MATRIX --}}
        <div class="pv-grid-4">
            <div wire:click="setQuadrant('CRITICAL_FAST')" class="pv-q-card pv-q-fast {{ $quadrant === 'CRITICAL_FAST' ? 'active' : '' }}">
                <div style="display: flex; justify-content: space-between; font-size: 0.6875rem; font-weight: 900; color: #dc2626;"><span>🔴 FAST MOVING KRITIS</span><span>&le; 7 HARI</span></div>
                <div style="font-size: 1.5rem; font-weight: 900; margin: 0.35rem 0;">{{ number_format($kpi['critical_fast']['count']) }} <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">SKU</span></div>
                <div style="font-size: 0.75rem; color: #dc2626;">Risiko Omset: <b>Rp {{ number_format($kpi['critical_fast']['potential_revenue_risk']) }}</b></div>
                <div style="font-size: 0.6875rem; color: #64748b; margin-top: 0.5rem; padding-top: 0.35rem; border-top: 1px solid #fecaca; display: flex; justify-content: space-between;">
                    <span>Stok: {{ number_format($kpi['critical_fast']['total_stock']) }} pcs</span><span style="color: #dc2626; font-weight: 800;">Segera PO &rarr;</span>
                </div>
            </div>

            <div wire:click="setQuadrant('OVERSTOCK_SLOW')" class="pv-q-card pv-q-slow {{ $quadrant === 'OVERSTOCK_SLOW' ? 'active' : '' }}">
                <div style="display: flex; justify-content: space-between; font-size: 0.6875rem; font-weight: 900; color: #d97706;"><span>🟡 SLOW OVERSTOCK</span><span>&ge; 60 HARI</span></div>
                <div style="font-size: 1.5rem; font-weight: 900; margin: 0.35rem 0;">{{ number_format($kpi['overstock_slow']['count']) }} <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">SKU</span></div>
                <div style="font-size: 0.75rem; color: #d97706;">Modal Tertahan: <b>Rp {{ number_format($kpi['overstock_slow']['capital_tied']) }}</b></div>
                <div style="font-size: 0.6875rem; color: #64748b; margin-top: 0.5rem; padding-top: 0.35rem; border-top: 1px solid #fde68a; display: flex; justify-content: space-between;">
                    <span>Stok: {{ number_format($kpi['overstock_slow']['total_stock']) }} pcs</span><span style="color: #d97706; font-weight: 800;">Kunci PO &rarr;</span>
                </div>
            </div>

            <div wire:click="setQuadrant('DEAD_STOCK')" class="pv-q-card pv-q-dead {{ $quadrant === 'DEAD_STOCK' ? 'active' : '' }}">
                <div style="display: flex; justify-content: space-between; font-size: 0.6875rem; font-weight: 900; color: #475569;"><span>⚫ DEAD STOCK (MACET)</span><span>0 LAKU</span></div>
                <div style="font-size: 1.5rem; font-weight: 900; margin: 0.35rem 0;">{{ number_format($kpi['dead_stock']['count']) }} <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">SKU</span></div>
                <div style="font-size: 0.75rem; color: #475569;">Modal Macet: <b>Rp {{ number_format($kpi['dead_stock']['capital_tied']) }}</b></div>
                <div style="font-size: 0.6875rem; color: #64748b; margin-top: 0.5rem; padding-top: 0.35rem; border-top: 1px solid #cbd5e1; display: flex; justify-content: space-between;">
                    <span>Stok: {{ number_format($kpi['dead_stock']['total_stock']) }} pcs</span><span style="font-weight: 800;">Retur/Cuci &rarr;</span>
                </div>
            </div>

            <div wire:click="setQuadrant('OPTIMAL')" class="pv-q-card pv-q-opt {{ $quadrant === 'OPTIMAL' ? 'active' : '' }}">
                <div style="display: flex; justify-content: space-between; font-size: 0.6875rem; font-weight: 900; color: #059669;"><span>🟢 OPTIMAL & SEHAT</span><span>8 - 59 HARI</span></div>
                <div style="font-size: 1.5rem; font-weight: 900; margin: 0.35rem 0;">{{ number_format($kpi['optimal']['count']) }} <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">SKU</span></div>
                <div style="font-size: 0.75rem; color: #059669;">Aset Produktif: <b>Rp {{ number_format($kpi['optimal']['capital_healthy']) }}</b></div>
                <div style="font-size: 0.6875rem; color: #64748b; margin-top: 0.5rem; padding-top: 0.35rem; border-top: 1px solid #a7f3d0; display: flex; justify-content: space-between;">
                    <span>Stok: {{ number_format($kpi['optimal']['total_stock']) }} pcs</span><span style="color: #059669; font-weight: 800;">Pola Terjaga &rarr;</span>
                </div>
            </div>
        </div>

        {{-- 3. DATA TABLE --}}
        <div class="pv-card">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 240px;">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari SKU, Barcode, Nama Produk..." class="pv-input" style="max-width: 320px;" />
                    @if($quadrant !== 'ALL')
                        <button wire:click="setQuadrant('ALL')" type="button" class="pv-pill" style="font-weight: 800;">Semua Kuadran &times;</button>
                    @endif
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Urutan:</span>
                    <select wire:model.live="sort_by" class="pv-input" style="width: auto;">
                        <option value="doh_asc">⚡ Cepat Habis (DOH Rendah)</option>
                        <option value="doh_desc">⏳ Paling Lambat (DOH Tinggi)</option>
                        <option value="revenue_desc">💰 Omset Tertinggi (Rp)</option>
                        <option value="qty_desc">📦 Volume Terbanyak (Pcs)</option>
                        <option value="capital_desc">💼 Modal Macet Terbesar (Rp)</option>
                        <option value="name_asc">🔤 Nama Produk (A-Z)</option>
                    </select>

                    <select wire:model.live="per_page" class="pv-input" style="width: auto;">
                        <option value="15">15 Baris</option>
                        <option value="25">25 Baris</option>
                        <option value="50">50 Baris</option>
                    </select>
                </div>
            </div>

            <div class="pv-table-wrap">
                <table class="pv-table">
                    <thead>
                        <tr>
                            <th style="text-align: center; width: 40px;">No</th>
                            <th>SKU & Barcode</th>
                            <th>Nama Barang & Kategori</th>
                            <th>Supplier</th>
                            <th style="text-align: right;">Laku ({{ $daysCount }} Hr)</th>
                            <th style="text-align: right;">ADS (Laju/Hr)</th>
                            <th style="text-align: right;">Omset</th>
                            <th style="text-align: right;">Stok Fisik</th>
                            <th style="text-align: right;">Modal Tertahan</th>
                            <th style="text-align: center;">DOH (Hari)</th>
                            <th style="text-align: center;">Status</th>
                            <th>Rekomendasi Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $it)
                            <tr>
                                <td style="text-align: center; color: #94a3b8;">{{ $report['from'] + $idx }}</td>
                                <td>
                                    <div style="font-weight: 800; font-family: monospace;">{{ $it['sku'] }}</div>
                                    <div style="color: #64748b; font-size: 0.6875rem;">{{ $it['barcode'] }}</div>
                                    @if(!empty($it['additional_barcodes']))
                                        <div style="color: #2563eb; font-size: 0.625rem; font-weight: 700;">+{{ count($it['additional_barcodes']) }} Multi-Barcode</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 700;">{{ $it['product_name'] }}</div>
                                    <div style="color: #64748b; font-size: 0.6875rem;">{{ $it['category_name'] }}</div>
                                </td>
                                <td style="color: #475569;">{{ $it['supplier_name'] }}</td>
                                <td style="text-align: right; font-weight: 700; color: #0284c7;">{{ number_format($it['qty_sold'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: 800; color: #2563eb;">{{ number_format($it['ads'], 1, ',', '.') }}/hr</td>
                                <td style="text-align: right; font-weight: 700;">Rp {{ number_format($it['revenue'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: 800; color: {{ $it['current_stock'] <= 5 ? '#dc2626' : '#059669' }};">
                                    {{ number_format($it['current_stock'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #b45309;">Rp {{ number_format($it['capital_tied'], 0, ',', '.') }}</td>
                                <td style="text-align: center; font-weight: 900; color: {{ $it['doh'] <= 7 ? '#dc2626' : ($it['doh'] >= 60 ? '#d97706' : '#059669') }};">
                                    {{ $it['doh_display'] }}
                                </td>
                                <td style="text-align: center;">
                                    <span class="pv-badge pv-badge-{{ $it['quadrant_badge'] }}">{{ $it['quadrant_label'] }}</span>
                                </td>
                                <td style="font-size: 0.6875rem; font-weight: 600; color: #475569;">{{ $it['recommended_action'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" style="text-align: center; padding: 2rem; color: #94a3b8;">Tidak ada data produk yang sesuai kriteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($report['total_items'] > 0)
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; margin-top: 0.75rem; font-size: 0.75rem; color: #64748b;">
                    <div>Menampilkan <b>{{ number_format($report['from']) }}</b> s/d <b>{{ number_format($report['to']) }}</b> dari <b>{{ number_format($report['total_items']) }}</b> produk</div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <button wire:click="prevPage" type="button" class="pv-pill" {{ $report['current_page'] <= 1 ? 'disabled' : '' }}>&larr; Sebelumnya</button>
                        <span style="font-weight: 800;">Hal. {{ $report['current_page'] }} / {{ $report['total_pages'] }}</span>
                        <button wire:click="nextPage" type="button" class="pv-pill" {{ $report['current_page'] >= $report['total_pages'] ? 'disabled' : '' }}>Berikutnya &rarr;</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
