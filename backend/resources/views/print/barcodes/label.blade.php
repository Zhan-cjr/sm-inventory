<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode</title>
    <style>
        @page {
            /* Kertas label 32x18mm 3 line rapat (tanpa jarak) = 96mm */
            size: 96mm 18mm;
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
            display: grid;
            grid-template-columns: 32mm 32mm 32mm;
            column-gap: 0; /* Tanpa jarak antar kolom */
            row-gap: 0; /* Tanpa jarak vertikal */
            grid-auto-rows: 18mm;
            width: 96mm;
            padding: 0;
        }
        .label {
            width: 32mm;
            height: 18mm;
            box-sizing: border-box;
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            page-break-inside: avoid;
            background-color: #fff;
        }
        
        .left-section {
            width: 5.5mm;
            background: #fff;
            color: #000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 1.5mm 0;
            box-sizing: border-box;
            border-right: 1px solid #000;
        }
        .branch-text-container {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 0.5mm;
            flex-grow: 1;
        }
        .branch-line {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 6px;
            font-weight: bold;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .cart-icon {
            width: 3.5mm;
            height: 3.5mm;
            fill: #000;
            margin-top: 1mm;
            transform: rotate(-90deg);
        }

        .middle-section {
            width: 22.5mm;
            padding: 1mm 1mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            background: #fff;
        }
        .product-name {
            font-size: 5.5px;
            font-weight: bold;
            line-height: 1.1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
            text-transform: uppercase;
            text-align: center;
        }
        
        .barcode-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }
        .barcode-wrapper {
            width: 100%;
            height: 5mm;
            display: flex;
            justify-content: center;
        }
        .barcode-wrapper svg {
            width: 100%;
            max-width: 21mm;
            height: 100%;
        }
        .barcode-number {
            font-size: 5px;
            letter-spacing: 1.5px;
            margin-top: 0.5mm;
            font-weight: bold;
        }

        .price-container {
            display: flex;
            justify-content: center;
            align-items: baseline;
            margin-top: 0.5mm;
        }
        .price-currency {
            font-size: 6.5px;
            font-weight: bold;
            margin-right: 1.5px;
        }
        .price-amount {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }

        .right-section {
            width: 4mm;
            border-left: 1px solid #000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1mm 0;
            box-sizing: border-box;
        }
        .date-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 6px;
            font-weight: bold;
            white-space: nowrap;
        }

        @media print {
            body {
                width: 100%;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $branch = \App\Models\Branch::find($branch_id ?? null);
        $branchName = $branch ? $branch->name : 'Pusat';
        
        $branchWords = explode(' ', $branchName);
        $branchLine1 = '';
        $branchLine2 = '';
        if (count($branchWords) > 2) {
             $branchLine1 = $branchWords[0];
             $branchLine2 = implode(' ', array_slice($branchWords, 1));
        } elseif (count($branchWords) == 2) {
             $branchLine1 = $branchWords[0];
             $branchLine2 = $branchWords[1];
        } else {
             $branchLine1 = $branchName;
        }

        if (($date_type ?? 'cetak') === 'expired' && !empty($custom_date)) {
            $dateStr = 'EXP: ' . \Carbon\Carbon::parse($custom_date)->format('d/m/Y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/Y');
        }
    @endphp

    <div class="container">
        @foreach($products as $product)
            @php 
                $itemCopies = $product->copies ?? 1; 
                $barcode = $product->barcode ?? $product->sku;
            @endphp
            @for($i = 0; $i < $itemCopies; $i++)
                <div class="label">
                    <div class="left-section">
                        <div class="branch-text-container">
                            <div class="branch-line">{{ strtoupper($branchLine1) }}</div>
                            @if($branchLine2)
                                <div class="branch-line">{{ strtoupper($branchLine2) }}</div>
                            @endif
                        </div>
                        <svg class="cart-icon" viewBox="0 0 24 24">
                            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </div>

                    <div class="middle-section">
                        <div class="product-name">{{ $product->name }}</div>
                        
                        <div class="barcode-section">
                            <div class="barcode-wrapper">
                                @if($barcode)
                                    {!! $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 2, 40) !!}
                                @else
                                    <div style="font-size: 6px;">No Barcode</div>
                                @endif
                            </div>
                            <div class="barcode-number">{{ $barcode }}</div>
                        </div>

                        <div class="price-container">
                            <span class="price-currency">Rp.</span>
                            <span class="price-amount">{{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="right-section">
                        <div class="date-text">{{ $dateStr }}</div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>
</body>
</html>
