@extends('print.documents.layout')

@section('content')
    @foreach($documents as $doc)
    <div class="page-container">
        <div class="org-info">
            @php
                $orgName = \App\Models\Organization::first()->name ?? 'NAMA ORGANISASI';
                $branchName = $doc->branch ? $doc->branch->name : 'Pusat / Global';
                $branchAddress = $doc->branch ? $doc->branch->address : (\App\Models\Organization::first()->address ?? '');
            @endphp
            <h2>{{ $orgName }}</h2>
            <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
        </div>
        <div class="document-title">
            <h1>Penerimaan Barang (Goods Receipt)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Terima</td>
                <td class="separator">:</td>
                <td>{{ $doc->receipt_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tanggal</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($doc->receipt_date)->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td class="separator">:</td>
                <td>{{ $doc->supplier ? $doc->supplier->name : '-' }}</td>
                <td class="label" style="text-align: right;">Referensi PO</td>
                <td class="separator">:</td>
                <td>{{ $doc->purchaseOrder ? $doc->purchaseOrder->po_number : '-' }}</td>
            </tr>
            <tr>
                <td class="label">No. Faktur Sup.</td>
                <td class="separator">:</td>
                <td>{{ $doc->faktur_supplier ?: '-' }}</td>
                <td class="label" style="text-align: right;">Penerima</td>
                <td class="separator">:</td>
                <td>{{ $doc->received_by }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Produk / Barang</th>
                    <th class="text-center" style="width: 40px;">Qty</th>
                    <th class="text-right" style="width: 80px;">Harga</th>
                    <th class="text-right" style="width: 80px;">Diskon(%)</th>
                    <th class="text-right" style="width: 100px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product ? $item->product->name : '-' }}
                        <span style="color: #666; font-size: 0.85em;"> | Barcode: {{ $item->product ? $item->product->barcode : '-' }}</span>
                    </td>
                    <td class="text-center">{{ $item->quantity_received }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price ?? $item->unit_cost ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">
                        {{ (float)($item->discount_1 ?? 0) }}
                        @if((float)($item->discount_2 ?? 0) > 0) +{{ (float)$item->discount_2 }} @endif
                        @if((float)($item->discount_3 ?? 0) > 0) +{{ (float)$item->discount_3 }} @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            @if($doc->tax_amount > 0)
            <tr>
                <td class="label">DPP (Dasar Pengenaan Pajak)</td>
                <td class="value">Rp {{ number_format($doc->total_amount - $doc->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Pajak (PPN {{ (float)(\App\Models\Organization::first()->tax_rate ?? 11) }}%)</td>
                <td class="value">Rp {{ number_format($doc->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL</td>
                <td class="value" style="font-size: 12pt; font-weight: bold;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="clear: both; margin-top: 20px;">
            <p><strong>Catatan:</strong><br>{{ $doc->notes ?: '-' }}</p>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    Penerima Gudang<br>
                    ({{ $doc->received_by ?: '............................' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Supervisor<br>
                    (............................)
                </td>
            </tr>
        </table>
    </div>
    @endforeach
@endsection
