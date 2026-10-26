<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0 0; font-size: 16px; }
        .header h3 { margin: 3px 0; font-size: 13px; }
        .header p { margin: 3px 0 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; }
        th { background-color: #f2f4f8; font-weight: bold; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
        .bg-dark-gray { background-color: #e9ecef; }
        .badge-bs { background-color: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
        .summary-box { display: flex; justify-content: flex-end; margin-top: 15px; }
        .summary-table { width: 45%; border-collapse: collapse; }
        .summary-table td { border: 1px solid #ddd; padding: 4px 8px; }
        @media print {
            body { font-size: 10px; }
            th, td { padding: 4px 5px; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $org = \App\Models\Organization::first();
        $org_name = $org ? strtoupper($org->name) : 'SM INVENTORY';
        $org_address = $org ? $org->address : '';
        
        $filters = request()->input('tableFilters', []);
        $branch_id = data_get($filters, 'branch_id.value', data_get($filters, 'branch_id', request('branch_id')));
        if (!$branch_id && auth()->check() && auth()->user()->branch_id) {
            $branch_id = auth()->user()->branch_id;
        }

        $branch = $branch_id ? \App\Models\Branch::find($branch_id) : null;
        $branch_name = $branch ? strtoupper($branch->name) : '';
        $header_address = $branch ? $branch->address : $org_address;
        $isBsOnly = ($stockStatus === 'has_bs');
    @endphp

    <div class="header">
        <h1>{{ $org_name }}</h1>
        @if($branch_name)
            <h3>{{ $branch_name }}</h3>
        @endif
        @if($header_address)
            <p style="font-size: 11px;">{{ $header_address }}</p>
        @endif
        <hr style="border: 1px solid #000; margin-bottom: 12px;">
        
        <h2>{{ $title }}</h2>
        <p>Dicetak pada: {{ $period }}</p>
    </div>

    @if($isBsOnly)
        {{-- TABEL KHUSUS FILTER BARANG BS / KARANTINA --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 45%;">Kategori / Sub Kategori</th>
                    <th class="text-right" style="width: 15%;">Stok BS (Siap Retur)</th>
                    <th class="text-right" style="width: 20%;">Harga Beli Rata-Rata</th>
                    <th class="text-right" style="width: 20%;">Valuasi BS</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grandTotalBsQty = 0; 
                    $grandTotalBsValuation = 0;
                @endphp
                @forelse($groupedData as $kategori => $items)
                    @php 
                        $subTotalBsQty = 0; 
                        $subTotalBsValuation = 0;
                    @endphp
                    <tr>
                        <td colspan="4" class="font-bold bg-dark-gray">{{ $kategori }}</td>
                    </tr>
                    @foreach($items as $row)
                        @php 
                            $subTotalBsQty += $row['damaged_qty']; 
                            $subTotalBsValuation += $row['damaged_valuation']; 
                        @endphp
                        <tr>
                            <td style="padding-left: 20px;">{{ $row['sub_kategori'] }}</td>
                            <td class="text-right">{{ number_format($row['damaged_qty'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['avg_price'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['damaged_valuation'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold bg-gray">
                        <td class="text-right">Subtotal {{ $kategori }}</td>
                        <td class="text-right">{{ number_format($subTotalBsQty, 2, ',', '.') }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($subTotalBsValuation, 2, ',', '.') }}</td>
                    </tr>
                    @php 
                        $grandTotalBsQty += $subTotalBsQty; 
                        $grandTotalBsValuation += $subTotalBsValuation;
                    @endphp
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 15px;">Tidak ada barang yang memiliki stok BS / Karantina.</td>
                    </tr>
                @endforelse
                <tr class="font-bold bg-dark-gray">
                    <td class="text-right">GRAND TOTAL STOK BS</td>
                    <td class="text-right">{{ number_format($grandTotalBsQty, 2, ',', '.') }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ number_format($grandTotalBsValuation, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 10px; font-size: 11px; color: #555;">
            <p><strong>Catatan:</strong> Laporan ini hanya mencetak dan merekapitulasi barang yang berstatus BS (Karantina) yang siap diretur ke supplier atau dimusnahkan.</p>
        </div>
    @else
        {{-- TABEL REKAP TOTAL (LAYAK JUAL + BS) --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Kategori / Sub Kategori</th>
                    <th class="text-right" style="width: 10%;">Layak Jual</th>
                    <th class="text-right" style="width: 10%;">Stok BS</th>
                    <th class="text-right" style="width: 10%;">Total Fisik</th>
                    <th class="text-right" style="width: 13%;">Harga Pokok Rata-Rata</th>
                    <th class="text-right" style="width: 11%;">Valuasi Layak Jual</th>
                    <th class="text-right" style="width: 10%;">Valuasi BS</th>
                    <th class="text-right" style="width: 11%;">Total Valuasi</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grandSalableQty = 0; 
                    $grandBsQty = 0; 
                    $grandTotalQty = 0; 
                    $grandSalableValuation = 0;
                    $grandBsValuation = 0;
                    $grandTotalValuation = 0;
                @endphp
                @forelse($groupedData as $kategori => $items)
                    @php 
                        $subSalableQty = 0; 
                        $subBsQty = 0; 
                        $subTotalQty = 0; 
                        $subSalableValuation = 0;
                        $subBsValuation = 0;
                        $subTotalValuation = 0;
                    @endphp
                    <tr>
                        <td colspan="8" class="font-bold bg-dark-gray">{{ $kategori }}</td>
                    </tr>
                    @foreach($items as $row)
                        @php 
                            $subSalableQty += $row['salable_qty']; 
                            $subBsQty += $row['damaged_qty']; 
                            $subTotalQty += $row['total_qty']; 
                            $subSalableValuation += $row['salable_valuation']; 
                            $subBsValuation += $row['damaged_valuation']; 
                            $subTotalValuation += $row['total_valuation']; 
                        @endphp
                        <tr>
                            <td style="padding-left: 20px;">{{ $row['sub_kategori'] }}</td>
                            <td class="text-right">{{ number_format($row['salable_qty'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['damaged_qty'], 2, ',', '.') }}</td>
                            <td class="text-right font-bold">{{ number_format($row['total_qty'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['avg_price'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['salable_valuation'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['damaged_valuation'], 2, ',', '.') }}</td>
                            <td class="text-right font-bold">{{ number_format($row['total_valuation'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold bg-gray">
                        <td class="text-right">Subtotal {{ $kategori }}</td>
                        <td class="text-right">{{ number_format($subSalableQty, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($subBsQty, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($subTotalQty, 2, ',', '.') }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($subSalableValuation, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($subBsValuation, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($subTotalValuation, 2, ',', '.') }}</td>
                    </tr>
                    @php 
                        $grandSalableQty += $subSalableQty; 
                        $grandBsQty += $subBsQty; 
                        $grandTotalQty += $subTotalQty; 
                        $grandSalableValuation += $subSalableValuation;
                        $grandBsValuation += $subBsValuation;
                        $grandTotalValuation += $subTotalValuation;
                    @endphp
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada data stok tersedia.</td>
                    </tr>
                @endforelse
                <tr class="font-bold bg-dark-gray">
                    <td class="text-right">GRAND TOTAL</td>
                    <td class="text-right">{{ number_format($grandSalableQty, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($grandBsQty, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($grandTotalQty, 2, ',', '.') }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ number_format($grandSalableValuation, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($grandBsValuation, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($grandTotalValuation, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table style="width: 50%; margin-left: auto; margin-top: 15px; border: 1px solid #bbb;">
            <tr class="bg-gray">
                <td style="padding: 6px 10px;"><strong>Total Valuasi Layak Jual</strong></td>
                <td class="text-right" style="padding: 6px 10px;">Rp {{ number_format($grandSalableValuation, 2, ',', '.') }}</td>
            </tr>
            <tr class="bg-gray">
                <td style="padding: 6px 10px;"><strong>Total Valuasi BS / Karantina</strong></td>
                <td class="text-right" style="padding: 6px 10px;">Rp {{ number_format($grandBsValuation, 2, ',', '.') }}</td>
            </tr>
            <tr class="font-bold bg-dark-gray" style="font-size: 12px;">
                <td style="padding: 7px 10px;">Grand Total Valuasi Fisik</td>
                <td class="text-right" style="padding: 7px 10px;">Rp {{ number_format($grandTotalValuation, 2, ',', '.') }}</td>
            </tr>
        </table>

        <div style="margin-top: 10px; font-size: 11px; color: #555;">
            <p><strong>Catatan:</strong> Nilai persediaan dihitung menggunakan metode FIFO (berdasarkan batch stok aktif). Stok BS (Karantina) diisolasi terpisah dari stok yang layak dijual.</p>
        </div>
    @endif
</body>
</html>
