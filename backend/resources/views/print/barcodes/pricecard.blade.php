<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Pricecard Rak - SM INVENTORY</title>
    <style>
        @page {
            margin: 5mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 4mm;
            padding: 2mm;
        }
        .pricecard {
            width: 62mm;
            height: 32mm;
            box-sizing: border-box;
            background: #ffffff;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            page-break-inside: avoid;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            height: 8.5mm;
            background: #ffffff;
        }
        .logo-container {
            padding: 1mm 2mm;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .logo-container img {
            max-height: 6mm;
            max-width: 100%;
            object-fit: contain;
        }
        .store-info {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            border-bottom-left-radius: 10px;
            padding: 1mm 2.5mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
            text-align: right;
            max-width: 58%;
        }
        .store-name {
            font-weight: 800;
            font-size: 8.5px;
            line-height: 1.1;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .store-motto {
            font-size: 4.5px;
            line-height: 1.1;
            white-space: nowrap;
            opacity: 0.9;
        }
        
        .separator {
            height: 1.5px;
            background: #dc2626;
            margin: 0 2mm;
        }
        
        .body-content {
            display: flex;
            flex: 1;
            padding: 1.5mm 2mm 0 2mm;
        }
        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-right: 1.5mm;
            justify-content: space-between;
        }
        .product-name {
            font-weight: 800;
            font-size: 8.5px;
            line-height: 1.25;
            text-transform: uppercase;
            color: #0f172a;
            height: 2.5em;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
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
            background: #e2e8f0;
            width: 100%;
            flex-shrink: 0;
        }
        
        .product-price {
            display: flex;
            align-items: flex-start;
            padding-top: 0.5mm;
            padding-bottom: 0.5mm;
        }
        .price-currency {
            font-weight: 900;
            font-size: 9px;
            color: #dc2626;
            margin-right: 2px;
            margin-top: 1mm;
        }
        .price-amount {
            font-weight: 900;
            font-size: 23px;
            color: #dc2626;
            letter-spacing: -0.8px;
            line-height: 0.85;
        }
        
        .barcode-wrapper {
            width: 25mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 0.5mm;
        }
        .barcode-wrapper svg {
            max-width: 100%;
            height: 8mm;
        }
        .barcode-text {
            font-size: 6.5px;
            letter-spacing: 0.8px;
            margin-top: 0.8mm;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 800;
            color: #1e293b;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            height: 4mm;
            padding-right: 2mm;
            background: #ffffff;
        }
        .print-date {
            background: #0f172a;
            color: #ffffff;
            padding: 1mm 2mm;
            border-top-right-radius: 6px;
            display: flex;
            align-items: center;
            font-size: 4.5px;
            font-weight: 800;
            height: 2.2mm;
            letter-spacing: 0.3px;
        }
        .print-date svg {
            width: 2mm;
            height: 2mm;
            margin-right: 1mm;
            fill: #ffffff;
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
        .stripe-red { background: #dc2626; width: 2.5mm; }
        .stripe-grey { background: #94a3b8; }
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
                                <h1 style="font-size: 11px; font-weight: 900; margin: 0; color: #dc2626;">{{ $orgName }}</h1>
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
                                <span class="price-amount">{{ number_format($product->display_price ?? $product->selling_price, 0, ',', '.') }}</span>
                            </div>
                            
                            <div class="grey-line" style="margin-bottom: 0.5mm;"></div>
                        </div>
                        
                        <div class="barcode-wrapper">
                            @if($product->barcode)
                                {!! $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128, 1, 32) !!}
                                <div class="barcode-text">{{ $product->barcode }}</div>
                            @else
                                <div style="font-size: 6px; margin-top: 3mm; color: #64748b;">No Barcode</div>
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
