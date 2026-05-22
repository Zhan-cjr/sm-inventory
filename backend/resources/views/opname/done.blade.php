<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhasil | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #060b18; color: #f1f5f9;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
            background-image: radial-gradient(ellipse 60% 50% at 50% 0%, rgba(16,185,129,.12) 0%, transparent 60%);
        }
        .card {
            background: #131f30; border: 1px solid rgba(255,255,255,.07);
            border-radius: 24px; padding: 44px 36px; text-align: center;
            max-width: 420px; width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .check-wrap {
            width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 22px;
            background: linear-gradient(135deg, #059669, #10b981);
            display: flex; align-items: center; justify-content: center;
            font-size: 38px;
            box-shadow: 0 0 0 8px rgba(16,185,129,.12), 0 8px 24px rgba(16,185,129,.3);
        }
        h1 { font-size: 26px; font-weight: 800; color: #10b981; letter-spacing: -.02em; margin-bottom: 8px; }
        .subtitle { color: #94a3b8; font-size: 15px; margin-bottom: 28px; line-height: 1.6; }
        .info-box {
            background: #0e1726; border-radius: 14px; padding: 16px 18px;
            margin-bottom: 28px; text-align: left; border: 1px solid rgba(255,255,255,.05);
        }
        .info-box .lbl { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
        .info-box .val { font-size: 18px; font-weight: 800; color: #f1f5f9; font-family: 'Courier New', monospace; }
        .btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 20px; border-radius: 14px; font-size: 15px;
            font-weight: 700; text-decoration: none; text-align: center;
            transition: opacity .2s, transform .15s; margin-bottom: 10px; border: none; cursor: pointer;
            width: 100%;
        }
        .btn:hover { opacity: .9; transform: translateY(-1px); }
        .btn-green  { background: linear-gradient(135deg,#059669,#10b981); color: white; }
        .btn-purple { background: linear-gradient(135deg,#7c3aed,#a855f7); color: white; }
        .btn-gray   { background: #1e293b; color: #94a3b8; border: 1px solid rgba(255,255,255,.07); }
        .note { font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="check-wrap">✅</div>
    <h1>Berhasil Disimpan!</h1>

    @if($role === 'penghitung')
    <p class="subtitle">Hasil hitungan untuk rak <strong style="color:#f1f5f9">{{ $rack_code }}</strong> telah berhasil disimpan dan rak telah dikunci.</p>
    @elseif($role === 'pengecek')
    <p class="subtitle">Hasil pengecekan untuk rak <strong style="color:#f1f5f9">{{ $rack_code }}</strong> telah berhasil disimpan.</p>
    @endif

    <div class="info-box">
        <div class="lbl">Rak yang diproses</div>
        <div class="val">{{ $rack_code }}</div>
    </div>

    @if($role === 'penghitung')
    <p class="note">🎉 Terima kasih! Lanjutkan ke rak berikutnya dengan scan QR rak selanjutnya.</p>
    @elseif($role === 'pengecek' && $next_url)
    <a href="{{ $next_url }}" class="btn btn-purple">🔍 Cek Rak Lainnya</a>
    @endif

    <a href="javascript:window.close()" class="btn btn-gray">Tutup Halaman</a>
</div>
</body>
</html>
