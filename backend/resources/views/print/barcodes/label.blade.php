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
            /* Safe printable inset area to avoid thermal reflective sensor clipping */
            padding: 0.8mm 1.5mm 0.6mm 1.5mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
        }
        
        /* Top Header: Cart Badge + Branch Name & Line + Product Name */
        .header-section {
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            height: 4.5mm;
            overflow: hidden;
        }

        .cart-badge {
            width: 3.8mm;
            height: 3.8mm;
            background-color: #000000;
            border-radius: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 0.8mm;
        }

        .cart-badge svg {
            width: 2.8mm;
            height: 2.8mm;
        }

        .header-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            width: calc(100% - 4.6mm);
        }

        .branch-title {
            width: 100%;
            font-size: 5.8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000000;
            border-bottom: 0.5px solid #000000;
            padding-bottom: 0.2mm;
            line-height: 1;
        }

        .product-title {
            width: 100%;
            font-size: 6px;
            font-weight: 800;
            line-height: 1.05;
            margin-top: 0.2mm;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
            text-transform: uppercase;
            text-align: center;
            color: #000000;
        }

        /* Middle Section: Price Badge + Price Amount */
        .price-section {
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            height: 3.8mm;
            margin-top: 0.1mm;
            margin-bottom: 0.1mm;
        }

        .rp-badge {
            background-color: #000000;
            color: #ffffff;
            font-size: 5.8px;
            font-weight: 900;
            padding: 0.5px 2px;
            border-radius: 1px;
            margin-right: 1.2mm;
            line-height: 1;
            display: inline-flex;
            align-items: center;
        }

        .price-amount {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: -0.4px;
            color: #000000;
            line-height: 1;
        }

        /* Barcode Graphic */
        .barcode-section {
            width: 100%;
            height: 4.2mm;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .barcode-section svg {
            width: 100%;
            max-width: 25mm; /* Safe printable width to prevent side edge cutting */
            height: 100%;
        }

        /* Footer Bar: Barcode Code (Left) & Calendar Icon + Date (Right) */
        .footer-section {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 3.2mm;
            padding-top: 0.1mm;
        }

        .barcode-number {
            font-size: 7.8px;
            font-weight: 900;
            font-family: Arial, Helvetica, sans-serif;
            letter-spacing: 0.2px;
            color: #000000;
            line-height: 1;
            white-space: nowrap;
        }

        .date-box {
            display: flex;
            align-items: center;
            font-size: 6.5px;
            font-weight: 800;
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            line-height: 1;
            white-space: nowrap;
        }

        .date-box svg {
            width: 2.3mm;
            height: 2.3mm;
            margin-right: 0.4mm;
            flex-shrink: 0;
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
            $dateStr = 'EXP:' . \Carbon\Carbon::parse($custom_date)->format('d/m/Y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/Y');
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
                        <!-- Top Header: Cart Badge + Branch Name & Line + Product Name -->
                        <div class="header-section">
                            <div class="cart-badge">
                                <svg viewBox="0 0 24 24" fill="#ffffff">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </div>
                            <div class="header-content">
                                <div class="branch-title">
                                    {{ $branchName }}
                                </div>
                                <div class="product-title">
                                    {{ $product->name }}
                                </div>
                            </div>
                        </div>

                        <!-- Middle Section: Price Badge (Rp) + Jumbo Price -->
                        <div class="price-section">
                            <div class="rp-badge">Rp</div>
                            <div class="price-amount">{{ number_format($product->display_price ?? $product->selling_price, 0, ',', '.') }}</div>
                        </div>

                        <!-- Barcode Graphic -->
                        <div class="barcode-section">
                            @if($barcode)
                                {!! $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 1.5, 32) !!}
                            @else
                                <div style="font-size: 5px;">NO BARCODE</div>
                            @endif
                        </div>

                        <!-- Footer: Barcode Code (Left) & Calendar Icon + Date (Right) -->
                        <div class="footer-section">
                            <div class="barcode-number">{{ $barcode }}</div>
                            <div class="date-box">
                                <svg viewBox="0 0 24 24" fill="#000000">
                                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zM7 12h5v5H7z"/>
                                </svg>
                                <span>{{ $dateStr }}</span>
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
