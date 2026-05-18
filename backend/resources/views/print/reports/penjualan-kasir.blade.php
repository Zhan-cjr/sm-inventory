@extends('print.layout')

@section('title', 'Laporan Penjualan Per Kassa Periode Laporan : ' . $period)

@section('content')
<table class="report-table">
    <thead>
        <tr>
            <th class="center">Tanggal</th>
            <th class="center">KS</th>
            <th class="right">Jml<br>Nota</th>
            <th class="right">Penjualan</th>
            <th class="right">Tunai</th>
            <th class="right">Kredit</th>
            <th class="right">Card</th>
            <th class="right">Charge</th>
            <th class="right">Voucher</th>
            <th class="right">Gift</th>
            <th class="right">Diskon</th>
            <th class="right">Retur</th>
            <th class="right">Jual Netto</th>
        </tr>
    </thead>
    <tbody>
        @php
            $t_jml = 0;
            $t_penjualan = 0;
            $t_tunai = 0;
            $t_kredit = 0;
            $t_card = 0;
            $t_charge = 0;
            $t_voucher = 0;
            $t_gift = 0;
            $t_diskon = 0;
            $t_retur = 0;
            $t_netto = 0;
        @endphp

        @foreach($data as $row)
            @php
                $t_jml += $row['jml_nota'];
                $t_penjualan += $row['penjualan'];
                $t_tunai += $row['tunai'];
                $t_kredit += $row['kredit'];
                $t_card += $row['card'];
                $t_charge += $row['charge'];
                $t_voucher += $row['voucher'];
                $t_gift += $row['gift'];
                $t_diskon += $row['diskon'];
                $t_retur += $row['retur'];
                $t_netto += $row['jual_netto'];
            @endphp
            <tr>
                <td class="center">{{ $row['tanggal'] }}</td>
                <td class="center">{{ $row['ks'] }}</td>
                <td class="right">{{ number_format($row['jml_nota'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['penjualan'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['tunai'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['kredit'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['card'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['charge'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['voucher'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['gift'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['diskon'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['retur'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['jual_netto'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="2">Total</td>
            <td class="right">{{ number_format($t_jml, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_penjualan, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_tunai, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_kredit, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_card, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_charge, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_voucher, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_gift, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_diskon, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_retur, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($t_netto, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endsection
