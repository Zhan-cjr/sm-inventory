<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        td:first-child {
            text-align: left;
        }
        .grand-total {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 11px;
            color: #666;
        }
        @media print {
            body { padding: 0; }
            @page { margin: 1cm; size: landscape; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $org = \App\Models\Organization::first();
        $companyName = $org ? $org->name : config('app.name');
    @endphp

    <div class="header">
        <div class="company-name">{{ $companyName }}</div>
        <p>{{ $title }}</p>
    </div>

    <div class="info">
        <p><strong>Periode:</strong> {{ $period }}</p>
        <p><strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama Cabang</th>
                <th>Penerimaan Barang</th>
                <th>Retur Pembelian</th>
                <th>Koreksi Stok Retur</th>
                <th>Penjualan Kasir</th>
                <th>Retur Penjualan</th>
                <th>Pengeluaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $b)
                <tr>
                    <td>{{ $b['branch_name'] }}</td>
                    <td>{{ number_format($b['penerimaan'], 0, ',', '.') }}</td>
                    <td>{{ number_format($b['retur_beli'], 0, ',', '.') }}</td>
                    <td>{{ number_format($b['koreksi_retur'], 0, ',', '.') }}</td>
                    <td>{{ number_format($b['penjualan'], 0, ',', '.') }}</td>
                    <td>{{ number_format($b['retur_jual'], 0, ',', '.') }}</td>
                    <td>{{ number_format($b['pengeluaran'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data cabang yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($branches) > 0)
            <tfoot>
                <tr class="grand-total">
                    <td>TOTAL KESELURUHAN</td>
                    <td>{{ number_format($totals['penerimaan'], 0, ',', '.') }}</td>
                    <td>{{ number_format($totals['retur_beli'], 0, ',', '.') }}</td>
                    <td>{{ number_format($totals['koreksi_retur'], 0, ',', '.') }}</td>
                    <td>{{ number_format($totals['penjualan'], 0, ',', '.') }}</td>
                    <td>{{ number_format($totals['retur_jual'], 0, ',', '.') }}</td>
                    <td>{{ number_format($totals['pengeluaran'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak oleh: {{ auth()->user() ? auth()->user()->name : 'System' }} pada {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
