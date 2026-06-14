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

    private function extractCustomerName($data) {
        $customerName = $data['customer_name'] ?? null;
        $sn = $data['sn'] ?? null;
        if (!$customerName && $sn) {
            $parts = explode('/', $sn);
            if (count($parts) > 1) {
                foreach($parts as $part) {
                    $part = trim($part);
                    if (preg_match('/[A-Za-z]{3,}/', $part) && !preg_match('/^(R\d|B\d|S\d)/i', $part)) {
                        $customerName = str_ireplace('SN:', '', $part);
                        return trim($customerName);
                    }
                }
            }
        }
        return $customerName;
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
        $ppobData = [];

        DB::transaction(function () use ($validated, $user, &$syncedIds, &$conflicts, &$ppobData) {
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

                    $paymentMethod = $txData['paymentMethod'] ?? 'CASH';
                    $paymentDetails = null;

                    if (isset($txData['payments']) && is_array($txData['payments']) && count($txData['payments']) > 1) {
                        $paymentMethod = 'MULTI';
                        $paymentDetails = $txData['payments'];
                    } else if (isset($txData['payments']) && is_array($txData['payments']) && count($txData['payments']) === 1) {
                        $paymentMethod = $txData['payments'][0]['method'];
                        $paymentDetails = $txData['payments'];
                    }

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
                        'manual_discount' => $txData['manualDiscount'] ?? ($txData['discountAmount'] ?? 0),
                        'promo_discount' => $txData['promoDiscount'] ?? 0,
                        'final_amount' => $txData['finalAmount'] ?? $txData['totalAmount'],
                        'payment_method' => $paymentMethod,
                        'payment_details' => $paymentDetails,
                        'bank_id' => $txData['bankId'] ?? null,
                        'received_amount' => $txData['receivedAmount'] ?? 0,
                        'change_amount' => $txData['changeAmount'] ?? 0,
                        'sync_status' => 'SYNCED',
                        'local_transaction_id' => $txData['localId'],
                        'receipt_number' => $txData['receipt_number'] ?? ('SMI-' . strtoupper(substr(uniqid(), -6))),
                    ]);

                    if (isset($txData['payments']) && is_array($txData['payments'])) {
                        foreach ($txData['payments'] as $payment) {
                            if (($payment['method'] === 'VOUCHER' || $payment['method'] === 'MULTI') && isset($payment['voucherId'])) {
                                \App\Models\Voucher::where('id', $payment['voucherId'])->update([
                                    'is_used' => true,
                                    'used_at' => now(),
                                    'transaction_id' => $tx->id,
                                ]);
                            }

                            // Handle Point Redemption
                            if ($payment['method'] === 'POINT' && isset($payment['points_deducted'])) {
                                $customer = \App\Models\Customer::find($tx->customer_id);
                                if ($customer) {
                                    $customer->deductPoints(
                                        $payment['points_deducted'], 
                                        'REDEMPTION', 
                                        $tx->id, 
                                        "Penukaran Poin di Kasir: #{$tx->receipt_number}"
                                    );
                                }
                            }
                        }
                    }

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
                        $txItem = TransactionItem::create([
                            'transaction_id' => $tx->id,
                            'product_id' => $isService ? null : $item['productId'],
                            'service_id' => $isService ? $item['productId'] : null,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unitPrice'],
                            'discount_per_item' => ($item['manualDiscount'] ?? 0) + ($item['discountPerItem'] ?? 0),
                            'promotion_id' => $item['promotionId'] ?? $item['promotion_id'] ?? null,
                            'original_transaction_id' => $item['originalTransactionId'] ?? null,
                        ]);

                        if (!$isService && isset($item['productType']) && $item['productType'] === 'digital') {
                            $product = \App\Models\Product::find($item['productId']);
                            if ($product && !empty($product->ppob_sku)) {
                                $customerNo = $item['customerNo'] ?? null;
                                if ($customerNo) {
                                    $refId = $tx->receipt_number . '-' . strtoupper(substr(uniqid(), -4));
                                    $digiflazzService = new \App\Services\DigiflazzService();
                                    $res = $digiflazzService->topup($product->ppob_sku, $customerNo, $refId);

                                    $status = 'Pending';
                                    if (isset($res['data']['status'])) {
                                        $status = $res['data']['status'];
                                    }
                                    
                                    $customerName = isset($res['data']) ? $this->extractCustomerName($res['data']) : null;
                                    
                                    \App\Models\PpobTransaction::create([
                                        'transaction_id' => $tx->id,
                                        'ref_id' => $refId,
                                        'customer_no' => $customerNo,
                                        'customer_name' => $customerName,
                                        'buyer_sku_code' => $product->ppob_sku,
                                        'price' => $res['data']['price'] ?? 0,
                                        'status' => $status,
                                        'rc' => $res['data']['rc'] ?? null,
                                        'sn' => $res['data']['sn'] ?? null,
                                        'message' => $res['data']['message'] ?? null,
                                        'raw_response' => json_encode($res),
                                    ]);
                                    
                                    if (!isset($ppobData[$txData['localId']])) {
                                        $ppobData[$txData['localId']] = [];
                                    }
                                    $ppobData[$txData['localId']][] = [
                                        'productId' => $item['productId'],
                                        'sn' => $res['data']['sn'] ?? null,
                                        'status' => $status,
                                        'message' => $res['data']['message'] ?? null,
                                    ];
                                }
                            }
                        }
                    }

                    // Panggil AccountingService untuk catat Jurnal
                    $accountingService = new \App\Services\AccountingService();
                    $accountingService->recordTransactionJournal($tx);

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
            'ppobData' => $ppobData,
        ]);
    }
}
