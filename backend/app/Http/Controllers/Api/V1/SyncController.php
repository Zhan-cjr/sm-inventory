<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    private function getUser() {
        return auth()->user() ?? (object)[
            'id' => '00000000-0000-0000-0000-000000000001',
            'organization_id' => \App\Models\Organization::first()->id,
            'branch_id' => '00000000-0000-0000-0000-000000000002',
            'role' => 'MANAGER'
        ];
    }

    public function batchSync(Request $request)
    {
        $validated = $request->validate([
            'transactions' => 'required|array|min:1',
            'deviceId' => 'required|string',
            'branchId' => 'required|string',
        ]);

        $user = $this->getUser();
        $syncedIds = [];
        $conflicts = [];

        DB::transaction(function () use ($validated, $user, &$syncedIds, &$conflicts) {
            foreach ($validated['transactions'] as $txData) {
                try {
                    $existing = Transaction::where('local_transaction_id', $txData['localId'])->first();

                    if ($existing) {
                        $syncedIds[] = $txData['localId'];
                        continue;
                    }

                    $tx = Transaction::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $validated['branchId'],
                        'terminal_id' => $txData['terminalId'] ?? null,
                        'customer_id' => $txData['customerId'] ?? null,
                        'shift_id' => $txData['shiftId'] ?? null,
                        'transaction_type' => 'SALES',
                        'transaction_date' => now(),
                        'cashier_id' => $user->id,
                        'total_amount' => $txData['totalAmount'],
                        'discount_amount' => $txData['discountAmount'] ?? 0,
                        'final_amount' => $txData['finalAmount'] ?? $txData['totalAmount'],
                        'payment_method' => $txData['paymentMethod'],
                        'bank_id' => $txData['bankId'] ?? null,
                        'received_amount' => $txData['receivedAmount'] ?? 0,
                        'change_amount' => $txData['changeAmount'] ?? 0,
                        'sync_status' => 'SYNCED',
                        'local_transaction_id' => $txData['localId'],
                    ]);

                    // Update customer points if applicable
                    if ($tx->customer_id) {
                        $customer = \App\Models\Customer::find($tx->customer_id);
                        if ($customer) {
                            $earnedPoints = floor($tx->final_amount / 1000); // 1 point per 1000
                            $customer->increment('points', $earnedPoints);
                        }
                    }

                    foreach ($txData['items'] as $item) {
                        $isService = isset($item['isService']) && $item['isService'] === true;

                        // Stock deduction is handled by TransactionItemObserver automatically
                        TransactionItem::create([
                            'transaction_id' => $tx->id,
                            'product_id' => $isService ? null : $item['productId'],
                            'service_id' => $isService ? $item['productId'] : null,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unitPrice'],
                        ]);
                    }

                    $syncedIds[] = $txData['localId'];

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Sync error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                    $conflicts[] = [
                        'localId' => $txData['localId'],
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'syncedIds' => $syncedIds,
            'conflicts' => $conflicts,
            'syncedCount' => count($syncedIds),
            'conflictCount' => count($conflicts),
        ]);
    }
}
