<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode</title>
    <style>
        @page {
            /* 32mm * 3 = 96mm. Using 100mm approx width */
            size: 100mm 18mm;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #fff;
            color: #000;
        }
        .container {
            width: 100mm;
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            justify-content: flex-start;
        }
        .label {
            width: 32mm;
            height: 18mm;
            box-sizing: border-box;
            padding: 1mm;
            text-align: center;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            page-break-inside: avoid;
        }
        .product-name {
            font-size: 7px;
            font-weight: bold;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            margin-bottom: 1px;
        }
        .barcode-container {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .barcode-container svg {
            max-width: 95%;
            max-height: 8mm;
        }
        .sku {
            font-size: 6px;
            line-height: 1;
            margin-top: 1px;
        }
        .price {
            font-size: 8px;
            font-weight: bold;
            line-height: 1;
        }
        
        @media print {
            body {
                width: 100%;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $org = \App\Models\Organization::first();
        $orgName = $org ? $org->name : 'Toko Kita';
        $branch = \App\Models\Branch::find($branch_id ?? null);
        $branchName = $branch ? $branch->name : 'Pusat';
        
        if (($date_type ?? 'cetak') === 'expired' && !empty($custom_date)) {
            $dateStr = 'Exp: ' . \Carbon\Carbon::parse($custom_date)->format('d/m/y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/y');
        }
    @endphp

    <div class="container">
        @foreach($products as $product)
            @php $itemCopies = isset($from_session) && $from_session ? $product->copies : $copies; @endphp
            @for($i = 0; $i < $itemCopies; $i++)
                <div class="label">
                    <div style="font-size: 5px; font-weight: bold; text-align: center; margin-bottom:1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">
                        {{ Str::limit($orgName . ' - ' . $branchName, 35) }}
                    </div>
                    <div class="product-name">{{ Str::limit($product->name, 22) }}</div>
                    
                    <div class="barcode-container">
                        @if($product->barcode)
                            {!! $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128, 1, 30) !!}
                        @else
                            <div style="font-size: 6px;">No Barcode</div>
                        @endif
                    </div>
                    
                    <div style="display:flex; justify-content: space-between; width:100%; align-items:center;">
                        <div class="sku">{{ $product->barcode ?? $product->sku }}</div>
                        <div style="font-size: 5px;">{{ $dateStr }}</div>
                    </div>
                    <div class="price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
                </div>
            @endfor
        @endforeach
    </div>
</body>
</html>
