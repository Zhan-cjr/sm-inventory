<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
        .bg-dark-gray { background-color: #eee; }
    </style>
</head>
<body onload="window.print()">
    @php
        $org = \App\Models\Organization::first();
        $org_name = $org ? strtoupper($org->name) : 'SM INVENTORY';
        $org_address = $org ? $org->address : '';
        
        $filters = request()->input('tableFilters', []);
        $branch_id = $filters['branch_id']['value'] ?? null;
        if (!$branch_id && auth()->check() && auth()->user()->branch_id) {
            $branch_id = auth()->user()->branch_id;
        }

        $branch = $branch_id ? \App\Models\Branch::find($branch_id) : null;
        $branch_name = $branch ? strtoupper($branch->name) : '';
        $header_address = $branch ? $branch->address : $org_address;
    @endphp

    <div class="header">
        <h1 style="margin: 0; font-size: 18px;">{{ $org_name }}</h1>
        @if($branch_name)
            <h3 style="margin: 3px 0; font-size: 14px;">{{ $branch_name }}</h3>
        @endif
        @if($header_address)
            <p style="margin: 0 0 10px 0; font-size: 11px;">{{ $header_address }}</p>
        @endif
        <hr style="border: 1px solid #000; margin-bottom: 15px;">
        
        <h2>{{ $title }}</h2>
        <p style="margin-top: 5px;">Dicetak pada: {{ $period }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kategori / Sub Kategori</th>
                <th class="text-right">Total Qty</th>
                <th class="text-right">Harga Beli Rata-Rata</th>
                <th class="text-right">Total Valuasi</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalQty = 0; 
                $grandTotalValuation = 0;
            @endphp
            @foreach($groupedData as $kategori => $items)
                @php 
                    $subTotalQty = 0; 
                    $subTotalValuation = 0;
                @endphp
                <tr>
                    <td colspan="4" class="font-bold bg-dark-gray">{{ $kategori }}</td>
                </tr>
                @foreach($items as $row)
                    @php 
                        $subTotalQty += $row['total_qty']; 
                        $subTotalValuation += $row['total_valuation']; 
                    @endphp
                    <tr>
                        <td style="padding-left: 20px;">{{ $row['sub_kategori'] }}</td>
                        <td class="text-right">{{ number_format($row['total_qty'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['avg_price'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['total_valuation'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="font-bold bg-gray">
                    <td class="text-right">Subtotal {{ $kategori }}</td>
                    <td class="text-right">{{ number_format($subTotalQty, 2, ',', '.') }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ number_format($subTotalValuation, 2, ',', '.') }}</td>
                </tr>
                @php 
                    $grandTotalQty += $subTotalQty; 
                    $grandTotalValuation += $subTotalValuation;
                @endphp
            @endforeach
            <tr class="font-bold bg-dark-gray">
                <td class="text-right">GRAND TOTAL</td>
                <td class="text-right">{{ number_format($grandTotalQty, 2, ',', '.') }}</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($grandTotalValuation, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
