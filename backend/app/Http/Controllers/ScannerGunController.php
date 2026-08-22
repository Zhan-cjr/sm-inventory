<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScannerGunController extends Controller
{
    /**
     * Tampilan Mobile Scanner Gun untuk Handphone
     */
    public function index(Request $request): View
    {
        $session = $request->query('session', 'default');
        return view('scanner.gun', compact('session'));
    }

    /**
     * Generate QR Code dengan fallback aman
     */
    public function qr(Request $request)
    {
        $url = $request->query('url', url('/'));
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($url);
                return response($svg, 200)->header('Content-Type', 'image/svg+xml');
            } catch (\Throwable $e) {
                // Fallback to QR API
            }
        }
        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);
        return redirect()->away($qrApiUrl);
    }

    /**
     * Menerima hasil scan dari kamera handphone
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'session' => 'required|string',
            'code' => 'required|string',
        ]);

        $session = $request->input('session');
        $code = trim($request->input('code'));

        // Simpan ke Cache selama 60 detik untuk diambil oleh PC
        Cache::put("scanner_gun_data_{$session}", $code, 60);
        Cache::put("scanner_gun_active_{$session}", now()->timestamp, 60);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil terkirim ke layar komputer',
            'session' => $session,
        ]);
    }

    /**
     * Polling dari browser PC untuk mengambil hasil scan terbaru
     */
    public function poll(Request $request): JsonResponse
    {
        $session = $request->query('session');
        if (empty($session)) {
            return response()->json(['code' => null, 'connected' => false]);
        }

        $code = Cache::pull("scanner_gun_data_{$session}");
        $lastActive = Cache::get("scanner_gun_active_{$session}");
        $isConnected = $lastActive && (now()->timestamp - $lastActive <= 15);

        return response()->json([
            'code' => $code,
            'connected' => (bool) $isConnected,
        ]);
    }

    /**
     * Heartbeat dari HP agar PC tahu HP aktif terhubung
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $session = $request->input('session');
        if ($session) {
            Cache::put("scanner_gun_active_{$session}", now()->timestamp, 30);
        }

        return response()->json(['status' => 'ok']);
    }
}
