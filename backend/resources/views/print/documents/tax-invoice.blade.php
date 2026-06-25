<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; margin: 0; padding: 20px; }
        .document-container { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; page-break-after: always; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .label { width: 150px; font-weight: bold; }
        .amount-box { border: 2px solid #000; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; margin: 20px 0; background: #f9f9f9; }
        .footer { display: table; width: 100%; margin-top: 40px; text-align: center; }
        .signature-box { display: table-cell; width: 50%; }
        .signature-line { margin-top: 60px; border-top: 1px solid #000; width: 60%; display: inline-block; }
        @media print {
            body { padding: 0; }
            .document-container { border: none; padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

@foreach($documents as $doc)
<div class="document-container">
    <div class="header">
        <h1>{{ $title }}</h1>
        <p style="margin: 5px 0 0;">{{ $doc->organization?->name ?? 'Pusat' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nomor Seri Faktur</td>
            <td>: {{ $doc->nomor_faktur }}</td>
            <td class="label">Tanggal Faktur</td>
            <td>: {{ \Carbon\Carbon::parse($doc->tanggal_faktur)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Pajak</td>
            <td>: {{ $doc->type == 'masukan' ? 'Pajak Masukan (Pembelian)' : 'Pajak Keluaran (Penjualan)' }}</td>
            <td class="label">Masa Pajak</td>
            <td>: {{ $doc->masa_pajak }}</td>
        </tr>
        <tr>
            <td class="label">Lawan Transaksi</td>
            <td colspan="3">: {{ $doc->nama_lawan ?: '-' }} (NPWP: {{ $doc->npwp_lawan ?: '-' }})</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td colspan="3">: {{ $doc->status == 'reported' ? 'Sudah Dilaporkan' : 'Belum Dilaporkan (Draft)' }}</td>
        </tr>
    </table>

    <div class="amount-box">
        DPP: Rp {{ number_format($doc->dpp, 0, ',', '.') }}<br>
        PPN: Rp {{ number_format($doc->ppn, 0, ',', '.') }}
    </div>

    <div class="footer">
        <div class="signature-box">
            <p>Dibuat Oleh,</p>
            <div class="signature-line"></div>
            <p>Admin</p>
        </div>
        <div class="signature-box">
            <p>Disetujui Oleh,</p>
            <div class="signature-line"></div>
            <p>Manajer Keuangan</p>
        </div>
    </div>
</div>
@endforeach

</body>
</html>
