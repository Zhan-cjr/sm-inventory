@extends('print.documents.layout')

@section('content')
<div class="page-container">
    <div class="org-info">
        @php
            $orgName = \App\Models\Organization::first()->name ?? 'NAMA ORGANISASI';
            $branchName = $branch ? $branch->name : 'Pusat / Global';
            $branchAddress = $branch ? $branch->address : (\App\Models\Organization::first()->address ?? '');
        @endphp
        <h2>{{ $orgName }}</h2>
        <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
    </div>
    <div class="document-title">
        <h1>BUKTI PEMINDAHAN BARANG RUSAK (KARANTINA BS)</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">No. Dokumen</td>
            <td class="separator">:</td>
            <td><strong>{{ $referenceNumber }}</strong></td>
            <td class="label" style="text-align: right; width: 100px;">Tanggal</td>
            <td class="separator">:</td>
            <td>{{ \Carbon\Carbon::parse($movements->first()->created_at ?? now())->format('d-m-Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Cabang Toko</td>
            <td class="separator">:</td>
            <td>{{ $branch ? $branch->name : '-' }}</td>
            <td class="label" style="text-align: right;">Petugas Input</td>
            <td class="separator">:</td>
            <td>{{ $creator ? $creator->name : (auth()->user()->name ?? 'Admin') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 130px;">Barcode / SKU</th>
                <th>Produk / Barang</th>
                <th class="text-center" style="width: 70px;">Qty Rusak</th>
                <th style="width: 140px;">Alasan Kerusakan</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($movements as $index => $item)
            @php $totalQty += (float)$item->quantity; @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 0.9em;">
                    {{ $item->product ? ($item->product->barcode ?: $item->product->sku) : '-' }}
                </td>
                <td>
                    <strong>{{ $item->product ? $item->product->name : '-' }}</strong>
                </td>
                <td class="text-center" style="font-weight: bold; font-size: 1.05em; color: #b91c1c;">
                    {{ (float)$item->quantity }} {{ $item->product->unit_of_measure ?? 'Pcs' }}
                </td>
                <td>{{ $item->reason ?: '-' }}</td>
                <td>{{ $item->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-box">
        <tr>
            <td class="label" style="font-size: 11pt;">TOTAL ITEM VARIAN</td>
            <td class="value" style="font-size: 11pt; font-weight: bold;">{{ count($movements) }} Item</td>
        </tr>
        <tr>
            <td class="label" style="font-size: 11pt;">TOTAL KUANTITAS BS</td>
            <td class="value" style="font-size: 12pt; font-weight: bold; color: #b91c1c;">{{ $totalQty }} Pcs</td>
        </tr>
    </table>

    <div style="clear: both; margin-top: 20px;">
        <p style="font-size: 10px; color: #555;">
            <em>* Catatan: Barang yang tercantum pada surat bukti ini telah dipindahkan dari rak display dan disatukan di area Karantina BS untuk persiapan klaim retur ke supplier atau pemusnahan resmi.</em>
        </p>
    </div>

    <table class="signature-table" style="margin-top: 30px;">
        <tr>
            <td style="width: 33%;">
                <div class="signature-line"></div>
                Yang Menyerahkan (Display/Kasir)<br>
                (............................)
            </td>
            <td style="width: 33%;">
                <div class="signature-line"></div>
                Penerima (PIC Gudang BS)<br>
                (............................)
            </td>
            <td style="width: 33%;">
                <div class="signature-line"></div>
                Mengetahui (Kepala Toko)<br>
                (............................)
            </td>
        </tr>
    </table>
</div>
@endsection
