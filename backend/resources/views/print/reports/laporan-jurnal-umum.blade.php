<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #333; }
        .document-container { max-width: 900px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .header h1 { margin: 0 0 10px 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 13px; }
        
        .journal-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .journal-table th, .journal-table td { padding: 6px 10px; border: 1px solid #ddd; }
        .journal-table th { background-color: #f5f5f5; text-align: center; font-weight: bold; }
        .journal-table .amount { text-align: right; }
        .journal-table .date-col { width: 10%; text-align: center; }
        .journal-table .ref-col { width: 15%; text-align: center; }
        .journal-table .desc-col { width: 35%; }
        
        .total-row { font-weight: bold; background-color: #f5f5f5; }
        
        .footer { display: table; width: 100%; margin-top: 50px; text-align: center; }
        .signature-box { display: table-cell; width: 33.33%; }
        .signature-line { margin-top: 60px; border-top: 1px solid #000; width: 60%; display: inline-block; }
        
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="document-container">
    <div class="header">
        <h1>{{ $title }}</h1>
        <p><strong>Cabang:</strong> {{ $branchName }}</p>
        <p><strong>Periode:</strong> {{ $period }}</p>
    </div>

    <table class="journal-table">
        <thead>
            <tr>
                <th class="date-col">Tanggal</th>
                <th class="ref-col">No. Referensi</th>
                <th class="desc-col">Akun & Keterangan</th>
                <th>Status</th>
                <th class="amount">Debit</th>
                <th class="amount">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalDebit = 0; 
                $grandTotalCredit = 0; 
            @endphp
            
            @forelse($journals as $journal)
                @php 
                    $lines = $journal->lines;
                    $rowspan = $lines->count();
                @endphp
                
                @foreach($lines as $index => $line)
                    @php
                        $grandTotalDebit += $line->debit;
                        $grandTotalCredit += $line->credit;
                    @endphp
                    <tr>
                        @if($index === 0)
                            <td rowspan="{{ $rowspan }}" class="date-col" style="vertical-align: top;">
                                {{ \Carbon\Carbon::parse($journal->entry_date)->format('d M Y') }}
                            </td>
                            <td rowspan="{{ $rowspan }}" class="ref-col" style="vertical-align: top;">
                                {{ $journal->reference_number }}
                                <div style="font-size: 10px; color: #666; margin-top: 5px;">{{ $journal->description }}</div>
                            </td>
                        @endif
                        
                        <td class="desc-col">
                            <strong>{{ $line->account->account_code ?? '' }} - {{ $line->account->name ?? '' }}</strong>
                            @if($line->description && $line->description !== $journal->description)
                                <div style="font-size: 10px; color: #555;">{{ $line->description }}</div>
                            @endif
                        </td>
                        
                        @if($index === 0)
                            <td rowspan="{{ $rowspan }}" style="text-align: center; vertical-align: top; text-transform: capitalize;">
                                {{ $journal->status }}
                            </td>
                        @endif
                        
                        <td class="amount">{{ $line->debit > 0 ? number_format($line->debit, 0, ',', '.') : '-' }}</td>
                        <td class="amount">{{ $line->credit > 0 ? number_format($line->credit, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada data jurnal untuk filter ini.</td>
                </tr>
            @endforelse
            
            @if($journals->count() > 0)
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>Total Keseluruhan</strong></td>
                <td class="amount"><strong>{{ number_format($grandTotalDebit, 0, ',', '.') }}</strong></td>
                <td class="amount"><strong>{{ number_format($grandTotalCredit, 0, ',', '.') }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Dibuat Oleh,</p>
            <div class="signature-line"></div>
            <p>{{ auth()->user()->name ?? 'Admin' }}</p>
        </div>
        <div class="signature-box">
            <p>Diperiksa Oleh,</p>
            <div class="signature-line"></div>
            <p>Spv. Akuntansi</p>
        </div>
        <div class="signature-box">
            <p>Disetujui Oleh,</p>
            <div class="signature-line"></div>
            <p>Manajer Keuangan</p>
        </div>
    </div>
</div>

</body>
</html>
