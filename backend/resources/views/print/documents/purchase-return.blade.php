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

        @php
            $itemGrList = $doc->items->map(function($i) use ($doc) {
                $effectiveGr = $i->goodsReceipt ?: $doc->goodsReceipt;
                return $effectiveGr ? $effectiveGr->receipt_number : ($i->goods_receipt_id ?: 'NON-GR');
            })->unique()->values();

            if ($itemGrList->count() === 1 && $itemGrList[0] !== 'NON-GR') {
                $headerGrLabel = $itemGrList[0];
            } elseif ($itemGrList->count() > 1) {
                $headerGrLabel = 'Multi-GR (Terlampir per Item)';
            } elseif ($itemGrList->count() === 1 && $itemGrList[0] === 'NON-GR') {
                $headerGrLabel = 'NON-GR / SALDO AWAL';
            } else {
                $headerGrLabel = $doc->goodsReceipt ? $doc->goodsReceipt->receipt_number : '-';
            }

            $totalPcs = $doc->items->sum('quantity');
            $formattedTotalPcs = (float) $totalPcs == (int) $totalPcs 
                ? number_format($totalPcs, 0, ',', '.') 
                : number_format($totalPcs, 2, ',', '.');
        @endphp

        <table class="info-table">
            <tr>
                <td class="label">No. Retur</td>
                <td class="separator">:</td>
                <td>{{ $doc->return_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tanggal</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($doc->return_date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td class="separator">:</td>
                <td>{{ $doc->supplier ? $doc->supplier->name : '-' }}</td>
                <td class="label" style="text-align: right;">Referensi GR</td>
                <td class="separator">:</td>
                <td><strong>{{ $headerGrLabel }}</strong></td>
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
                    <th style="width: 170px;">Ref. No GR</th>
                    <th class="text-center" style="width: 65px;">Qty Retur</th>
                    <th class="text-right" style="width: 90px;">Harga Beli</th>
                    <th class="text-right" style="width: 110px;">Subtotal</th>
                    <th style="width: 120px;">Alasan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc->items as $index => $item)
                @php
                    $effectiveGr = $item->goodsReceipt ?: $doc->goodsReceipt;
                    $grText = $effectiveGr ? $effectiveGr->receipt_number : ($item->goods_receipt_id ?: 'NON-GR / SALDO AWAL');
                    $grDateText = $effectiveGr && $effectiveGr->receipt_date ? \Carbon\Carbon::parse($effectiveGr->receipt_date)->format('d/m/Y') : null;
                    $qtyFormatted = (float) $item->quantity == (int) $item->quantity 
                        ? number_format($item->quantity, 0, ',', '.') 
                        : number_format($item->quantity, 2, ',', '.');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $item->product ? $item->product->name : '-' }}</div>
                        <div style="color: #666; font-size: 0.85em;">Barcode: {{ $item->product ? $item->product->barcode : '-' }}</div>
                    </td>
                    <td style="font-size: 0.9em;">
                        <strong>{{ $grText }}</strong>
                        @if($grDateText)
                            <div style="color: #666; font-size: 0.85em;">Tgl: {{ $grDateText }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-weight: 600;">{{ $qtyFormatted }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: 600;">Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
                    <td style="font-size: 0.85em;">{{ $item->reason ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <td class="label" style="font-size: 10pt; color: #555;">TOTAL BARANG / PCS</td>
                <td class="value" style="font-size: 10pt; font-weight: 600; text-align: right;">{{ count($doc->items) }} Baris / {{ $formattedTotalPcs }} Pcs</td>
            </tr>
            <tr>
                <td class="label" style="font-size: 12pt; font-weight: bold;">TOTAL NILAI RETUR</td>
                <td class="value" style="font-size: 12pt; font-weight: bold; color: #dc2626; text-align: right;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
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
