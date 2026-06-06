<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use App\Models\StockBatch;
use App\Models\StockBatchDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'final_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|not_in:0',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount_per_item' => 'nullable|numeric',
            'items.*.customer_no' => 'nullable|string',
        ]);

        $user = $this->getUser();
        $transaction = null;

        try {
            DB::transaction(function () use ($validated, $user, $request, &$transaction) {
                $paymentMethod = $validated['payment_method'] ?? 'CASH';
                $paymentDetails = null;
                $requestPayments = $request->input('payments');

                if (is_array($requestPayments) && count($requestPayments) > 1) {
                    $paymentMethod = 'MULTI';
                    $paymentDetails = json_encode($requestPayments);
                } else if (is_array($requestPayments) && count($requestPayments) === 1) {
                    $paymentMethod = $requestPayments[0]['method'];
                    $paymentDetails = json_encode($requestPayments);
                }

                $transaction = Transaction::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'transaction_type' => 'SALES',
                    'transaction_date' => now(),
                    'cashier_id' => $user->id,
                    'total_amount' => $validated['total_amount'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'manual_discount' => $request->input('manualDiscount', $validated['discount_amount'] ?? 0),
                    'promo_discount' => $request->input('promoDiscount', 0),
                    'final_amount' => $validated['final_amount'] ?? 0,
                    'payment_method' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                    'sync_status' => 'SYNCED',
                    'receipt_number' => $request->receipt_number ?? ('SMI-' . strtoupper(substr(uniqid(), -6))),
                ]);

                if (is_array($requestPayments)) {
                    foreach ($requestPayments as $payment) {
                        if (($payment['method'] === 'VOUCHER' || $payment['method'] === 'MULTI') && isset($payment['voucherId'])) {
                            \App\Models\Voucher::where('id', $payment['voucherId'])->update([
                                'is_used' => true,
                                'used_at' => now(),
                                'transaction_id' => $transaction->id,
                            ]);
                        }
                        
                        // Handle Point Redemption
                        if ($payment['method'] === 'POINT' && isset($payment['points_deducted'])) {
                            $customer = \App\Models\Customer::find($transaction->customer_id);
                            if ($customer) {
                                $customer->deductPoints(
                                    $payment['points_deducted'], 
                                    'REDEMPTION', 
                                    $transaction->id, 
                                    "Penukaran Poin di Kasir: #{$transaction->receipt_number}"
                                );
                            }
                        }
                    }
                }

                // Update customer points if applicable
                if ($transaction->customer_id) {
                    $customer = \App\Models\Customer::find($transaction->customer_id);
                    if ($customer) {
                        $pointConversionRate = $user->organization?->point_conversion_rate ?? 1000;
                        $earnedPoints = floor($transaction->final_amount / $pointConversionRate);
                        if ($earnedPoints > 0) {
                            $customer->addPoints($earnedPoints, 'TRANSACTION', $transaction->id, "Poin Belanja POS: #{$transaction->receipt_number}");
                        }
                    }
                }

                foreach ($validated['items'] as $item) {
                    $txItem = TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_per_item' => $item['discount_per_item'] ?? 0,
                    ]);

                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product && $product->product_type === 'digital' && !empty($product->ppob_sku)) {
                        $customerNo = $item['customer_no'] ?? null;
                        if ($customerNo) {
                            $refId = $transaction->receipt_number . '-' . strtoupper(substr(uniqid(), -4));
                            $digiflazzService = new \App\Services\DigiflazzService();
                            $res = $digiflazzService->topup($product->ppob_sku, $customerNo, $refId);

                            $status = 'Pending';
                            if (isset($res['data']['status'])) {
                                $status = $res['data']['status'];
                            }
                            
                            $customerName = isset($res['data']) ? $this->extractCustomerName($res['data']) : null;
                            
                            \App\Models\PpobTransaction::create([
                                'transaction_id' => $transaction->id,
                                'ref_id' => $refId,
                                'customer_no' => $customerNo,
                                'customer_name' => $customerName,
                                'buyer_sku_code' => $product->ppob_sku,
                                'price' => $res['data']['price'] ?? 0,
                                'status' => $status,
                                'rc' => $res['data']['rc'] ?? null,
                                'sn' => $res['data']['sn'] ?? null,
                                'message' => $res['data']['message'] ?? null,
                                'raw_response' => json_encode($res)
                            ]);
                        }
                    }
                }

                // Panggil AccountingService untuk catat Jurnal
                $accountingService = new \App\Services\AccountingService();
                $accountingService->recordTransactionJournal($transaction);

                Cache::tags(['inventory', "branch:{$user->branch_id}"])->flush();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction' => [
                    'id' => $transaction->id,
                    'receipt_number' => $transaction->receipt_number,
                    'transaction_date' => $transaction->transaction_date,
                    'total_amount' => $transaction->total_amount,
                    'final_amount' => $transaction->final_amount,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Transaction creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    private function refreshPendingPpobTransactions($transaction)
    {
        if (!$transaction || !$transaction->ppobTransactions) return;

        $hasPending = false;
        $digiflazz = null;

        foreach ($transaction->ppobTransactions as $ppob) {
            if ($ppob->status === 'Pending') {
                if (!$digiflazz) $digiflazz = new \App\Services\DigiflazzService();
                $hasPending = true;

                $res = $digiflazz->topup($ppob->buyer_sku_code, $ppob->customer_no, $ppob->ref_id);

                if (isset($res['data'])) {
                    $newStatus = $res['data']['status'] ?? 'Pending';
                    if ($newStatus !== 'Pending') {
                        $updateData = [
                            'status' => $newStatus,
                            'sn' => $res['data']['sn'] ?? $ppob->sn,
                            'rc' => $res['data']['rc'] ?? $ppob->rc,
                            'message' => $res['data']['message'] ?? $ppob->message,
                            'raw_response' => json_encode($res),
                        ];
                        
                        $customerName = $this->extractCustomerName($res['data']);
                        if ($customerName) {
                            $updateData['customer_name'] = $customerName;
                        }
                        
                        $ppob->update($updateData);
                    }
                }
            }
        }

        if ($hasPending) {
            $transaction->load('ppobTransactions');
        }
    }

    public function getLatestTransaction(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $terminalId = $request->header('X-Terminal-ID');
        
        $query = Transaction::where('branch_id', $user->branch_id)
            ->with(['items.product', 'ppobTransactions']);
            
        if ($terminalId) {
            $query->where('terminal_id', $terminalId);
        }

        $transaction = $query->latest('created_at')->first();

        if (!$transaction) {
            return response()->json(['message' => 'Belum ada transaksi di kassa ini.'], 404);
        }

        $this->refreshPendingPpobTransactions($transaction);

        return response()->json($transaction);
    }

    public function getTransactionByReceipt($receipt)
    {
        $transaction = Transaction::where('id', $receipt)
            ->orWhere('receipt_number', $receipt)
            ->orWhere('local_transaction_id', $receipt)
            ->with(['items.product', 'ppobTransactions'])
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $this->refreshPendingPpobTransactions($transaction);

        return response()->json($transaction);
    }

    public function getTodayPpobTransactions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $branchId = $user->branch_id;
        $today = \Carbon\Carbon::today();

        $transactions = Transaction::where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->whereHas('ppobTransactions')
            ->with(['ppobTransactions', 'items.product', 'cashier'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function checkPpobStatus(Request $request, $ppobTransactionId)
    {
        $ppob = \App\Models\PpobTransaction::with('transaction.items.product')->find($ppobTransactionId);

        if (!$ppob) {
            return response()->json(['message' => 'PPOB Transaction not found'], 404);
        }

        if ($ppob->status !== 'Pending') {
            return response()->json([
                'message' => 'Status is already updated',
                'data' => $ppob
            ]);
        }

        $digiflazz = new \App\Services\DigiflazzService();
        $res = $digiflazz->topup($ppob->buyer_sku_code, $ppob->customer_no, $ppob->ref_id);

        if (isset($res['data'])) {
            $newStatus = $res['data']['status'] ?? 'Pending';
            if ($newStatus !== 'Pending') {
                $updateData = [
                    'status' => $newStatus,
                    'sn' => $res['data']['sn'] ?? $ppob->sn,
                    'rc' => $res['data']['rc'] ?? $ppob->rc,
                    'message' => $res['data']['message'] ?? $ppob->message,
                    'raw_response' => json_encode($res),
                ];
                
                $customerName = $this->extractCustomerName($res['data']);
                if ($customerName) {
                    $updateData['customer_name'] = $customerName;
                }
                
                $ppob->update($updateData);
            }
        }

        return response()->json([
            'message' => 'Status checked successfully',
            'data' => $ppob->fresh()
        ]);
    }
}
