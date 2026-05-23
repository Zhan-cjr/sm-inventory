<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .report-table th, .report-table td {
            border: 1px dashed #333;
            padding: 4px 6px;
            text-align: left;
        }
        .report-table th.right, .report-table td.right {
            text-align: right;
        }
        .report-table th.center, .report-table td.center {
            text-align: center;
        }
        .report-table th {
            font-weight: normal;
        }
        .report-table .total-row td {
            font-weight: normal;
        }
        @media print {
            body {
                padding: 0;
            }
        }
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
        
        <h2>@yield('title')</h2>
    </div>

    @yield('content')
</body>
</html>
