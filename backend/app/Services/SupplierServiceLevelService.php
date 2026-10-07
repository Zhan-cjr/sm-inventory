<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierServiceLevelService
{
    /**
     * Report Cache Prefix & TTL
     */
    protected const CACHE_PREFIX = 'sl_report_v4_';
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Clear all report cache entries
     */
    public function clearReportCache(): void
    {
        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Fallback if full flush restricted
        }
    }

    /**
     * Helper to reliably resolve unit price from a PO Item (handles unit_cost & subtotal)
     */
    protected function resolvePoItemPrice($poItem): float
    {
        if ($poItem->unit_cost !== null && (float)$poItem->unit_cost > 0) {
            return (float)$poItem->unit_cost;
        }

        $qty = (float)($poItem->quantity_ordered ?? 0);
        $subtotal = (float)($poItem->subtotal ?? 0);
        if ($qty > 0 && $subtotal > 0) {
            return $subtotal / $qty;
        }

        if ($poItem->product && (float)($poItem->product->buy_price ?? 0) > 0) {
            return (float)$poItem->product->buy_price;
        }

        return (float)($poItem->unit_price ?? 0);
    }

    /**
     * Helper to reliably resolve unit price from a Goods Receipt Item
     */
    protected function resolveGrItemPrice($grItem, float $fallbackPrice = 0): float
    {
        if ($grItem->unit_price !== null && (float)$grItem->unit_price > 0) {
            return (float)$grItem->unit_price;
        }

        $qty = (float)($grItem->quantity_received ?? 0);
        $subtotal = (float)($grItem->subtotal ?? 0);
        if ($qty > 0 && $subtotal > 0) {
            return $subtotal / $qty;
        }

        return $fallbackPrice;
    }

    /**
     * Compute or retrieve the Unified Report Dataset in a Single High-Speed Pass
     */
    public function getUnifiedReport(array $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . md5(json_encode([
            $filters['start_date'] ?? '',
            $filters['end_date'] ?? '',
            $filters['branch_id'] ?? 'ALL',
            $filters['supplier_id'] ?? 'ALL',
            $filters['search'] ?? '',
        ]));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = PurchaseOrder::query()
                ->with(['supplier', 'division', 'branch', 'items.product', 'warehouseChecks.items', 'goodsReceipts.items']);

            if (!empty($filters['start_date'])) {
                $query->whereDate('po_date', '>=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $query->whereDate('po_date', '<=', $filters['end_date']);
            }
            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'ALL') {
                $query->where('branch_id', $filters['branch_id']);
            }
            if (!empty($filters['supplier_id']) && $filters['supplier_id'] !== 'ALL') {
                $query->where('supplier_id', $filters['supplier_id']);
            }
            if (!empty($filters['search'])) {
                $term = trim($filters['search']);
                $query->where(function ($q) use ($term) {
                    $q->where('po_number', 'like', "%{$term}%")
                      ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                      ->orWhereHas('items.product', fn ($pq) => $pq->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhere('barcode', 'like', "%{$term}%"));
                });
            }

            $pos = $query->orderBy('po_date', 'desc')->get();

            $totalPo = $pos->count();
            $totalPoAmount = 0;
            $totalGrAmount = 0;
            $totalOrderedQty = 0;
            $totalReceivedQty = 0;
            $totalRejectedQty = 0;
            $onTimeCount = 0;
            $poWithReceiptsCount = 0;
            $priceDiscrepancyCount = 0;

            $groupedSuppliers = [];
            $reconciliations = [];
            $rawDiscrepancies = [];
            $counts = ['ALL' => 0, 'PRICE_DIFF' => 0, 'REJECTED' => 0, 'SHORT_QTY' => 0];

            $now = Carbon::now();

            foreach ($pos as $po) {
                $poAmount = (float) $po->total_amount;
                $totalPoAmount += $poAmount;

                $receipts = $po->goodsReceipts->where('status', '!=', 'CANCELLED');
                $poReceivedQty = 0;
                $poGrAmount = 0;
                $firstReceiptDate = null;
                $receiptNumbers = [];

                // Pre-index receipts by product_id for O(1) lightning lookup
                $poReceiptMap = [];
                foreach ($receipts as $gr) {
                    $poGrAmount += (float) $gr->total_amount;
                    $receiptNumbers[] = $gr->receipt_number;
                    if (!$firstReceiptDate || $gr->receipt_date < $firstReceiptDate) {
                        $firstReceiptDate = $gr->receipt_date;
                    }
                    foreach ($gr->items as $grItem) {
                        $pid = $grItem->product_id;
                        $grPrice = $this->resolveGrItemPrice($grItem);
                        if (!isset($poReceiptMap[$pid])) {
                            $poReceiptMap[$pid] = ['qty' => 0, 'price' => $grPrice, 'receipt_numbers' => []];
                        }
                        $poReceiptMap[$pid]['qty'] += (float)$grItem->quantity_received;
                        $poReceiptMap[$pid]['price'] = $grPrice;
                        $poReceiptMap[$pid]['receipt_numbers'][] = $gr->receipt_number;
                        $poReceivedQty += (float)$grItem->quantity_received;
                    }
                }

                $totalGrAmount += $poGrAmount;
                $totalReceivedQty += $poReceivedQty;

                // Pre-index warehouse checks by product_id for O(1) lightning lookup
                $scannedQty = 0;
                $poRejectedQty = 0;
                $poRejectMap = [];
                foreach ($po->warehouseChecks as $wc) {
                    foreach ($wc->items as $wcItem) {
                        $scannedQty += (float) $wcItem->qty_scanned;
                        $rej = (float) $wcItem->qty_rejected;
                        if ($rej > 0) {
                            $poRejectedQty += $rej;
                            $pid = $wcItem->product_id;
                            if (!isset($poRejectMap[$pid])) {
                                $poRejectMap[$pid] = ['rejected_qty' => 0, 'reasons' => []];
                            }
                            $poRejectMap[$pid]['rejected_qty'] += $rej;
                            if ($wcItem->rejected_reason) {
                                $poRejectMap[$pid]['reasons'][] = $wcItem->rejected_reason;
                            }
                        }
                    }
                }
                $totalRejectedQty += $poRejectedQty;

                $poOrderedQty = 0;
                foreach ($po->items as $poItem) {
                    $poOrderedQty += (float) $poItem->quantity_ordered;
                }
                $totalOrderedQty += $poOrderedQty;

                $isOnTime = false;
                if ($receipts->isNotEmpty()) {
                    $poWithReceiptsCount++;
                    $deadline = $po->expired_date ?: $po->expected_delivery_date;
                    if ($deadline && $firstReceiptDate) {
                        if (Carbon::parse($firstReceiptDate)->startOfDay()->lte(Carbon::parse($deadline)->startOfDay())) {
                            $isOnTime = true;
                            $onTimeCount++;
                        }
                    } else {
                        $isOnTime = true;
                        $onTimeCount++;
                    }
                }

                $leadDays = null;
                if ($firstReceiptDate && $po->po_date) {
                    $leadDays = Carbon::parse($po->po_date)->diffInDays(Carbon::parse($firstReceiptDate));
                }

                $isClosed = strtolower((string)$po->status) === 'closed';
                $isExpired = $po->expired_date && $now->startOfDay()->gt(Carbon::parse($po->expired_date)->startOfDay()) && ($poOrderedQty - $poReceivedQty) > 0;
                $outstandingQty = $isClosed ? 0 : max(0, $poOrderedQty - $poReceivedQty);
                $outstandingAmount = $isClosed ? 0 : max(0, $poAmount - $poGrAmount);
                $pemenuhanBarangPersen = $poOrderedQty > 0 ? ($poReceivedQty / $poOrderedQty) * 100 : 0;

                if ($isClosed) {
                    $statusKey = 'CLOSED';
                    $statusLabel = 'Sisa Dihanguskan (Closed)';
                    $statusBadge = 'secondary';
                } elseif ($poReceivedQty >= $poOrderedQty && $poOrderedQty > 0) {
                    $statusKey = 'COMPLETED';
                    $statusLabel = 'Lengkap (100%)';
                    $statusBadge = 'success';
                } elseif ($poReceivedQty > 0) {
                    $statusKey = 'PARTIAL';
                    $statusLabel = 'Sebagian (' . round($pemenuhanBarangPersen) . '%)';
                    $statusBadge = 'warning';
                } elseif ($isExpired) {
                    $statusKey = 'EXPIRED';
                    $statusLabel = 'Expired (Perlu Ditutup)';
                    $statusBadge = 'danger';
                } else {
                    $statusKey = 'PENDING';
                    $statusLabel = 'Menunggu Kiriman';
                    $statusBadge = 'secondary';
                }

                $reconciliations[] = [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'po_date' => $po->po_date ? $po->po_date->format('d/m/Y') : '-',
                    'expected_date' => $po->expected_delivery_date ? $po->expected_delivery_date->format('d/m/Y') : '-',
                    'expired_date' => $po->expired_date ? $po->expired_date->format('d/m/Y') : '-',
                    'supplier_name' => $po->supplier?->name ?? 'Tanpa Supplier',
                    'division_name' => $po->division?->name ?? '-',
                    'branch_name' => $po->branch?->name ?? 'Semua',
                    'ordered_qty' => $poOrderedQty,
                    'scanned_qty' => $scannedQty,
                    'received_qty' => $poReceivedQty,
                    'outstanding_qty' => $outstandingQty,
                    'rejected_qty' => $poRejectedQty,
                    'po_amount' => $poAmount,
                    'gr_amount' => $poGrAmount,
                    'outstanding_amount' => $outstandingAmount,
                    'sl_qty' => round($pemenuhanBarangPersen, 1),
                    'lead_days' => $leadDays,
                    'receipt_numbers' => implode(', ', array_filter($receiptNumbers)),
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_badge' => $statusBadge,
                    'item_count' => $po->items->count(),
                    'is_closed' => $isClosed,
                    'can_close' => !$isClosed && $outstandingQty > 0,
                ];

                // Supplier accumulator
                $sId = (string) ($po->supplier_id ?? '0');
                if (!isset($groupedSuppliers[$sId])) {
                    $supplier = $po->supplier;
                    $groupedSuppliers[$sId] = [
                        'supplier_id' => $sId,
                        'supplier_name' => $supplier ? $supplier->name : 'Tanpa Supplier',
                        'supplier_code' => $supplier ? ($supplier->code ?? '-') : '-',
                        'po_count' => 0,
                        'ordered_qty' => 0,
                        'received_qty' => 0,
                        'rejected_qty' => 0,
                        'po_amount' => 0,
                        'gr_amount' => 0,
                        'on_time_count' => 0,
                        'receipt_count' => 0,
                        'total_lead_days' => 0,
                        'lead_count' => 0,
                        'price_dev_count' => 0,
                    ];
                }
                $groupedSuppliers[$sId]['po_count']++;
                $groupedSuppliers[$sId]['ordered_qty'] += $poOrderedQty;
                $groupedSuppliers[$sId]['received_qty'] += $poReceivedQty;
                $groupedSuppliers[$sId]['rejected_qty'] += $poRejectedQty;
                $groupedSuppliers[$sId]['po_amount'] += $poAmount;
                $groupedSuppliers[$sId]['gr_amount'] += $poGrAmount;
                if ($receipts->isNotEmpty()) {
                    $groupedSuppliers[$sId]['receipt_count']++;
                    if ($isOnTime) $groupedSuppliers[$sId]['on_time_count']++;
                }
                if ($leadDays !== null) {
                    $groupedSuppliers[$sId]['total_lead_days'] += $leadDays;
                    $groupedSuppliers[$sId]['lead_count']++;
                }

                // Discrepancy scan (using accurate PO price resolution)
                foreach ($po->items as $poItem) {
                    $product = $poItem->product;
                    $productId = $poItem->product_id;
                    $ordered = (float) $poItem->quantity_ordered;
                    $poPrice = $this->resolvePoItemPrice($poItem);

                    $rec = $poReceiptMap[$productId] ?? null;
                    $received = $rec ? $rec['qty'] : 0;
                    $grPrice = $rec ? $rec['price'] : $poPrice;
                    $itemReceipts = $rec ? $rec['receipt_numbers'] : [];

                    $qtyDiff = max(0, $ordered - $received);
                    $priceDiff = $grPrice - $poPrice;
                    $hasPriceDiff = abs($priceDiff) > 0.01;

                    $rej = $poRejectMap[$productId] ?? null;
                    $rejQty = $rej ? $rej['rejected_qty'] : 0;
                    $rejectReasons = $rej ? $rej['reasons'] : [];

                    $hasRej = $rejQty > 0;
                    $hasShort = $qtyDiff > 0;

                    if ($hasPriceDiff) {
                        $priceDiscrepancyCount++;
                        $groupedSuppliers[$sId]['price_dev_count']++;
                    }

                    if ($hasShort || $hasPriceDiff || $hasRej) {
                        $impactAmount = ($qtyDiff * $poPrice) + (abs($priceDiff) * $received);
                        $counts['ALL']++;
                        if ($hasPriceDiff) $counts['PRICE_DIFF']++;
                        if ($hasRej) $counts['REJECTED']++;
                        if ($hasShort) $counts['SHORT_QTY']++;

                        $rawDiscrepancies[] = [
                            'po_number' => $po->po_number,
                            'po_date' => $po->po_date ? $po->po_date->format('d/m/Y') : '-',
                            'po_date_raw' => $po->po_date ? $po->po_date->format('Y-m-d') : '1970-01-01',
                            'supplier_name' => $po->supplier?->name ?? 'Tanpa Supplier',
                            'branch_name' => $po->branch?->name ?? '-',
                            'sku' => $product?->sku ?? '-',
                            'barcode' => $product?->barcode ?? '-',
                            'product_name' => $product?->name ?? 'Barang Tidak Dikenal',
                            'ordered_qty' => $ordered,
                            'received_qty' => $received,
                            'qty_diff' => $qtyDiff,
                            'rejected_qty' => $rejQty,
                            'reject_reason' => implode('; ', array_filter($rejectReasons)) ?: '-',
                            'po_price' => $poPrice,
                            'gr_price' => $grPrice,
                            'price_diff' => $priceDiff,
                            'has_price_deviation' => $hasPriceDiff,
                            'has_rejected' => $hasRej,
                            'has_short_qty' => $hasShort,
                            'receipt_numbers' => implode(', ', array_filter($itemReceipts)) ?: '-',
                            'impact_amount' => $impactAmount,
                        ];
                    }
                }
            }

            // Finalize scorecards
            $scorecards = [];
            foreach ($groupedSuppliers as $sup) {
                $pemenuhan = $sup['ordered_qty'] > 0 ? ($sup['received_qty'] / $sup['ordered_qty']) * 100 : 0;
                $realisasi = $sup['po_amount'] > 0 ? ($sup['gr_amount'] / $sup['po_amount']) * 100 : 0;
                $tepat = $sup['receipt_count'] > 0 ? ($sup['on_time_count'] / $sup['receipt_count']) * 100 : 100;
                $avgLead = $sup['lead_count'] > 0 ? round($sup['total_lead_days'] / $sup['lead_count'], 1) : 0;
                $outQty = max(0, $sup['ordered_qty'] - $sup['received_qty']);
                $outAmt = max(0, $sup['po_amount'] - $sup['gr_amount']);

                if ($pemenuhan >= 95 && $tepat >= 90) {
                    $grade = 'A';
                    $gradeLabel = 'Sangat Baik (A)';
                    $recommendation = 'Mitra Prima — Pertahankan & Prioritaskan Alokasi Order';
                    $badgeColor = 'success';
                } elseif ($pemenuhan >= 85) {
                    $grade = 'B';
                    $gradeLabel = 'Baik (B)';
                    $recommendation = 'Mitra Baik — Pemenuhan Stabil, Pantau Keterlambatan';
                    $badgeColor = 'info';
                } elseif ($pemenuhan >= 70) {
                    $grade = 'C';
                    $gradeLabel = 'Cukup (C)';
                    $recommendation = 'Perlu Evaluasi — Sering Kurang Kirim / Risiko Barang Kosong';
                    $badgeColor = 'warning';
                } else {
                    $grade = 'D';
                    $gradeLabel = 'Kritis (D)';
                    $recommendation = 'Bermasalah — Tinjau Ulang Kontrak & Alihkan Alokasi PO';
                    $badgeColor = 'danger';
                }

                $scorecards[] = [
                    'supplier_id' => $sup['supplier_id'],
                    'supplier_name' => $sup['supplier_name'],
                    'supplier_code' => $sup['supplier_code'],
                    'po_count' => $sup['po_count'],
                    'ordered_qty' => $sup['ordered_qty'],
                    'received_qty' => $sup['received_qty'],
                    'outstanding_qty' => $outQty,
                    'rejected_qty' => $sup['rejected_qty'],
                    'po_amount' => $sup['po_amount'],
                    'gr_amount' => $sup['gr_amount'],
                    'outstanding_amount' => $outAmt,
                    'sl_qty' => round($pemenuhan, 1),
                    'sl_amount' => round($realisasi, 1),
                    'otd_rate' => round($tepat, 1),
                    'avg_lead_time' => $avgLead,
                    'price_dev_count' => $sup['price_dev_count'],
                    'grade' => $grade,
                    'grade_label' => $gradeLabel,
                    'recommendation' => $recommendation,
                    'badge_color' => $badgeColor,
                ];
            }
            usort($scorecards, fn ($a, $b) => $b['sl_qty'] <=> $a['sl_qty']);

            // Finalize KPI
            $pemenuhanPersen = $totalOrderedQty > 0 ? ($totalReceivedQty / $totalOrderedQty) * 100 : 0;
            $realisasiPersen = $totalPoAmount > 0 ? ($totalGrAmount / $totalPoAmount) * 100 : 0;
            $tepatPersen = $poWithReceiptsCount > 0 ? ($onTimeCount / $poWithReceiptsCount) * 100 : ($totalPo > 0 ? 100 : 0);

            $kpi = [
                'total_po' => $totalPo,
                'total_po_amount' => $totalPoAmount,
                'total_gr_amount' => $totalGrAmount,
                'sl_qty' => round($pemenuhanPersen, 1),
                'sl_amount' => round($realisasiPersen, 1),
                'otd_rate' => round($tepatPersen, 1),
                'total_ordered_qty' => $totalOrderedQty,
                'total_received_qty' => $totalReceivedQty,
                'total_outstanding_qty' => max(0, $totalOrderedQty - $totalReceivedQty),
                'total_outstanding_amount' => max(0, $totalPoAmount - $totalGrAmount),
                'total_rejected_qty' => $totalRejectedQty,
                'price_discrepancy_count' => $priceDiscrepancyCount,
            ];

            return [
                'kpi' => $kpi,
                'scorecards' => $scorecards,
                'reconciliations' => $reconciliations,
                'raw_discrepancies' => $rawDiscrepancies,
                'counts' => $counts,
            ];
        });
    }

    /**
     * Calculate 5 Executive KPI Metrics
     */
    public function getKpiSummary(array $filters): array
    {
        $report = $this->getUnifiedReport($filters);
        return $report['kpi'];
    }

    /**
     * Compute Supplier Scorecards & Grading (A/B/C/D) with Interactive Pagination
     */
    public function getSupplierScorecards(array $filters): array
    {
        $report = $this->getUnifiedReport($filters);
        $scorecards = $report['scorecards'];

        if (!empty($filters['grade']) && $filters['grade'] !== 'ALL') {
            $grade = $filters['grade'];
            $scorecards = array_values(array_filter($scorecards, fn ($s) => $s['grade'] === $grade));
        }

        // Totals across all filtered suppliers
        $totalSuppliers = count($scorecards);
        $totalPo = array_sum(array_column($scorecards, 'po_count'));
        $orderedQty = array_sum(array_column($scorecards, 'ordered_qty'));
        $receivedQty = array_sum(array_column($scorecards, 'received_qty'));
        $outstandingQty = array_sum(array_column($scorecards, 'outstanding_qty'));
        $poAmount = array_sum(array_column($scorecards, 'po_amount'));
        $grAmount = array_sum(array_column($scorecards, 'gr_amount'));
        $overallPct = $orderedQty > 0 ? round(($receivedQty / $orderedQty) * 100, 1) : 0;

        // Pagination
        $perPage = max(5, (int)($filters['scorecard_per_page'] ?? 15));
        $page = max(1, (int)($filters['scorecard_page'] ?? 1));
        $totalPages = max(1, (int)ceil($totalSuppliers / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($scorecards, $offset, $perPage);

        return [
            'items' => $pagedItems,
            'all_items' => $scorecards,
            'total_items' => $totalSuppliers,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $totalSuppliers > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalSuppliers),
            'totals' => [
                'total_suppliers' => $totalSuppliers,
                'total_po' => $totalPo,
                'ordered_qty' => $orderedQty,
                'received_qty' => $receivedQty,
                'outstanding_qty' => $outstandingQty,
                'po_amount' => $poAmount,
                'gr_amount' => $grAmount,
                'overall_pct' => $overallPct,
            ],
        ];
    }

    /**
     * Compute PO-Level 3-Way Reconciliation with Interactive Pagination & Grand Totals
     */
    public function getPoReconciliations(array $filters): array
    {
        $report = $this->getUnifiedReport($filters);
        $reconciliations = $report['reconciliations'];

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $status = $filters['status'];
            $reconciliations = array_values(array_filter($reconciliations, fn ($r) => $r['status_key'] === $status));
        }

        // Totals across all filtered POs
        $totalPos = count($reconciliations);
        $orderedQty = array_sum(array_column($reconciliations, 'ordered_qty'));
        $scannedQty = array_sum(array_column($reconciliations, 'scanned_qty'));
        $receivedQty = array_sum(array_column($reconciliations, 'received_qty'));
        $outstandingQty = array_sum(array_column($reconciliations, 'outstanding_qty'));
        $poAmount = array_sum(array_column($reconciliations, 'po_amount'));
        $grAmount = array_sum(array_column($reconciliations, 'gr_amount'));
        $diffAmount = $grAmount - $poAmount;
        $overallPct = $orderedQty > 0 ? round(($receivedQty / $orderedQty) * 100, 1) : 0;

        // Pagination
        $perPage = max(5, (int)($filters['recon_per_page'] ?? 15));
        $page = max(1, (int)($filters['recon_page'] ?? 1));
        $totalPages = max(1, (int)ceil($totalPos / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($reconciliations, $offset, $perPage);

        return [
            'items' => $pagedItems,
            'all_items' => $reconciliations,
            'total_items' => $totalPos,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $totalPos > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalPos),
            'totals' => [
                'total_pos' => $totalPos,
                'ordered_qty' => $orderedQty,
                'scanned_qty' => $scannedQty,
                'received_qty' => $receivedQty,
                'outstanding_qty' => $outstandingQty,
                'po_amount' => $poAmount,
                'gr_amount' => $grAmount,
                'diff_amount' => $diffAmount,
                'overall_pct' => $overallPct,
            ],
        ];
    }

    /**
     * Compute Item & Price Discrepancies with Triage Filtering, Sorting & Pagination
     */
    public function getItemDiscrepancies(array $filters): array
    {
        $report = $this->getUnifiedReport($filters);
        $rawDiscrepancies = $report['raw_discrepancies'];
        $counts = $report['counts'];

        // Apply Triage Filter
        $typeFilter = $filters['discrepancy_type'] ?? 'ALL';
        $filtered = array_filter($rawDiscrepancies, function ($item) use ($typeFilter) {
            if ($typeFilter === 'PRICE_DIFF') return $item['has_price_deviation'];
            if ($typeFilter === 'REJECTED') return $item['has_rejected'];
            if ($typeFilter === 'SHORT_QTY') return $item['has_short_qty'];
            return true;
        });

        // Apply Sorting
        $sortBy = $filters['discrepancy_sort'] ?? 'impact_desc';
        usort($filtered, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'qty_desc') {
                return $b['qty_diff'] <=> $a['qty_diff'];
            } elseif ($sortBy === 'date_desc') {
                return strcmp($b['po_date_raw'], $a['po_date_raw']);
            } else {
                return $b['impact_amount'] <=> $a['impact_amount'];
            }
        });

        // Apply Pagination
        $perPage = max(5, (int)($filters['discrepancy_per_page'] ?? 15));
        $page = max(1, (int)($filters['discrepancy_page'] ?? 1));
        $totalItems = count($filtered);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $pagedItems = array_slice($filtered, $offset, $perPage);

        // Subtotals for current filtered items
        $totalOrderedQty = array_sum(array_column($filtered, 'ordered_qty'));
        $totalReceivedQty = array_sum(array_column($filtered, 'received_qty'));
        $totalQtyDiff = array_sum(array_column($filtered, 'qty_diff'));
        $totalRejectedQty = array_sum(array_column($filtered, 'rejected_qty'));

        $totalShortQtyAmount = 0;
        $totalRejectedAmount = 0;
        $totalNetPriceDiffAmount = 0;
        $totalImpactFiltered = 0;

        foreach ($filtered as $item) {
            $totalShortQtyAmount += ($item['qty_diff'] * $item['po_price']);
            $totalRejectedAmount += ($item['rejected_qty'] * $item['po_price']);
            $totalNetPriceDiffAmount += ($item['price_diff'] * $item['received_qty']);
            $totalImpactFiltered += $item['impact_amount'];
        }

        // Global reconciliation summary for the report scope (Tab 1 & Tab 2 comparison)
        $scopePoAmount = array_sum(array_column($report['reconciliations'], 'po_amount'));
        $scopeGrAmount = array_sum(array_column($report['reconciliations'], 'gr_amount'));
        $scopeNetDiff = $scopePoAmount - $scopeGrAmount;

        $scopeShortQtyAmount = 0;
        $scopeNetPriceDevAmount = 0;
        $scopeRejectedAmount = 0;
        foreach ($rawDiscrepancies as $d) {
            $scopeShortQtyAmount += ($d['qty_diff'] * $d['po_price']);
            $scopeNetPriceDevAmount += ($d['price_diff'] * $d['received_qty']);
            $scopeRejectedAmount += ($d['rejected_qty'] * $d['po_price']);
        }

        return [
            'items' => $pagedItems,
            'all_items' => $filtered,
            'counts' => $counts,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $totalItems > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalItems),
            'totals' => [
                'ordered_qty' => $totalOrderedQty,
                'received_qty' => $totalReceivedQty,
                'qty_diff' => $totalQtyDiff,
                'rejected_qty' => $totalRejectedQty,
                'short_qty_amount' => $totalShortQtyAmount,
                'rejected_amount' => $totalRejectedAmount,
                'net_price_diff_amount' => $totalNetPriceDiffAmount,
                'impact_amount' => $totalImpactFiltered,
            ],
            'reconciliation' => [
                'po_amount' => $scopePoAmount,
                'gr_amount' => $scopeGrAmount,
                'net_diff' => $scopeNetDiff,
                'short_qty_amount' => $scopeShortQtyAmount,
                'net_price_dev_amount' => $scopeNetPriceDevAmount,
                'rejected_amount' => $scopeRejectedAmount,
            ],
        ];
    }

    /**
     * Compute Detailed PO breakdown
     */
    public function getPoDetail(string $poId): ?array
    {
        $po = PurchaseOrder::with(['supplier', 'division', 'branch', 'items.product', 'warehouseChecks.items', 'goodsReceipts.items'])
            ->find($poId);

        if (!$po) {
            return null;
        }

        $receipts = $po->goodsReceipts->where('status', '!=', 'CANCELLED');

        $items = [];
        foreach ($po->items as $poItem) {
            $product = $poItem->product;
            $productId = $poItem->product_id;
            $orderedQty = (float) $poItem->quantity_ordered;
            $poPrice = $this->resolvePoItemPrice($poItem);

            $receivedQty = 0;
            $grPrice = $poPrice;
            foreach ($receipts as $gr) {
                $grItem = $gr->items->firstWhere('product_id', $productId);
                if ($grItem) {
                    $receivedQty += (float) $grItem->quantity_received;
                    $grPrice = $this->resolveGrItemPrice($grItem, $poPrice);
                }
            }

            $scannedQty = 0;
            $rejectedQty = 0;
            $rejectReasons = [];
            foreach ($po->warehouseChecks as $wc) {
                $wcItem = $wc->items->firstWhere('product_id', $productId);
                if ($wcItem) {
                    $scannedQty += (float) $wcItem->qty_scanned;
                    $rejectedQty += (float) $wcItem->qty_rejected;
                    if ($wcItem->rejected_reason) {
                        $rejectReasons[] = $wcItem->rejected_reason;
                    }
                }
            }

            $pemenuhanItemPersen = $orderedQty > 0 ? ($receivedQty / $orderedQty) * 100 : 0;
            $priceDiff = $grPrice - $poPrice;

            $items[] = [
                'sku' => $product?->sku ?? '-',
                'barcode' => $product?->barcode ?? '-',
                'product_name' => $product?->name ?? '-',
                'ordered_qty' => $orderedQty,
                'scanned_qty' => $scannedQty,
                'received_qty' => $receivedQty,
                'outstanding_qty' => max(0, $orderedQty - $receivedQty),
                'rejected_qty' => $rejectedQty,
                'reject_reason' => implode('; ', array_filter($rejectReasons)) ?: '-',
                'po_price' => $poPrice,
                'gr_price' => $grPrice,
                'price_diff' => $priceDiff,
                'po_subtotal' => (float) $poItem->subtotal,
                'gr_subtotal' => $receivedQty * $grPrice,
                'sl_item' => round($pemenuhanItemPersen, 1),
            ];
        }

        $isClosed = strtolower((string)$po->status) === 'closed';

        return [
            'id' => $po->id,
            'po_number' => $po->po_number,
            'po_date' => $po->po_date ? $po->po_date->format('d M Y') : '-',
            'expected_date' => $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : '-',
            'expired_date' => $po->expired_date ? $po->expired_date->format('d M Y') : '-',
            'supplier_name' => $po->supplier?->name ?? '-',
            'division_name' => $po->division?->name ?? '-',
            'branch_name' => $po->branch?->name ?? '-',
            'total_amount' => (float) $po->total_amount,
            'notes' => $po->notes,
            'status' => $po->status,
            'is_closed' => $isClosed,
            'items' => $items,
        ];
    }

    /**
     * Force Close a single PO and cancel its outstanding shortage
     */
    public function closePo(string $poId, string $reason = 'Sisa PO Dihanguskan / Ditutup Manual'): bool
    {
        $po = PurchaseOrder::find($poId);
        if (!$po) {
            return false;
        }

        $po->status = 'closed';
        $timestamp = Carbon::now()->format('d/m/Y H:i');
        $po->notes = trim(($po->notes ?? '') . " [Sisa ditutup/dihanguskan pada {$timestamp}: {$reason}]");
        $po->save();

        $this->clearReportCache();

        return true;
    }

    /**
     * Force Close all expired POs with outstanding quantities
     */
    public function closeAllExpiredPos(): int
    {
        $expiredPos = PurchaseOrder::whereNotIn('status', ['closed', 'cancelled'])
            ->whereNotNull('expired_date')
            ->whereDate('expired_date', '<', Carbon::now()->toDateString())
            ->get();

        $count = 0;
        $timestamp = Carbon::now()->format('d/m/Y H:i');

        foreach ($expiredPos as $po) {
            $po->status = 'closed';
            $po->notes = trim(($po->notes ?? '') . " [Auto-close PO Expired pada {$timestamp}]");
            $po->save();
            $count++;
        }

        if ($count > 0) {
            $this->clearReportCache();
        }

        return $count;
    }

    /**
     * Export report data as CSV (Zero Jargon Indonesian Headers)
     */
    public function exportCsv(string $activeTab, array $filters): StreamedResponse
    {
        $timestamp = Carbon::now()->format('Ymd_His');

        if ($activeTab === 'scorecard') {
            $filename = "Laporan_Kinerja_Supplier_{$timestamp}.csv";
            $res = $this->getSupplierScorecards($filters);
            $data = $res['all_items'] ?? [];
            
            $callback = function () use ($data) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Kode Supplier', 'Nama Supplier', 'Total PO', 'Qty Pesan', 'Qty Terima', 'Sisa Kurang', 'Qty Rusak/Reject', 'Nominal PO (Rp)', 'Nominal Faktur (Rp)', 'Sisa Nominal (Rp)', '% Pemenuhan Barang', '% Realisasi Nilai', '% Tepat Waktu', 'Waktu Tunggu Kirim (Hari)', 'Jml Selisih Harga', 'Grade Rapor', 'Rekomendasi Manajemen']);

                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['supplier_code'],
                        $row['supplier_name'],
                        $row['po_count'],
                        $row['ordered_qty'],
                        $row['received_qty'],
                        $row['outstanding_qty'],
                        $row['rejected_qty'],
                        $row['po_amount'],
                        $row['gr_amount'],
                        $row['outstanding_amount'],
                        $row['sl_qty'] . '%',
                        $row['sl_amount'] . '%',
                        $row['otd_rate'] . '%',
                        $row['avg_lead_time'],
                        $row['price_dev_count'],
                        $row['grade'],
                        $row['recommendation'],
                    ]);
                }
                fclose($handle);
            };
        } elseif ($activeTab === 'reconciliation') {
            $filename = "Rekonsiliasi_PO_vs_Penerimaan_{$timestamp}.csv";
            $res = $this->getPoReconciliations($filters);
            $data = $res['all_items'] ?? [];

            $callback = function () use ($data) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['No PO', 'Tgl PO', 'Tgl Expired', 'Supplier', 'Divisi', 'Cabang', 'Qty Order', 'Qty Scan Fisik', 'Qty Terima Faktur', 'Sisa Kurang', 'Qty Reject', 'Total PO (Rp)', 'Total Faktur (Rp)', 'Sisa Rp', '% Pemenuhan Barang', 'Waktu Tunggu (Hari)', 'No Penerimaan Faktur', 'Status Pemenuhan']);

                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['po_number'],
                        $row['po_date'],
                        $row['expired_date'],
                        $row['supplier_name'],
                        $row['division_name'],
                        $row['branch_name'],
                        $row['ordered_qty'],
                        $row['scanned_qty'],
                        $row['received_qty'],
                        $row['outstanding_qty'],
                        $row['rejected_qty'],
                        $row['po_amount'],
                        $row['gr_amount'],
                        $row['outstanding_amount'],
                        $row['sl_qty'] . '%',
                        $row['lead_days'] ?? '-',
                        $row['receipt_numbers'],
                        $row['status_label'],
                    ]);
                }
                fclose($handle);
            };
        } else {
            $filename = "Audit_Selisih_Barang_dan_Harga_{$timestamp}.csv";
            $res = $this->getItemDiscrepancies($filters);
            $data = $res['all_items'] ?? [];

            $callback = function () use ($data) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['No PO', 'Tgl PO', 'Supplier', 'Cabang', 'SKU', 'Barcode', 'Nama Barang', 'Qty PO', 'Qty Terima', 'Selisih Kurang', 'Qty Rusak', 'Alasan Rusak', 'Harga PO (Rp)', 'Harga Faktur (Rp)', 'Selisih Harga', 'No Faktur', 'Dampak Nominal (Rp)']);

                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row['po_number'],
                        $row['po_date'],
                        $row['supplier_name'],
                        $row['branch_name'],
                        $row['sku'],
                        $row['barcode'],
                        $row['product_name'],
                        $row['ordered_qty'],
                        $row['received_qty'],
                        $row['qty_diff'],
                        $row['rejected_qty'],
                        $row['reject_reason'],
                        $row['po_price'],
                        $row['gr_price'],
                        $row['price_diff'],
                        $row['receipt_numbers'],
                        $row['impact_amount'],
                    ]);
                }
                fclose($handle);
            };
        }

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }
}
