<?php

namespace App\Services;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductVelocityAnalysisService
{
    /**
     * Hitung analisis pergerakan produk (Fast, Slow, Dead Stock, Optimal, Empty)
     * Format modular, performan tinggi, dan strictly < 250 baris.
     */
    public function getVelocityReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(29)->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        $branchId = $filters['branch_id'] ?? 'ALL';
        $supplierId = $filters['supplier_id'] ?? 'ALL';
        $categoryId = $filters['category_id'] ?? 'ALL';
        $quadrantFilter = $filters['quadrant'] ?? 'ALL';
        $sortBy = $filters['sort_by'] ?? 'doh_asc';
        $search = trim($filters['search'] ?? '');
        $perPage = max(5, (int)($filters['per_page'] ?? 15));
        $page = max(1, (int)($filters['page'] ?? 1));

        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon = Carbon::parse($endDate)->endOfDay();
        $daysCount = max(1, $startCarbon->diffInDays($endCarbon) + 1);

        // 1. Agregasi Penjualan Riil
        $salesQuery = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('t.is_voided', false)->orWhereNull('t.is_voided');
            });

        if ($branchId !== 'ALL') {
            $salesQuery->where('t.branch_id', $branchId);
        }

        $salesRows = $salesQuery->groupBy('ti.product_id')
            ->selectRaw('
                ti.product_id,
                SUM(ti.quantity) as total_qty_sold,
                SUM(ti.quantity * (ti.unit_price - COALESCE(ti.discount_per_item, 0))) as total_revenue
            ')
            ->get();

        $salesMap = [];
        foreach ($salesRows as $row) {
            $salesMap[$row->product_id] = [
                'qty_sold' => (float) $row->total_qty_sold,
                'revenue' => (float) $row->total_revenue,
            ];
        }

        // 2. Query Stok Fisik & Relasi Master
        $stockQuery = Stock::query()
            ->with(['product.category', 'product.supplier', 'branch'])
            ->whereHas('product', function ($q) use ($supplierId, $categoryId) {
                $q->where('is_active', true);
                if ($supplierId !== 'ALL') $q->where('supplier_id', $supplierId);
                if ($categoryId !== 'ALL') $q->where('category_id', $categoryId);
            });

        if ($branchId !== 'ALL') {
            $stockQuery->where('branch_id', $branchId);
        }

        $stocks = $stockQuery->get();

        // 3. Agregasi Master Produk & Multi-Barcode
        $aggregated = [];
        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (!$product) continue;
            $pid = $product->id;

            if (!isset($aggregated[$pid])) {
                $salesData = $salesMap[$pid] ?? ['qty_sold' => 0, 'revenue' => 0];
                $costPrice = (float) ($stock->cost_price ?: $product->cost_price ?: 0);
                $sellingPrice = (float) ($stock->selling_price ?: $product->selling_price ?: 0);

                $additionalBarcodes = [];
                if (!empty($product->metadata['additional_barcodes'])) {
                    $additionalBarcodes = is_array($product->metadata['additional_barcodes'])
                        ? $product->metadata['additional_barcodes']
                        : array_map('trim', explode(',', (string) $product->metadata['additional_barcodes']));
                }

                $aggregated[$pid] = [
                    'product_id' => $pid,
                    'sku' => $product->sku ?? '-',
                    'barcode' => $product->barcode ?? '-',
                    'additional_barcodes' => $additionalBarcodes,
                    'product_name' => $product->name ?? 'Barang Tanpa Nama',
                    'category_name' => $product->category?->name ?? 'Tanpa Kategori',
                    'supplier_name' => $product->supplier?->name ?? 'Tanpa Supplier',
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'current_stock' => 0,
                    'qty_sold' => $salesData['qty_sold'],
                    'revenue' => $salesData['revenue'],
                    'ads' => round($salesData['qty_sold'] / $daysCount, 2),
                ];
            }

            $aggregated[$pid]['current_stock'] += (float) ($stock->quantity_on_hand ?? 0);
        }

        // 4. Klasifikasi 4 Kuadran Manajerial
        $evaluated = [];
        $kpi = [
            'total_skus' => count($aggregated),
            'total_revenue' => 0,
            'total_capital_tied' => 0,
            'critical_fast' => ['count' => 0, 'potential_revenue_risk' => 0, 'total_stock' => 0],
            'overstock_slow' => ['count' => 0, 'capital_tied' => 0, 'total_stock' => 0],
            'dead_stock' => ['count' => 0, 'capital_tied' => 0, 'total_stock' => 0],
            'optimal' => ['count' => 0, 'capital_healthy' => 0, 'total_stock' => 0],
            'empty_zero' => ['count' => 0, 'total_stock' => 0],
        ];

        foreach ($aggregated as $item) {
            $stock = $item['current_stock'];
            $ads = $item['ads'];
            $sold = $item['qty_sold'];
            $capital = max(0, $stock * $item['cost_price']);

            $kpi['total_revenue'] += $item['revenue'];
            $kpi['total_capital_tied'] += $capital;

            if ($sold <= 0.0001) {
                if ($stock > 0) {
                    $quad = 'DEAD_STOCK';
                    $label = 'Dead Stock (Macet)';
                    $badge = 'dark';
                    $action = 'Retur Supplier / Cuci Gudang';
                    $doh = 999999;
                    $kpi['dead_stock']['count']++;
                    $kpi['dead_stock']['capital_tied'] += $capital;
                    $kpi['dead_stock']['total_stock'] += $stock;
                } else {
                    $quad = 'EMPTY_ZERO';
                    $label = 'Stok Kosong / Pasif';
                    $badge = 'secondary';
                    $action = 'Evaluasi Penghapusan / PO';
                    $doh = 0;
                    $kpi['empty_zero']['count']++;
                }
            } else {
                $doh = $ads > 0 ? round($stock / $ads, 1) : 0;
                if ($doh <= 7) {
                    $quad = 'CRITICAL_FAST';
                    $label = 'Fast Moving Kritis';
                    $badge = 'danger';
                    $action = 'Segera Reorder PO';
                    $kpi['critical_fast']['count']++;
                    $kpi['critical_fast']['potential_revenue_risk'] += ($ads * 7 * $item['selling_price']);
                    $kpi['critical_fast']['total_stock'] += $stock;
                } elseif ($doh >= 60) {
                    $quad = 'OVERSTOCK_SLOW';
                    $label = 'Slow Moving Overstock';
                    $badge = 'warning';
                    $action = 'Kunci Order / Promo Obral';
                    $kpi['overstock_slow']['count']++;
                    $kpi['overstock_slow']['capital_tied'] += $capital;
                    $kpi['overstock_slow']['total_stock'] += $stock;
                } else {
                    $quad = 'OPTIMAL';
                    $label = 'Optimal (Sehat)';
                    $badge = 'success';
                    $action = 'Pola Order Terjaga';
                    $kpi['optimal']['count']++;
                    $kpi['optimal']['capital_healthy'] += $capital;
                    $kpi['optimal']['total_stock'] += $stock;
                }
            }

            $item['doh'] = $doh;
            $item['doh_display'] = $doh >= 999999 ? '∞ (Mati)' : "{$doh} Hari";
            $item['capital_tied'] = $capital;
            $item['quadrant'] = $quad;
            $item['quadrant_label'] = $label;
            $item['quadrant_badge'] = $badge;
            $item['recommended_action'] = $action;

            $evaluated[] = $item;
        }

        // 5. Filter & Sorting
        $filtered = array_filter($evaluated, function ($it) use ($quadrantFilter, $search) {
            if ($quadrantFilter !== 'ALL' && $it['quadrant'] !== $quadrantFilter) return false;
            if ($search !== '') {
                $s = strtolower($search);
                $match = str_contains(strtolower($it['product_name']), $s) || str_contains(strtolower($it['sku']), $s) || str_contains(strtolower($it['barcode']), $s);
                if (!$match) {
                    foreach ($it['additional_barcodes'] as $mb) {
                        if (str_contains(strtolower($mb), $s)) { $match = true; break; }
                    }
                }
                return $match;
            }
            return true;
        });

        usort($filtered, function ($a, $b) use ($sortBy) {
            return match ($sortBy) {
                'doh_asc' => $a['doh'] <=> $b['doh'],
                'doh_desc' => $b['doh'] <=> $a['doh'],
                'revenue_desc' => $b['revenue'] <=> $a['revenue'],
                'qty_desc' => $b['qty_sold'] <=> $a['qty_sold'],
                'capital_desc' => $b['capital_tied'] <=> $a['capital_tied'],
                'name_asc' => strcasecmp($a['product_name'], $b['product_name']),
                default => $a['doh'] <=> $b['doh'],
            };
        });

        $totalItems = count($filtered);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($filtered, $offset, $perPage),
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $totalItems > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalItems),
            'days_count' => $daysCount,
            'kpi' => $kpi,
        ];
    }
}
