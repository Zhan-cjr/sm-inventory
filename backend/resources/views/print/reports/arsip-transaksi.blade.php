@extends('print.layout')

@section('title', 'Arsip Transaksi Periode : ' . $period)

@section('content')
<table class="report-table">
    <thead>
        <tr>
            <th class="center">No Transaksi</th>
            <th class="center">Tanggal</th>
            <th>Cabang</th>
            <th>Kasir</th>
            <th>Customer</th>
            <th>Metode</th>
            <th>Status</th>
            <th class="right">Pendapatan Bersih</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total_bersih = 0;
        @endphp
        @foreach($transactions as $t)
            @php
                $pointPayment = 0.0;
                if (!empty($t->payment_details)) {
                    $details = $t->payment_details;
                    if (is_string($details)) $details = json_decode($details, true);
                    if (is_array($details)) {
                        $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                    }
                } elseif (strtoupper($t->payment_method) === 'POINT') {
                    $pointPayment = (float) $t->final_amount;
                }
                $netRevenue = $t->final_amount - $pointPayment;
                $total_bersih += $netRevenue;
            @endphp
            <tr>
                <td class="center">{{ $t->local_transaction_id }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i') }}</td>
                <td>{{ $t->branch ? $t->branch->name : '-' }}</td>
                <td>{{ $t->cashier ? $t->cashier->name : '-' }}</td>
                <td>{{ $t->customer ? $t->customer->name : 'Tunai' }}</td>
                <td>{{ strtoupper($t->payment_method) }}</td>
                <td>{{ $t->is_voided ? 'Batal' : 'Berhasil' }}</td>
                <td class="right">{{ number_format($netRevenue, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="7" class="right"><strong>Total Keseluruhan</strong></td>
            <td class="right"><strong>{{ number_format($total_bersih, 0, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>
@endsection
