<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wireless Scanner Gun - SM Inventory</title>
    <script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #090d16; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .header { padding: 10px 14px; background: #111827; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1f2937; }
        .logo { font-weight: 800; font-size: 14px; color: #38bdf8; }
        .session-badge { font-size: 11px; background: #0284c7; padding: 3px 8px; border-radius: 999px; font-weight: 700; }
        .viewport-wrapper { position: relative; width: 100%; height: 320px; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        #videoFeed { width: 100%; height: 100%; object-fit: cover; display: none; }
        #previewImg { width: 100%; height: 100%; object-fit: contain; display: none; }
        .viewport-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #94a3b8; font-size: 13px; text-align: center; padding: 20px; }
        .reticle { position: absolute; width: 230px; height: 230px; border: 2px solid rgba(56, 189, 248, 0.7); border-radius: 16px; box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.5); pointer-events: none; z-index: 10; }
        .laser-sweep { position: absolute; top: 0; left: 0; right: 0; height: 2.5px; background: linear-gradient(90deg, transparent, #ef4444, #f87171, #ef4444, transparent); box-shadow: 0 0 10px #ef4444; animation: sweep 2s infinite ease-in-out; }
        @keyframes sweep { 0%, 100% { top: 8%; } 50% { top: 92%; } }
        .controls { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
        .status-card { background: #111827; border: 1px solid #1f2937; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .btn-snap { background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #fff; border: none; border-radius: 10px; padding: 14px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-live { background: #1e293b; color: #cbd5e1; border: 1px solid #334155; border-radius: 8px; padding: 9px; font-size: 12px; font-weight: 600; cursor: pointer; }
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
    <div class="viewport-wrapper" onclick="triggerCamera()">
        <video id="videoFeed" playsinline autoplay muted></video>
        <img id="previewImg" alt="Pratinjau Foto" />
        <div class="viewport-placeholder" id="placeholderView">
            <span style="font-size: 40px;">📸</span>
            <span style="font-weight: 700; color: #f8fafc;">Sentuh untuk Buka Kamera</span>
            <span style="font-size: 11px; color: #64748b;">Arahkan ke QR Faktur Pajak di meja</span>
        </div>
        <div class="reticle"><div class="laser-sweep"></div></div>
    </div>
    <div class="controls">
        <div class="status-card">
            <div class="status-dot"></div>
            <div>
                <div style="font-weight: 700; font-size: 13px;" id="statusTitle">Siap Menembak Faktur</div>
                <div style="font-size: 11px; color: #94a3b8;" id="statusSubtitle">Tekan tombol biru di bawah atau ketuk layar</div>
            </div>
        </div>

        <input type="file" id="cameraSnapInput" accept="image/*" capture="environment" style="display:none;" onchange="processSnappedImage(this)">
        <button class="btn-snap" onclick="triggerCamera()">
            📸 TEMBAK FAKTUR PAJAK (SCAN)
        </button>
        <button class="btn-live" id="btnStartLive" onclick="startCameraStream()">
            ▶️ Coba Mode Kamera Live Stream
        </button>

        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Histori Tembakan (<span id="scanCount">0</span> Faktur)</div>
        <div class="log-box" id="logBox"><div class="log-item">Menunggu tembakan pertama...</div></div>
    </div>
    <script>
        const SESSION_ID = @json($session);
        const video = document.getElementById('videoFeed');
        const previewImg = document.getElementById('previewImg');
        const placeholderView = document.getElementById('placeholderView');
        let scanCount = 0, lastScannedText = '', lastScanTime = 0, zxingReader = null;

        if (window.ZXing) { zxingReader = new ZXing.BrowserQRCodeReader(); }

        function triggerCamera() {
            document.getElementById('cameraSnapInput').click();
        }

        function playFeedback() {
            try {
                const ac = new (window.AudioContext || window.webkitAudioContext)();
                const o = ac.createOscillator(), g = ac.createGain();
                o.type = 'sine'; o.frequency.setValueAtTime(1400, ac.currentTime);
                g.gain.setValueAtTime(0.3, ac.currentTime);
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
                document.getElementById('statusTitle').textContent = 'Siap Tembak Faktur Berikutnya';
                document.getElementById('statusSubtitle').textContent = 'Arahkan HP ke QR e-Faktur berikutnya';
            }, 2500);
        }

        async function processSnappedImage(input) {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];
            document.getElementById('statusTitle').textContent = '⏳ Membaca QR Code...';
            document.getElementById('statusSubtitle').textContent = 'Sedang menganalisis piksel...';

            const imgUrl = URL.createObjectURL(file);
            placeholderView.style.display = 'none';
            video.style.display = 'none';
            previewImg.src = imgUrl;
            previewImg.style.display = 'block';

            let decoded = null;

            // 1. Native BarcodeDetector (Paling Akurat di Android Chrome)
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
            if (!decoded) {
                try {
                    decoded = await decodeWithJsQr(imgUrl);
                } catch (e) {}
            }

            // 3. ZXing Fallback
            if (!decoded && zxingReader) {
                try {
                    const res = await zxingReader.decodeFromImageUrl(imgUrl);
                    if (res && res.getText()) decoded = res.getText();
                } catch (e) {}
            }

            if (decoded) {
                onSuccessfulScan(decoded);
            } else {
                document.getElementById('statusTitle').textContent = '❌ QR Belum Terdeteksi';
                document.getElementById('statusSubtitle').textContent = 'Pastikan foto QR code fokus dan tidak blur.';
            }

            input.value = '';
        }

        function decodeWithJsQr(imgUrl) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const scales = [1000, 600, img.width];
                    for (let maxDim of scales) {
                        let w = img.width, h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
                            else { w = Math.round((w * maxDim) / h); h = maxDim; }
                        }
                        const c = document.createElement('canvas');
                        c.width = w; c.height = h;
                        const ctx = c.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        const idata = ctx.getImageData(0, 0, w, h);
                        if (window.jsQR) {
                            const code = jsQR(idata.data, w, h, { inversionAttempts: "attemptBoth" });
                            if (code && code.data) return resolve(code.data);
                        }
                    }
                    resolve(null);
                };
                img.onerror = () => resolve(null);
                img.src = imgUrl;
            });
        }

        async function startCameraStream() {
            try {
                if (zxingReader) {
                    await zxingReader.decodeFromVideoDevice(undefined, 'videoFeed', (result) => {
                        if (result) onSuccessfulScan(result.getText());
                    });
                    placeholderView.style.display = 'none';
                    previewImg.style.display = 'none';
                    video.style.display = 'block';
                    document.getElementById('statusTitle').textContent = 'Kamera Live Stream Aktif';
                    document.getElementById('btnStartLive').style.display = 'none';
                }
            } catch (err) {
                document.getElementById('statusTitle').textContent = 'Gunakan Mode Tombol Tembak';
            }
        }

        setInterval(() => { fetch('/scanner-gun/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ session: SESSION_ID }) }).catch(() => {}); }, 5000);
    </script>
</body>
</html>
