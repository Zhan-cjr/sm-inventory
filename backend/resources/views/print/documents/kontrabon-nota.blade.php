@extends('print.documents.layout')

@section('content')
    <div class="page-container">
        <div class="org-info">
            @php
                $orgName = $organization->name ?? 'NAMA ORGANISASI';
                $branchName = $kontrabon->branch ? $kontrabon->branch->name : 'Pusat / Global';
                $branchAddress = $kontrabon->branch ? $kontrabon->branch->address : ($organization->address ?? '');
            @endphp
            <h2>{{ $orgName }}</h2>
            <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
        </div>
        <div class="document-title">
            <h1>Tukar Faktur (Kontrabon)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Kontrabon</td>
                <td class="separator">:</td>
                <td>{{ $kontrabon->kontrabon_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tgl Kontrabon</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($kontrabon->tanggal_kontrabon)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td class="separator">:</td>
                <td>{{ $kontrabon->supplier ? $kontrabon->supplier->name : '-' }}</td>
                <td class="label" style="text-align: right;">Jatuh Tempo</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($kontrabon->tanggal_jatuh_tempo)->format('d-m-Y') }}</td>
            </tr>
        </table>

        @if($kontrabon->goodsReceipts->count() > 0)
        <h3>Faktur Tagihan (Penerimaan Barang)</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>No. Surat Jalan / GR</th>
                    <th>Tgl Faktur</th>
                    <th class="text-right" style="width: 150px;">Nominal Faktur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kontrabon->goodsReceipts as $index => $gr)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $gr->receipt_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($gr->receipt_date)->format('d-m-Y') }}</td>
                    <td class="text-right">Rp {{ number_format($gr->total_amount ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @elseif(isset($selloutItems) && $selloutItems->count() > 0)
        <h3>Rincian Barang Terjual (Sellout Konsinyasi)</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th style="width: 130px;">Barcode</th>
                    <th>SKU / Nama Produk</th>
                    <th class="text-right" style="width: 90px;">Harga Jual</th>
                    <th class="text-right" style="width: 90px;">HPP (Beli)</th>
                    <th class="text-center" style="width: 70px;">Qty</th>
                    <th class="text-right" style="width: 130px;">Subtotal Tagihan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($selloutItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['barcode'] }}</td>
                    <td><strong>{{ $item['sku'] }}</strong><br>{{ $item['name'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['selling_price'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item['cost_price'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($item['qty']) }}</td>
                    <td class="text-right">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($kontrabon->kontrabonDeductions->count() > 0)
        <h3 style="margin-top: 15px;">Potongan & Klaim</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Keterangan / ID Promo</th>
                    <th class="text-right" style="width: 150px;">Nominal Terpotong</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kontrabon->kontrabonDeductions as $index => $deduction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $deduction->supplierDeduction ? $deduction->supplierDeduction->notes : '-' }}</td>
                    <td class="text-right">- Rp {{ number_format($deduction->amount_applied ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table class="summary-box" style="margin-top: 15px;">
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL TAGIHAN BERSIH</td>
                <td class="value" style="font-size: 12pt; font-weight: bold;">Rp {{ number_format($kontrabon->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="clear: both; margin-top: 20px;">
            <p><strong>Catatan:</strong><br>{{ $kontrabon->notes ?: '-' }}</p>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    Dibuat Oleh<br>
                    ({{ auth()->user() ? auth()->user()->name : 'Admin' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Disetujui Oleh<br>
                    (............................)
                </td>
                <td>
                    <div class="signature-line"></div>
                    Supplier<br>
                    ({{ $kontrabon->supplier ? $kontrabon->supplier->name : '............................' }})
                </td>
            </tr>
        </table>
    </div>
@endsection
