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

                    $shiftId = $txData['shiftId'] ?? null;
                    if (!$shiftId && !empty($txData['terminalId'])) {
                        // Find any open shift on this terminal for this cashier
                        $activeShift = \App\Models\Shift::where('terminal_id', $txData['terminalId'])
                            ->where('user_id', $user->id)
                            ->where('status', 'OPEN')
                            ->first();
                        
                        if ($activeShift) {
                            $shiftId = $activeShift->id;
                        }
                    }

                    $txDate = $txData['transactionDate'] ?? $txData['transaction_date'] ?? $txData['date'] ?? now();

                    $tx = Transaction::create([
                        'organization_id' => $user->organization_id,
                        'branch_id' => $validated['branchId'],
                        'terminal_id' => $txData['terminalId'] ?? null,
                        'customer_id' => $txData['customerId'] ?? null,
                        'shift_id' => $shiftId,
                        'transaction_type' => $txData['transaction_type'] ?? ($txData['finalAmount'] < 0 ? 'RETURN' : 'SALES'),
                        'transaction_date' => $txDate,
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
                        'receipt_number' => $txData['receipt_number'] ?? ('SMI-' . strtoupper(substr(uniqid(), -6))),
                    ]);

                    // Update customer points if applicable
                    if ($tx->customer_id) {
                        $customer = \App\Models\Customer::find($tx->customer_id);
                        if ($customer) {
                            $pointConversionRate = 1000;
                            if (auth()->user()) {
                                $pointConversionRate = auth()->user()->organization?->point_conversion_rate ?? 1000;
                            } else {
                                $pointConversionRate = \App\Models\Organization::first()?->point_conversion_rate ?? 1000;
                            }
                            $earnedPoints = floor($tx->final_amount / $pointConversionRate);
                            if ($earnedPoints > 0) {
                                $customer->addPoints($earnedPoints, 'TRANSACTION', $tx->id, "Poin Belanja POS (Offline): #{$tx->receipt_number}");
                            }
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
                            'discount_per_item' => $item['manualDiscount'] ?? 0,
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
