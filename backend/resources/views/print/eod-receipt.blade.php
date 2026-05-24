<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan EOD - {{ $shift->shift_name }}</title>
    <style>
        @page { margin: 0; size: auto; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            border: 1px dashed #666;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h2 { margin: 0 0 5px 0; font-size: 15px; font-weight: bold; line-height: 1.2; }
        .header h3 { margin: 0 0 5px 0; font-size: 12px; font-weight: normal; line-height: 1.2; }
        .header p { margin: 0; font-size: 10px; color: #444; line-height: 1.2; }
        .divider { border-bottom: 1px dashed #000; margin: 8px 0; }
        
        .info-table { width: 100%; font-size: 11px; margin-bottom: 10px; border-collapse: collapse; }
        .info-table td { padding: 2px 0; }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 2px 0;
        }
        .summary-row.bold {
            font-weight: bold;
        }
        .sub-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 1px 0 1px 15px;
            color: #333;
        }
        
        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 11px;
        }
        
        @media print {
            body { padding: 0; margin: 0; font-size: 11px; }
            .receipt-container { 
                border: none; 
                padding: 0 0 60px 0 !important; 
                max-width: 100% !important; 
                width: 68mm !important; 
            }
            .header h2 { font-size: 13px !important; }
            .header h3 { font-size: 11px !important; }
            .header p { font-size: 9px !important; }
            .summary-row { font-size: 11px !important; }
            .sub-row { font-size: 10px !important; }
            .info-table { font-size: 10px !important; }
            .footer { font-size: 10px !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <div class="header">
            <h2>{{ $shift->branch && $shift->branch->organization ? $shift->branch->organization->name : 'SMI POS' }}</h2>
            <h3>{{ $shift->branch ? $shift->branch->name : 'Cabang Utama' }}</h3>
            @if($shift->branch && $shift->branch->address)
                <p>{{ $shift->branch->address }}</p>
            @endif
            <div class="divider"></div>
            <h3 style="text-align: center; font-weight: bold; margin: 5px 0;">*** REPRINT ***</h3>
            <h2>LAPORAN END OF DAY</h2>
            <div class="divider"></div>
        </div>

        <table class="info-table">
            <tr><td>Kasir</td><td>: {{ $shift->user ? $shift->user->name : 'Unknown' }}</td></tr>
            <tr><td>Kassa</td><td>: {{ $shift->terminal ? $shift->terminal->name : 'Unknown' }}</td></tr>
            <tr><td>Shift</td><td>: {{ $shift->shift_name }}</td></tr>
            <tr><td>Mulai</td><td>: {{ \Carbon\Carbon::parse($shift->start_time)->format('d/m/Y, H.i.s') }}</td></tr>
            <tr><td>Selesai</td><td>: {{ \Carbon\Carbon::parse($shift->end_time)->format('d/m/Y, H.i.s') }}</td></tr>
        </table>
        
        <div class="divider"></div>

        <div class="summary-row">
            <span>Modal Awal</span>
            <span>{{ number_format($shift->starting_cash, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span>Penjualan Tunai</span>
            <span>{{ number_format($shift->total_cash_sales, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span>Penjualan Non-Tunai</span>
            <span>{{ number_format(($shift->total_card_sales ?? 0) + ($shift->total_voucher_sales ?? 0), 0, ',', '.') }}</span>
        </div>

        @if($shift->card_sales_by_bank && count($shift->card_sales_by_bank) > 0)
            @foreach($shift->card_sales_by_bank as $bank)
            <div class="sub-row">
                <span>- {{ $bank->name }}</span>
                <span>{{ number_format($bank->total_amount, 0, ',', '.') }}</span>
            </div>
            @endforeach
        @else
            <div class="sub-row">
                <span>- Belum ada rincian bank</span>
                <span>0</span>
            </div>
        @endif

        @if(($shift->total_voucher_sales ?? 0) > 0)
        <div class="sub-row">
            <span>- Voucher</span>
            <span>{{ number_format($shift->total_voucher_sales, 0, ',', '.') }}</span>
        </div>
        @endif

        @if($shift->total_cash_returns > 0)
        <div class="summary-row" style="color: #ef4444;">
            <span>Retur Tunai</span>
            <span>-{{ number_format($shift->total_cash_returns, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($shift->total_card_returns > 0)
        <div class="summary-row" style="color: #ef4444;">
            <span>Retur Non-Tunai</span>
            <span>-{{ number_format($shift->total_card_returns, 0, ',', '.') }}</span>
        </div>
        @endif

        <div class="summary-row">
            <span>Kas Masuk</span>
            <span>{{ number_format($shift->total_cash_in ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span>Kas Keluar</span>
            <span>{{ number_format($shift->total_cash_out ?? 0, 0, ',', '.') }}</span>
        </div>

        <div class="divider"></div>
        <div style="text-align: center; font-weight: bold; font-size: 12px;">POTONGAN & DISKON</div>
        
        <div class="summary-row">
            <span>Diskon Manual</span>
            <span>{{ number_format($shift->discount_details['manual_discount'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span>Diskon Promo</span>
            <span>{{ number_format($shift->discount_details['promo_discount'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span>Poin Member</span>
            <span>{{ number_format($shift->discount_details['point_deduction'] ?? 0, 0, ',', '.') }}</span>
        </div>

        <div class="divider"></div>

        <div class="summary-row bold">
            <span>EXPECTED CASH</span>
            <span>{{ number_format($shift->expected_cash, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row bold">
            <span>ACTUAL CASH</span>
            <span>{{ number_format($shift->actual_cash, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row bold" style="color: {{ $shift->difference < 0 ? '#ef4444' : 'black' }};">
            <span>SELISIH</span>
            <span>{{ number_format($shift->difference, 0, ',', '.') }}</span>
        </div>

        <div class="divider"></div>
        
        @if($shift->cashMovements && count($shift->cashMovements) > 0)
        <div style="text-align: center; font-weight: bold; font-size: 12px;">DETAIL KAS</div>
        @foreach($shift->cashMovements as $m)
        <div style="font-size: 12px; margin-top: 5px;">{{ $m->type === 'CASH_IN' ? '[IN]' : '[OUT]' }} {{ $m->description }}</div>
        <div style="text-align: right; font-size: 12px;">{{ number_format($m->amount, 0, ',', '.') }}</div>
        @endforeach
        <div class="divider"></div>
        @endif

        <div style="text-align: center; font-weight: bold; font-size: 12px;">DETAIL RETUR</div>
        @if($shift->returns_detail && count($shift->returns_detail) > 0)
            @foreach($shift->returns_detail as $r)
            <div style="font-size: 12px; margin-top: 5px;">{{ $r['quantity'] }}x {{ $r['product_name'] }}</div>
            <div style="text-align: right; font-size: 12px;">{{ number_format($r['total'], 0, ',', '.') }}</div>
            @endforeach
        @else
            <div style="text-align: center; font-size: 12px; margin-top: 5px;">Tidak ada retur</div>
        @endif
        <div class="divider"></div>

        <div class="footer">
            <p>Tanda Tangan</p>
            <br><br><br>
            <p>(.......................)</p>
            <p>Kasir / SPV</p>
        </div>
    </div>
</body>
</html>
