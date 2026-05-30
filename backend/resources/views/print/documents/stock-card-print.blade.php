<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Stok - {{ $product->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; }
        .header p { margin: 0; font-size: 12px; }
        
        .info-table { width: 100%; margin-bottom: 15px; font-size: 12px; }
        .info-table td { padding: 3px 0; }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        .items-table th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        @media print {
            body { padding: 0; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>KARTU STOK PRODUK</h2>
        <p>{{ \App\Models\Organization::first()->name ?? 'SM INVENTORY' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="120"><strong>Nama Produk</strong></td>
            <td>: {{ $product->name }}</td>
            <td width="120"><strong>Tanggal Cetak</strong></td>
            <td>: {{ date('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>SKU / Barcode</strong></td>
            <td>: {{ $product->sku }} / {{ $product->barcode }}</td>
            <td><strong>Cabang</strong></td>
            <td>: {{ $branch->name }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 100px;">Tanggal</th>
                <th style="width: 130px;">No Transaksi</th>
                <th>Keterangan</th>
                <th class="text-right" style="width: 60px;">Awal</th>
                <th class="text-right" style="width: 60px;">Masuk</th>
                <th class="text-right" style="width: 60px;">Keluar</th>
                <th class="text-right" style="width: 60px;">Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td style="font-family: monospace; font-size: 10px;">{{ $log->reference_doc_id ?? '-' }}</td>
                    <td>
                        <strong>{{ $log->log_type }}</strong><br>
                        <span style="font-size: 10px;">{{ $log->reason_code }} - {{ $log->notes }}</span>
                    </td>
                    <td class="text-right">{{ (float) $log->quantity_before }}</td>
                    <td class="text-right">
                        {{ $log->quantity_change > 0 ? (float) $log->quantity_change : '' }}
                    </td>
                    <td class="text-right">
                        {{ $log->quantity_change < 0 ? abs((float) $log->quantity_change) : '' }}
                    </td>
                    <td class="text-right">
                        <strong>{{ (float) $log->quantity_after }}</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Belum ada riwayat stok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
