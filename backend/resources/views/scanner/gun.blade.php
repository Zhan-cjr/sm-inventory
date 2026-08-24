<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wireless Scanner Gun - SM Inventory</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #090d16; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .header { padding: 10px 14px; background: #111827; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1f2937; }
        .logo { font-weight: 800; font-size: 14px; color: #38bdf8; }
        .session-badge { font-size: 11px; background: #0284c7; padding: 3px 8px; border-radius: 999px; font-weight: 700; }
        .viewport-wrapper { position: relative; width: 100%; height: 320px; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        #reader { width: 100%; height: 100%; }
        #reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
        .torch-btn { position: absolute; top: 12px; right: 12px; z-index: 30; background: rgba(17, 24, 39, 0.85); border: 1px solid #4b5563; color: #f8fafc; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; backdrop-filter: blur(6px); }
        .controls { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
        .status-card { background: #111827; border: 1px solid #1f2937; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .status-dot.scanning { animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .btn-snap { background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #fff; border: none; border-radius: 10px; padding: 12px; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; }
        .log-box { background: #000; border: 1px solid #1f2937; border-radius: 8px; padding: 8px 12px; max-height: 110px; overflow-y: auto; font-size: 11px; font-family: monospace; }
        .log-item { padding: 3px 0; border-bottom: 1px solid #111827; color: #94a3b8; }
        .log-item.success { color: #4ade80; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">⚡ WIRELESS SCANNER GUN</div>
        <div class="session-badge">SESI: {{ $session }}</div>
    </div>

    <div class="viewport-wrapper">
        <div id="reader"></div>
        <button type="button" class="torch-btn" id="torchBtn" onclick="toggleTorch()" title="Nyalakan Lampu Senter">💡</button>
    </div>

    <div class="controls">
        <div class="status-card">
            <div class="status-dot scanning" id="statusDot"></div>
            <div>
                <div style="font-weight: 700; font-size: 13px;" id="statusTitle">🔍 Kamera Live Scan Aktif</div>
                <div style="font-size: 11px; color: #94a3b8;" id="statusSubtitle">Arahkan kamera ke QR e-Faktur di meja</div>
            </div>
        </div>

        <input type="file" id="cameraSnapInput" accept="image/*" capture="environment" style="display:none;" onchange="processSnappedImage(this)">
        <button type="button" class="btn-snap" onclick="document.getElementById('cameraSnapInput').click()">
            📸 Opsi Alternatif: Jepret Foto Tajam (Autofokus)
        </button>

        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Histori Tembakan (<span id="scanCount">0</span> Faktur)</div>
        <div class="log-box" id="logBox"><div class="log-item">Menunggu tembakan pertama...</div></div>
    </div>

    <script>
        const SESSION_ID = @json($session);
        let scanCount = 0, lastScannedText = '', lastScanTime = 0;
        let html5QrCode = null, isTorchOn = false;

        function playFeedback() {
            try {
                const ac = new (window.AudioContext || window.webkitAudioContext)();
                const o = ac.createOscillator(), g = ac.createGain();
                o.type = 'sine'; o.frequency.setValueAtTime(1600, ac.currentTime);
                g.gain.setValueAtTime(0.35, ac.currentTime);
                g.gain.exponentialRampToValueAtTime(0.01, ac.currentTime + 0.12);
                o.connect(g); g.connect(ac.destination); o.start(); o.stop(ac.currentTime + 0.12);
            } catch (e) {}
            if ('vibrate' in navigator) navigator.vibrate([80, 40, 80]);
        }

        async function sendToPc(qrText) {
            try {
                const res = await fetch('/scanner-gun/push', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ session: SESSION_ID, code: qrText })
                });
                return await res.json();
            } catch (e) { return null; }
        }

        function onSuccessfulScan(decodedText) {
            const now = Date.now();
            if (decodedText === lastScannedText && (now - lastScanTime < 2500)) return;
            lastScannedText = decodedText; lastScanTime = now; scanCount++;
            playFeedback();

            document.getElementById('scanCount').textContent = scanCount;
            document.getElementById('statusTitle').textContent = '✅ Berhasil Ditembak ke PC!';
            document.getElementById('statusSubtitle').textContent = 'Data langsung terisi di layar PC.';

            const logBox = document.getElementById('logBox');
            const item = document.createElement('div');
            item.className = 'log-item success';
            item.textContent = `[${new Date().toLocaleTimeString()}] #${scanCount}: ` + (decodedText.length > 28 ? decodedText.substring(0, 28) + '...' : decodedText);
            logBox.prepend(item);
            sendToPc(decodedText);

            setTimeout(() => {
                document.getElementById('statusTitle').textContent = '🔍 Kamera Live Scan Aktif';
                document.getElementById('statusSubtitle').textContent = 'Arahkan kamera ke QR e-Faktur di meja';
            }, 2000);
        }

        async function startScanner() {
            if (!window.Html5Qrcode) {
                setTimeout(startScanner, 300);
                return;
            }

            try {
                html5QrCode = new Html5Qrcode("reader");
                const config = {
                    fps: 20,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    }
                };

                await html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => onSuccessfulScan(decodedText),
                    () => {}
                );

                // Check torch capabilities
                try {
                    const capabilities = html5QrCode.getRunningTrackCapabilities();
                    if (capabilities && capabilities.torch) {
                        document.getElementById('torchBtn').style.display = 'flex';
                    }
                } catch(e) {}
            } catch (err) {
                document.getElementById('statusTitle').textContent = '⚠️ Izin Kamera Diperlukan';
                document.getElementById('statusSubtitle').textContent = 'Klik tombol Jepret Foto Tajam di bawah.';
            }
        }

        async function toggleTorch() {
            if (!html5QrCode) return;
            try {
                isTorchOn = !isTorchOn;
                await html5QrCode.applyVideoConstraints({
                    advanced: [{ torch: isTorchOn }]
                });
                document.getElementById('torchBtn').textContent = isTorchOn ? '🔦' : '💡';
                document.getElementById('torchBtn').style.background = isTorchOn ? '#eab308' : 'rgba(17,24,39,0.85)';
            } catch (e) {
                alert('Lampu flash tidak didukung pada browser/perangkat ini.');
            }
        }

        async function processSnappedImage(input) {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];
            document.getElementById('statusTitle').textContent = '⏳ Membaca QR Code...';
            document.getElementById('statusSubtitle').textContent = 'Menganalisis foto resolusi tinggi...';

            let decoded = null;

            // 1. Native BarcodeDetector
            if ('BarcodeDetector' in window) {
                try {
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    const bitmap = await createImageBitmap(file);
                    const barcodes = await detector.detect(bitmap);
                    if (barcodes.length > 0 && barcodes[0].rawValue) {
                        decoded = barcodes[0].rawValue;
                    }
                } catch (e) {}
            }

            // 2. jsQR Multi-Scale Fallback
            if (!decoded && window.jsQR) {
                try {
                    decoded = await decodeFileWithJsQr(file);
                } catch (e) {}
            }

            if (decoded) {
                onSuccessfulScan(decoded);
            } else {
                document.getElementById('statusTitle').textContent = '❌ QR Belum Terbaca';
                document.getElementById('statusSubtitle').textContent = 'Pastikan foto QR code tidak blur dan terkena cahaya.';
            }
            input.value = '';
        }

        function decodeFileWithJsQr(file) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const scales = [1200, 800, img.width];
                    for (let maxDim of scales) {
                        let w = img.width, h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
                            else { w = Math.round((w * maxDim) / h); h = maxDim; }
                        }
                        canvas.width = w; canvas.height = h;
                        ctx.drawImage(img, 0, 0, w, h);
                        const idata = ctx.getImageData(0, 0, w, h);
                        const code = jsQR(idata.data, w, h, { inversionAttempts: "attemptBoth" });
                        if (code && code.data) return resolve(code.data);
                    }
                    resolve(null);
                };
                img.onerror = () => resolve(null);
                img.src = URL.createObjectURL(file);
            });
        }

        window.addEventListener('DOMContentLoaded', startScanner);
        setInterval(() => { fetch('/scanner-gun/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ session: SESSION_ID }) }).catch(() => {}); }, 5000);
    </script>
</body>
</html>
