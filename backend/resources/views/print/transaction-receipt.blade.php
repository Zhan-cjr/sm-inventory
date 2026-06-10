<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi - {{ $transaction->local_transaction_id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 1px dashed #333;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .info-table td {
            padding: 2px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .items-table th, .items-table td {
            border-bottom: 1px dashed #eee;
            padding: 5px 0;
            text-align: left;
        }
        .items-table th.right, .items-table td.right {
            text-align: right;
        }
        .totals-table {
            width: 100%;
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .totals-table .bold {
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px dashed #333;
            padding-top: 10px;
            font-size: 12px;
        }
        @media print {
            body {
                padding: 0;
            }
            .receipt-container {
                border: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <div class="header">
            <h2>{{ $transaction->organization ? $transaction->organization->name : 'SM INVENTORY' }}</h2>
            <p>{{ $transaction->branch ? $transaction->branch->name : 'Cabang Utama' }}</p>
        </div>

        <table class="info-table">
            <tr>
                <td>No. Nota</td>
                <td>: {{ $transaction->local_transaction_id }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>: {{ $transaction->cashier ? $transaction->cashier->name : '-' }}</td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td>: {{ $transaction->customer ? $transaction->customer->name : 'Tunai' }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: {{ $transaction->is_voided ? 'VOID/BATAL' : 'BERHASIL' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th class="right">Qty</th>
                    <th class="right">Harga</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $item)
                <tr>
                    <td>{{ $item->product ? $item->product->name : 'Item' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format(($item->unit_price - $item->discount_per_item) * $item->quantity, 0, ',', '.') }}</td>
                </tr>
                @if($item->discount_per_item > 0)
                <tr>
                    <td colspan="4" style="font-size: 10px; color: #555; border: none; padding-top: 0;">
                        * Diskon/item: {{ number_format($item->discount_per_item, 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td>Total Penjualan</td>
                <td class="right">{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td class="right">- {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="bold">TOTAL TRANSAKSI</td>
                <td class="right bold">Rp {{ number_format($transaction->final_amount, 0, ',', '.') }}</td>
            </tr>
            @if(strtoupper($transaction->payment_method) === 'MULTI' && !empty($transaction->payment_details))
                @php
                    $details = $transaction->payment_details;
                    if (is_string($details)) {
                        $details = json_decode($details, true);
                    }
                @endphp
                @if(is_array($details))
                    @foreach($details as $payment)
                        @php
                            $label = $payment['label'] ?? (strtoupper($payment['method'] ?? ''));
                            if (strtoupper($payment['method'] ?? '') === 'CASH') {
                                $label = 'Tunai';
                            }
                        @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="right">{{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @else
                @php
                    $methodLabel = strtoupper($transaction->payment_method);
                    if ($methodLabel === 'CASH') $methodLabel = 'TUNAI';
                @endphp
                <tr>
                    <td>Pembayaran ({{ $methodLabel }})</td>
                    <td class="right">{{ number_format($transaction->received_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if(($transaction->change_amount ?? 0) > 0 || ($transaction->received_amount - $transaction->final_amount) > 0)
            <tr>
                <td>Kembalian</td>
                <td class="right">{{ number_format($transaction->change_amount ?? max(0, $transaction->received_amount - $transaction->final_amount), 0, ',', '.') }}</td>
            </tr>
            @endif
        </table>

        <div class="footer">
            <p>Terima kasih atas kunjungan Anda</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
        </div>
    </div>
</body>
</html>
