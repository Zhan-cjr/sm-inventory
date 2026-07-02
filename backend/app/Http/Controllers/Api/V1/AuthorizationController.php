<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthorizationController extends Controller
{
    public function requestAuthorization(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'action' => 'required|string',
            'details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $authRequest = AuthorizationRequest::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'cashier_id' => $user->id,
            'action' => $request->action,
            'details' => $request->details,
            'status' => 'PENDING',
            'expires_at' => Carbon::now()->addMinutes(10)
        ]);

        // Send Telegram Notification
        $token = env('TELEGRAM_BOT_TOKEN');
        if ($token) {
            $supervisors = \App\Models\User::whereNotNull('telegram_chat_id')
                ->where(function($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhereNull('branch_id');
                })
                ->whereJsonContains('pos_authorizations', $request->action)
                ->get();

            $branchName = $user->branch ? $user->branch->name : 'Pusat';
            $actionLabel = str_replace('_', ' ', strtoupper($request->action));
            
            $baseUrl = env('FRONTEND_URL', request()->getSchemeAndHttpHost());
            $link = rtrim($baseUrl, '/') . "/mobile/auth";
            
            $message = "🔔 <b>Permintaan Otorisasi Baru</b>\n\n";
            $message .= "<b>Cabang:</b> {$branchName}\n";
            $message .= "<b>Kasir:</b> {$user->name}\n";
            $message .= "<b>Tindakan:</b> {$actionLabel}\n\n";
            $message .= "Tautan: " . $link;

            foreach ($supervisors as $spv) {
                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $spv->telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            }
        }

        return response()->json(['data' => $authRequest], 201);
    }

    public function checkStatus($id)
    {
        $authRequest = AuthorizationRequest::find($id);
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($authRequest->status === 'PENDING' && $authRequest->expires_at < Carbon::now()) {
            $authRequest->update(['status' => 'REJECTED']); // Auto-reject if expired
        }

        return response()->json(['data' => $authRequest]);
    }

    public function getPendingRequests(Request $request)
    {
        $user = $request->user();
        
        $hasPosAuth = is_array($user->pos_authorizations) && count($user->pos_authorizations) > 0;

        // Ensure user has pos_authorizations to view pending requests
        if (!$hasPosAuth) {
            return response()->json(['error' => 'Unauthorized: Tidak memiliki hak akses otorisasi POS'], 403);
        }

        $query = AuthorizationRequest::with(['cashier:id,name', 'branch:id,name'])
            ->where('status', 'PENDING')
            ->whereIn('action', $user->pos_authorizations)
            ->where('expires_at', '>', Carbon::now());

        $branchId = $user->branch_id;
        
        // If user has no branch assigned (Pusat), they can filter by branch
        if (!$branchId) {
            $branchId = $request->query('branch_id');
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $requests]);
    }

    public function approveRequest(Request $request, $id)
    {
        $user = $request->user();
        $authRequest = AuthorizationRequest::find($id);
        
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($user->branch_id !== null && $authRequest->branch_id !== $user->branch_id) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak dapat menyetujui otorisasi di luar cabang Anda'], 403);
        }

        if (!$user->pos_authorizations || !in_array($authRequest->action, $user->pos_authorizations)) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak memiliki izin otorisasi untuk aksi ini'], 403);
        }

        if ($authRequest->status !== 'PENDING') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $authRequest->update([
            'status' => 'APPROVED',
            'supervisor_id' => $user->id
        ]);

        return response()->json(['data' => $authRequest]);
    }

    public function rejectRequest(Request $request, $id)
    {
        $user = $request->user();
        $authRequest = AuthorizationRequest::find($id);
        
        if (!$authRequest) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($user->branch_id !== null && $authRequest->branch_id !== $user->branch_id) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak dapat menolak otorisasi di luar cabang Anda'], 403);
        }

        if (!$user->pos_authorizations || !in_array($authRequest->action, $user->pos_authorizations)) {
            return response()->json(['error' => 'Akses ditolak: Anda tidak memiliki izin otorisasi untuk aksi ini'], 403);
        }

        if ($authRequest->status !== 'PENDING') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $authRequest->update([
            'status' => 'REJECTED',
            'supervisor_id' => $user->id
        ]);

        return response()->json(['data' => $authRequest]);
    }
}
