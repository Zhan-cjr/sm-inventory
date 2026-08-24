<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wireless Scanner Gun - SM Inventory</title>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #090d16; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .header { padding: 10px 14px; background: #111827; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1f2937; }
        .logo { font-weight: 800; font-size: 14px; color: #38bdf8; }
        .session-badge { font-size: 11px; background: #0284c7; padding: 3px 8px; border-radius: 999px; font-weight: 700; }
        .viewport-wrapper { position: relative; width: 100%; height: 340px; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        #videoFeed { width: 100%; height: 100%; object-fit: cover; }
        .reticle { position: absolute; width: 250px; height: 250px; border: 2px solid rgba(56, 189, 248, 0.9); border-radius: 18px; box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.45); pointer-events: none; z-index: 10; transition: border-color 0.2s; }
        .reticle.found { border-color: #22c55e; box-shadow: 0 0 0 4000px rgba(34, 197, 94, 0.2); }
        .laser-sweep { position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, #ef4444, #f87171, #ef4444, transparent); box-shadow: 0 0 12px #ef4444; animation: sweep 1.8s infinite ease-in-out; }
        @keyframes sweep { 0%, 100% { top: 6%; } 50% { top: 94%; } }
        .torch-btn { position: absolute; top: 12px; right: 12px; z-index: 20; background: rgba(17, 24, 39, 0.8); border: 1px solid #374151; color: #f8fafc; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer; backdrop-filter: blur(4px); }
        .controls { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
        .status-card { background: #111827; border: 1px solid #1f2937; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .status-dot.scanning { animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .log-box { background: #000; border: 1px solid #1f2937; border-radius: 8px; padding: 8px 12px; max-height: 120px; overflow-y: auto; font-size: 11px; font-family: monospace; }
        .log-item { padding: 3px 0; border-bottom: 1px solid #111827; color: #94a3b8; }
        .log-item.success { color: #4ade80; }
        .tips-text { font-size: 11.5px; color: #64748b; text-align: center; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">⚡ WIRELESS SCANNER GUN</div>
        <div class="session-badge">SESI: {{ $session }}</div>
    </div>

    <div class="viewport-wrapper">
        <video id="videoFeed" playsinline autoplay muted></video>
        <button type="button" class="torch-btn" id="torchBtn" onclick="toggleTorch()" title="Nyalakan Senter">💡</button>
        <div class="reticle" id="reticleBox"><div class="laser-sweep"></div></div>
    </div>

    <div class="controls">
        <div class="status-card">
            <div class="status-dot scanning" id="statusDot"></div>
            <div>
                <div style="font-weight: 700; font-size: 13px;" id="statusTitle">🔍 Memindai QR Code...</div>
                <div style="font-size: 11px; color: #94a3b8;" id="statusSubtitle">Arahkan kotak ke QR e-Faktur di meja</div>
            </div>
        </div>

        <div class="tips-text">
            💡 <strong>Tips:</strong> Nyalakan tombol lampu di atas jika ruangan redup atau ada bayangan pada kertas faktur.
        </div>

        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Histori Tembakan (<span id="scanCount">0</span> Faktur)</div>
        <div class="log-box" id="logBox"><div class="log-item">Menunggu tembakan pertama...</div></div>
    </div>

    <canvas id="hiddenCanvas" style="display: none;"></canvas>

    <script>
        const SESSION_ID = @json($session);
        const video = document.getElementById('videoFeed');
        const reticleBox = document.getElementById('reticleBox');
        const canvas = document.getElementById('hiddenCanvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        let scanCount = 0, lastScannedText = '', lastScanTime = 0, mediaTrack = null, isTorchOn = false;
        let isProcessing = false, nativeDetector = null;

        if ('BarcodeDetector' in window) {
            try { nativeDetector = new BarcodeDetector({ formats: ['qr_code'] }); } catch(e) {}
        }

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

            reticleBox.classList.add('found');
            setTimeout(() => reticleBox.classList.remove('found'), 1200);

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
                document.getElementById('statusTitle').textContent = '🔍 Memindai QR Code...';
                document.getElementById('statusSubtitle').textContent = 'Arahkan kotak ke QR e-Faktur di meja';
            }, 2000);
        }

        async function scanLoop() {
            if (video.readyState === video.HAVE_ENOUGH_DATA && !isProcessing) {
                isProcessing = true;
                let foundCode = null;

                // 1. Hardware Accelerated Native BarcodeDetector (Android Chrome GPU)
                if (nativeDetector) {
                    try {
                        const barcodes = await nativeDetector.detect(video);
                        if (barcodes.length > 0 && barcodes[0].rawValue) {
                            foundCode = barcodes[0].rawValue;
                        }
                    } catch(e) {}
                }

                // 2. High Resolution Multi-Pass jsQR Fallback
                if (!foundCode && window.jsQR) {
                    try {
                        const vw = video.videoWidth, vh = video.videoHeight;
                        if (vw > 0 && vh > 0) {
                            canvas.width = vw; canvas.height = vh;
                            ctx.drawImage(video, 0, 0, vw, vh);
                            const imgData = ctx.getImageData(0, 0, vw, vh);
                            const code = jsQR(imgData.data, vw, vh, { inversionAttempts: "attemptBoth" });
                            if (code && code.data) foundCode = code.data;
                        }
                    } catch(e) {}
                }

                if (foundCode) onSuccessfulScan(foundCode);
                isProcessing = false;
            }
            requestAnimationFrame(scanLoop);
        }

        async function initCamera() {
            try {
                const constraints = {
                    video: {
                        facingMode: { ideal: "environment" },
                        width: { ideal: 1920, min: 1280 },
                        height: { ideal: 1080, min: 720 },
                        focusMode: { ideal: "continuous" },
                        advanced: [{ focusMode: "continuous" }]
                    },
                    audio: false
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;
                mediaTrack = stream.getVideoTracks()[0];

                video.onloadedmetadata = () => {
                    video.play();
                    requestAnimationFrame(scanLoop);
                };

                // Check torch capability
                if (mediaTrack && mediaTrack.getCapabilities && mediaTrack.getCapabilities().torch) {
                    document.getElementById('torchBtn').style.display = 'flex';
                }
            } catch (err) {
                document.getElementById('statusTitle').textContent = '⚠️ Izin Kamera Diperlukan';
                document.getElementById('statusSubtitle').textContent = 'Izinkan akses kamera pada browser HP Anda.';
            }
        }

        async function toggleTorch() {
            if (!mediaTrack) return;
            try {
                isTorchOn = !isTorchOn;
                await mediaTrack.applyConstraints({ advanced: [{ torch: isTorchOn }] });
                document.getElementById('torchBtn').textContent = isTorchOn ? '🔦' : '💡';
                document.getElementById('torchBtn').style.background = isTorchOn ? '#eab308' : 'rgba(17,24,39,0.8)';
            } catch (e) {}
        }

        window.addEventListener('DOMContentLoaded', initCamera);
        setInterval(() => { fetch('/scanner-gun/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ session: SESSION_ID }) }).catch(() => {}); }, 5000);
    </script>
</body>
</html>
