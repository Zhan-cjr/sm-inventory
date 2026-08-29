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
        // Based on PDF: HTTP GET
        // http://<ip client>?trxid=<trxid>&userid=<userid>&sn=<serialnumber>&status=<status transaksi>&msg=<pesan>
        
        $trxid = $request->query('trxid');
        $userid = $request->query('userid');
        $sn = $request->query('sn');
        $status = $request->query('status');
        $msg = $request->query('msg');

        if (!$trxid) {
            return response()->json(['error' => 'Missing trxid'], 400);
        }

        try {
            $transaction = PpobTransaction::where('ref_id', $trxid)->first();
            
            if ($transaction) {
                // Map AMA status to our standard status if needed
                // E.g., '00' = Success, '04' = Failed, '68' = Pending
                $mappedStatus = 'Pending';
                if ($status === '00' || $status === 'Success') {
                    $mappedStatus = 'Gagal'; // Default mapping just in case
                }
                
                // Better mapping based on PDF Response Code table
                if ($status === '00') {
                    $mappedStatus = 'Sukses';
                } elseif (in_array($status, ['03', '04', '05', '06', '63', '65', '67', '99'])) {
                    $mappedStatus = 'Gagal';
                } elseif ($status === '68') {
                    $mappedStatus = 'Pending';
                } else {
                    // Fallback to literal status if unknown
                    $mappedStatus = $status;
                }

                $transaction->update([
                    'status' => $mappedStatus,
                    'sn' => $sn,
                    'message' => $msg,
                    'raw_response' => json_encode($request->all())
                ]);

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
