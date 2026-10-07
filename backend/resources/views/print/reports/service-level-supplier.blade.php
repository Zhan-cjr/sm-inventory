<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - SM Inventory</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #111827;
            background: #ffffff;
            padding: 15px 20px;
        }

        /* Top Action Bar (hidden on print) */
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 8.5pt;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-print {
            background: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-close {
            background: #475569;
            color: #ffffff;
        }
        .btn-close:hover {
            background: #334155;
        }

        /* Header / Kop Laporan */
        .report-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .company-title {
            font-size: 15pt;
            font-weight: 900;
            letter-spacing: -0.01em;
            color: #000000;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 1px;
        }
        .report-name {
            font-size: 12pt;
            font-weight: 800;
            color: #1e3a8a;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .meta-box {
            text-align: right;
            font-size: 8pt;
            color: #374151;
            line-height: 1.4;
        }

        /* Filter Summary Strip */
        .filter-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        .filter-item b {
            color: #111827;
        }

        /* Executive KPI Strip */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .kpi-card {
            border: 1px solid #9ca3af;
            border-radius: 6px;
            padding: 6px 10px;
            background: #f9fafb;
        }
        .kpi-label {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4b5563;
        }
        .kpi-val {
            font-size: 11pt;
            font-weight: 800;
            color: #111827;
            margin-top: 2px;
        }
        .kpi-sub {
            font-size: 7pt;
            color: #6b7280;
            margin-top: 1px;
        }

        /* Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 18px;
        }
        table.data-table th {
            background-color: #f3f4f6;
            color: #000000;
            border: 1px solid #4b5563;
            padding: 5px 6px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 7pt;
            text-align: left;
            vertical-align: middle;
        }
        table.data-table td {
            border: 1px solid #9ca3af;
            padding: 4px 6px;
            color: #111827;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        table.data-table tfoot td {
            background-color: #f3f4f6;
            font-weight: 800;
            border-top: 2px solid #000000;
            border-bottom: 2px solid #000000;
            padding: 6px;
            font-size: 8pt;
        }

        /* Badges */
        .grade-badge {
            display: inline-block;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            border: 1px solid #333;
            font-size: 7pt;
        }
        .status-pill {
            display: inline-block;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 6.5pt;
            border: 1px solid #999;
        }

        /* Signatures */
        .sign-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            font-size: 8pt;
        }
        .sign-title {
            font-weight: 700;
            margin-bottom: 50px;
        }
        .sign-name {
            font-weight: 800;
            border-top: 1px solid #000000;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
        }

        /* Print Media Settings */
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0 !important;
            }
            @page {
                size: landscape;
                margin: 8mm 6mm 8mm 6mm;
            }
            table.data-table tr {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); }, 250)">

    {{-- Top Action Bar (No Print) --}}
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 800; font-size: 10pt;">SM Inventory — Pratinjau Cetak Laporan</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="action-btn btn-print">
                🖨️ Cetak Dokumen / Simpan PDF
            </button>
            <button onclick="window.close()" class="action-btn btn-close">
                ✕ Tutup
            </button>
        </div>
    </div>

    {{-- Kop Laporan --}}
    <div class="report-header">
        <div class="header-top">
            <div>
                <div class="company-title">{{ $organization->name ?? 'SM INVENTORY' }}</div>
                <div class="company-subtitle">{{ $organization->address ?? 'Sistem Manajemen Inventaris & Rantai Pasok Terpadu' }}</div>
                <div class="report-name">{{ $reportTitle }}</div>
            </div>
            <div class="meta-box">
                <div>Dicetak: <b>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} WIB</b></div>
                <div>Operator: <b>{{ auth()->user()->name ?? 'Administrator' }}</b></div>
                <div>Format: <b>Landscape / A4</b></div>
            </div>
        </div>
    </div>

    {{-- Filter Metadata --}}
    <div class="filter-strip">
        <div class="filter-item">Periode PO: <b>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</b></div>
        <div class="filter-item">Cabang/Lokasi: <b>{{ $branchName }}</b></div>
        <div class="filter-item">Mitra Supplier: <b>{{ $supplierName }}</b></div>
        <div class="filter-item">Kategori Tab: <b>{{ $tabLabel }}</b></div>
    </div>

    {{-- KPI Overview --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Pemenuhan Jumlah Barang</div>
            <div class="kpi-val">{{ $kpi['sl_qty'] }}%</div>
            <div class="kpi-sub">{{ number_format($kpi['total_received_qty'], 0, ',', '.') }} / {{ number_format($kpi['total_ordered_qty'], 0, ',', '.') }} pcs</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Realisasi Nilai Faktur</div>
            <div class="kpi-val">{{ $kpi['sl_amount'] }}%</div>
            <div class="kpi-sub">Rp {{ number_format($kpi['total_gr_amount'] / 1000000, 1, ',', '.') }} Juta / Rp {{ number_format($kpi['total_po_amount'] / 1000000, 1, ',', '.') }} Juta</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Ketepatan Waktu Kirim</div>
            <div class="kpi-val">{{ $kpi['otd_rate'] }}%</div>
            <div class="kpi-sub">Total Terdata: {{ $kpi['total_po'] }} PO</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Deviasi & Selisih Harga</div>
            <div class="kpi-val">{{ $kpi['price_discrepancy_count'] }} Item</div>
            <div class="kpi-sub">Status: {{ $kpi['price_discrepancy_count'] > 0 ? 'Perlu Audit Faktur' : 'Sesuai Kontrak' }}</div>
        </div>
    </div>

    {{-- TAB 1: SCORECARD SUPPLIER --}}
    @if($activeTab === 'scorecard')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>Nama Mitra Supplier</th>
                    <th style="width: 50px; text-align: right;">Total PO</th>
                    <th style="width: 65px; text-align: right;">Qty Order</th>
                    <th style="width: 65px; text-align: right;">Qty Terima</th>
                    <th style="width: 65px; text-align: right;">Sisa Kurang</th>
                    <th style="width: 70px; text-align: center;">% Pemenuhan</th>
                    <th style="width: 85px; text-align: right;">Nilai PO (Rp)</th>
                    <th style="width: 85px; text-align: right;">Nilai Faktur (Rp)</th>
                    <th style="width: 65px; text-align: center;">% Faktur</th>
                    <th style="width: 70px; text-align: center;">Tepat Waktu</th>
                    <th style="width: 60px; text-align: center;">Beda Harga</th>
                    <th style="width: 65px; text-align: center;">Grade Rapor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($scorecards['items'] as $idx => $s)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <b>{{ $s['supplier_name'] }}</b>
                            @if(!empty($s['supplier_code']))
                                <span style="font-size: 6.5pt; color: #6b7280;">({{ $s['supplier_code'] }})</span>
                            @endif
                        </td>
                        <td style="text-align: right;">{{ number_format($s['po_count']) }}</td>
                        <td style="text-align: right;">{{ number_format($s['ordered_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($s['received_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: {{ $s['outstanding_qty'] > 0 ? '#b91c1c' : '#4b5563' }};">
                            {{ number_format($s['outstanding_qty'], 0, ',', '.') }}
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $s['sl_qty'] }}%</td>
                        <td style="text-align: right;">{{ number_format($s['po_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($s['gr_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $s['sl_amount'] }}%</td>
                        <td style="text-align: center;">{{ $s['otd_rate'] }}%</td>
                        <td style="text-align: center;">{{ $s['price_dev_count'] }} item</td>
                        <td style="text-align: center;">
                            <span class="grade-badge">Grade {{ $s['grade'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 15px;">Tidak ada data supplier yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($scorecards['items']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right;">TOTAL KESELURUHAN ({{ count($scorecards['items']) }} Supplier):</td>
                        <td style="text-align: right;">{{ number_format($scorecards['totals']['total_po']) }}</td>
                        <td style="text-align: right;">{{ number_format($scorecards['totals']['ordered_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($scorecards['totals']['received_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #b91c1c;">{{ number_format($scorecards['totals']['outstanding_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: center;">{{ $scorecards['totals']['overall_pct'] }}%</td>
                        <td style="text-align: right;">{{ number_format($scorecards['totals']['po_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($scorecards['totals']['gr_amount'], 0, ',', '.') }}</td>
                        <td colspan="4" style="text-align: center;">-</td>
                    </tr>
                </tfoot>
            @endif
        </table>

    {{-- TAB 2: REKONSILIASI PO --}}
    @elseif($activeTab === 'reconciliation')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>Nomor PO</th>
                    <th style="width: 65px;">Tgl PO</th>
                    <th style="width: 65px;">Expired</th>
                    <th>Mitra Supplier</th>
                    <th style="width: 80px;">Cabang</th>
                    <th style="width: 75px; text-align: center;">Status</th>
                    <th style="width: 55px; text-align: right;">Order</th>
                    <th style="width: 55px; text-align: right;">Terima</th>
                    <th style="width: 55px; text-align: right;">Sisa</th>
                    <th style="width: 50px; text-align: center;">% Kirim</th>
                    <th style="width: 80px; text-align: right;">Nilai PO (Rp)</th>
                    <th style="width: 80px; text-align: right;">Faktur (Rp)</th>
                    <th style="width: 75px; text-align: right;">Selisih (+/-)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reconciliations['items'] as $idx => $r)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td><b>{{ $r['po_number'] }}</b></td>
                        <td>{{ $r['po_date'] }}</td>
                        <td>{{ $r['expired_date'] }}</td>
                        <td>{{ $r['supplier_name'] }}</td>
                        <td>{{ $r['branch_name'] }}</td>
                        <td style="text-align: center;">
                            <span class="status-pill">{{ $r['status_label'] }}</span>
                        </td>
                        <td style="text-align: right;">{{ number_format($r['ordered_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($r['received_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: {{ $r['outstanding_qty'] > 0 ? '#b91c1c' : '#4b5563' }};">
                            {{ number_format($r['outstanding_qty'], 0, ',', '.') }}
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $r['sl_qty'] }}%</td>
                        <td style="text-align: right;">{{ number_format($r['po_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($r['gr_amount'], 0, ',', '.') }}</td>
                        @php $rowDiff = (float)$r['gr_amount'] - (float)$r['po_amount']; @endphp
                        <td style="text-align: right; font-weight: 700; color: {{ $rowDiff > 0 ? '#b91c1c' : ($rowDiff < 0 ? '#d97706' : '#15803d') }};">
                            {{ $rowDiff > 0 ? '+' . number_format($rowDiff, 0, ',', '.') : ($rowDiff < 0 ? '-' . number_format(abs($rowDiff), 0, ',', '.') : '0') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" style="text-align: center; padding: 15px;">Tidak ada data PO yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($reconciliations['items']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align: right;">TOTAL KESELURUHAN ({{ count($reconciliations['items']) }} PO):</td>
                        <td style="text-align: right;">{{ number_format($reconciliations['totals']['ordered_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($reconciliations['totals']['received_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #b91c1c;">{{ number_format($reconciliations['totals']['outstanding_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: center;">{{ $reconciliations['totals']['overall_pct'] }}%</td>
                        <td style="text-align: right;">{{ number_format($reconciliations['totals']['po_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($reconciliations['totals']['gr_amount'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">
                            {{ $reconciliations['totals']['diff_amount'] > 0 ? '+' . number_format($reconciliations['totals']['diff_amount'], 0, ',', '.') : ($reconciliations['totals']['diff_amount'] < 0 ? '-' . number_format(abs($reconciliations['totals']['diff_amount']), 0, ',', '.') : '0') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>

    {{-- TAB 3: AUDIT SELISIH BARANG & HARGA --}}
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>No PO & Supplier</th>
                    <th>SKU / Barcode</th>
                    <th>Nama Barang</th>
                    <th style="width: 80px; text-align: center;">Tipe Masalah</th>
                    <th style="width: 50px; text-align: right;">Order</th>
                    <th style="width: 50px; text-align: right;">Terima</th>
                    <th style="width: 50px; text-align: right;">Kurang</th>
                    <th style="width: 50px; text-align: right;">Rusak</th>
                    <th>Alasan Rusak</th>
                    <th style="width: 70px; text-align: right;">Harga PO</th>
                    <th style="width: 70px; text-align: right;">Harga Faktur</th>
                    <th style="width: 65px; text-align: right;">Beda Harga</th>
                    <th style="width: 75px; text-align: right;">Dampak (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discrepancies['items'] as $idx => $d)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <b>{{ $d['po_number'] }}</b>
                            <div style="font-size: 6.5pt; color: #4b5563;">{{ $d['supplier_name'] }} ({{ $d['branch_name'] }})</div>
                        </td>
                        <td style="font-family: monospace; font-size: 7pt;">
                            {{ $d['sku'] }}
                            @if(!empty($d['barcode']))
                                <div style="color: #6b7280;">{{ $d['barcode'] }}</div>
                            @endif
                        </td>
                        <td><b>{{ $d['product_name'] }}</b></td>
                        <td style="text-align: center;">
                            @if($d['has_price_deviation']) <span class="status-pill" style="border-color:#b91c1c; color:#b91c1c;">Beda Harga</span> @endif
                            @if($d['has_rejected']) <span class="status-pill" style="border-color:#b91c1c; color:#b91c1c;">Rusak</span> @endif
                            @if($d['has_short_qty']) <span class="status-pill" style="border-color:#d97706; color:#d97706;">Kurang</span> @endif
                        </td>
                        <td style="text-align: right;">{{ number_format($d['ordered_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($d['received_qty'], 0, ',', '.') }}</td>
                        <td style="text-align: right; color: {{ $d['qty_diff'] > 0 ? '#b91c1c' : '#4b5563' }};">
                            {{ number_format($d['qty_diff'], 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; color: {{ $d['rejected_qty'] > 0 ? '#b91c1c' : '#4b5563' }};">
                            {{ number_format($d['rejected_qty'], 0, ',', '.') }}
                        </td>
                        <td style="font-size: 7pt; color: #4b5563;">{{ $d['reject_reason'] }}</td>
                        <td style="text-align: right;">{{ number_format($d['po_price'], 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($d['gr_price'], 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: 700; color: {{ $d['price_diff'] > 0 ? '#b91c1c' : ($d['price_diff'] < 0 ? '#15803d' : '#4b5563') }};">
                            {{ $d['price_diff'] > 0 ? '+' . number_format($d['price_diff'], 0, ',', '.') : ($d['price_diff'] < 0 ? '-' . number_format(abs($d['price_diff']), 0, ',', '.') : '-') }}
                        </td>
                        <td style="text-align: right; font-weight: 800;">
                            {{ number_format($d['impact_amount'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" style="text-align: center; padding: 15px;">Tidak ditemukan data selisih barang yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($discrepancies['items']) > 0)
                @php
                    $discTotals = $discrepancies['totals'] ?? [];
                    $discRecon = $discrepancies['reconciliation'] ?? [];
                @endphp
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right; font-weight: 700;">TOTAL KESELURUHAN ({{ count($discrepancies['items']) }} Item Masalah):</td>
                        <td style="text-align: right;">{{ number_format($discTotals['ordered_qty'] ?? array_sum(array_column($discrepancies['items'], 'ordered_qty')), 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($discTotals['received_qty'] ?? array_sum(array_column($discrepancies['items'], 'received_qty')), 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #b91c1c;">{{ number_format($discTotals['qty_diff'] ?? array_sum(array_column($discrepancies['items'], 'qty_diff')), 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #b91c1c;">{{ number_format($discTotals['rejected_qty'] ?? array_sum(array_column($discrepancies['items'], 'rejected_qty')), 0, ',', '.') }}</td>
                        <td colspan="3" style="text-align: right; font-weight: 700;">Total Dampak Risiko Masalah:</td>
                        <td style="text-align: right; font-weight: 700; color: #b91c1c;">
                            Rp {{ number_format($discTotals['impact_amount'] ?? array_sum(array_column($discrepancies['items'], 'impact_amount')), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>

        @if(!empty($discrepancies['reconciliation']) && ($discrepancies['reconciliation']['po_amount'] ?? 0) > 0)
            @php $recon = $discrepancies['reconciliation']; @endphp
            <div style="margin-top: 15px; margin-bottom: 15px; border: 1px solid #1e293b; padding: 8px 12px; background: #f8fafc; font-size: 7.5pt;">
                <div style="font-weight: 700; text-transform: uppercase; margin-bottom: 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px;">
                    Rekonsiliasi Nilai PO, Realisasi Faktur & Selisih Fisik (Klop 100%)
                </div>
                <table style="width: 100%; font-size: 7.5pt; border-collapse: collapse;">
                    <tr>
                        <td style="width: 20%; padding: 4px; border: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="color: #64748b; font-size: 6.5pt;">1. Total Pesanan (PO)</div>
                            <div style="font-weight: 700; font-size: 8.5pt; color: #1e3a8a;">Rp {{ number_format($recon['po_amount'], 0, ',', '.') }}</div>
                        </td>
                        <td style="width: 20%; padding: 4px; border: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="color: #b91c1c; font-size: 6.5pt;">2. (-) Barang Kurang Kirim</div>
                            <div style="font-weight: 700; font-size: 8.5pt; color: #dc2626;">-Rp {{ number_format($recon['short_qty_amount'], 0, ',', '.') }}</div>
                        </td>
                        <td style="width: 20%; padding: 4px; border: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="color: #c2410c; font-size: 6.5pt;">3. (+/-) Selisih Harga Faktur</div>
                            <div style="font-weight: 700; font-size: 8.5pt; color: {{ $recon['net_price_dev_amount'] >= 0 ? '#b91c1c' : '#15803d' }};">
                                {{ $recon['net_price_dev_amount'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($recon['net_price_dev_amount']), 0, ',', '.') }}
                            </div>
                        </td>
                        <td style="width: 20%; padding: 4px; border: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="color: #15803d; font-size: 6.5pt;">4. (=) Total Faktur Tagihan</div>
                            <div style="font-weight: 700; font-size: 8.5pt; color: #16a34a;">Rp {{ number_format($recon['gr_amount'], 0, ',', '.') }}</div>
                        </td>
                        <td style="width: 20%; padding: 4px; border: 1px solid #e2e8f0; background: #ffffff;">
                            <div style="color: #475569; font-size: 6.5pt;">5. Selisih Bersih (PO - Faktur)</div>
                            <div style="font-weight: 700; font-size: 8.5pt; color: #0f172a;">Rp {{ number_format($recon['net_diff'], 0, ',', '.') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endif

    {{-- Signatures --}}
    <div class="sign-grid">
        <div class="sign-box">
            <div class="sign-title">Dibuat Oleh (Purchasing/Admin)</div>
            <div class="sign-name">{{ auth()->user()->name ?? 'Operator' }}</div>
        </div>
        <div class="sign-box">
            <div class="sign-title">Diperiksa Oleh (SPV / Keuangan)</div>
            <div class="sign-name">( ............................................ )</div>
        </div>
        <div class="sign-box">
            <div class="sign-title">Disetujui Oleh (Pimpinan / Owner)</div>
            <div class="sign-name">( ............................................ )</div>
        </div>
    </div>

</body>
</html>
