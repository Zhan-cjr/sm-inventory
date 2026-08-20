<div class="tax-invoice-modal-container" style="font-family: inherit; color: #1f2937; line-height: 1.5;">
    <style>
        .tax-invoice-modal-container {
            padding: 4px;
        }
        .modal-grid-header {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .header-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .header-card-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-card-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .badge-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-masukan { background-color: #dcfce7; color: #166534; }
        .badge-keluaran { background-color: #ffe4e6; color: #9f1239; }
        .badge-reported { background-color: #e0e7ff; color: #3730a3; }
        .badge-draft { background-color: #fef3c7; color: #92400e; }

        /* KPI Banner */
        .kpi-banner {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 16px;
        }
        .kpi-item {
            text-align: right;
        }
        .kpi-label {
            font-size: 11px;
            color: #166534;
            font-weight: 500;
        }
        .kpi-val {
            font-size: 16px;
            font-weight: 800;
            color: #14532d;
        }

        /* Items Table */
        .table-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .table-box-header {
            background-color: #f1f5f9;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-box-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-scroll-wrap {
            max-height: 320px;
            overflow-y: auto;
        }
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: left;
        }
        .styled-table thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #cbd5e1;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .styled-table tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .styled-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .styled-table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .styled-table tfoot td {
            padding: 10px 12px;
            background-color: #f1f5f9;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>

    <!-- 1. Grid Informasi Utama -->
    <div class="modal-grid-header">
        <div class="header-card">
            <span class="header-card-label">Nomor Seri Faktur</span>
            <span class="header-card-value" style="color: #0284c7; font-family: monospace; font-size: 14px;">
                {{ $record->nomor_faktur }}
            </span>
        </div>
        <div class="header-card">
            <span class="header-card-label">Tanggal & Masa Pajak</span>
            <span class="header-card-value">
                {{ $record->tanggal_faktur ? \Carbon\Carbon::parse($record->tanggal_faktur)->format('d/m/Y') : '-' }}
                <span style="font-size: 11px; font-weight: normal; color: #64748b;">(Masa: {{ $record->masa_pajak }})</span>
            </span>
        </div>
        <div class="header-card">
            <span class="header-card-label">Lawan Transaksi (Pemasok)</span>
            <span class="header-card-value" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $record->nama_lawan }}">
                {{ $record->nama_lawan ?: '-' }}
            </span>
            <span style="font-size: 10.5px; color: #64748b;">NPWP: {{ $record->npwp_lawan ?: '-' }}</span>
        </div>
        <div class="header-card">
            <span class="header-card-label">Jenis & Status</span>
            <div style="margin-top: 4px; display: flex; gap: 6px;">
                <span class="badge-pill {{ $record->type === 'masukan' ? 'badge-masukan' : 'badge-keluaran' }}">
                    {{ ucfirst($record->type) }}
                </span>
                <span class="badge-pill {{ $record->status === 'reported' ? 'badge-reported' : 'badge-draft' }}">
                    {{ $record->status === 'reported' ? 'Dilaporkan' : 'Draft' }}
                </span>
            </div>
        </div>
    </div>

    <!-- 2. KPI Banner Keuangan -->
    <div class="kpi-banner">
        <div class="kpi-item">
            <div class="kpi-label">Dasar Pengenaan Pajak (DPP)</div>
            <div class="kpi-val">Rp {{ number_format($record->dpp, 0, ',', '.') }}</div>
        </div>
        <div class="kpi-item" style="border-left: 1px solid #86efac; padding-left: 20px;">
            <div class="kpi-label">Total PPN (11% / 12%)</div>
            <div class="kpi-val" style="color: #059669;">Rp {{ number_format($record->ppn, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- 3. Tabel Rincian Detail Barang -->
    <div class="table-box">
        <div class="table-box-header">
            <div class="table-box-title">
                📦 Rincian Barang Kena Pajak / Jasa ({{ $record->items->count() }} Item Terdaftar)
            </div>
            <div style="font-size: 11px; color: #64748b;">
                Tabel dapat di-scroll vertikal jika memuat banyak barang
            </div>
        </div>

        <div class="table-scroll-wrap">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Nama Barang / Jasa Kena Pajak</th>
                        <th class="text-center" style="width: 70px;">Qty</th>
                        <th class="text-right" style="width: 110px;">Harga Satuan</th>
                        <th class="text-right" style="width: 110px;">Harga Total</th>
                        <th class="text-right" style="width: 90px;">Diskon</th>
                        <th class="text-right" style="width: 110px;">DPP</th>
                        <th class="text-right" style="width: 100px;">PPN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->items as $idx => $item)
                    @php
                        $qtyFormatted = (floor($item->jumlah_barang) == $item->jumlah_barang) 
                            ? number_format($item->jumlah_barang, 0, ',', '.') 
                            : rtrim(rtrim(number_format($item->jumlah_barang, 3, ',', '.'), '0'), ',');
                    @endphp
                    <tr>
                        <td class="text-center" style="color: #64748b; font-weight: 500;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $item->name }}</strong>
                        </td>
                        <td class="text-center" style="font-weight: 700; color: #0f172a;">{{ $qtyFormatted }}</td>
                        <td class="text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: 600;">Rp {{ number_format($item->harga_total, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #e11d48;">
                            {{ $item->diskon > 0 ? 'Rp ' . number_format($item->diskon, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-right" style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item->dpp, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: 700; color: #059669;">Rp {{ number_format($item->ppn, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 24px; color: #94a3b8;">
                            (Tidak ada rincian detail barang yang tersimpan untuk faktur ini)
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($record->items->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-center">TOTAL REKAPITULASI ({{ $record->items->count() }} ITEM)</td>
                        <td class="text-center">{{ number_format($record->items->sum('jumlah_barang'), 0, ',', '.') }}</td>
                        <td></td>
                        <td class="text-right">Rp {{ number_format($record->items->sum('harga_total'), 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #e11d48;">Rp {{ number_format($record->items->sum('diskon'), 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($record->dpp, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #059669;">Rp {{ number_format($record->ppn, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
