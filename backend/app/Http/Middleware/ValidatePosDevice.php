<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\PosDevice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePosDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Bypass validation for public paths
        $bypassPaths = [
            '*api/v1/devices/handshake',
            '*api/v1/login',
            '*api/v1/ecommerce/*', // E-Commerce public endpoints
            '*api/v1/webhook/*', // Bypass untuk Webhook Pihak Ketiga (Telegram/Payment)
            '*print/transaction/*',
            '*print/report/*',
            '*up' // Health check
        ];

        foreach ($bypassPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // 2. We only validate API requests starting with api/v1/
        if (!$request->is('api/v1/*')) {
            return $next($request);
        }

        // 3. Extract the X-Device-UUID header
        $deviceUuid = $request->header('X-Device-UUID');

        if (!$deviceUuid) {
            return response()->json([
                'status' => 'UNAUTHORIZED_DEVICE',
                'message' => 'Akses Ditolak: Device UUID tidak disertakan.'
            ], 403);
        }

        // 4. Query the device (cached as plain array in Redis for 5 minutes — Laravel 13 safe; auto-invalidated on PosDevice save/delete)
        $cacheKey = PosDevice::cacheKey($deviceUuid);
        $device = null;
        try {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached['id'])) {
                $device = (new PosDevice())->newFromBuilder($cached);
            }
        } catch (\Throwable) {
            $device = null;
        }

        if (!$device) {
            $device = PosDevice::where('device_uuid', $deviceUuid)->first();
            if ($device) {
                try {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $device->getAttributes(), 300);
                } catch (\Throwable) {
                    // Abaikan jika cache sementara tidak tersedia
                }
            }
        }

        if (!$device) {
            return response()->json([
                'status' => 'UNREGISTERED_DEVICE',
                'message' => 'Akses Ditolak: Perangkat belum terdaftar.'
            ], 403);
        }

        if ($device->status !== 'APPROVED') {
            $statusMsg = $device->status === 'BLOCKED' 
                ? 'Perangkat ini telah diblokir.' 
                : 'Perangkat sedang menunggu persetujuan Admin/Owner.';

            return response()->json([
                'status' => $device->status . '_DEVICE',
                'message' => 'Akses Ditolak: ' . $statusMsg
            ], 403);
        }

        // 5. Store device information in request attributes for controller usage
        $request->attributes->set('pos_device', $device);

        return $next($request);
    }
}
