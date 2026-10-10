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
            table-layout: fixed;
            word-wrap: break-word;
        }
        .report-table th, .report-table td {
            border: 1px dashed #333;
            padding: 4px 6px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .report-table th.right, .report-table td.right {
            text-align: right;
        }
        .report-table th.center, .report-table td.center {
            text-align: center;
        }
        .report-table th {
            font-weight: bold;
            background-color: #f1f5f9;
        }
        .report-table .total-row td {
            font-weight: bold;
            background-color: #e2e8f0;
        }
        .report-table thead {
            display: table-header-group;
        }
        .report-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        @media screen {
            .no-print-bar {
                position: sticky;
                top: 0;
                background: #0f172a;
                color: #f8fafc;
                padding: 10px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 9999;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);
                margin: -20px -20px 20px -20px;
                font-family: system-ui, -apple-system, sans-serif;
            }
            .btn-action {
                border: none;
                padding: 6px 14px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .btn-print {
                background: #0284c7;
                color: white;
            }
            .btn-print:hover {
                background: #0369a1;
            }
            .btn-close {
                background: #475569;
                color: white;
                margin-left: 8px;
            }
            .btn-close:hover {
                background: #334155;
            }
        }
    </style>
</head>
<body>
    @if(request('export') !== 'xls')
        <div class="no-print-bar no-print">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: bold; font-size: 13px;">Mode Cetak Laporan</span>
                <span style="font-size: 11px; color: #94a3b8; background: #1e293b; padding: 2px 8px; border-radius: 4px;">Tekan tombol Cetak di bawah jika dialog print tertutup</span>
            </div>
            <div>
                <button type="button" onclick="window.print()" class="btn-action btn-print">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak / Simpan PDF
                </button>
                <button type="button" onclick="window.close()" class="btn-action btn-close">Tutup</button>
            </div>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    window.print();
                }, 300);
            });
        </script>
    @endif
    @php
        $org = \App\Models\Organization::first();
        $org_name = $org ? strtoupper($org->name) : 'SM INVENTORY';
        $org_address = $org ? $org->address : '';
        
        $filters = request()->input('tableFilters', []);
        $branch_id = request()->input('branch_id') ?? ($filters['branch_id']['value'] ?? null);
        if (!$branch_id && auth()->check() && auth()->user()->branch_id) {
            $branch_id = auth()->user()->branch_id;
        }

        $branch = $branch_id ? \App\Models\Branch::find($branch_id) : null;
        $branch_name = $branch ? strtoupper($branch->name) : '';
        $header_address = $branch ? $branch->address : $org_address;
    @endphp

    @if(request('export') === 'xls')
        <table border="0">
            <tr>
                <td colspan="10" style="font-size: 20px;"><b>{{ $org_name }}</b></td>
            </tr>
            @if($branch_name)
            <tr>
                <td colspan="10" style="font-size: 16px;"><b>{{ $branch_name }}</b></td>
            </tr>
            @endif
            @if($header_address)
            <tr>
                <td colspan="10" style="font-size: 12px;">{{ $header_address }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="10"></td>
            </tr>
            <tr>
                <td colspan="10" style="font-size: 16px;"><b>@yield('title')</b></td>
            </tr>
            <tr>
                <td colspan="10"></td>
            </tr>
        </table>
    @else
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
    @endif


    @yield('content')
</body>
</html>
