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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'final_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid',
            'items.*.quantity' => 'required|integer|not_in:0',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount_per_item' => 'nullable|numeric',
        ]);

        $user = $this->getUser();
        $transaction = null;

        try {
            DB::transaction(function () use ($validated, $user, &$transaction) {
                $transaction = Transaction::create([
                    'organization_id' => $user->organization_id,
                    'branch_id' => $user->branch_id,
                    'transaction_type' => 'SALES',
                    'transaction_date' => now(),
                    'cashier_id' => $user->id,
                    'total_amount' => $validated['total_amount'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'final_amount' => $validated['final_amount'] ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'sync_status' => 'SYNCED',
                    'receipt_number' => $request->receipt_number ?? ('SMI-' . strtoupper(substr(uniqid(), -6))),
                ]);

                foreach ($validated['items'] as $item) {
                    $txItem = TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_per_item' => $item['discount_per_item'] ?? 0,
                    ]);

                }

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

    public function getTransactionByReceipt($receipt)
    {
        $transaction = Transaction::where('id', $receipt)
            ->orWhere('receipt_number', $receipt)
            ->orWhere('local_transaction_id', $receipt)
            ->with(['items.product'])
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        return response()->json($transaction);
    }
}
