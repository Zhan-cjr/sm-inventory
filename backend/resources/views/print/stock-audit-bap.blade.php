@extends('print.layout')

@section('title', 'Berita Acara Pemeriksaan (BAP) Sidak Audit Stok — ' . $audit->audit_number)

@section('content')
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; }

    .doc-header {
        border-bottom: 2px solid #333;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .doc-title {
        font-size: 15px;
        font-weight: bold;
        margin: 0 0 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .meta-table {
        width: 100%;
        margin-bottom: 12px;
        font-size: 11px;
    }
    .meta-table td {
        padding: 3px 6px;
        vertical-align: top;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
    }
    .status-draft { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
    .status-investigation { background: #e0f2fe; color: #0369a1; border: 1px solid #0284c7; }
    .status-adjusted { background: #dcfce7; color: #15803d; border: 1px solid #22c55e; }
    .status-closed { background: #f3f4f6; color: #374151; border: 1px solid #9ca3af; }

    .summary-grid {
        display: flex;
        gap: 12px;
        margin: 12px 0 16px 0;
    }
    .summary-card {
        flex: 1;
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 8px 12px;
        text-align: center;
        background: #fafafa;
    }
    .summary-card.danger {
        border-color: #fca5a5;
        background: #fef2f2;
    }
    .summary-card.success {
        border-color: #86efac;
        background: #f0fdf4;
    }
    .summary-title {
        font-size: 9px;
        text-transform: uppercase;
        color: #666;
        font-weight: bold;
        letter-spacing: 0.05em;
    }
    .summary-value {
        font-size: 15px;
        font-weight: bold;
        margin-top: 4px;
    }

    .audit-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 10px;
    }
    .audit-table th {
        background: #f3f4f6;
        border: 1px solid #999;
        padding: 6px 4px;
        text-align: center;
        font-weight: bold;
    }
    .audit-table td {
        border: 1px solid #ccc;
        padding: 5px 4px;
        vertical-align: middle;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .diff-minus { color: #dc2626; font-weight: bold; }
    .diff-plus { color: #2563eb; font-weight: bold; }
    .diff-match { color: #16a34a; font-weight: bold; }

    .signature-section {
        margin-top: 30px;
        page-break-inside: avoid;
    }
    .sig-table {
        width: 100%;
        text-align: center;
    }
    .sig-space {
        height: 60px;
    }
</style>

<div class="doc-header">
    <div class="doc-title">BERITA ACARA PEMERIKSAAN (BAP) SIDAK AUDIT STOK</div>
    <div style="font-size: 11px; color: #555;">SM INVENTORY &bull; SISTEM AUDIT & KONTROL INTERNAL TOKO</div>
</div>

<table class="meta-table">
    <tr>
        <td style="width: 15%;"><strong>No. Sidak</strong></td>
        <td style="width: 35%;">: <strong>{{ $audit->audit_number }}</strong></td>
        <td style="width: 15%;"><strong>Cabang Toko</strong></td>
        <td style="width: 35%;">: {{ $audit->branch?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Tanggal Sidak</strong></td>
        <td>: {{ $audit->inspection_date?->translatedFormat('d F Y') ?? date('d M Y') }}</td>
        <td><strong>Auditor / Pemeriksa</strong></td>
        <td>: {{ $audit->auditor?->name ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Mode Pemeriksaan</strong></td>
        <td>: 
            @if($audit->inspection_mode === 'FREE_SCAN')
                Scan Bebas (Spot Check)
            @elseif($audit->inspection_mode === 'RANDOM_LOTTERY')
                Undian Acak Sistem (Random Sampling)
            @else
                Pemeriksaan Berbasis Risiko
            @endif
        </td>
        <td><strong>Status Dokumen</strong></td>
        <td>: 
            @if($audit->status === 'DRAFT')
                <span class="status-badge status-draft">DRAFT / BERJALAN</span>
            @elseif($audit->status === 'INVESTIGATION_PENDING')
                <span class="status-badge status-investigation">MENUNGGU KLARIFIKASI / INVESTIGASI</span>
            @elseif($audit->status === 'ADJUSTED')
                <span class="status-badge status-adjusted">STOK DISESUAIKAN (KOREKSI DITERAPKAN)</span>
            @else
                <span class="status-badge status-closed">DITUTUP</span>
            @endif
        </td>
    </tr>
    @if($audit->notes)
    <tr>
        <td><strong>Catatan Sidak</strong></td>
        <td colspan="3">: {{ $audit->notes }}</td>
    </tr>
    @endif
</table>

<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-title">TOTAL ITEM DICEK</div>
        <div class="summary-value">{{ $audit->total_items_checked }} Macam Produk</div>
    </div>
    <div class="summary-card">
        <div class="summary-title">TOTAL ITEM SELISIH</div>
        <div class="summary-value" style="color: {{ $audit->total_items_discrepancy > 0 ? '#dc2626' : '#16a34a' }};">
            {{ $audit->total_items_discrepancy }} Item
        </div>
    </div>
    <div class="summary-card danger">
        <div class="summary-title">TOTAL KERUGIAN (MINUS)</div>
        <div class="summary-value" style="color: #dc2626;">
            -Rp {{ number_format($audit->total_loss_nominal, 0, ',', '.') }}
        </div>
    </div>
    <div class="summary-card success">
        <div class="summary-title">TOTAL LEBIH (PLUS)</div>
        <div class="summary-value" style="color: #16a34a;">
            +Rp {{ number_format($audit->total_gain_nominal, 0, ',', '.') }}
        </div>
    </div>
</div>

<div style="font-weight: bold; margin-bottom: 4px; text-transform: uppercase; font-size: 11px;">Rincian Hasil Penghitungan Fisik per Produk:</div>
<table class="audit-table">
    <thead>
        <tr>
            <th style="width: 25px;">No</th>
            <th style="width: 100px;">Barcode / SKU</th>
            <th>Nama Produk</th>
            <th style="width: 60px;">Rak</th>
            <th style="width: 55px;">Sistem</th>
            <th style="width: 45px;">Mutasi</th>
            <th style="width: 55px;">Fisik</th>
            <th style="width: 55px;">Selisih</th>
            <th style="width: 75px;">HPP Satuan</th>
            <th style="width: 85px;">Total Selisih (Rp)</th>
            <th style="width: 110px;">Keterangan / Klarifikasi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($audit->items as $idx => $it)
        <tr>
            <td class="text-center">{{ $idx + 1 }}</td>
            <td class="text-left font-mono" style="font-size: 9px;">
                {{ $it->product?->barcode ?? $it->product?->sku ?? '-' }}
            </td>
            <td class="text-left">
                <strong>{{ $it->product?->name ?? 'Produk Dihapus' }}</strong>
            </td>
            <td class="text-center" style="font-size: 9px;">
                {{ $it->rack?->rack_name ?? '-' }}
            </td>
            <td class="text-right">{{ number_format($it->system_quantity, 0) }}</td>
            <td class="text-right" style="color: #666; font-size: 9px;">
                {{ $it->delta_sales_quantity != 0 ? ($it->delta_sales_quantity > 0 ? '+' : '') . number_format($it->delta_sales_quantity, 0) : '-' }}
            </td>
            <td class="text-right" style="font-weight: bold;">{{ number_format($it->physical_quantity, 0) }}</td>
            <td class="text-right">
                @if($it->discrepancy_quantity < 0)
                    <span class="diff-minus">{{ number_format($it->discrepancy_quantity, 0) }}</span>
                @elseif($it->discrepancy_quantity > 0)
                    <span class="diff-plus">+{{ number_format($it->discrepancy_quantity, 0) }}</span>
                @else
                    <span class="diff-match">0</span>
                @endif
            </td>
            <td class="text-right">Rp {{ number_format($it->unit_cost, 0, ',', '.') }}</td>
            <td class="text-right">
                @if($it->discrepancy_cost < 0)
                    <span class="diff-minus">-Rp {{ number_format(abs($it->discrepancy_cost), 0, ',', '.') }}</span>
                @elseif($it->discrepancy_cost > 0)
                    <span class="diff-plus">+Rp {{ number_format($it->discrepancy_cost, 0, ',', '.') }}</span>
                @else
                    <span class="diff-match">Rp 0</span>
                @endif
            </td>
            <td class="text-left" style="font-size: 9px; color: #444;">
                {{ $it->clarification_notes ?: '-' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center" style="padding: 15px; color: #888;">
                Belum ada produk yang dipindai dalam sesi sidak ini.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="signature-section">
    <table class="sig-table">
        <tr>
            <td style="width: 50%;">
                Dihitung & Diperiksa Oleh,<br>
                <strong>Tim Internal Audit / Pemeriksa</strong>
                <div class="sig-space"></div>
                <u>( {{ $audit->auditor?->name ?? '....................................' }} )</u><br>
                <span style="font-size: 9px; color: #555;">Auditor Internal / Supervisor</span>
            </td>
            <td style="width: 50%;">
                Disaksikan & Diterima Oleh,<br>
                <strong>Kepala Toko / Penanggung Jawab Toko</strong>
                <div class="sig-space"></div>
                <u>( {{ $audit->acknowledged_by ?? '....................................' }} )</u><br>
                <span style="font-size: 9px; color: #555;">Kepala Cabang / SPV Toko</span>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 20px; font-size: 9px; color: #777; border-top: 1px dashed #ccc; padding-top: 4px;">
    Dicetak dari SM-Inventory ERP pada: {{ now()->translatedFormat('d F Y H:i:s') }} | Nomor Dokumen Resmi BAP Internal Audit.
</div>
@endsection

