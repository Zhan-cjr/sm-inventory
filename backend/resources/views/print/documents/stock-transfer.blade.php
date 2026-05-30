@extends('print.documents.layout')

@section('content')
    @foreach($documents as $doc)
    <div class="page-container">
        <div class="org-info">
            @php
                $orgName = \App\Models\Organization::first()->name ?? 'NAMA ORGANISASI';
                $branchName = $doc->fromBranch ? $doc->fromBranch->name : 'Pusat / Global';
                $branchAddress = $doc->fromBranch ? $doc->fromBranch->address : (\App\Models\Organization::first()->address ?? '');
            @endphp
            <h2>{{ $orgName }}</h2>
            <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
        </div>
        <div class="document-title">
            <h1>Transfer Stok (Stock Transfer)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Referensi</td>
                <td class="separator">:</td>
                <td>{{ $doc->reference_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tgl Transfer</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($doc->transfer_date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Cabang Asal</td>
                <td class="separator">:</td>
                <td>{{ $doc->fromBranch ? $doc->fromBranch->name : '-' }}</td>
                <td class="label" style="text-align: right;">Status</td>
                <td class="separator">:</td>
                <td>{{ strtoupper($doc->status) }}</td>
            </tr>
            <tr>
                <td class="label">Cabang Tujuan</td>
                <td class="separator">:</td>
                <td colspan="4">{{ $doc->toBranch ? $doc->toBranch->name : '-' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Produk / Barang</th>
                    <th class="text-center" style="width: 60px;">Qty Kirim</th>
                    <th class="text-center" style="width: 60px;">Qty Terima</th>
                    <th class="text-right" style="width: 100px;">Total Nilai</th>
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
                    <td class="text-center">{{ $item->quantity_received ?: '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL NILAI</td>
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
                    Pengirim<br>
                    ({{ $doc->creator ? $doc->creator->name : '............................' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Sopir / Kurir<br>
                    (............................)
                </td>
                <td>
                    <div class="signature-line"></div>
                    Penerima<br>
                    (............................)
                </td>
            </tr>
        </table>
    </div>
    @endforeach
@endsection
