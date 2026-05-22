<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Opname | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:      #060b18;
            --surface: #0e1726;
            --card:    #131f30;
            --border:  rgba(255,255,255,.07);
            --accent:  #6366f1;
            --blue:    #3b82f6;
            --purple:  #a855f7;
            --text:    #f1f5f9;
            --muted:   #64748b;
            --faint:   #1e293b;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            background-image:
                radial-gradient(ellipse 60% 50% at 10% 0%, rgba(99,102,241,.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 90% 100%, rgba(168,85,247,.08) 0%, transparent 60%);
        }
        .wrapper { max-width: 500px; width: 100%; }

        /* ─── Header ─── */
        .header { text-align: center; margin-bottom: 28px; }
        .logo-wrap {
            width: 72px; height: 72px; border-radius: 20px; margin: 0 auto 16px;
            background: linear-gradient(135deg, var(--accent), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 8px 24px rgba(99,102,241,.3);
        }
        .session-badge {
            display: inline-block;
            background: rgba(99,102,241,.15); border: 1px solid rgba(99,102,241,.3);
            color: #a5b4fc; border-radius: 20px; padding: 3px 14px;
            font-size: 11px; font-weight: 700; letter-spacing: .1em;
            margin-bottom: 10px; text-transform: uppercase;
        }
        .header h1 { font-size: 24px; font-weight: 800; color: #f8fafc; letter-spacing: -.02em; }
        .header .meta { font-size: 13px; color: var(--muted); margin-top: 6px; line-height: 1.6; }
        .header .meta strong { color: #94a3b8; }

        /* ─── Progress Stats ─── */
        .stats-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;
        }
        .stat-box {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; padding: 14px 16px;
        }
        .stat-label { font-size: 11px; color: var(--muted); font-weight: 600; letter-spacing: .05em; text-transform: uppercase; }
        .stat-value { font-size: 22px; font-weight: 800; margin-top: 4px; letter-spacing: -.02em; }
        .stat-sub   { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .c1 { color: #60a5fa; }
        .c2 { color: #c084fc; }
        .cd { color: #f87171; }

        /* ─── Progress Bar ─── */
        .pbar-wrap { margin-top: 8px; }
        .pbar { height: 4px; background: rgba(255,255,255,.06); border-radius: 4px; overflow: hidden; }
        .pbar-fill { height: 100%; border-radius: 4px; transition: width .5s ease; }
        .pbar-fill.blue   { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
        .pbar-fill.purple { background: linear-gradient(90deg,#9333ea,#c084fc); }

        /* ─── Divider ─── */
        .divider {
            text-align: center; font-size: 11px; font-weight: 600;
            color: var(--muted); margin: 22px 0; position: relative;
            letter-spacing: .06em; text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: ''; position: absolute; top: 50%;
            width: calc(50% - 60px); height: 1px; background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        /* ─── Scan Box (Penghitung 1 instruction) ─── */
        .scan-box {
            background: var(--card); border: 2px dashed rgba(59,130,246,.3);
            border-radius: 16px; padding: 22px 20px; text-align: center; margin-bottom: 14px;
        }
        .scan-box .icon { font-size: 38px; margin-bottom: 10px; }
        .scan-box h4 { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .scan-box p { font-size: 13px; color: var(--muted); line-height: 1.6; }

        /* ─── Role Card ─── */
        .role-card {
            display: block; text-decoration: none;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 18px; padding: 22px; margin-bottom: 12px;
            transition: border-color .2s, background .2s, transform .15s;
            cursor: pointer;
        }
        .role-card:hover { transform: translateY(-2px); }
        .role-card.checker:hover { border-color: #a855f7; background: rgba(168,85,247,.06); }

        .role-header { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
        .role-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }
        .icon-checker { background: rgba(168,85,247,.12); }
        .role-title    { font-size: 16px; font-weight: 700; color: var(--text); }
        .role-subtitle { font-size: 12px; color: var(--muted); margin-top: 3px; }
        .role-desc     { font-size: 13px; color: #94a3b8; line-height: 1.6; }
        .role-arrow    { float: right; font-size: 22px; color: var(--muted); margin-top: -30px; }

        /* ─── Info Box ─── */
        .info-box {
            background: rgba(234,179,8,.07); border: 1px solid rgba(234,179,8,.2);
            border-radius: 12px; padding: 14px 16px;
            font-size: 13px; color: #fde68a; margin-top: 16px; line-height: 1.6;
        }

        /* ─── Footer ─── */
        .footer { text-align: center; margin-top: 24px; font-size: 11px; color: var(--muted); }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="logo-wrap">📋</div>
        <div class="session-badge">Stok Opname</div>
        <h1>{{ $session->branch?->name }}</h1>
        <div class="meta">
            <strong>{{ $session->session_number }}</strong>
            &nbsp;·&nbsp;{{ $session->opname_date?->format('d M Y') }}
        </div>
    </div>

    {{-- Progress Stats --}}
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Rak Dihitung (P1)</div>
            <div class="stat-value c1">{{ $c1Done }}/{{ $total }}</div>
            <div class="pbar-wrap">
                <div class="pbar"><div class="pbar-fill blue" style="width:{{ $total > 0 ? round($c1Done/$total*100) : 0 }}%"></div></div>
            </div>
            <div class="stat-sub">{{ $total > 0 ? round($c1Done/$total*100) : 0 }}% selesai</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Rak Dicek (P2)</div>
            <div class="stat-value c2">{{ $c2Done }}/{{ $total }}</div>
            <div class="pbar-wrap">
                <div class="pbar"><div class="pbar-fill purple" style="width:{{ $total > 0 ? round($c2Done/$total*100) : 0 }}%"></div></div>
            </div>
            <div class="stat-sub">{{ $total > 0 ? round($c2Done/$total*100) : 0 }}% selesai</div>
        </div>
        @if($discrepancy > 0)
        <div class="stat-box" style="grid-column: span 2;">
            <div class="stat-label">⚠️ Item Selisih</div>
            <div class="stat-value cd">{{ $discrepancy }} item</div>
            <div class="stat-sub">Perlu verifikasi Supervisor</div>
        </div>
        @endif
    </div>

    @if(in_array($session->status, ['COUNTING', 'CHECKING']))
        <div class="divider">Saya adalah</div>

        {{-- Penghitung 1: instruksi scan rak --}}
        <div class="scan-box">
            <div class="icon">📦</div>
            <h4>Penghitung 1</h4>
            <p>Scan QR yang tertempel di rak untuk mulai menghitung produk di rak tersebut.</p>
        </div>

        {{-- Pengecek 2 --}}
        <a href="{{ route('opname.cek', $sessionToken) }}" class="role-card checker">
            <div class="role-header">
                <div class="role-icon icon-checker">🔍</div>
                <div>
                    <div class="role-title">Pengecek ke-2</div>
                    <div class="role-subtitle">Audit independen — cek ulang rak yang sudah dihitung P1</div>
                </div>
            </div>
            <div class="role-desc">
                Tampilkan daftar rak yang sudah selesai dihitung dan belum dicek.
                Hasil hitungan P1 <strong style="color:#e2e8f0">tidak ditampilkan</strong> agar pengecekan objektif.
            </div>
            <span class="role-arrow">›</span>
        </a>
    @endif

    @if(!in_array($session->status, ['COUNTING', 'CHECKING', 'DRAFT']))
    <div class="info-box">
        ℹ️ Sesi opname ini sudah dalam status
        <strong>{{ match($session->status) {
            'FINAL_CHECK' => 'Final Check SPV',
            'COMPLETED'   => 'Selesai',
            'CANCELLED'   => 'Dibatalkan',
            default       => $session->status,
        } }}</strong>.
        Tidak menerima input dari penghitung / pengecek.
    </div>
    @endif

    @if($session->status === 'DRAFT')
    <div class="info-box">
        ⏳ Sesi opname ini belum dimulai oleh admin. Silakan hubungi admin untuk memulai sesi.
    </div>
    @endif

    <div class="footer">{{ config('app.name') }} · Sistem Manajemen Inventori</div>

</div>
</body>
</html>
