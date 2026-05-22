<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rak Terkunci | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #060b18; color: #f1f5f9;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
            background-image: radial-gradient(ellipse 50% 40% at 50% 0%, rgba(234,179,8,.09) 0%, transparent 60%);
        }
        .card {
            background: #131f30; border: 1px solid rgba(234,179,8,.15);
            border-radius: 24px; padding: 44px 36px; text-align: center;
            max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .icon-wrap {
            width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 22px;
            background: rgba(234,179,8,.1); border: 2px solid rgba(234,179,8,.2);
            display: flex; align-items: center; justify-content: center; font-size: 38px;
        }
        h1 { font-size: 24px; font-weight: 800; color: #f59e0b; letter-spacing: -.02em; margin-bottom: 12px; }
        .subtitle { color: #94a3b8; font-size: 14px; margin-bottom: 28px; line-height: 1.7; }
        .info-box {
            background: #0e1726; border-radius: 14px; padding: 18px 20px;
            margin-bottom: 24px; text-align: left; border: 1px solid rgba(255,255,255,.05);
        }
        .info-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-lbl { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; flex-shrink: 0; margin-right: 12px; }
        .info-val { font-size: 14px; font-weight: 700; color: #f1f5f9; text-align: right; }
        .btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px 20px; border-radius: 14px; font-size: 14px;
            font-weight: 700; text-decoration: none; transition: opacity .2s, transform .15s;
            background: #1e293b; color: #94a3b8; border: 1px solid rgba(255,255,255,.07);
            width: 100%; cursor: pointer; border: none;
        }
        .btn:hover { opacity: .9; transform: translateY(-1px); }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">🔒</div>
    <h1>Rak Sudah Terkunci</h1>
    <p class="subtitle">{{ $message }}</p>

    <div class="info-box">
        <div class="info-row">
            <span class="info-lbl">Rak</span>
            <span class="info-val">{{ $rack?->rack_code }} — {{ $rack?->rack_name }}</span>
        </div>
        @if($locker_name)
        <div class="info-row">
            <span class="info-lbl">Dikunci oleh</span>
            <span class="info-val">{{ $locker_name }}</span>
        </div>
        @endif
        @if($locked_at)
        <div class="info-row">
            <span class="info-lbl">Pada</span>
            <span class="info-val">{{ \Carbon\Carbon::parse($locked_at)->format('d M Y, H:i') }}</span>
        </div>
        @endif
    </div>

    <button class="btn" onclick="history.back()">← Kembali</button>
</div>
</body>
</html>
