<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Rak — {{ $session->session_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 24px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .print-header .logo { font-size: 40px; margin-bottom: 8px; }
        .print-header h1 { font-size: 22px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em; }
        .print-header p  { font-size: 13px; color: #64748b; margin-top: 6px; line-height: 1.6; }
        .print-header .badge {
            display: inline-block;
            margin-top: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 18px;
        }

        .qr-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 16px;
            text-align: center;
            break-inside: avoid;
            page-break-inside: avoid;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            transition: box-shadow .2s;
        }
        .qr-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
        .qr-card .rack-code {
            font-size: 22px;
            font-weight: 900;
            font-family: 'Courier New', monospace;
            color: #1e40af;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .qr-card .rack-name {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 14px;
            min-height: 16px;
        }
        .qr-card .scan-label {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 20px;
            border: 1px solid #bfdbfe;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: 0.04em;
        }
        .qr-card .qr-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 14px;
        }
        .qr-card .qr-inner {
            border: 2px solid #dbeafe;
            border-radius: 12px;
            padding: 8px;
            background: white;
        }
        .qr-card .qr-placeholder { line-height: 0; }
        .qr-card .url-text {
            font-size: 9px;
            color: #94a3b8;
            word-break: break-all;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        .qr-card .branch-info {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        .toolbar {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .btn {
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-print { background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: white; }
        .btn-back  { background: white; color: #475569; border: 1px solid #e2e8f0; }

        @media print {
            body { padding: 8px; background: white; }
            .toolbar { display: none !important; }
            .qr-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .qr-card { border: 1.5px solid #cbd5e1; padding: 12px; box-shadow: none; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

    <div class="print-header">
        <div class="logo">📦</div>
        <h1>QR Code Rak — Stok Opname</h1>
        <p>
            Sesi: <strong>{{ $session->session_number }}</strong> &nbsp;·&nbsp;
            Cabang: <strong>{{ $session->branch?->name }}</strong> &nbsp;·&nbsp;
            Tanggal: <strong>{{ $session->opname_date?->format('d M Y') }}</strong>
        </p>
        <p style="margin-top: 6px; font-size: 12px; color: #94a3b8;">
            Tempel QR ini di masing-masing rak sebelum proses penghitungan dimulai
        </p>
        <span class="badge">📱 SCAN UNTUK MULAI HITUNG</span>
    </div>

    <div class="qr-grid">
        @foreach($rackSessions as $rs)
        <div class="qr-card">
            <div class="rack-code">{{ $rs->rack?->rack_code }}</div>
            <div class="rack-name">{{ $rs->rack?->rack_name }}</div>
            <div class="scan-label">📱 SCAN UNTUK HITUNG</div>
            <div class="qr-wrap">
                <div class="qr-inner" style="display: flex; align-items: center; justify-content: center; width: 186px; height: 186px; margin: 0 auto;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=170x170&data={{ urlencode(url('/opname/hitung/' . $rs->rack_token)) }}"
                         alt="QR Code {{ $rs->rack?->rack_code }}"
                         width="170"
                         height="170" />
                </div>
            </div>
            <div class="url-text">{{ url('/opname/hitung/' . $rs->rack_token) }}</div>
            <div class="branch-info">🏪 {{ $session->branch?->name }}</div>
        </div>
        @endforeach
    </div>

    <!-- Floating Toolbar -->
    <div class="toolbar">
        <button class="btn btn-back" onclick="window.close()">← Kembali</button>
        <button class="btn btn-print" onclick="window.print()">🖨️ Cetak / Save PDF</button>
    </div>
</body>
</html>
