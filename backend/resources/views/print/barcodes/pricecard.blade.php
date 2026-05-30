<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Pricecard Rak</title>
    <style>
        @page {
            margin: 5mm;
            size: A4;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', 'Segoe UI', sans-serif;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
            justify-content: flex-start;
        }
        .pricecard {
            width: 70mm;
            height: 35mm;
            box-sizing: border-box;
            border: 1px solid #d32f2f; /* Vibrant Red Border */
            border-radius: 2mm;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Header: Org & Date with Yellow Banner */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffcc00; /* Eye-catching Yellow */
            padding: 1.5mm 2mm;
            border-bottom: 1.5px solid #d32f2f;
        }
        .org-name {
            font-size: 5px;
            font-weight: 900;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 70%;
            text-transform: uppercase;
        }
        .date {
            font-size: 5px;
            font-weight: 900;
            color: #000;
            width: 30%;
            text-align: right;
            background: #fff;
            padding: 0.2mm 1mm;
            border-radius: 1mm;
        }

        /* Body Wrapper */
        .body-content {
            padding: 1mm 2mm 1.5mm 2mm;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        /* Product Name */
        .product-name {
            font-size: 9px;
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
            text-align: center;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0b2239; /* Dark Navy for contrast */
        }

        /* Price */
        .price-area {
            text-align: center;
            margin-top: -1mm;
            margin-bottom: 0.5mm;
            display: flex;
            justify-content: center;
            align-items: baseline;
            color: #d32f2f; /* Bold Red Price */
        }
        .rp {
            font-size: 10px;
            font-weight: 900;
            margin-right: 1mm;
        }
        .price-val {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -1px;
            line-height: 0.8;
            text-shadow: 1px 1px 0px rgba(0,0,0,0.1);
        }

        /* Footer: Barcode & SKU */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #ccc;
            padding-top: 1mm;
        }
        .barcode {
            width: 42mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .barcode svg {
            width: 100%;
            height: 5.5mm;
        }
        .barcode-text {
            font-size: 5px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 0.5mm;
            color: #333;
        }
        
        .sku-info {
            font-size: 7.5px; /* Enlarge SKU */
            font-weight: 900;
            color: #000;
            text-align: right;
            padding-bottom: 0.5mm;
            display: flex;
            flex-direction: column;
            background: #f0f0f0; /* Slight highlight for SKU */
            padding: 0.5mm 1mm;
            border-radius: 1mm;
        }
        .sku-lbl {
            font-size: 4px;
            color: #d32f2f;
            text-transform: uppercase;
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
            $dateStr = \Carbon\Carbon::parse($custom_date)->format('d/m/Y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/Y');
        }
    @endphp

    <div class="container">
        @foreach($products as $product)
            @php 
                $itemCopies = isset($from_session) && $from_session ? $product->copies : $copies; 
                $formattedPrice = number_format($product->selling_price, 0, ',', '.');
            @endphp
            @for($i = 0; $i < $itemCopies; $i++)
                <div class="pricecard">
                    
                    <!-- Header -->
                    <div class="header">
                        <div class="org-name">{{ $orgName }} - {{ $branchName }}</div>
                        <div class="date">{{ $dateStr }}</div>
                    </div>
                    
                    <div class="body-content">
                        <!-- Product Name -->
                        <div class="product-name">
                            {{ $product->name }}
                        </div>
                        
                        <!-- Price -->
                        <div class="price-area">
                            <span class="rp">Rp</span>
                            <span class="price-val">{{ $formattedPrice }}</span>
                        </div>

                        <!-- Footer -->
                        <div class="footer">
                            <div class="barcode">
                                @if($product->barcode)
                                    {!! $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128, 1.5, 35) !!}
                                    <div class="barcode-text">{{ $product->barcode }}</div>
                                @else
                                    <div style="font-size: 8px; border: 1px solid #ccc; width: 100%; text-align: center; padding: 1mm 0;">No Barcode</div>
                                @endif
                            </div>
                            <div class="sku-info">
                                <span class="sku-lbl">SKU / KODE</span>
                                <span>{{ $product->sku }}</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
            @endfor
        @endforeach
    </div>
</body>
</html>
