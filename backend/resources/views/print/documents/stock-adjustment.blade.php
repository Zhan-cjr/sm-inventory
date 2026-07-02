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
            <h1>Koreksi Stok (Stock Adjustment)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Koreksi</td>
                <td class="separator">:</td>
                <td>{{ $doc->adjustment_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tanggal</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($doc->adjustment_date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Alasan</td>
                <td class="separator">:</td>
                <td>{{ $doc->adjustmentReason ? $doc->adjustmentReason->name : '-' }}</td>
                <td class="label" style="text-align: right;">Pencatat</td>
                <td class="separator">:</td>
                <td>{{ $doc->recorder ? $doc->recorder->name : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="separator">:</td>
                <td colspan="4">{{ strtoupper($doc->status) }}</td>
            </tr>
        </table>

        @php
            $isRetur = $doc->adjustmentReason && strtolower($doc->adjustmentReason->name) === 'retur';
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Produk / Barang</th>
                    @if($isRetur)
                        <th class="text-center" style="width: 80px;">Qty Retur</th>
                    @else
                        <th class="text-center" style="width: 60px;">Stok Lama</th>
                        <th class="text-center" style="width: 60px;">Stok Baru</th>
                        <th class="text-center" style="width: 40px;">Selisih</th>
                    @endif
                    <th class="text-right" style="width: 90px;">HPP / Nilai</th>
                    <th class="text-right" style="width: 90px;">Total Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc->items as $index => $item)
                @php
                    $prevQty = (float) $item->previous_quantity;
                    $newQty = (float) $item->new_quantity;
                    $diff = $newQty - $prevQty;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product ? $item->product->name : '-' }}
                        <span style="color: #666; font-size: 0.85em;"> | Barcode: {{ $item->product ? $item->product->barcode : '-' }}</span>
                    </td>
                    @if($isRetur)
                        <td class="text-center">{{ abs($diff) }}</td>
                    @else
                        <td class="text-center">{{ $prevQty }}</td>
                        <td class="text-center">{{ $newQty }}</td>
                        <td class="text-center">
                            {{ $diff > 0 ? '+'.$diff : $diff }}
                        </td>
                    @endif
                    <td class="text-right">Rp {{ number_format($item->unit_cost ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_cost ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL NILAI KOREKSI</td>
                <td class="value" style="font-size: 12pt; font-weight: bold;">Rp {{ number_format($doc->total_value, 0, ',', '.') }}</td>
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
                    ({{ $doc->recorder ? $doc->recorder->name : '............................' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Disetujui Oleh<br>
                    (............................)
                </td>
            </tr>
        </table>
    </div>
    @endforeach
@endsection
