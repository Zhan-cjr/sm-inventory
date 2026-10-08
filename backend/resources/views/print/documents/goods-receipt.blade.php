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
                <td>{{ \Carbon\Carbon::parse($doc->receipt_date)->format('d-m-Y') }}</td>
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
            <tr>
                <td class="label"></td>
                <td class="separator"></td>
                <td></td>
                <td class="label" style="text-align: right;">Jatuh Tempo</td>
                <td class="separator">:</td>
                <td>{{ $doc->due_date ? \Carbon\Carbon::parse($doc->due_date)->format('d-m-Y') : '-' }}</td>
            </tr>
        </table>

        @php
            $taxRate = (float)(\App\Models\Organization::first()->tax_rate ?? 11);
            $taxMultiplier = 1 + ($taxRate / 100);
            $isInclude = ($doc->tax_type === 'include');
            $subtotalItems = $doc->items->sum('subtotal');
            $discountVal = (float)($doc->discount_subtotal ?? 0);
            $dppNet = $doc->total_amount - ($doc->tax_amount ?? 0);
        @endphp

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Produk / Barang</th>
                    <th class="text-center" style="width: 40px;">Qty</th>
                    <th class="text-right" style="width: 90px;">Harga {{ $isInclude ? '(+PPN)' : '(DPP)' }}</th>
                    <th class="text-right" style="width: 100px;">Diskon</th>
                    <th class="text-right" style="width: 105px;">Subtotal {{ $isInclude ? '(+PPN)' : '(DPP)' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc->items as $index => $item)
                @php
                    $rowPrice = $isInclude ? round($item->unit_price * $taxMultiplier, 2) : $item->unit_price;
                    $rowSubtotal = $isInclude ? round($item->subtotal * $taxMultiplier, 2) : $item->subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product ? $item->product->name : '-' }}
                        <span style="color: #666; font-size: 0.85em;"> | Barcode: {{ $item->product ? $item->product->barcode : '-' }}</span>
                    </td>
                    <td class="text-center">{{ $item->quantity_received }}</td>
                    <td class="text-right">Rp {{ number_format($rowPrice, 0, ',', '.') }}</td>
                    <td class="text-right">
                        @php
                            $discs = [];
                            foreach([1, 2, 3] as $tier) {
                                $val = (float)($item->{"discount_{$tier}"} ?? 0);
                                $type = $item->{"discount_{$tier}_type"} ?? 'percent';
                                if ($val > 0) {
                                    $discs[] = ($type === 'nominal') ? 'Rp ' . number_format($val, 0, ',', '.') : $val . '%';
                                }
                            }
                        @endphp
                        {{ count($discs) > 0 ? implode(' + ', $discs) : '0%' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($rowSubtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            @if($isInclude)
                {{-- 1. MODE INCLUDE PPN (seperti Amidis, Danone/Aqua) --}}
                @php
                    $subtotalGross = round($subtotalItems * $taxMultiplier, 0);
                    $discGross = $discountVal > 0 ? ($doc->discount_subtotal_type === 'percent' ? round($subtotalGross * ($discountVal / 100), 0) : $discountVal) : 0;
                @endphp
                <tr>
                    <td class="label">Subtotal Barang (+PPN)</td>
                    <td class="value">Rp {{ number_format($subtotalGross, 0, ',', '.') }}</td>
                </tr>
                @if($discGross > 0)
                <tr>
                    <td class="label">
                        Diskon Faktur
                        @if($doc->discount_subtotal_type === 'percent')
                            ({{ (float)$discountVal }}%)
                        @endif
                    </td>
                    <td class="value" style="color: #b91c1c;">- Rp {{ number_format($discGross, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top: 1px dashed #ccc;">
                    <td class="label">DPP (Dasar Pengenaan Pajak)</td>
                    <td class="value">Rp {{ number_format($dppNet, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Pajak (PPN {{ $taxRate }}%)</td>
                    <td class="value">Rp {{ number_format($doc->tax_amount, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td class="label" style="font-size: 11pt;">TOTAL FAKTUR</td>
                    <td class="value" style="font-size: 11pt; font-weight: bold;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
                </tr>
            @elseif($doc->tax_type === 'exclude')
                {{-- 2. MODE EXCLUDE PPN (seperti Unilever, Wings, Mayora) --}}
                @php
                    $discDpp = $discountVal > 0 ? ($doc->discount_subtotal_type === 'percent' ? round($subtotalItems * ($discountVal / 100), 0) : $discountVal) : 0;
                @endphp
                <tr>
                    <td class="label">Subtotal Barang (DPP)</td>
                    <td class="value">Rp {{ number_format($subtotalItems, 0, ',', '.') }}</td>
                </tr>
                @if($discDpp > 0)
                <tr>
                    <td class="label">
                        Diskon Faktur
                        @if($doc->discount_subtotal_type === 'percent')
                            ({{ (float)$discountVal }}%)
                        @endif
                    </td>
                    <td class="value" style="color: #b91c1c;">- Rp {{ number_format($discDpp, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top: 1px dashed #ccc;">
                    <td class="label">DPP Netto</td>
                    <td class="value">Rp {{ number_format($dppNet, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Pajak (PPN {{ $taxRate }}%)</td>
                    <td class="value">Rp {{ number_format($doc->tax_amount, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td class="label" style="font-size: 11pt;">TOTAL FAKTUR</td>
                    <td class="value" style="font-size: 11pt; font-weight: bold;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
                </tr>
            @else
                {{-- 3. MODE NON-PPN --}}
                <tr>
                    <td class="label">Subtotal Barang</td>
                    <td class="value">Rp {{ number_format($subtotalItems, 0, ',', '.') }}</td>
                </tr>
                @if($discountVal > 0)
                <tr>
                    <td class="label">
                        Diskon Faktur
                        @if($doc->discount_subtotal_type === 'percent')
                            ({{ (float)$discountVal }}%)
                        @endif
                    </td>
                    <td class="value" style="color: #b91c1c;">- Rp {{ number_format($discountVal, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top: 2px solid #333;">
                    <td class="label" style="font-size: 11pt;">TOTAL</td>
                    <td class="value" style="font-size: 11pt; font-weight: bold;">Rp {{ number_format($doc->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
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
