<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use App\Models\InventoryLog;
use App\Models\StockBatch;
use App\Models\StockBatchDeduction;
use App\Models\Bank;
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
        // Normalisasi payload jika frontend mengirim camelCase (productId, unitPrice, dll)
        $rawItems = $request->input('items', []);
        if (is_array($rawItems)) {
            $normalizedItems = [];
            foreach ($rawItems as $item) {
                $normalizedItems[] = [
                    'product_id' => $item['product_id'] ?? $item['productId'] ?? null,
                    'quantity' => $item['quantity'] ?? $item['qty'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? $item['unitPrice'] ?? $item['price'] ?? 0,
                    'manual_discount' => $item['manual_discount'] ?? $item['manualDiscount'] ?? 0,
                    'discount_per_item' => $item['discount_per_item'] ?? $item['discountPerItem'] ?? 0,
                    'customer_no' => $item['customer_no'] ?? $item['customerNo'] ?? null,
                    'customer_wa_phone' => $item['customer_wa_phone'] ?? $item['customerWaPhone'] ?? null,
                    'promotionId' => $item['promotionId'] ?? $item['promotion_id'] ?? null,
                    'originalTransactionId' => $item['originalTransactionId'] ?? $item['original_transaction_id'] ?? null,
                ];
            }
            $request->merge(['items' => $normalizedItems]);
        }
        if ($request->has('paymentMethod') && !$request->has('payment_method')) {
            $request->merge(['payment_method' => $request->input('paymentMethod')]);
        }
        if ($request->has('totalAmount') && !$request->has('total_amount')) {
            $request->merge(['total_amount' => $request->input('totalAmount')]);
        }
        if ($request->has('finalAmount') && !$request->has('final_amount')) {
            $request->merge(['final_amount' => $request->input('finalAmount')]);
        }
        if ($request->has('discountAmount') && !$request->has('discount_amount')) {
            $request->merge(['discount_amount' => $request->input('discountAmount')]);
        }

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

                // Validasi minimal transaksi bank / QRIS
                $bankId = $request->input('bankId') ?? $request->input('bank_id');
                if ($bankId) {
                    $bank = Bank::find($bankId);
                    if ($bank && $bank->min_transaction_amount > 0) {
                        $paidForBank = (float) ($request->input('receivedAmount') ?? $request->input('received_amount') ?? $validated['final_amount']);
                        if ($paidForBank < (float) $bank->min_transaction_amount) {
                            throw new \Exception("Nominal pembayaran via {$bank->name} minimal Rp " . number_format($bank->min_transaction_amount, 0, ',', '.') . "!");
                        }
                    }
                }
                if (is_array($requestPayments)) {
                    foreach ($requestPayments as $p) {
                        $pBankId = $p['bankId'] ?? $p['bank_id'] ?? null;
                        if (!empty($pBankId)) {
                            $bank = Bank::find($pBankId);
                            if ($bank && $bank->min_transaction_amount > 0 && ((float) ($p['amount'] ?? 0)) < (float) $bank->min_transaction_amount) {
                                throw new \Exception("Nominal porsi pembayaran via {$bank->name} minimal Rp " . number_format($bank->min_transaction_amount, 0, ',', '.') . "!");
                            }
                        }
                    }
                }

                $receivedAmount = (float) ($request->input('received_amount') ?? $request->input('receivedAmount') ?? $validated['final_amount']);
                $changeAmount = (float) ($request->input('change_amount') ?? $request->input('changeAmount') ?? 0);
                $terminalId = $request->input('terminal_id') ?? $request->header('X-Terminal-Id') ?? null;
                $shiftId = $request->input('shift_id') ?? null;
                $txType = $request->input('transaction_type', 'SALES');

                $transaction = Transaction::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'terminal_id' => $terminalId,
                    'shift_id' => $shiftId,
                    'transaction_type' => $txType,
                    'transaction_date' => now(),
                    'cashier_id' => $user->id,
                    'total_amount' => $validated['total_amount'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'manual_discount' => $request->input('manualDiscount', $request->input('manual_discount', $validated['discount_amount'] ?? 0)),
                    'promo_discount' => $request->input('promoDiscount', $request->input('promo_discount', 0)),
                    'final_amount' => $validated['final_amount'] ?? 0,
                    'payment_method' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                    'bank_id' => $bankId,
                    'received_amount' => $receivedAmount,
                    'change_amount' => $changeAmount,
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

                $productIds = collect($validated['items'])->pluck('product_id')->unique();
                $productsMap = \App\Models\Product::with(['assemblies', 'conversions'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                foreach ($validated['items'] as $item) {
                    $product = $productsMap->get($item['product_id']);

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

                    // Check Assembly (using in-memory relation, no extra query)
                    if ($product && $product->assemblies && $product->assemblies->isNotEmpty()) {
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

                    if ($product && $product->product_type === 'digital' && !empty($product->ppob_sku)) {
                        $customerNo = $item['customer_no'] ?? null;
                        if ($customerNo) {
                            $refId = $transaction->receipt_number;
                            $existingPpobCount = \App\Models\PpobTransaction::where('transaction_id', $transaction->id)->count();
                            if ($existingPpobCount > 0) {
                                $refId .= '-' . ($existingPpobCount + 1);
                            }
                            
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
                            
                            // Pre-Flight Check: Jika provider langsung merespon Gagal (Nomor Salah / Gangguan / Saldo Kurang),
                            // batalkan seluruh transaksi agar kasir tidak perlu mem-void dan laci kasir tidak selisih!
                            if (strtolower($status) === 'gagal') {
                                $providerMsg = $res['data']['message'] ?? 'Pengisian ditolak oleh operator/provider.';
                                throw new \App\Exceptions\PpobFailedException(
                                    "Pengisian {$product->name} Gagal: {$providerMsg}",
                                    [
                                        'product_id' => $product->id,
                                        'product_name' => $product->name,
                                        'customer_no' => $customerNo,
                                        'provider_message' => $providerMsg,
                                    ]
                                );
                            }

                            $customerWaPhone = $item['customer_wa_phone'] ?? null;

                            \App\Models\PpobTransaction::create([
                                'transaction_id' => $transaction->id,
                                'provider' => $product->ppob_provider ?? 'digiflazz',
                                'ref_id' => $refId,
                                'customer_no' => $customerNo,
                                'customer_name' => $customerName,
                                'customer_wa_phone' => $customerWaPhone,
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

        } catch (\App\Exceptions\PpobFailedException $e) {
            Log::warning('PPOB Pre-flight Rejected: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'PPOB_FAILED',
                'message' => $e->getMessage(),
                'failed_item' => $e->getFailedItem(),
            ], 422);
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

                $res = $ppobServices[$ppob->provider]->checkStatus($ppob->buyer_sku_code, $ppob->customer_no, $ppob->ref_id);

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

        $returnsMap = \App\Models\TransactionItem::where('original_transaction_id', $transaction->id)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_returned')
            ->pluck('total_returned', 'product_id');

        foreach ($transaction->items as $item) {
            $returnedQuantity = $returnsMap->get($item->product_id, 0);
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
        $todayStart = \Carbon\Carbon::today()->startOfDay();
        $todayEnd = \Carbon\Carbon::today()->endOfDay();

        $transactions = Transaction::where('branch_id', $branchId)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereHas('ppobTransactions')
            ->with(['ppobTransactions', 'items.product', 'cashier'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function checkPpobStatus(Request $request, $ppobTransactionId)
    {
        $user = $request->user();
        $ppob = \App\Models\PpobTransaction::with('transaction.items.product')->find($ppobTransactionId);

        if (!$ppob) {
            return response()->json(['message' => 'PPOB Transaction not found'], 404);
        }

        if ($user && $ppob->transaction && $ppob->transaction->branch_id !== $user->branch_id && !in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Unauthorized: Transaksi ini milik cabang lain'], 403);
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

                $branchId = $ppob->transaction?->branch_id ?? $user?->branch_id;
                if ($branchId) {
                    event(new \App\Events\PpobStatusUpdated($branchId, $ppob->fresh()->load('transaction.items.product')));
                }
            }
        }

        return response()->json([
            'message' => 'Status checked successfully',
            'data' => $ppob->fresh()
        ]);
    }

    /**
     * Refund dana PPOB yang gagal ke konsumen (Tunai Laci atau Non-Tunai Transfer).
     * Jika Tunai: mencatat CashMovement CASH_OUT pada shift aktif kasir yang bertugas,
     * sehingga target cash laci saat close shift otomatis berkurang dan tidak selisih!
     */
    public function refundPpobTransaction(Request $request, $ppobTransactionId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'refund_method' => 'required|in:CASH,TRANSFER,EWALLET',
            'notes' => 'nullable|string|max:255',
            'terminal_id' => 'nullable|string',
        ]);

        $ppob = \App\Models\PpobTransaction::with(['transaction.items.product'])->find($ppobTransactionId);
        if (!$ppob) {
            return response()->json(['message' => 'Transaksi PPOB tidak ditemukan'], 404);
        }

        // Validasi kepemilikan cabang ketat
        $branchId = $ppob->transaction?->branch_id ?? $user->branch_id;
        if ($branchId !== $user->branch_id && !in_array(strtoupper($user->role), ['ADMIN', 'SUPER_ADMIN', 'SUPERADMIN'])) {
            return response()->json(['message' => 'Unauthorized: Transaksi ini milik cabang lain'], 403);
        }

        // Hanya transaksi berstatus Gagal yang bisa direfund
        if (strtolower($ppob->status) !== 'gagal') {
            return response()->json(['message' => 'Hanya transaksi berstatus Gagal yang dapat direfund'], 422);
        }

        if ($ppob->refund_status === 'REFUNDED') {
            return response()->json(['message' => 'Transaksi PPOB ini sudah pernah direfund sebelumnya'], 422);
        }

        // Tentukan nominal refund: ambil dari harga jual item transaksi, atau fallback ke price
        $refundAmount = 0;
        if ($ppob->transaction && $ppob->transaction->items) {
            $matchedItem = $ppob->transaction->items->first(function ($item) use ($ppob) {
                return $item->product && $item->product->ppob_sku === $ppob->buyer_sku_code;
            });
            if ($matchedItem) {
                $refundAmount = (float) $matchedItem->unit_price;
            }
        }
        if ($refundAmount <= 0) {
            $refundAmount = (float) $ppob->price;
        }

        return DB::transaction(function () use ($ppob, $validated, $user, $refundAmount, $branchId, $request) {
            $shift = null;
            if ($validated['refund_method'] === 'CASH') {
                $terminalId = $validated['terminal_id'] ?? $request->header('X-Terminal-Id');
                
                // Cari shift OPEN milik kasir saat ini di cabang ini
                $shiftQuery = \App\Models\Shift::where('branch_id', $user->branch_id)
                    ->where('user_id', $user->id)
                    ->where('status', 'OPEN');
                if ($terminalId) {
                    $shiftQuery->where('terminal_id', $terminalId);
                }
                $shift = $shiftQuery->latest()->first();

                // Fallback: jika kasir berganti tapi terminal sama
                if (!$shift && $terminalId) {
                    $shift = \App\Models\Shift::where('branch_id', $user->branch_id)
                        ->where('terminal_id', $terminalId)
                        ->where('status', 'OPEN')
                        ->latest()
                        ->first();
                }

                // Catat CashMovement (CASH_OUT) pada shift aktif agar uang laci seimbang
                if ($shift) {
                    \App\Models\CashMovement::create([
                        'shift_id' => $shift->id,
                        'user_id' => $user->id,
                        'terminal_id' => $shift->terminal_id,
                        'type' => 'CASH_OUT',
                        'amount' => $refundAmount,
                        'description' => "Refund PPOB Gagal: Struk #{$ppob->transaction?->receipt_number} ({$ppob->customer_no})" . ($validated['notes'] ? " - " . $validated['notes'] : ''),
                    ]);

                    $shift->total_cash_out += $refundAmount;
                    $shift->save();
                }
            }

            $ppob->update([
                'refund_status' => 'REFUNDED',
                'refund_method' => $validated['refund_method'],
                'refund_amount' => $refundAmount,
                'refunded_by' => $user->id,
                'refunded_at' => now(),
                'refund_notes' => $validated['notes'] ?? null,
            ]);

            // Broadcast status terupdate ke WebSocket cabang ini
            event(new \App\Events\PpobStatusUpdated($branchId, $ppob->fresh()->load('transaction.items.product')));

            return response()->json([
                'success' => true,
                'message' => 'Pengembalian dana PPOB berhasil diproses.',
                'data' => $ppob->fresh(),
                'cash_movement_recorded' => $shift !== null,
            ]);
        });
    }
}
