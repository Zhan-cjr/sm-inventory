<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductVelocityAnalysisService
{
    /**
     * Hitung analisis kecepatan pergerakan produk (Fast, Slow, Dead Stock, & Optimal)
     */
    public function getVelocityReport(array $filters): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        $branchId = $filters['branch_id'] ?? 'ALL';
        $supplierId = $filters['supplier_id'] ?? 'ALL';
        $categoryId = $filters['category_id'] ?? 'ALL';
        $quadrantFilter = $filters['quadrant'] ?? 'ALL'; // ALL, CRITICAL_FAST, OVERSTOCK_SLOW, DEAD_STOCK, OPTIMAL
        $sortBy = $filters['sort_by'] ?? 'doh_asc'; // doh_asc, doh_desc, revenue_desc, qty_desc, capital_desc, name_asc
        $search = trim($filters['search'] ?? '');
        $perPage = max(5, (int)($filters['per_page'] ?? 15));
        $page = max(1, (int)($filters['page'] ?? 1));

        // 1. Hitung durasi hari periode analisis (minimal 1 hari)
        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon = Carbon::parse($endDate)->endOfDay();
        $daysCount = max(1, $startCarbon->diffInDays($endCarbon) + 1);

        // 2. Query Agregasi Penjualan POS & E-Commerce (Terindeks & Bersih dari Transaksi Batal/Void)
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
                SUM(ti.subtotal) as total_revenue,
                SUM(COALESCE(ti.cogs, 0) * ti.quantity) as total_cogs
            ')
            ->get();

        $salesMap = [];
        foreach ($salesRows as $row) {
            $salesMap[$row->product_id] = [
                'qty_sold' => (float) $row->total_qty_sold,
                'revenue' => (float) $row->total_revenue,
                'cogs' => (float) $row->total_cogs,
            ];
        }

        // 3. Query Seluruh Stok Aktif Produk beserta Relasi Cabang, Supplier, & Kategori
        $stockQuery = Stock::query()
            ->with(['product.category', 'product.supplier', 'product.supplierDivision', 'branch'])
            ->whereHas('product', function ($q) use ($supplierId, $categoryId) {
                $q->where('is_active', true);
                if ($supplierId !== 'ALL') {
                    $q->where('supplier_id', $supplierId);
                }
                if ($categoryId !== 'ALL') {
                    $q->where('category_id', $categoryId);
                }
            });

        if ($branchId !== 'ALL') {
            $stockQuery->where('branch_id', $branchId);
        }

        $stocks = $stockQuery->get();

        // 4. Agregasi per Produk (Menyatukan stok multi-cabang jika filter ALL, serta multi-barcode)
        $aggregatedProducts = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (!$product) continue;

            $pid = $product->id;

            if (!isset($aggregatedProducts[$pid])) {
                $salesData = $salesMap[$pid] ?? ['qty_sold' => 0, 'revenue' => 0, 'cogs' => 0];
                $costPrice = (float) ($product->cost_price ?? $stock->cost_price ?? 0);
                $sellingPrice = (float) ($product->selling_price ?? 0);

                // Multi-barcode strings
                $additionalBarcodes = [];
                if (!empty($product->metadata['additional_barcodes'])) {
                    $additionalBarcodes = is_array($product->metadata['additional_barcodes'])
                        ? $product->metadata['additional_barcodes']
                        : array_map('trim', explode(',', (string) $product->metadata['additional_barcodes']));
                }

                $aggregatedProducts[$pid] = [
                    'product_id' => $pid,
                    'sku' => $product->sku ?? '-',
                    'barcode' => $product->barcode ?? '-',
                    'additional_barcodes' => $additionalBarcodes,
                    'product_name' => $product->name ?? 'Barang Tanpa Nama',
                    'category_name' => $product->category?->name ?? 'Tanpa Kategori',
                    'supplier_name' => $product->supplier?->name ?? 'Tanpa Supplier',
                    'supplier_id' => $product->supplier_id,
                    'category_id' => $product->category_id,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'current_stock' => 0,
                    'qty_sold' => $salesData['qty_sold'],
                    'revenue' => $salesData['revenue'],
                    'cogs' => $salesData['cogs'],
                    'ads' => round($salesData['qty_sold'] / $daysCount, 2),
                    'branch_names' => [],
                ];
            }

            $aggregatedProducts[$pid]['current_stock'] += (float) $stock->quantity;
            if ($stock->branch) {
                $aggregatedProducts[$pid]['branch_names'][] = $stock->branch->name;
            }
        }

        // 5. Evaluasi Kuadran, Days on Hand (DOH), dan Nilai Modal Mengendap
        $evaluatedList = [];
        $kpi = [
            'total_skus' => count($aggregatedProducts),
            'total_revenue' => 0,
            'total_capital_tied' => 0,
            'critical_fast' => [
                'count' => 0,
                'potential_revenue_risk' => 0,
                'total_stock' => 0,
            ],
            'overstock_slow' => [
                'count' => 0,
                'capital_tied' => 0,
                'total_stock' => 0,
            ],
            'dead_stock' => [
                'count' => 0,
                'capital_tied' => 0,
                'total_stock' => 0,
            ],
            'optimal' => [
                'count' => 0,
                'capital_healthy' => 0,
                'total_stock' => 0,
            ],
        ];

        foreach ($aggregatedProducts as $item) {
            $currentStock = $item['current_stock'];
            $ads = $item['ads'];
            $qtySold = $item['qty_sold'];
            $costPrice = $item['cost_price'];
            $revenue = $item['revenue'];

            $capitalTied = max(0, $currentStock * $costPrice);
            $kpi['total_revenue'] += $revenue;
            $kpi['total_capital_tied'] += $capitalTied;

            // Hitung DOH (Days on Hand)
            if ($ads > 0) {
                $doh = round($currentStock / $ads, 1);
            } else {
                $doh = $currentStock > 0 ? 999999 : 0; // 999999 menandakan stok mati tanpa penjualan
            }

            // Klasifikasi Kuadran Baku
            if ($currentStock > 0 && $qtySold <= 0.0001) {
                $quadrant = 'DEAD_STOCK';
                $quadrantLabel = 'Dead Stock (Stok Mati)';
                $quadrantBadge = 'dark';
                $recommendedAction = 'Retur Supplier / Pemutihan / Obral';
                $kpi['dead_stock']['count']++;
                $kpi['dead_stock']['capital_tied'] += $capitalTied;
                $kpi['dead_stock']['total_stock'] += $currentStock;
            } elseif ($doh <= 7) {
                $quadrant = 'CRITICAL_FAST';
                $quadrantLabel = 'Fast Moving Kritis';
                $quadrantBadge = 'danger';
                $recommendedAction = 'Segera Buat Draft PO Restock';
                $kpi['critical_fast']['count']++;
                $kpi['critical_fast']['potential_revenue_risk'] += ($ads * 7 * $item['selling_price']);
                $kpi['critical_fast']['total_stock'] += $currentStock;
            } elseif ($doh >= 60) {
                $quadrant = 'OVERSTOCK_SLOW';
                $quadrantLabel = 'Slow Moving Overstock';
                $quadrantBadge = 'warning';
                $recommendedAction = 'Kunci Order / Promo Cuci Gudang / Mutasi';
                $kpi['overstock_slow']['count']++;
                $kpi['overstock_slow']['capital_tied'] += $capitalTied;
                $kpi['overstock_slow']['total_stock'] += $currentStock;
            } else {
                $quadrant = 'OPTIMAL';
                $quadrantLabel = 'Optimal (Sehat)';
                $quadrantBadge = 'success';
                $recommendedAction = 'Pertahankan Pola Order Rutin';
                $kpi['optimal']['count']++;
                $kpi['optimal']['capital_healthy'] += $capitalTied;
                $kpi['optimal']['total_stock'] += $currentStock;
            }

            $item['doh'] = $doh;
            $item['doh_display'] = $doh >= 999999 ? '∞ (Mati)' : "{$doh} Hari";
            $item['capital_tied'] = $capitalTied;
            $item['quadrant'] = $quadrant;
            $item['quadrant_label'] = $quadrantLabel;
            $item['quadrant_badge'] = $quadrantBadge;
            $item['recommended_action'] = $recommendedAction;
            $item['branch_summary'] = implode(', ', array_unique($item['branch_names'])) ?: 'Semua Cabang';

            $evaluatedList[] = $item;
        }

        // 6. Terapkan Filter Pencarian Teks & Filter Kuadran
        $filtered = array_filter($evaluatedList, function ($item) use ($quadrantFilter, $search) {
            // Filter Kuadran
            if ($quadrantFilter !== 'ALL' && $item['quadrant'] !== $quadrantFilter) {
                return false;
            }

            // Filter Pencarian (Nama, SKU, Barcode, Multi-barcode)
            if ($search !== '') {
                $searchLower = strtolower($search);
                $matchName = str_contains(strtolower($item['product_name']), $searchLower);
                $matchSku = str_contains(strtolower($item['sku']), $searchLower);
                $matchBarcode = str_contains(strtolower($item['barcode']), $searchLower);
                $matchMulti = false;
                foreach ($item['additional_barcodes'] as $mb) {
                    if (str_contains(strtolower($mb), $searchLower)) {
                        $matchMulti = true;
                        break;
                    }
                }
                return $matchName || $matchSku || $matchBarcode || $matchMulti;
            }

            return true;
        });

        // 7. Terapkan Sorting Cerdas
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

        // 8. Terapkan Paginasi Interaktif
        $totalItems = count($filtered);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($filtered, $offset, $perPage);

        return [
            'items' => $pagedItems,
            'all_filtered_items' => $filtered,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $totalItems > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalItems),
            'days_count' => $daysCount,
            'kpi' => $kpi,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'quadrant' => $quadrantFilter,
                'sort_by' => $sortBy,
                'search' => $search,
            ],
        ];
    }
}
