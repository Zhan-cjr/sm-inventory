@extends('print.layout')

@section('title', 'Laporan Final Check — ' . $session->session_number)

@section('content')
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; }

    .doc-title {
        font-size: 14px;
        font-weight: bold;
        margin: 0 0 4px 0;
    }
    .doc-meta {
        font-size: 11px;
        color: #333;
        margin-bottom: 2px;
    }
    .status-badge {
        display: inline-block;
        padding: 2px 10px;
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 20px;
        font-size: 10px;
        font-weight: bold;
        color: #92400e;
        margin-top: 4px;
    }

    .summary-box {
        display: flex;
        gap: 16px;
        margin: 14px 0;
        flex-wrap: wrap;
    }
    .summary-item {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 6px 14px;
        text-align: center;
        min-width: 80px;
    }
    .summary-label { font-size: 9px; text-transform: uppercase; color: #666; letter-spacing: 0.04em; }
    .summary-val { font-size: 16px; font-weight: bold; color: #000; margin-top: 2px; }

    .section-title {
        font-size: 12px;
        font-weight: bold;
        margin: 18px 0 4px;
        border-bottom: 1px solid #333;
        padding-bottom: 4px;
    }

    /* Product block */
    .product-block { margin-bottom: 20px; page-break-inside: avoid; }
    .product-header {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-bottom: none;
        padding: 8px 12px;
        border-radius: 4px 4px 0 0;
    }
    .product-name { font-size: 12px; font-weight: bold; }
    .product-sku  { font-size: 10px; color: #666; font-family: monospace; }

    .product-pills {
        display: flex;
        gap: 10px;
        margin-top: 6px;
    }
    .pill {
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: bold;
    }
    .pill-sistem  { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
    .pill-p1      { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .pill-p2      { background: #fef3c7; color: #78350f; border: 1px solid #fcd34d; }
    .pill-selisih { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }

    /* Table */
    .fc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
        border: 1px solid #d1d5db;
        border-radius: 0 0 4px 4px;
        overflow: hidden;
    }
    .fc-table th {
        background: #f9fafb;
        border-bottom: 2px solid #d1d5db;
        border-right: 1px solid #e5e7eb;
        padding: 6px 10px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #374151;
        font-weight: bold;
    }
    .fc-table td {
        padding: 7px 10px;
        border-bottom: 1px solid #e5e7eb;
        border-right: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    .fc-table tbody tr:last-child td { border-bottom: none; }
    .fc-table tbody tr:nth-child(even) td { background: #f9fafb; }

    .td-right { text-align: right; }
    .td-center { text-align: center; }
    .disc-neg { color: #dc2626; font-weight: bold; }
    .disc-pos { color: #16a34a; font-weight: bold; }

    .final-qty-box {
        display: inline-block;
        border: 1.5px solid #6366f1;
        border-radius: 4px;
        padding: 3px 12px;
        min-width: 70px;
        text-align: right;
        font-weight: bold;
        color: #4338ca;
        background: #eef2ff;
    }
    .final-qty-empty {
        display: inline-block;
        border: 1.5px dashed #d1d5db;
        border-radius: 4px;
        padding: 3px 12px;
        min-width: 70px;
        text-align: right;
        color: #9ca3af;
    }

    /* Footer */
    .signature-area {
        margin-top: 30px;
        display: flex;
        justify-content: flex-end;
        gap: 60px;
    }
    .signature-box { text-align: center; }
    .signature-line {
        margin-top: 50px;
        border-top: 1px solid #333;
        padding-top: 4px;
        font-size: 10px;
    }
    .print-footer {
        margin-top: 24px;
        border-top: 1px dashed #ccc;
        padding-top: 8px;
        font-size: 9px;
        color: #666;
        text-align: center;
    }
</style>

{{-- Document Title --}}
<div style="margin-bottom: 14px;">
    <p class="doc-title">LAPORAN FINAL CHECK STOK OPNAME</p>
    <p class="doc-meta">Nomor Sesi : <strong>{{ $session->session_number }}</strong></p>
    <p class="doc-meta">Cabang     : <strong>{{ $session->branch?->name }}</strong></p>
    <p class="doc-meta">Tanggal    : <strong>{{ $session->opname_date?->format('d M Y') }}</strong></p>
    <p class="doc-meta">Status     : <span class="status-badge">⚠️ FINAL CHECK SPV</span></p>
</div>

{{-- Summary Box --}}
<div class="summary-box">
    <div class="summary-item">
        <div class="summary-label">Produk Selisih</div>
        <div class="summary-val">{{ count($groups) }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Total Item Rak</div>
        <div class="summary-val">{{ collect($groups)->sum(fn($g) => count($g['racks'])) }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Dicetak</div>
        <div class="summary-val" style="font-size:11px;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<hr style="border:1px solid #ddd; margin:10px 0 16px;">

{{-- Instruction --}}
<p style="font-size:10px; color:#555; margin-bottom:16px; line-height:1.5;">
    <strong>Instruksi SPV:</strong> Di bawah ini adalah daftar produk yang ditemukan selisih antara Penghitung 1 dan Pengecek 2.
    Kolom <strong>Final Qty (SPV)</strong> menunjukkan hasil verifikasi akhir yang sudah diisi (jika sudah). Lengkapi kolom tanda tangan di bagian bawah.
</p>

{{-- Product Blocks --}}
@forelse($groups as $group)
@php $selisihP1P2 = $group['total_count2'] - $group['total_count1']; @endphp
<div class="product-block">
    <div class="product-header">
        <div class="product-name">{{ $group['product_name'] }}</div>
        <div class="product-sku">SKU: {{ $group['product_sku'] }}</div>
        <div class="product-pills">
            <span class="pill pill-sistem">Sistem: {{ number_format($group['system_qty'], 0) }}</span>
            <span class="pill pill-p1">Total P1: {{ number_format($group['total_count1'], 0) }}</span>
            <span class="pill pill-p2">Total P2: {{ number_format($group['total_count2'], 0) }}</span>
            <span class="pill pill-selisih">Selisih P1↔P2: {{ $selisihP1P2 > 0 ? '+' : '' }}{{ number_format($selisihP1P2, 0) }}</span>
        </div>
    </div>
    <table class="fc-table">
        <thead>
            <tr>
                <th>Rak</th>
                <th class="td-right">Hitung P1</th>
                <th class="td-right">Cek P2</th>
                <th class="td-right">Selisih P1↔P2</th>
                <th class="td-right">Final Qty (SPV)</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group['racks'] as $rack)
            @php
                $disc       = $rack['discrepancy'] ?? ($rack['count2_quantity'] - $rack['count1_quantity']);
                $finalQty   = $rack['final_quantity'] ?? null;
                $finalNotes = $rack['final_notes'] ?? '';
            @endphp
            <tr>
                <td>
                    <strong style="font-family:monospace;">{{ $rack['rack_code'] }}</strong>
                    @if(!empty($rack['rack_name']))
                    <br><span style="font-size:9px;color:#666;">{{ $rack['rack_name'] }}</span>
                    @endif
                </td>
                <td class="td-right">{{ number_format($rack['count1_quantity'], 0) }}</td>
                <td class="td-right">{{ number_format($rack['count2_quantity'], 0) }}</td>
                <td class="td-right {{ $disc != 0 ? ($disc < 0 ? 'disc-neg' : 'disc-pos') : '' }}">
                    {{ $disc > 0 ? '+' : '' }}{{ number_format($disc, 0) }}
                </td>
                <td class="td-right">
                    @if($finalQty !== null)
                        <span class="final-qty-box">{{ number_format($finalQty, 0) }}</span>
                    @else
                        <span class="final-qty-empty">_______</span>
                    @endif
                </td>
                <td style="font-size:10px;color:#555;">{{ $finalNotes ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@empty
<p style="text-align:center;color:#666;padding:20px;">Tidak ada item selisih yang perlu di-final-check.</p>
@endforelse

{{-- Signature Area --}}
<div class="signature-area">
    <div class="signature-box">
        <div style="font-size:10px;color:#555;">Supervisor / Kepala Cabang</div>
        <div class="signature-line">( _________________________ )</div>
        <div style="font-size:9px;color:#888;">Nama & Tanda Tangan</div>
    </div>
    <div class="signature-box">
        <div style="font-size:10px;color:#555;">Penghitung 1</div>
        <div class="signature-line">( _________________________ )</div>
        <div style="font-size:9px;color:#888;">Nama & Tanda Tangan</div>
    </div>
    <div class="signature-box">
        <div style="font-size:10px;color:#555;">Pengecek 2</div>
        <div class="signature-line">( _________________________ )</div>
        <div style="font-size:9px;color:#888;">Nama & Tanda Tangan</div>
    </div>
</div>

<div class="print-footer">
    Dicetak dari SM Inventory &nbsp;|&nbsp; {{ $session->session_number }} &nbsp;|&nbsp; {{ now()->format('d M Y H:i:s') }}
</div>

@endsection
