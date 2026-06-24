<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuggestedOrderService
{
    const FORECAST_DAYS = 30;

    protected array $aiCache = [];

    protected function fetchFromAI(string $branchId): array
    {
        if (isset($this->aiCache[$branchId])) {
            return $this->aiCache[$branchId];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get('http://localhost:8001/api/v1/ai/restock-suggestions', [
                'branch_id' => $branchId
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                // Index by product_id for quick lookup
                $indexed = [];
                foreach ($data as $item) {
                    $indexed[$item['product_id']] = $item;
                }
                $this->aiCache[$branchId] = $indexed;
                return $indexed;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Restock Prediction Error: ' . $e->getMessage());
        }

        $this->aiCache[$branchId] = [];
        return [];
    }

    public function calculateForBranch(string $branchId, array $filters = []): array
    {
        $aiData = $this->fetchFromAI($branchId);
        
        // Convert to array of values
        $suggestions = array_values($aiData);

        // Apply filters if necessary
        if (!empty($filters['product_id'])) {
            $suggestions = array_filter($suggestions, fn($item) => $item['product_id'] === $filters['product_id']);
        }
        if (!empty($filters['supplier_id'])) {
            // Need to match supplier_id. AI returns supplier_name but not ID, let's fetch products
            $products = Product::where('supplier_id', $filters['supplier_id'])->pluck('id')->toArray();
            $suggestions = array_filter($suggestions, fn($item) => in_array($item['product_id'], $products));
        }

        return array_values($suggestions);
    }

    public function calculateForStock(Stock $stock): array
    {
        $aiData = $this->fetchFromAI($stock->branch_id);
        
        if (isset($aiData[$stock->product_id])) {
            $aiItem = $aiData[$stock->product_id];
            return [
                'product_id' => $stock->product_id,
                'supplier_id' => $stock->product->supplier_id,
                'sku' => $stock->product->sku,
                'name' => $stock->product->name,
                'current_qty' => $aiItem['current_qty'],
                'ads' => $aiItem['ads'],
                'reorder_point' => $aiItem['reorder_point'],
                'target_days' => $aiItem['target_days'] ?? 30,
                'suggested_qty' => $aiItem['suggested_qty'],
                'status' => $aiItem['status'],
                'lead_time' => $aiItem['lead_time'],
            ];
        }

        // Fallback if AI doesn't return data for this stock (e.g. no sales history)
        $current_qty = (float)$stock->quantity_on_hand;
        $suggested_qty = 0;
        $status = 'OK';
        
        if ($current_qty < 0) {
            $suggested_qty = abs($current_qty);
            $status = 'CRITICAL';
        } else if ($current_qty == 0) {
            $status = 'REORDER';
        }

        return [
            'product_id' => $stock->product_id,
            'supplier_id' => $stock->product->supplier_id,
            'sku' => $stock->product->sku,
            'name' => $stock->product->name,
            'current_qty' => $current_qty,
            'ads' => 0,
            'reorder_point' => 0,
            'target_days' => $stock->desired_inventory_days ?? 30,
            'suggested_qty' => $suggested_qty,
            'status' => $status,
            'lead_time' => $stock->product->lead_time_days ?? 7,
        ];
    }
}
