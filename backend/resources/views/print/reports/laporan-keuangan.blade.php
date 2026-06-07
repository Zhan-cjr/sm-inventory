<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; color: #333; }
        .document-container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .header h1 { margin: 0 0 10px 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 14px; }
        .section-title { font-size: 18px; font-weight: bold; margin: 30px 0 15px; padding-bottom: 5px; border-bottom: 1px solid #ddd; }
        
        .account-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .account-table th, .account-table td { padding: 8px 12px; text-align: left; }
        .account-table th { border-bottom: 1px solid #000; }
        .account-table .amount { text-align: right; }
        .account-table .total-row td { font-weight: bold; border-top: 1px solid #000; border-bottom: 2px solid #000; }
        
        .summary-box { margin-top: 40px; border: 2px solid #000; padding: 15px; text-align: right; font-size: 16px; font-weight: bold; background-color: #f9f9f9; }
        
        .footer { display: table; width: 100%; margin-top: 50px; text-align: center; }
        .signature-box { display: table-cell; width: 50%; }
        .signature-line { margin-top: 80px; border-top: 1px solid #000; width: 60%; display: inline-block; }
        
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="document-container">
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">{{ strtoupper($organization->name ?? 'SM INVENTORY') }}</h1>
        @if($branchName !== 'Semua Cabang (Global)')
            <h3 style="margin: 3px 0; font-size: 16px;">{{ strtoupper($branchName) }}</h3>
            @php $headerAddress = \App\Models\Branch::where('name', $branchName)->first()?->address ?? ($organization->address ?? ''); @endphp
        @else
            @php $headerAddress = $organization->address ?? ''; @endphp
        @endif
        @if($headerAddress)
            <p style="margin: 0 0 10px 0; font-size: 13px;">{{ $headerAddress }}</p>
        @endif
        <hr style="border: 1px solid #000; margin-bottom: 15px;">
        
        <h2>{{ $title }}</h2>
        <p><strong>Periode:</strong> {{ $period }}</p>
    </div>

    <div class="section-title">Laba Rugi (Profit & Loss)</div>
    <table class="account-table">
        <thead>
            <tr>
                <th>Kode Akun</th>
                <th>Nama Akun (Pendapatan & Beban)</th>
                <th class="amount">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="3"><strong>Pendapatan (Revenue)</strong></td></tr>
            @php $totalRevenue = 0; @endphp
            @foreach($revenues as $item)
            @php $totalRevenue += $item['balance']; @endphp
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->name }}</td>
                <td class="amount">{{ number_format($item['balance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Pendapatan</td>
                <td class="amount">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="3" style="padding-top:15px"><strong>Beban (Expense)</strong></td></tr>
            @php $totalExpense = 0; @endphp
            @foreach($expenses as $item)
            @php $totalExpense += $item['balance']; @endphp
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->name }}</td>
                <td class="amount">{{ number_format($item['balance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Beban</td>
                <td class="amount">{{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-box">
        Laba Bersih (Net Profit): Rp {{ number_format($netProfit, 0, ',', '.') }}
    </div>

    <div style="page-break-before: always;"></div>

    <div class="header" style="margin-top: 30px;">
        <h1>Neraca Saldo (Balance Sheet)</h1>
        <p><strong>Cabang:</strong> {{ $branchName }}</p>
        <p><strong>Periode:</strong> {{ $period }}</p>
    </div>

    <table class="account-table">
        <thead>
            <tr>
                <th>Kode Akun</th>
                <th>Nama Akun (Aset, Kewajiban, Ekuitas)</th>
                <th class="amount">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="3"><strong>Aset (Asset)</strong></td></tr>
            @php $totalAsset = 0; @endphp
            @foreach($assets as $item)
            @php $totalAsset += $item['balance']; @endphp
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->name }}</td>
                <td class="amount">{{ number_format($item['balance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Aset</td>
                <td class="amount">{{ number_format($totalAsset, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="3" style="padding-top:15px"><strong>Kewajiban (Liability)</strong></td></tr>
            @php $totalLiability = 0; @endphp
            @foreach($liabilities as $item)
            @php $totalLiability += $item['balance']; @endphp
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->name }}</td>
                <td class="amount">{{ number_format($item['balance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Kewajiban</td>
                <td class="amount">{{ number_format($totalLiability, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="3" style="padding-top:15px"><strong>Ekuitas (Equity)</strong></td></tr>
            @php $totalEquity = 0; @endphp
            @foreach($equities as $item)
            @php $totalEquity += $item['balance']; @endphp
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->name }}</td>
                <td class="amount">{{ number_format($item['balance'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(isset($retainedEarnings) && $retainedEarnings != 0)
            <tr>
                <td>-</td>
                <td>Laba Ditahan (Periode Sebelumnya)</td>
                <td class="amount">{{ number_format($retainedEarnings, 0, ',', '.') }}</td>
            </tr>
            @php $totalEquity += $retainedEarnings; @endphp
            @endif
            <tr>
                <td>-</td>
                <td>Laba Berjalan (Net Profit)</td>
                <td class="amount">{{ number_format($netProfit, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Ekuitas + Laba Berjalan</td>
                <td class="amount">{{ number_format($totalEquity + $netProfit, 0, ',', '.') }}</td>
            </tr>
            
            <tr><td colspan="3" style="padding:20px 0;"></td></tr>
            
            <tr class="total-row" style="font-size: 16px;">
                <td colspan="2" style="text-align:right"><strong>Total Aset</strong></td>
                <td class="amount"><strong>{{ number_format($totalAsset, 0, ',', '.') }}</strong></td>
            </tr>
            <tr class="total-row" style="font-size: 16px;">
                <td colspan="2" style="text-align:right"><strong>Total Kewajiban & Ekuitas</strong></td>
                <td class="amount"><strong>{{ number_format($totalLiability + $totalEquity + $netProfit, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Dibuat Oleh,</p>
            <div class="signature-line"></div>
            <p>{{ auth()->user()->name ?? 'Admin' }}</p>
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
