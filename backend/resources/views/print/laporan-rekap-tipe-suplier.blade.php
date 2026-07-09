<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi Per Tipe Suplier</title>
    <style>
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            margin-bottom: 20px;
        }
        .company-name {
            font-weight: bold;
            font-size: 14px;
            font-style: italic;
        }
        .company-address {
            font-size: 11px;
        }
        .report-title {
            font-weight: bold;
            font-size: 14px;
            font-style: italic;
            margin-top: 10px;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .summary-table-container {
            margin-bottom: 30px;
        }
        .summary-title {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
        }
        th {
            background-color: inherit;
            font-weight: bold;
            text-align: center;
        }
        .bg-green-header {
            background-color: #6bb055 !important;
            color: white;
            font-weight: bold;
        }
        .bg-green-light {
            background-color: #90ee90;
        }
        .bg-gray-row {
            background-color: #d9d9d9;
        }
        .bg-blue-light {
            background-color: #cce5ff;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-bold {
            font-weight: bold;
        }
        .hak-ppn-table {
            width: auto;
            float: right;
            margin-bottom: 10px;
            min-width: 200px;
        }
        .hak-ppn-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        .hak-ppn-label {
            background-color: #90ee90;
            text-align: right;
        }
        .hak-ppn-value {
            background-color: #90ee90;
            font-weight: bold;
            text-align: right;
            font-size: 14px;
        }
        .clear {
            clear: both;
        }
        .legend-table {
            width: auto;
            border: none;
        }
        .legend-table td {
            border: none;
            padding: 2px 10px 2px 0;
            vertical-align: top;
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Top Summary Section -->
    <div class="header">
        <div class="company-name">{{ $organization->name ?? 'SELAMAT "PASIRHAYAM"' }}</div>
        @if($branch)
            <div class="company-name" style="font-weight: normal; font-style: normal;">{{ $branch->name }}</div>
            <div class="company-address">{{ $branch->address }}</div>
        @else
            <div class="company-address">{{ $organization->address ?? 'JL. PASIR HAYAM NO 39 (Jebrod) - CIANJUR' }}</div>
        @endif
    </div>

    <table class="hak-ppn-table">
        <tr>
            <td class="hak-ppn-label">Hak PPN</td>
        </tr>
        <tr>
            <td class="hak-ppn-value">{{ number_format($fpHakPpn, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="summary-title">Rekapitulasi Transaksi barang</div>
    <div class="info-row">Periode Laporan : {{ $periodString }}</div>
    <div class="info-row">Dicetak oleh {{ auth()->user()->name ?? 'Admin' }} {{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</div>

    <div class="summary-table-container">
        <table>
            <thead>
                <tr>
                    <th class="bg-green-header">TOTAL HARGA JUAL</th>
                    <th class="bg-green-header">TOTAL HPP</th>
                    <th class="bg-green-header">RETUR JUAL</th>
                    <th class="bg-green-header">HPP RETUR</th>
                    <th class="bg-green-header">SELISIH</th>
                    <th class="bg-green-header">RATA-2 MARGIN</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-green-light">
                    <td class="text-right">{{ $fpRow ? number_format($fpRow->jual, 0, ',', '.') : '0' }}</td>
                    <td class="text-right">{{ $fpRow ? number_format($fpRow->hpp, 0, ',', '.') : '0' }}</td>
                    <td class="text-right">{{ $fpRow ? number_format($fpRow->retur, 0, ',', '.') : '0' }}</td>
                    <td class="text-right">{{ $fpRow ? number_format($fpRow->hpp_retur, 0, ',', '.') : '0' }}</td>
                    <td class="text-right">{{ $fpRow ? number_format($fpRow->selisih, 0, ',', '.') : '0' }}</td>
                    <td class="text-center">{{ number_format($fpMargin, 2, ',', '.') }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="clear"></div>
    <br><br>

    <!-- Main Report Section -->
    <div class="header">
        <div class="report-title">REKAP TRANSAKSI BARANG PER TIPE SUPLIER</div>
    </div>

    <div class="info-row">Periode Laporan : {{ $periodString }}</div>

    <table>
        <thead>
            <tr class="bg-blue-light">
                <th>Tipe</th>
                <th>Jual</th>
                <th>Hpp</th>
                <th>Retur</th>
                <th>Hpp Retur</th>
                <th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
                @php
                    $rowClass = '';
                    if ($row->tipe_suplier === 'FP') {
                        $rowClass = 'bg-green-light';
                    } elseif ($row->tipe_suplier === 'KP') {
                        $rowClass = 'bg-gray-row';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $row->tipe_suplier }}</td>
                    <td class="text-right">{{ number_format($row->jual, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->hpp, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->retur, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->hpp_retur, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->selisih, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-blue-light text-bold">
                <td>Grand Total</td>
                <td class="text-right">{{ number_format($totalJual, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalHpp, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalRetur, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalHppRetur, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalSelisih, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <br>
    <div>
        <u>Keterangan :</u>
        <table class="legend-table">
            <tr>
                <td>CS</td>
                <td>= CASH</td>
            </tr>
            <tr class="bg-green-light">
                <td>FP</td>
                <td>= FAKTUR PAJAK</td>
            </tr>
            <tr>
                <td>KH</td>
                <td>= KONSINYASI HARIAN</td>
            </tr>
            <tr>
                <td>KM</td>
                <td>= KONSINYASI MINGGUAN</td>
            </tr>
            <tr>
                <td>KB</td>
                <td>= KONSINYASI BULANAN</td>
            </tr>
            <tr class="bg-gray-row">
                <td>KP</td>
                <td>= KONSINYASI FAKTUR PAJAK</td>
            </tr>
            <tr>
                <td>KT</td>
                <td>= KONSINYASI TAHUNAN</td>
            </tr>
            <tr>
                <td>NF</td>
                <td>= NON FAKTUR PAJAK</td>
            </tr>
            <tr>
                <td>SM</td>
                <td>= SELAMAT GROUP</td>
            </tr>
        </table>
    </div>

</body>
</html>
