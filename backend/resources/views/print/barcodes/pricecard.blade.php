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
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 5px;
            padding: 5px;
        }
        .pricecard {
            width: 60mm;
            height: 30mm;
            box-sizing: border-box;
            background: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            page-break-inside: avoid;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            height: 8mm;
        }
        .logo-container {
            padding: 1mm 2mm;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .logo-container img {
            max-height: 5.5mm;
            max-width: 100%;
            object-fit: contain;
        }
        .store-info {
            background: #cc0000;
            color: #ffffff;
            border-bottom-left-radius: 8px;
            padding: 1mm 2mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            max-width: 55%;
        }
        .store-name {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 1px;
            white-space: nowrap;
            text-transform: uppercase;
        }
        .store-motto {
            font-size: 4.5px;
            line-height: 1.1;
            white-space: nowrap;
        }
        
        .separator {
            height: 1px;
            background: #cc0000;
            margin: 0 2mm;
        }
        
        .body-content {
            display: flex;
            flex: 1;
            padding: 1mm 2mm 0 2mm;
        }
        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-right: 1mm;
        }
        .product-name {
            font-weight: bold;
            font-size: 8px;
            line-height: 1.2;
            text-transform: uppercase;
            color: #111;
            height: 2.4em; /* Jatah pas 2 baris */
            display: flex;
            flex-direction: column;
            justify-content: flex-end; /* Supaya teks menempel ke bawah */
        }
        .product-name span {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .grey-line {
            height: 1px;
            background: #ccc;
            width: 100%;
            flex-shrink: 0;
        }
        
        .product-price {
            display: flex;
            align-items: flex-start; /* Supaya Rp. sejajar atas dengan angka */
            flex: 1;
            padding-top: 0.5mm;
            padding-bottom: 0.5mm;
        }
        .price-currency {
            font-weight: 900;
            font-size: 8px;
            color: #cc0000;
            margin-right: 2px;
            margin-top: 1.5mm; /* Penyesuaian visual agar rata atas dengan angka besar */
        }
        .price-amount {
            font-weight: 900;
            font-size: 24px;
            color: #cc0000;
            letter-spacing: -1px;
            line-height: 0.8;
        }
        
        .barcode-wrapper {
            width: 24mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 0.5mm;
        }
        .barcode-wrapper svg {
            max-width: 100%;
            height: 7.5mm;
        }
        .barcode-text {
            font-size: 6px;
            letter-spacing: 1px;
            margin-top: 1mm;
            font-family: monospace;
            font-weight: bold;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            height: 4mm;
            padding-right: 2mm;
        }
        .print-date {
            background: #222;
            color: #fff;
            padding: 1mm 2mm;
            border-top-right-radius: 6px;
            display: flex;
            align-items: center;
            font-size: 4px;
            font-weight: bold;
            height: 2mm;
        }
        .print-date svg {
            width: 2mm;
            height: 2mm;
            margin-right: 1mm;
            fill: #fff;
        }
        .stripes {
            display: flex;
            gap: 1mm;
            height: 2mm;
            margin-bottom: 1mm;
        }
        .stripe {
            width: 2mm;
            transform: skewX(-30deg);
        }
        .stripe-red { background: #cc0000; width: 2.5mm; }
        .stripe-grey { background: #b0b0b0; }
    </style>
</head>
<body onload="window.print()">
    @php
        $generator = new Picqer\Barcode\BarcodeGeneratorSVG();
        $org = \App\Models\Organization::first();
        $orgName = $org ? $org->name : 'Toko Kita';
        $branch = \App\Models\Branch::find($branch_id ?? null);
        $branchName = $branch ? $branch->name : 'Pasar UmMat';
        
        if (($date_type ?? 'cetak') === 'expired' && !empty($custom_date)) {
            $dateStr = \Carbon\Carbon::parse($custom_date)->format('d/m/Y');
        } else {
            $dateStr = \Carbon\Carbon::now()->format('d/m/Y');
        }
    @endphp

    <div class="container">
        @foreach($products as $product)
            @php $itemCopies = $product->copies ?? 1; @endphp
            @for($i = 0; $i < $itemCopies; $i++)
                <div class="pricecard">
                    <!-- Header -->
                    <div class="header">
                        <div class="logo-container">
                            @if($org && $org->logo_path)
                                <img src="{{ asset('storage/' . $org->logo_path) }}" alt="Logo">
                            @else
                                <h1 style="font-size: 10px; margin: 0; color: #cc0000;">{{ $orgName }}</h1>
                            @endif
                        </div>
                        <div class="store-info">
                            <div class="store-name">{{ Str::limit($branchName, 20) }}</div>
                            <div class="store-motto">Untung Murah Manfaat<br>dan InsyaAllah Berkah</div>
                        </div>
                    </div>
                    
                    <div class="separator"></div>
                    
                    <!-- Body -->
                    <div class="body-content">
                        <div class="product-info">
                            <div class="product-name">
                                <span>{{ $product->name }}</span>
                            </div>
                            
                            <div class="grey-line" style="margin-top: 1mm; margin-bottom: 0.5mm;"></div>
                            
                            <div class="product-price">
                                <span class="price-currency">Rp.</span>
                                <span class="price-amount">{{ number_format($product->selling_price, 0, ',', '.') }}</span>
                            </div>
                            
                            <div class="grey-line" style="margin-bottom: 0.5mm;"></div>
                        </div>
                        
                        <div class="barcode-wrapper">
                            @if($product->barcode)
                                {!! $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128, 1, 30) !!}
                                <div class="barcode-text">{{ $product->barcode }}</div>
                            @else
                                <div style="font-size: 6px; margin-top: 3mm;">No Barcode</div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="footer">
                        <div class="print-date">
                            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                            TGL CETAK <span style="margin: 0 1mm; font-weight: normal;">|</span> {{ $dateStr }}
                        </div>
                        <div class="stripes">
                            <div class="stripe stripe-red"></div>
                            <div class="stripe stripe-grey"></div>
                            <div class="stripe stripe-grey"></div>
                            <div class="stripe stripe-grey"></div>
                            <div class="stripe stripe-grey"></div>
                        </div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>
</body>
</html>
