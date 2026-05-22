<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Rak {{ $rackSession->rack?->rack_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 20px;
        }
        .card {
            background: white;
            border: 3px solid #1d4ed8;
            border-radius: 20px;
            padding: 36px 28px;
            text-align: center;
            max-width: 340px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(29,78,216,.15);
        }
        .brand {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 14px;
        }
        .rack-code {
            font-size: 38px;
            font-weight: 900;
            font-family: 'Courier New', monospace;
            color: #1e40af;
            letter-spacing: .08em;
            margin-bottom: 4px;
        }
        .rack-name { font-size: 14px; color: #475569; font-weight: 500; margin-bottom: 4px; }
        .branch    { font-size: 12px; color: #94a3b8; margin-bottom: 22px; }
        .scan-label {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eff6ff; color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 20px; padding: 6px 18px;
            font-size: 13px; font-weight: 700; margin-bottom: 20px;
            letter-spacing: .03em;
        }
        .qr-wrap {
            display: flex; justify-content: center; margin-bottom: 18px;
        }
        .qr-inner {
            border: 2px solid #dbeafe; border-radius: 14px;
            padding: 10px; background: white;
        }
        .qr-placeholder { line-height: 0; }
        .url-text {
            font-size: 9px; color: #94a3b8; word-break: break-all;
            line-height: 1.5; margin-bottom: 18px;
        }
        .divider { border-top: 1px dashed #e2e8f0; margin: 0 0 16px; }
        .session-info { font-size: 11px; color: #64748b; line-height: 1.8; }
        .session-info strong { color: #334155; }
        .toolbar {
            position: fixed; bottom: 20px; right: 20px;
            display: flex; gap: 8px; z-index: 100;
        }
        .btn {
            padding: 10px 20px; border-radius: 10px; font-size: 13px;
            font-weight: 700; border: none; cursor: pointer;
            transition: opacity .2s, transform .1s;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .btn:hover { opacity: .9; transform: translateY(-1px); }
        .btn-print { background: linear-gradient(135deg,#1d4ed8,#3b82f6); color: white; }
        .btn-back  { background: white; color: #475569; border: 1px solid #e2e8f0; }
        @media print {
            body { background: white; display: block; }
            .toolbar { display: none; }
            .card { box-shadow: none; border: 2px solid #1d4ed8; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">📦 Stok Opname — QR Rak</div>
        <div class="rack-code">{{ $rackSession->rack?->rack_code }}</div>
        <div class="rack-name">{{ $rackSession->rack?->rack_name }}</div>
        <div class="branch">🏪 {{ $rackSession->session?->branch?->name }}</div>
        <div class="scan-label">📱 SCAN UNTUK MENGHITUNG</div>
        <div class="qr-wrap">
            <div class="qr-inner" style="display: flex; align-items: center; justify-content: center; width: 240px; height: 240px; margin: 0 auto;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode(url('/opname/hitung/' . $rackSession->rack_token)) }}"
                     alt="QR Code {{ $rackSession->rack?->rack_code }}"
                     width="220"
                     height="220" />
            </div>
        </div>
        <div class="url-text">{{ url('/opname/hitung/' . $rackSession->rack_token) }}</div>
        <div class="divider"></div>
        <div class="session-info">
            Sesi: <strong>{{ $rackSession->session?->session_number }}</strong><br>
            Tanggal: <strong>{{ $rackSession->session?->opname_date?->format('d M Y') }}</strong>
        </div>
    </div>

    <div class="toolbar">
        <button class="btn btn-back" onclick="window.close()">← Kembali</button>
        <button class="btn btn-print" onclick="window.print()">🖨️ Cetak</button>
    </div>
</body>
</html>
