<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode - Toshiba TEC & Thermal Printer</title>
    <style>
        @page {
            /* Kertas label thermal 3 kolom: 96mm width x 18mm height per baris/black mark */
            size: 96mm 18mm;
            margin: 0mm;
        }
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 96mm;
            background: #ffffff;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        /* 17.5mm row height to prevent Chrome subpixel rounding blank page feed at 100% scale */
        .label-row {
            width: 96mm;
            height: 17.5mm;
            max-height: 17.5mm;
            display: flex;
            flex-direction: row;
            page-break-after: always;
            page-break-inside: avoid;
            box-sizing: border-box;
            overflow: hidden;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .label {
            width: 32mm;
            height: 17.5mm;
            box-sizing: border-box;
            padding: 0.5mm 1mm 0.4mm 1mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            background-color: #ffffff;
        }
        
        /* Line 1: Full Branch Name (No Truncation) */
        .branch-header {
            width: 100%;
            font-size: 5.6px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            color: #000000;
            border-bottom: 0.5px solid #000000;
            padding-bottom: 0.3mm;
            height: 2.3mm;
            line-height: 1.1;
        }

        /* Line 2: Product Name (Full Width, Max 2 Lines) */
        .product-name {
            width: 100%;
            font-size: 6.3px;
            font-weight: 800;
            line-height: 1.15;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
            text-transform: uppercase;
            text-align: center;
            color: #000000;
            height: 3.4mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Line 3: SVG Barcode & Code Number */
        .barcode-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 0.2mm;
            margin-bottom: 0.2mm;
        }
        .barcode-wrapper {
            width: 100%;
            height: 4.5mm;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .barcode-wrapper svg {
            width: 100%;
            max-width: 28mm;
            height: 100%;
        }
        .barcode-number {
            font-size: 5.2px;
            letter-spacing: 1px;
            margin-top: 0.2mm;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #000000;
            line-height: 1;
        }

        /* Line 4: Footer Bar (Jumbo Price on Left, Date on Right) */
        .footer-bar {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 0.5px solid #000000;
            padding-top: 0.3mm;
            height: 3.8mm;
        }
        .price-box {
            display: flex;
            align-items: baseline;
        }
        .price-currency {
            font-size: 5.5px;
            font-weight: bold;
            margin-right: 1px;
        }
        .price-amount {
            font-size: 11.5px;
            font-weight: 900;
            letter-spacing: -0.4px;
            color: #000000;
            line-height: 1;
        }
        .date-box {
            font-size: 5.2px;
            font-weight: 800;
            font-family: monospace;
            white-space: nowrap;
            color: #000000;
            line-height: 1;
            align-self: flex-end;
            margin-bottom: 0.2mm;
        }

        @media print {
            html, body {
                width: 96mm;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $branch = \App\Models\Branch::find($branch_id ?? null);
        $branchName = $branch ? $branch->name : 'Pusat';

        if (($date_type ?? 'cetak') === 'expired' && !empty($custom_date)) {
            $dateStr = 'EXP:' . \Carbon\Carbon::parse($custom_date)->format('d/m/y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/y');
        }

        // Build flat array of all label items based on copies requested
        $allLabels = [];
        foreach($products as $product) {
            $itemCopies = $product->copies ?? 1;
            $barcode = $product->barcode ?? $product->sku;
            for ($i = 0; $i < $itemCopies; $i++) {
                $allLabels[] = [
                    'product' => $product,
                    'barcode' => $barcode,
                ];
            }
        }
        
        // Chunk into 3-column rows matching 96mm x 18mm thermal label layout
        $rows = array_chunk($allLabels, 3);
    @endphp

    <div>
        @foreach($rows as $row)
            <div class="label-row">
                @foreach($row as $lbl)
                    @php 
                        $product = $lbl['product']; 
                        $barcode = $lbl['barcode']; 
                    @endphp
                    <div class="label">
                        <!-- Line 1: Full Branch Name -->
                        <div class="branch-header">
                            {{ $branchName }}
                        </div>

                        <!-- Line 2: Product Name -->
                        <div class="product-name">
                            <span>{{ $product->name }}</span>
                        </div>

                        <!-- Line 3: Barcode Graphic & Number -->
                        <div class="barcode-section">
                            <div class="barcode-wrapper">
                                @if($barcode)
                                    {!! $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 1.5, 32) !!}
                                @else
                                    <div style="font-size: 5px;">NO BARCODE</div>
                                @endif
                            </div>
                            <div class="barcode-number">{{ $barcode }}</div>
                        </div>

                        <!-- Line 4: Price Callout (Left) + Date (Right) -->
                        <div class="footer-bar">
                            <div class="price-box">
                                <span class="price-currency">Rp</span>
                                <span class="price-amount">{{ number_format($product->display_price ?? $product->selling_price, 0, ',', '.') }}</span>
                            </div>
                            <div class="date-box">
                                {{ $dateStr }}
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Fill empty columns for incomplete last row --}}
                @for($e = count($row); $e < 3; $e++)
                    <div class="label" style="visibility: hidden;"></div>
                @endfor
            </div>
        @endforeach
    </div>
</body>
</html>
