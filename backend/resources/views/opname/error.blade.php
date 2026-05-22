<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Tidak Aktif | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #060b18; color: #f1f5f9;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
            background-image: radial-gradient(ellipse 50% 40% at 50% 0%, rgba(239,68,68,.1) 0%, transparent 60%);
        }
        .card {
            background: #131f30; border: 1px solid rgba(239,68,68,.15);
            border-radius: 24px; padding: 44px 36px; text-align: center;
            max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .icon-wrap {
            width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 22px;
            background: rgba(239,68,68,.1); border: 2px solid rgba(239,68,68,.2);
            display: flex; align-items: center; justify-content: center; font-size: 38px;
        }
        h1 { font-size: 24px; font-weight: 800; color: #ef4444; letter-spacing: -.02em; margin-bottom: 12px; }
        .subtitle { color: #94a3b8; font-size: 14px; line-height: 1.7; }
        .info-box {
            background: #0e1726; border-radius: 14px; padding: 16px 18px;
            margin-top: 24px; text-align: left; border: 1px solid rgba(255,255,255,.05);
        }
        .info-box .row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .info-box .row + .row { margin-top: 8px; }
        .info-box .lbl { color: #64748b; }
        .info-box .val { font-weight: 700; color: #f1f5f9; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">⚠️</div>
    <h1>Sesi Tidak Aktif</h1>
    <p class="subtitle">{{ $message }}</p>
    @if(isset($session))
    <div class="info-box">
        <div class="row"><span class="lbl">Sesi</span><span class="val">{{ $session->session_number }}</span></div>
        <div class="row"><span class="lbl">Status</span><span class="val">{{ $session->status }}</span></div>
    </div>
    @endif
</div>
</body>
</html>
