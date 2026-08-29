<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Pajak - {{ $documents->first()?->nomor_faktur }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
            line-height: 1.3;
        }
        .faktur-wrapper {
            max-width: 780px;
            margin: 0 auto 30px auto;
            border: 1.5px solid #000;
            padding: 0;
            page-break-after: always;
            box-sizing: border-box;
        }
        .title-header {
            text-align: center;
            padding: 8px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #000;
        }
        .section-row {
            border-bottom: 1.5px solid #000;
            padding: 6px 10px;
        }
        .section-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }
        .label-col {
            width: 90px;
        }
        .colon-col {
            width: 12px;
            text-align: center;
        }

        /* Items Table */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #000;
        }
        .table-items th {
            border: 1px solid #000;
            border-top: none;
            padding: 6px 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            background-color: #f9f9f9;
        }
        .table-items td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 10.5px;
            vertical-align: top;
        }
        .table-items tr:first-child td {
            border-top: 1px solid #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Calculation Summary */
        .calc-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #000;
        }
        .calc-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            font-size: 11px;
        }
        .calc-label {
            font-weight: normal;
        }
        .calc-val {
            text-align: right;
            width: 170px;
            font-weight: bold;
        }

        /* Footer & Signatures */
        .footer-section {
            padding: 10px 12px;
            font-size: 9.5px;
        }
        .disclaimer {
            width: 58%;
            float: left;
            font-size: 9px;
            color: #222;
            line-height: 1.3;
        }
        .signature-area {
            width: 38%;
            float: right;
            text-align: center;
        }
        .signature-date {
            margin-bottom: 50px;
            font-size: 11px;
        }
        .signer-name {
            font-weight: bold;
            font-size: 11px;
            text-decoration: underline;
        }
        .clearfix {
            clear: both;
        }
        @media print {
            body { padding: 0; }
            .faktur-wrapper { border: 1.5px solid #000; width: 100%; max-width: 100%; margin: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

@foreach($documents as $doc)
@php
    $isMasukan = ($doc->type === 'masukan');

    // Cari Pemasok dari Database Berdasarkan NPWP atau Nama
    $supplier = null;
    if ($isMasukan) {
        $cleanNpwp = preg_replace('/[^0-9]/', '', $doc->npwp_lawan ?? '');
        $supplier = \App\Models\Supplier::where(function($q) use ($cleanNpwp, $doc) {
            if (!empty($cleanNpwp)) {
                $q->where('npwp', 'like', "%{$cleanNpwp}%");
            }
            if (!empty($doc->nama_lawan)) {
                $q->orWhere('name', 'like', "%{$doc->nama_lawan}%");
            }
        })->first();
    }

    // Ambil Data Cabang / Alamat Perusahaan
    $orgBranch = \App\Models\Branch::where('organization_id', $doc->organization_id)->first();
    $alamatOrganisasi = $doc->organization?->address 
        ?: ($orgBranch?->address ?: 'Jl. Raya Utama No. 1, Pusat');

    // Penjual (Seller)
    $namaPenjual = $isMasukan 
        ? ($supplier?->name ?: ($doc->nama_lawan ?: 'PEMASOK')) 
        : ($doc->organization?->name ?? 'PERUSAHAAN');
    $npwpPenjual = $isMasukan 
        ? ($supplier?->npwp ?: ($doc->npwp_lawan ?: '-')) 
        : ($doc->organization?->code ?? '-');
    $alamatPenjual = $isMasukan 
        ? ($supplier?->address ?: 'Alamat belum diatur pada Master Pemasok') 
        : $alamatOrganisasi;

    // Pembeli (Buyer)
    $namaPembeli = $isMasukan 
        ? ($doc->organization?->name ?? 'PERUSAHAAN') 
        : ($doc->nama_lawan ?: 'PELANGGAN');
    $npwpPembeli = $isMasukan 
        ? ($doc->organization?->code ?? '-') 
        : ($doc->npwp_lawan ?: '-');
    $alamatPembeli = $isMasukan 
        ? $alamatOrganisasi 
        : ($doc->nama_lawan ? 'Alamat Pelanggan' : '-');

    $tanggalFormatted = $doc->tanggal_faktur 
        ? \Carbon\Carbon::parse($doc->tanggal_faktur)->translatedFormat('d F Y') 
        : now()->translatedFormat('d F Y');
@endphp

<div class="faktur-wrapper">
    <div class="title-header">
        Faktur Pajak
    </div>

    <div class="section-row" style="background-color: #fdfdfd;">
        <table class="info-grid">
            <tr>
                <td style="font-weight: bold; width: 220px;">Kode dan Nomor Seri Faktur Pajak</td>
                <td class="colon-col">:</td>
                <td style="font-weight: bold; font-size: 12px; letter-spacing: 0.5px;">{{ $doc->nomor_faktur }}</td>
            </tr>
        </table>
    </div>

    <!-- Pengusaha Kena Pajak (Penjual) -->
    <div class="section-row">
        <div class="section-title">Pengusaha Kena Pajak</div>
        <table class="info-grid">
            <tr>
                <td class="label-col">Nama</td>
                <td class="colon-col">:</td>
                <td><strong>{{ $namaPenjual }}</strong></td>
            </tr>
            <tr>
                <td class="label-col">Alamat</td>
                <td class="colon-col">:</td>
                <td>{{ $alamatPenjual }}</td>
            </tr>
            <tr>
                <td class="label-col">NPWP</td>
                <td class="colon-col">:</td>
                <td>{{ $npwpPenjual }}</td>
            </tr>
        </table>
    </div>

    <!-- Pembeli Barang Kena Pajak -->
    <div class="section-row">
        <div class="section-title">Pembeli Barang Kena Pajak / Penerima Jasa Kena Pajak</div>
        <table class="info-grid">
            <tr>
                <td class="label-col">Nama</td>
                <td class="colon-col">:</td>
                <td><strong>{{ $namaPembeli }}</strong></td>
            </tr>
            <tr>
                <td class="label-col">Alamat</td>
                <td class="colon-col">:</td>
                <td>{{ $alamatPembeli }}</td>
            </tr>
            <tr>
                <td class="label-col">NPWP</td>
                <td class="colon-col">:</td>
                <td>{{ $npwpPembeli }}</td>
            </tr>
        </table>
    </div>

    <!-- Daftar Barang -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 32px;">No.</th>
                <th>Nama Barang Kena Pajak / Jasa Kena Pajak</th>
                <th style="width: 170px;">Harga Jual / Penggantian / Uang Muka / Termin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($doc->items as $index => $item)
            @php
                $qtyStr = (floor($item->jumlah_barang) == $item->jumlah_barang) 
                    ? number_format($item->jumlah_barang, 0, ',', '.') 
                    : rtrim(rtrim(number_format($item->jumlah_barang, 3, ',', '.'), '0'), ',');
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->name }}</strong>
                    <div style="font-size: 9.5px; color: #444; margin-top: 2px;">
                        Rp {{ number_format($item->harga_satuan, 0, ',', '.') }} x {{ $qtyStr }}
                        @if($item->diskon > 0)
                            &nbsp;|&nbsp; <em>Potongan: Rp {{ number_format($item->diskon, 0, ',', '.') }}</em>
                        @endif
                    </div>
                </td>
                <td class="text-right" style="font-weight: bold;">
                    {{ number_format($item->harga_total, 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td class="text-center">1</td>
                <td>
                    <strong>Penyerahan Barang / Jasa (Rekap Sesuai Faktur)</strong>
                </td>
                <td class="text-right" style="font-weight: bold;">
                    {{ number_format($doc->dpp, 2, ',', '.') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Rincian Perhitungan DPP & PPN -->
    @php
        $totalHargaJual = $doc->items->isNotEmpty() ? $doc->items->sum('harga_total') : $doc->dpp;
        $totalDiskon = $doc->items->isNotEmpty() ? $doc->items->sum('diskon') : 0;
    @endphp
    <table class="calc-table">
        <tr>
            <td class="calc-label">Harga Jual / Penggantian</td>
            <td class="calc-val">{{ number_format($totalHargaJual, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="calc-label">Dikurangi Potongan Harga</td>
            <td class="calc-val">{{ number_format($totalDiskon, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="calc-label">Dikurangi Uang Muka</td>
            <td class="calc-val">0,00</td>
        </tr>
        <tr>
            <td class="calc-label" style="font-weight: bold; background-color: #fafafa;">Dasar Pengenaan Pajak (DPP)</td>
            <td class="calc-val" style="background-color: #fafafa;">{{ number_format($doc->dpp, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="calc-label" style="font-weight: bold;">Total PPN (Pajak Pertambahan Nilai)</td>
            <td class="calc-val">{{ number_format($doc->ppn, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="calc-label">Total PPnBM (Pajak Penjualan Barang Mewah)</td>
            <td class="calc-val">0,00</td>
        </tr>
    </table>

    <!-- Footer DJP Disclaimer & Tanda Tangan -->
    <div class="footer-section">
        <div class="disclaimer">
            <em>Pemberitahuan: Faktur Pajak ini telah dilaporkan ke Direktorat Jenderal Pajak dan telah memperoleh persetujuan sesuai dengan ketentuan peraturan perpajakan yang berlaku. Peringatan: PKP yang menerbitkan Faktur Pajak yang tidak sesuai dengan keadaan yang sebenarnya dikenai sanksi sesuai perundang-undangan perpajakan.</em>
        </div>
        <div class="signature-area">
            <div class="signature-date">
                {{ $tanggalFormatted }}
            </div>
            <div class="signer-name">
                {{ $namaPenjual }}
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
@endforeach

</body>
</html>
