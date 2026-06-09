@extends('print.layout')

@section('title', 'Laporan Penjualan Per Kassa Periode Laporan : ' . $period)

@section('content')
<table class="report-table">
    <thead>
        <tr>
            <th class="center" rowspan="2">Tanggal</th>
            <th class="center" rowspan="2">Shift</th>
            <th class="center" rowspan="2">Kasir</th>
            <th class="right" rowspan="2">Jml<br>Nota</th>
            <th class="right" rowspan="2">Penjualan</th>
            <th class="right" rowspan="2">Tunai</th>
            @if(count($banks) > 0)
                <th class="center" colspan="{{ count($banks) }}">Card Bank</th>
            @endif
            <th class="right" rowspan="2">Voucher</th>
            <th class="right" rowspan="2">Diskon</th>
            <th class="right" rowspan="2">Retur</th>
            <th class="right" rowspan="2">Jual Netto</th>
        </tr>
        <tr>
            @foreach($banks as $bank)
                <th class="right">{{ $bank->name }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $t_jml = 0;
            $t_penjualan = 0;
            $t_tunai = 0;
            $t_voucher = 0;
            $t_diskon = 0;
            $t_retur = 0;
            $t_netto = 0;
            $t_banks = [];
            foreach($banks as $bank) {
                $t_banks['bank_'.$bank->id] = 0;
            }
        @endphp

        @foreach($data as $row)
            @php
                $t_jml += $row['jml_nota'];
                $t_penjualan += $row['penjualan'];
                $t_tunai += $row['tunai'];
                $t_voucher += $row['voucher'];
                $t_diskon += $row['diskon'];
                $t_retur += $row['retur'];
                $t_netto += $row['jual_netto'];
                foreach($banks as $bank) {
                    $t_banks['bank_'.$bank->id] += $row['bank_'.$bank->id] ?? 0;
                }
            @endphp
            <tr>
                <td class="center">{{ $row['tanggal'] }}</td>
                <td class="center">{{ $row['shift'] }}</td>
                <td class="center">{{ $row['kasir'] }}</td>
                <td class="right">{{ number_format($row['jml_nota'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['penjualan'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['tunai'], 0, ',', '.') }}</td>
                @foreach($banks as $bank)
                    <td class="right">{{ number_format($row['bank_'.$bank->id] ?? 0, 0, ',', '.') }}</td>
                @endforeach
                <td class="right">{{ number_format($row['voucher'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['diskon'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['retur'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['jual_netto'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="3">Total</td>
            <td class="right">{{ number_format($t_jml, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_penjualan, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_tunai, 0, ',', '.') }}</td>
            @foreach($banks as $bank)
                <td class="right">{{ number_format($t_banks['bank_'.$bank->id], 0, ',', '.') }}</td>
            @endforeach
            <td class="right">{{ number_format($t_voucher, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_diskon, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_retur, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_netto, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
@endsection
