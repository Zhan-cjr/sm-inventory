@php
    $portalUrl = route('opname.final', $record->session_token);
@endphp

<div style="text-align: center; padding: 1.5rem 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 1.25rem;">
    <div style="background: #f8fafc; border: 2px dashed #6366f1; border-radius: 1rem; padding: 1rem; display: inline-block;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($portalUrl) }}"
             alt="QR Pengecek Final"
             width="220"
             height="220"
             style="display: block; border-radius: 8px;" />
    </div>

    <div>
        <h3 style="font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 0.35rem;">
            📱 Scan QR Portal Pengecek Final
        </h3>
        <p style="font-size: 0.85rem; color: #64748b; max-width: 400px; margin: 0 auto; line-height: 1.5;">
            Gunakan smartphone atau scanner mobile untuk membuka portal verifikasi selisih fisik. Input kuantitas akan terbuka otomatis setelah barcode fisik produk di-scan.
        </p>
    </div>

    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.5rem 1rem; font-family: monospace; font-size: 0.775rem; color: #334155; max-width: 100%; word-break: break-all;">
        {{ $portalUrl }}
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
        <a href="{{ $portalUrl }}" target="_blank"
           style="background: #4f46e5; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
            📱 Buka di Tab Baru
        </a>
        <a href="{{ route('opname.print-final-check', ['sessionId' => $record->id]) }}" target="_blank"
           style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
            🖨 Cetak Lembar Final Check
        </a>
    </div>
</div>
