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
            'items.*.promotionId' => 'nullable|uuid',
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
                    $paymentDetails = $requestPayments;
                } else if (is_array($requestPayments) && count($requestPayments) === 1) {
                    $paymentMethod = $requestPayments[0]['method'];
                    $paymentDetails = $requestPayments;
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
                            $org = \App\Models\Organization::find($user->organization_id);
                            if (!$org || !$org->point_redemption_enabled) {
                                throw new \Exception('Penukaran poin saat ini dinonaktifkan oleh Perusahaan.');
                            }

                            $minPoints = $org->minimum_points_to_redeem ?? 100;
                            if ($payment['points_deducted'] < $minPoints) {
                                throw new \Exception("Minimal penukaran poin adalah {$minPoints} poin.");
                            }

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
                    $product = \App\Models\Product::with(['assemblies', 'conversions'])->find($item['product_id']);

                    // Check Auto-Conversion if stock is not enough (only for physical products)
                    if ($product && $product->product_type === 'physical') {
                        $stock = \App\Models\Stock::where('product_id', $product->id)
                            ->where('branch_id', $transaction->branch_id)
                            ->first();

                        $currentQty = $stock ? $stock->quantity_on_hand : 0;
                        if ($currentQty < $item['quantity']) {
                            // Find conversion rule where this product is the target
                            $conversion = \App\Models\ProductConversion::where('target_product_id', $product->id)
                                ->where('auto_convert', true)
                                ->first();

                            if ($conversion) {
                                $neededDeficit = $item['quantity'] - $currentQty;
                                // How many source items needed?
                                $sourceQtyToUnpack = ceil($neededDeficit / $conversion->conversion_qty);
                                
                                // Deduct from source
                                $sourceStock = \App\Models\Stock::firstOrCreate([
                                    'branch_id' => $transaction->branch_id,
                                    'product_id' => $conversion->source_product_id,
                                ], ['quantity_on_hand' => 0]);

                                $sourceStock->log_type = 'UNPACKING';
                                $sourceStock->reason_code = 'AUTO_CONVERSION';
                                $sourceStock->reference_doc_type = 'TRANSACTION';
                                $sourceStock->reference_doc_id = $transaction->id;
                                $sourceStock->quantity_on_hand -= $sourceQtyToUnpack;
                                $sourceStock->save();

                                // Add to target
                                if (!$stock) {
                                    $stock = \App\Models\Stock::create([
                                        'branch_id' => $transaction->branch_id,
                                        'product_id' => $product->id,
                                        'quantity_on_hand' => 0
                                    ]);
                                }
                                $stock->log_type = 'UNPACKING';
                                $stock->reason_code = 'AUTO_CONVERSION';
                                $stock->reference_doc_type = 'TRANSACTION';
                                $stock->reference_doc_id = $transaction->id;
                                $stock->quantity_on_hand += ($sourceQtyToUnpack * $conversion->conversion_qty);
                                $stock->save();
                            }
                        }
                    }

                    $txItem = TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_per_item' => min((float)$item['unit_price'], (float)($item['discountPerItem'] ?? $item['discount_per_item'] ?? 0)),
                        'promotion_id' => $item['promotionId'] ?? $item['promotion_id'] ?? null,
                        'original_transaction_id' => $item['originalTransactionId'] ?? $item['original_transaction_id'] ?? null,
                    ]);

                    // Check Assembly
                    if ($product && $product->assemblies()->exists()) {
                        foreach ($product->assemblies as $assembly) {
                            TransactionItem::create([
                                'transaction_id' => $transaction->id,
                                'product_id' => $assembly->child_product_id,
                                'quantity' => $assembly->quantity * $item['quantity'],
                                'unit_price' => 0,
                                'discount_per_item' => 0,
                                'is_assembly_component' => true,
                                'assembly_parent_id' => $product->id,
                            ]);
                        }
                    }

                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product && $product->product_type === 'digital' && !empty($product->ppob_sku)) {
                        $customerNo = $item['customer_no'] ?? null;
                        if ($customerNo) {
                            $refId = $transaction->receipt_number . '-' . strtoupper(substr(uniqid(), -4));
                            
                            $user = auth()->user();
                            $additionalInfo = $user ? [$user->organization_id ?? 'Toko', $user->name] : [];
                            
                            try {
                                $ppobService = \App\Services\PpobServiceManager::make($product->ppob_provider);
                                $res = $ppobService->topup($product->ppob_sku, $customerNo, $refId, $additionalInfo);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('PPOB Service Error: ' . $e->getMessage());
                                $res = ['data' => ['status' => 'Gagal', 'message' => $e->getMessage()]];
                            }

                            $status = 'Pending';
                            if (isset($res['data']['status'])) {
                                $status = $res['data']['status'];
                            }
                            
                            $customerName = isset($res['data']) ? $this->extractCustomerName($res['data']) : null;
                            
                            \App\Models\PpobTransaction::create([
                                'transaction_id' => $transaction->id,
                                'provider' => $product->ppob_provider ?? 'digiflazz',
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

            // Broadcast the new transaction to the branch channel
            event(new \App\Events\TransactionCreated($transaction->branch_id, [
                'id' => $transaction->id,
                'receipt_number' => $transaction->receipt_number,
                'transaction_date' => $transaction->transaction_date,
                'total_amount' => $transaction->total_amount,
                'final_amount' => $transaction->final_amount,
            ]));

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
        $ppobServices = [];

        foreach ($transaction->ppobTransactions as $ppob) {
            if ($ppob->status === 'Pending') {
                if (!isset($ppobServices[$ppob->provider])) {
                    $ppobServices[$ppob->provider] = \App\Services\PpobServiceManager::make($ppob->provider);
                }
                $hasPending = true;

                $res = $ppobServices[$ppob->provider]->topup($ppob->buyer_sku_code, $ppob->customer_no, $ppob->ref_id);

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

        foreach ($transaction->items as $item) {
            $returnedQuantity = \App\Models\TransactionItem::where('original_transaction_id', $transaction->id)
                ->where('product_id', $item->product_id)
                ->sum('quantity');
            
            // return quantities are stored as negative numbers
            $item->returned_quantity = abs($returnedQuantity);
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

        $ppobService = \App\Services\PpobServiceManager::make($ppob->provider);
        $res = $ppobService->checkStatus($ppob->buyer_sku_code, $ppob->customer_no, $ppob->ref_id);

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
