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
            <h1>Retur Pembelian (Purchase Return)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Retur</td>
                <td class="separator">:</td>
                <td>{{ $doc->return_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tanggal</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($doc->return_date)->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td class="separator">:</td>
                <td>{{ $doc->supplier ? $doc->supplier->name : '-' }}</td>
                <td class="label" style="text-align: right;">Referensi GR</td>
                <td class="separator">:</td>
                <td>{{ $doc->goodsReceipt ? $doc->goodsReceipt->receipt_number : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Pembuat</td>
                <td class="separator">:</td>
                <td colspan="4">{{ $doc->created_by ? \App\Models\User::find($doc->created_by)?->name : 'Sistem' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Produk / Barang</th>
                    <th class="text-center" style="width: 40px;">Qty Retur</th>
                    <th class="text-right" style="width: 80px;">Harga Beli</th>
                    <th class="text-right" style="width: 100px;">Subtotal</th>
                    <th style="width: 120px;">Alasan</th>
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
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $item->reason ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL NILAI RETUR</td>
                <td class="value" style="font-size: 12pt; font-weight: bold; color: #dc2626;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="clear: both; margin-top: 20px;">
            <p><strong>Catatan:</strong><br>{{ $doc->notes ?: '-' }}</p>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    Dibuat Oleh<br>
                    ({{ $doc->created_by ? \App\Models\User::find($doc->created_by)?->name : '............................' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Mengetahui<br>
                    (............................)
                </td>
                <td>
                    <div class="signature-line"></div>
                    Pihak Supplier<br>
                    (............................)
                </td>
            </tr>
        </table>
    </div>
    @endforeach
@endsection
