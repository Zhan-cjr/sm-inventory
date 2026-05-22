<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengecek ke-2 — Pilih Rak | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #060b18; color: #f1f5f9;
            min-height: 100vh; padding: 20px;
            background-image: radial-gradient(ellipse 60% 50% at 80% 10%, rgba(168,85,247,.1) 0%, transparent 60%);
        }
        .wrapper { max-width: 600px; margin: 0 auto; }
        .header { text-align: center; padding: 32px 0 28px; }
        .header .logo { font-size: 44px; margin-bottom: 10px; }
        .badge {
            display: inline-block; background: rgba(168,85,247,.15);
            border: 1px solid rgba(168,85,247,.3); color: #d8b4fe;
            border-radius: 20px; padding: 3px 14px; font-size: 11px;
            font-weight: 700; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 10px;
        }
        .header h1 { font-size: 22px; font-weight: 800; color: #f8fafc; }
        .header .meta { font-size: 13px; color: #64748b; margin-top: 5px; }
        .flash-error {
            background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25);
            border-radius: 12px; padding: 12px 16px; font-size: 13px; color: #fca5a5; margin-bottom: 16px;
        }
        .section-title {
            font-size: 11px; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: #64748b; margin-bottom: 12px;
        }
        .rack-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px; }
        .rack-card {
            display: flex; align-items: center; gap: 14px;
            background: #131f30; border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px; padding: 16px 18px;
            text-decoration: none; color: #f1f5f9;
            transition: border-color .2s, transform .15s, background .2s;
        }
        .rack-card:hover { transform: translateY(-2px); border-color: rgba(168,85,247,.5); background: rgba(168,85,247,.05); }
        .rack-icon {
            width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
            background: rgba(168,85,247,.12); display: flex; align-items: center;
            justify-content: center; font-size: 20px;
        }
        .rack-info { flex: 1 1 0; min-width: 0; }
        .rack-code { font-family: 'Courier New', monospace; font-weight: 800; font-size: 15px; color: #f8fafc; }
        .rack-name { font-size: 12px; color: #64748b; margin-top: 2px; }
        .rack-by   { font-size: 11px; color: #a78bfa; margin-top: 3px; }
        .rack-arrow { font-size: 20px; color: #64748b; flex-shrink: 0; }
        .rack-card.done { opacity: .4; pointer-events: none; }
        .badge-done {
            font-size: 10px; font-weight: 700; background: rgba(16,185,129,.15);
            color: #6ee7b7; border: 1px solid rgba(16,185,129,.2);
            border-radius: 20px; padding: 2px 10px; flex-shrink: 0;
        }
        .badge-ready {
            font-size: 10px; font-weight: 700; background: rgba(168,85,247,.15);
            color: #d8b4fe; border: 1px solid rgba(168,85,247,.25);
            border-radius: 20px; padding: 2px 10px; flex-shrink: 0;
        }
        .empty-state {
            background: #131f30; border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px; padding: 36px 20px; text-align: center; margin-bottom: 28px;
        }
        .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; color: #64748b; line-height: 1.6; }
        .footer { text-align: center; font-size: 11px; color: #64748b; padding: 24px 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="logo">🔍</div>
        <div class="badge">Pengecek ke-2</div>
        <h1>{{ $session->branch?->name }}</h1>
        <div class="meta">
            <strong style="color:#94a3b8">{{ $session->session_number }}</strong>
            &nbsp;·&nbsp;{{ $session->opname_date?->format('d M Y') }}
        </div>
    </div>

    @if(session('error'))
    <div class="flash-error">⚠️ {{ session('error') }}</div>
    @endif

    @php $available = $racksReady->where('count2_status','PENDING'); @endphp
    <div class="section-title">📋 Rak Siap Dicek ({{ $available->count() }})</div>

    @if($available->count() > 0)
    <div class="rack-list">
        @foreach($available as $rs)
        <a href="{{ route('opname.cek.form', [$sessionToken, $rs->id]) }}" class="rack-card">
            <div class="rack-icon">🗂️</div>
            <div class="rack-info">
                <div class="rack-code">{{ $rs->rack?->rack_code }}</div>
                <div class="rack-name">{{ $rs->rack?->rack_name }}</div>
                @if($rs->count1_by_name)
                <div class="rack-by">✅ Dihitung oleh {{ $rs->count1_by_name }} · {{ $rs->count1_at?->format('H:i') }}</div>
                @endif
            </div>
            <span class="badge-ready">CEK SEKARANG</span>
            <div class="rack-arrow">›</div>
        </a>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <div class="icon">⏳</div>
        <p>Belum ada rak yang sudah selesai dihitung oleh Penghitung 1.<br>Tunggu sebentar lalu refresh halaman ini.</p>
    </div>
    @endif

    @if($racksDone->count() > 0)
    <div class="section-title" style="margin-top:8px;">✅ Rak Selesai Dicek ({{ $racksDone->count() }})</div>
    <div class="rack-list">
        @foreach($racksDone as $rs)
        <div class="rack-card done">
            <div class="rack-icon">✅</div>
            <div class="rack-info">
                <div class="rack-code">{{ $rs->rack?->rack_code }}</div>
                <div class="rack-name">{{ $rs->rack?->rack_name }}</div>
                @if($rs->count2_by_name)
                <div class="rack-by">Dicek oleh {{ $rs->count2_by_name }} · {{ $rs->count2_at?->format('H:i') }}</div>
                @endif
            </div>
            <span class="badge-done">SELESAI</span>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">{{ config('app.name') }} · Sistem Manajemen Inventori</div>
</div>
</body>
</html>
