<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use Illuminate\Http\Request;

class PosDeviceController extends Controller
{
    public function handshake(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required|string',
            'name' => 'nullable|string',
        ]);

        $deviceUuid = $request->device_uuid;
        $userAgent = $request->header('User-Agent');

        $device = PosDevice::where('device_uuid', $deviceUuid)->first();

        if (!$device) {
            // Register a new device with PENDING status
            $device = PosDevice::create([
                'device_uuid' => $deviceUuid,
                'name' => $request->name ?: 'Device Baru (Belum Dinamai)',
                'user_agent' => $userAgent,
                'status' => 'PENDING',
            ]);

            return response()->json([
                'status' => 'PENDING',
                'message' => 'Perangkat kasir berhasil didaftarkan. Harap hubungi Admin/Owner untuk melakukan persetujuan (approval).',
                'device_uuid' => $device->device_uuid,
                'device_name' => $device->name,
            ]);
        }

        // Handle existing device states
        if ($device->status === 'PENDING') {
            return response()->json([
                'status' => 'PENDING',
                'message' => 'Perangkat kasir sedang menunggu persetujuan Admin/Owner.',
                'device_uuid' => $device->device_uuid,
                'device_name' => $device->name,
            ]);
        }

        if ($device->status === 'BLOCKED') {
            return response()->json([
                'status' => 'BLOCKED',
                'message' => 'Perangkat kasir ini telah diblokir. Akses ditolak!',
                'device_uuid' => $device->device_uuid,
            ]);
        }

        if ($device->status === 'APPROVED') {
            // Load relations to get names
            $device->load(['branch', 'terminal']);

            return response()->json([
                'status' => 'APPROVED',
                'message' => 'Perangkat telah disetujui.',
                'device_uuid' => $device->device_uuid,
                'device_name' => $device->name,
                'branch_id' => $device->branch_id,
                'branch_name' => $device->branch?->name,
                'terminal_id' => $device->terminal_id,
                'terminal_name' => $device->terminal?->name,
            ]);
        }

        return response()->json([
            'status' => 'ERROR',
            'message' => 'Status perangkat tidak valid.',
        ], 400);
    }
}
