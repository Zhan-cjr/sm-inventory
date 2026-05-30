<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cetak Dokumen' }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .page-container {
            width: 100%;
            page-break-after: always;
            box-sizing: border-box;
        }
        .page-container:last-child {
            page-break-after: auto;
        }
        .org-info {
            text-align: left;
            margin-bottom: 15px;
        }
        .org-info h2 {
            margin: 0;
            font-size: 12pt;
            text-transform: uppercase;
        }
        .org-info p {
            margin: 2px 0 0;
            font-size: 10pt;
            color: #333;
        }
        .document-title {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .document-title h1 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .info-table td.label {
            width: 150px;
            font-weight: bold;
        }
        .info-table td.separator {
            width: 20px;
            text-align: center;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .items-table th, .items-table td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: left;
        }
        .items-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .summary-box {
            width: 300px;
            float: right;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 4px 8px;
        }
        .summary-box td.label {
            font-weight: bold;
        }
        .summary-box td.value {
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            width: 100%;
            clear: both;
        }
        .signature-table {
            width: 100%;
            text-align: center;
            margin-top: 30px;
        }
        .signature-table td {
            width: 33.33%;
            padding-top: 60px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
        }
        
        @media print {
            body {
                background: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    @yield('content')
</body>
</html>
