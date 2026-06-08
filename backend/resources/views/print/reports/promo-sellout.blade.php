@extends('print.layout')

@section('title', $title . ' - ' . $promo->name)

@section('content')
<div style="margin-bottom: 20px;">
    <strong>Promo:</strong> {{ $promo->name }}<br>
    <strong>Supplier:</strong> {{ $promo->supplier ? $promo->supplier->name : '-' }}<br>
    <strong>Tgl Validitas:</strong> {{ \Carbon\Carbon::parse($promo->valid_from)->format('d M Y H:i') }} s/d {{ \Carbon\Carbon::parse($promo->valid_until)->format('d M Y H:i') }}
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>No</th>
            <th>No Transaksi</th>
            <th>Tgl Transaksi</th>
            <th>Cabang</th>
            <th>Produk</th>
            <th class="right">Qty</th>
            <th class="right">Harga Jual</th>
            <th class="right">Diskon Promo</th>
            <th class="right">Diskon Ditanggung Supplier ({{ (float)$promo->supplier_sponsorship_percent }}%)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total_qty = 0;
            $total_diskon = 0;
            $total_ditanggung = 0;
        @endphp
        @foreach($items as $index => $item)
            @php
                $qty = $item->quantity;
                $discount_per_item = floatval($item->discount_per_item);
                if ($discount_per_item <= 0) {
                    if ($promo->promo_type === 'PERCENTAGE' || $promo->promo_type === 'FLASH_SALE') {
                        $discount_per_item = $item->unit_price * ($promo->discount_value / 100);
                    } elseif ($promo->promo_type === 'FIXED') {
                        $discount_per_item = $promo->discount_value;
                    }
                }
                $diskon_item = $discount_per_item * $qty;
                $ditanggung = $diskon_item * ($promo->supplier_sponsorship_percent / 100);
                
                $total_qty += $qty;
                $total_diskon += $diskon_item;
                $total_ditanggung += $ditanggung;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->transaction ? $item->transaction->receipt_number : '-' }}</td>
                <td>{{ $item->transaction ? \Carbon\Carbon::parse($item->transaction->transaction_date)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ ($item->transaction && $item->transaction->branch) ? $item->transaction->branch->name : 'Pusat' }}</td>
                <td>{{ $item->product ? $item->product->name : '-' }}</td>
                <td class="right">{{ $qty }}</td>
                <td class="right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($diskon_item, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($ditanggung, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr style="font-weight: bold;">
            <td colspan="5" style="text-align: right;">TOTAL</td>
            <td class="right">{{ $total_qty }}</td>
            <td></td>
            <td class="right">{{ number_format($total_diskon, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($total_ditanggung, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div style="margin-top: 60px; display: flex; justify-content: flex-end; text-align: center; gap: 80px; margin-right: 50px;">
    <div>
        <p style="margin-bottom: 60px;">Mengetahui,</p>
        <p style="border-bottom: 1px solid #000; width: 150px; margin: 0 auto;"></p>
        <p style="margin-top: 5px;">Supplier</p>
    </div>
    <div>
        <p style="margin-bottom: 60px;">Dibuat Oleh,</p>
        <p style="border-bottom: 1px solid #000; width: 150px; margin: 0 auto;"></p>
        <p style="margin-top: 5px;">{{ auth()->check() ? auth()->user()->name : 'Admin' }}</p>
    </div>
</div>
@endsection
