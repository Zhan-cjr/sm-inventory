<div class="flex justify-center" style="background-color: #f3f4f6; padding: 1.5rem; border-radius: 12px; width: 100%;">
    <div style="background-color: #ffffff; color: #000000; padding: 1.5rem; font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.3; width: 100%; max-width: 320px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); border: 1px dashed #666666; box-sizing: border-box; text-align: left;">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 15px; font-weight: bold; margin: 0 0 3px 0; color: #000000; line-height: 1.2;">{{ $shift->branch && $shift->branch->organization ? $shift->branch->organization->name : 'SMI POS' }}</h2>
            <h3 style="font-size: 12px; margin: 0 0 3px 0; font-weight: normal; color: #000000;">{{ $shift->branch ? $shift->branch->name : 'Cabang Utama' }}</h3>
            @if($shift->branch && $shift->branch->address)
                <p style="margin: 0; font-size: 10px; color: #444444; line-height: 1.2;">{{ $shift->branch->address }}</p>
            @endif
            <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>
            <h3 style="font-size: 12px; font-weight: bold; margin: 3px 0; color: #000000;">*** REPRINT ***</h3>
            <h2 style="font-size: 14px; font-weight: bold; margin: 3px 0; color: #000000;">LAPORAN END OF DAY</h2>
            <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>
        </div>

        <!-- Info Table -->
        <table style="width: 100%; font-size: 11px; border-collapse: collapse; margin-bottom: 10px; color: #000000;">
            <tr><td style="padding: 2px 0; width: 60px;">Kasir</td><td style="padding: 2px 0;">: {{ $shift->user ? $shift->user->name : 'Unknown' }}</td></tr>
            <tr><td style="padding: 2px 0;">Kassa</td><td style="padding: 2px 0;">: {{ $shift->terminal ? $shift->terminal->name : 'Unknown' }}</td></tr>
            <tr><td style="padding: 2px 0;">Shift</td><td style="padding: 2px 0;">: {{ $shift->shift_name }}</td></tr>
            <tr><td style="padding: 2px 0;">Mulai</td><td style="padding: 2px 0;">: {{ \Carbon\Carbon::parse($shift->start_time)->format('d/m/Y, H.i.s') }}</td></tr>
            <tr><td style="padding: 2px 0;">Selesai</td><td style="padding: 2px 0;">: {{ \Carbon\Carbon::parse($shift->end_time)->format('d/m/Y, H.i.s') }}</td></tr>
        </table>
        
        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>

        <!-- Sales Summary -->
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Modal Awal</span>
            <span>{{ number_format($shift->starting_cash, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Penjualan Tunai</span>
            <span>{{ number_format($shift->total_cash_sales, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Penjualan Non-Tunai</span>
            <span>{{ number_format($shift->total_card_sales, 0, ',', '.') }}</span>
        </div>

        @if($shift->card_sales_by_bank && count($shift->card_sales_by_bank) > 0)
            @foreach($shift->card_sales_by_bank as $bank)
            <div style="display: flex; justify-content: space-between; padding: 1px 0 1px 15px; font-size: 11px; color: #333333;">
                <span>- {{ $bank->name }}</span>
                <span>{{ number_format($bank->total_amount, 0, ',', '.') }}</span>
            </div>
            @endforeach
        @else
            <div style="display: flex; justify-content: space-between; padding: 1px 0 1px 15px; font-size: 11px; color: #333333;">
                <span>- Belum ada rincian bank</span>
                <span>0</span>
            </div>
        @endif

        @if($shift->total_cash_returns > 0)
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #ef4444;">
            <span>Retur Tunai</span>
            <span>-{{ number_format($shift->total_cash_returns, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($shift->total_card_returns > 0)
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #ef4444;">
            <span>Retur Non-Tunai</span>
            <span>-{{ number_format($shift->total_card_returns, 0, ',', '.') }}</span>
        </div>
        @endif

        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Kas Masuk</span>
            <span>{{ number_format($shift->total_cash_in ?? 0, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Kas Keluar</span>
            <span>{{ number_format($shift->total_cash_out ?? 0, 0, ',', '.') }}</span>
        </div>

        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>
        <div style="text-align: center; font-weight: bold; margin-bottom: 5px; color: #000000;">POTONGAN & DISKON</div>
        
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Diskon Manual</span>
            <span>{{ number_format($shift->discount_details['manual_discount'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Diskon Promo</span>
            <span>{{ number_format($shift->discount_details['promo_discount'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #000000;">
            <span>Poin Member</span>
            <span>{{ number_format($shift->discount_details['point_deduction'] ?? 0, 0, ',', '.') }}</span>
        </div>

        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>

        <!-- Expected / Actual / Difference -->
        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-weight: bold; color: #000000;">
            <span>EXPECTED CASH</span>
            <span>{{ number_format($shift->expected_cash, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-weight: bold; color: #000000;">
            <span>ACTUAL CASH</span>
            <span>{{ number_format($shift->actual_cash, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; font-weight: bold; color: {{ $shift->difference < 0 ? '#ef4444' : '#000000' }};">
            <span>SELISIH</span>
            <span>{{ number_format($shift->difference, 0, ',', '.') }}</span>
        </div>

        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>
        
        <!-- Cash Movements Detail -->
        @if($shift->cashMovements && count($shift->cashMovements) > 0)
        <div style="text-align: center; font-weight: bold; margin-bottom: 5px; color: #000000;">DETAIL KAS</div>
        @foreach($shift->cashMovements as $m)
        <div style="font-size: 11px; margin-top: 3px; color: #000000;">{{ $m->type === 'CASH_IN' ? '[IN]' : '[OUT]' }} {{ $m->description }}</div>
        <div style="text-align: right; font-size: 11px; font-weight: bold; color: #000000;">{{ number_format($m->amount, 0, ',', '.') }}</div>
        @endforeach
        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>
        @endif

        <!-- Return Detail -->
        <div style="text-align: center; font-weight: bold; margin-bottom: 5px; color: #000000;">DETAIL RETUR</div>
        @if($shift->returns_detail && count($shift->returns_detail) > 0)
            @foreach($shift->returns_detail as $r)
            <div style="font-size: 11px; margin-top: 3px; color: #000000;">{{ $r['quantity'] }}x {{ $r['product_name'] }}</div>
            <div style="text-align: right; font-size: 11px; font-weight: bold; color: #000000;">{{ number_format($r['total'], 0, ',', '.') }}</div>
            @endforeach
        @else
            <div style="text-align: center; font-size: 11px; margin-top: 3px; color: #666666;">Tidak ada retur</div>
        @endif
        <div style="border-bottom: 1px dashed #000000; margin: 8px 0;"></div>

        <!-- Signatures -->
        <div style="text-align: center; margin-top: 25px; font-size: 11px; color: #000000;">
            <p style="margin: 0;">Tanda Tangan</p>
            <br><br><br>
            <p style="margin: 0; font-weight: bold;">(.......................)</p>
            <p style="margin: 5px 0 0 0;">Kasir / SPV</p>
        </div>
    </div>
</div>
