<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\Log;

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $secret = env('DIGIFLAZZ_WEBHOOK_SECRET'); 
        
        $signature = $request->header('X-Hub-Signature');
        
        // Very basic validation - ideally use hash_hmac to verify signature
        if (!$signature) {
            return response()->json(['error' => 'No signature provided'], 401);
        }

        try {
            $data = $payload['data'] ?? null;
            if (!$data) {
                return response()->json(['error' => 'Invalid payload format'], 400);
            }

            $refId = $data['ref_id'] ?? null;
            $status = $data['status'] ?? null;
            $sn = $data['sn'] ?? null;
            $message = $data['message'] ?? null;
            $rc = $data['rc'] ?? null;

            $customerName = $data['customer_name'] ?? null;
            if (!$customerName && $sn) {
                $parts = explode('/', $sn);
                if (count($parts) > 1) {
                    foreach($parts as $part) {
                        $part = trim($part);
                        if (preg_match('/[A-Za-z]{3,}/', $part) && !preg_match('/^(R\d|B\d|S\d)/i', $part)) {
                            $customerName = str_ireplace('SN:', '', $part);
                            $customerName = trim($customerName);
                            break;
                        }
                    }
                }
            }

            if ($refId) {
                $transaction = PpobTransaction::where('ref_id', $refId)->first();
                if ($transaction) {
                    $updateData = [
                        'status' => $status,
                        'sn' => $sn,
                        'message' => $message,
                        'rc' => $rc,
                        'raw_response' => $payload
                    ];
                    if ($customerName) {
                        $updateData['customer_name'] = $customerName;
                    }
                    $transaction->update($updateData);

                    Log::info("Digiflazz Webhook Received for ref_id: {$refId}, Status: {$status}");
                } else {
                    Log::warning("Digiflazz Webhook: Transaction with ref_id {$refId} not found.");
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Digiflazz Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
