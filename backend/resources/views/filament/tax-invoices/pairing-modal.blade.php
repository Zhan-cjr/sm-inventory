@php
    $sessionId = 'SCAN_' . substr(md5(auth()->id() ?? 'guest'), 0, 6);
@endphp

<div class="pairing-container" 
     style="font-family: inherit; color: #1f2937; text-align: center; padding: 4px;"
     x-data="{
        sess: @js($sessionId),
        ip: localStorage.getItem('sminventory_laptop_ip') || (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' ? '192.168.8.199' : window.location.hostname),
        port: window.location.port ? (':' + window.location.port) : '',
        proto: window.location.protocol,
        connected: false,
        isLocal: (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'),
        getUrl() {
            return `${this.proto}//${this.ip}${this.port}/scanner-gun?session=${this.sess}`;
        },
        getQrUrl() {
            return `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(this.getUrl())}`;
        },
        saveIp() {
            localStorage.setItem('sminventory_laptop_ip', this.ip.trim());
        },
        init() {
            this.saveIp();
            if (window.__scannerPollTimer) clearInterval(window.__scannerPollTimer);
            window.__scannerPollTimer = setInterval(() => {
                fetch(`/scanner-gun/poll?session=${this.sess}`)
                    .then(res => res.json())
                    .then(data => {
                        this.connected = !!data.connected;
                        if (data && data.code) {
                            const inputField = document.querySelector('input[name=\'scan_qr_url\']') 
                                || document.querySelector('input[wire\\:model*=\'scan_qr_url\']')
                                || document.querySelector('input[placeholder*=\'http://svc.efaktur\']');
                            
                            if (inputField) {
                                inputField.value = data.code;
                                inputField.dispatchEvent(new Event('input', { bubbles: true }));
                                inputField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    })
                    .catch(e => {
                        // Silent fail if network momentarily disconnects
                    });
            }, 1500);
        }
     }">

    <style>
        .pairing-box { display: flex; flex-direction: column; align-items: center; gap: 14px; }
        .qr-card { background: #ffffff; padding: 12px; border-radius: 12px; border: 2px dashed #0284c7; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08); display: inline-block; min-width: 180px; min-height: 180px; }
        .ip-config-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; width: 100%; max-width: 440px; text-align: left; font-size: 12px; }
        .ip-input-group { display: flex; gap: 8px; margin-top: 6px; }
        .ip-input { flex-grow: 1; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-family: monospace; background: #ffffff; color: #0f172a; }
        .btn-update-ip { background: #0284c7; color: #ffffff; border: none; border-radius: 6px; padding: 6px 14px; font-weight: 600; cursor: pointer; font-size: 12px; }
        .btn-update-ip:hover { background: #0369a1; }
        .live-status-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; transition: all 0.3s ease; }
        .live-status-pill.connected { background: #dcfce7; color: #166534; border-color: #86efac; }
        .status-indicator-dot { width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; }
        .connected .status-indicator-dot { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .pairing-steps { text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; width: 100%; max-width: 440px; font-size: 12px; line-height: 1.5; color: #334155; }
        .pairing-steps ol { padding-left: 18px; margin-top: 4px; }
    </style>

    <div class="pairing-box">
        <!-- Status Pill -->
        <div class="live-status-pill" :class="{ 'connected': connected }">
            <span class="status-indicator-dot"></span>
            <span x-text="connected ? '🟢 HP Terhubung! Siap menembak faktur di meja' : 'Menunggu scan dari HP...'"></span>
        </div>

        <!-- Box Input IP WiFi (Tampil saat lokal dev) -->
        <div class="ip-config-box" x-show="isLocal">
            <div style="font-weight: 700; color: #166534; margin-bottom: 2px;">
                📶 IP Jaringan WiFi Laptop Anda:
            </div>
            <div style="color: #475569; font-size: 11px;">
                Sesuaikan jika IP laptop Anda berubah pada WiFi yang berbeda:
            </div>
            <div class="ip-input-group">
                <input type="text" x-model="ip" class="ip-input" placeholder="192.168.8.199" @keydown.enter.prevent="saveIp()" />
                <button type="button" class="btn-update-ip" @click="saveIp()">Terapkan</button>
            </div>
        </div>

        <!-- QR Code Card -->
        <div class="qr-card">
            <img :src="getQrUrl()" @error="$event.target.src = '/scanner-gun/qr?url=' + encodeURIComponent(getUrl())" alt="QR Pairing Scanner Gun" style="width: 170px; height: 170px; display: block; margin: 0 auto; border-radius: 6px;" />
        </div>

        <div style="font-size: 11.5px; color: #64748b;">
            Buka link ini di browser HP: <br>
            <strong style="color: #0284c7; word-break: break-all;" x-text="getUrl()"></strong>
        </div>

        <div class="pairing-steps">
            <strong>Langkah Mudah:</strong>
            <ol>
                <li>Buka kamera HP Anda, lalu sorot QR Code di atas.</li>
                <li>Arahkan HP ke QR e-Faktur di meja → data otomatis masuk ke PC!</li>
            </ol>
        </div>
    </div>
</div>
