<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\Log;

class AmaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Based on PDF: HTTP GET (or POST fallback)
        // http://<ip client>?trxid=<trxid>&userid=<userid>&sn=<serialnumber>&status=<status transaksi>&msg=<pesan>
        
        $trxid = $request->input('trxid');
        $userid = $request->input('userid');
        $sn = $request->input('sn') ?? $request->input('serialnumber');
        $status = (string) $request->input('status');
        $msg = $request->input('msg') ?? $request->input('message');

        if (!$trxid) {
            return response()->json(['error' => 'Missing trxid'], 400);
        }

        try {
            $transaction = PpobTransaction::where('ref_id', $trxid)->first();
            
            if ($transaction) {
                // Map AMA status to our standard status
                // '00' = Success, '04' = Failed, '68' = Pending
                $mappedStatus = 'Pending';
                if ($status === '00' || strtolower($status) === 'success') {
                    $mappedStatus = 'Sukses';
                } elseif (in_array($status, ['03', '04', '05', '06', '63', '65', '67', '99']) || strtolower($status) === 'failed') {
                    $mappedStatus = 'Gagal';
                } elseif ($status === '68' || strtolower($status) === 'pending') {
                    $mappedStatus = 'Pending';
                } else {
                    $mappedStatus = $status;
                }

                $transaction->update([
                    'status' => $mappedStatus,
                    'sn' => $sn,
                    'message' => $msg,
                    'raw_response' => json_encode($request->all())
                ]);

                $branchId = $transaction->transaction?->branch_id;
                if ($branchId) {
                    event(new \App\Events\PpobStatusUpdated($branchId, $transaction->fresh()->load('transaction.items.product')));
                }

                Log::info("AMA Webhook Received for trxid: {$trxid}, Status: {$status} ({$mappedStatus})");
                
                // Return Ack OK to partner
                return response('OK', 200)->header('Content-Type', 'text/plain');
            } else {
                Log::warning("AMA Webhook: Transaction with trxid {$trxid} not found.");
                return response('OK', 200)->header('Content-Type', 'text/plain'); // Still ack OK
            }
        } catch (\Exception $e) {
            Log::error('AMA Webhook Error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
}
