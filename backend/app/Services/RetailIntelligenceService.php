<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\MarketBasketRule;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Stock;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;

class RetailIntelligenceService
{
    /**
     * Dapatkan tarif PPN organisasi yang aktif (default 11%).
     */
    public static function getActiveTaxRate(?string $orgId = null): float
    {
        try {
            $orgId = $orgId ?: auth()->user()?->organization_id;
            if ($orgId) {
                $taxRate = Organization::where("id", $orgId)->value("tax_rate");
                if ($taxRate !== null && $taxRate !== "") {
                    return (float) $taxRate;
                }
            }
            $firstOrgTax = Organization::value("tax_rate");
            if ($firstOrgTax !== null && $firstOrgTax !== "") {
                return (float) $firstOrgTax;
            }
        } catch (\Throwable $e) {
            // Fallback default
        }

        return 11.0;
    }

    /**
     * Resolusi branch ID & nama branch yang sedang dievaluasi.
     */
    public static function resolveBranchContext(?string $selectedBranchId = null): array
    {
        $user = auth()->user();
        $userBranchId = $user?->branch_id;
        $allBranches = Branch::where("is_active", true)->orderBy("sort_order")->get();
        $totalBranchesCount = $allBranches->count();

        $evalBranchId = null;
        $branchLabel = "Semua Cabang";
        $isSpecificBranch = false;

        if ($userBranchId !== null) {
            // User cabang: terkunci pada cabangnya sendiri
            $evalBranchId = $userBranchId;
            $b = $allBranches->firstWhere("id", $userBranchId) ?: Branch::find($userBranchId);
            $branchLabel = $b?->name ?? "Cabang";
            $isSpecificBranch = true;
        } elseif ($selectedBranchId !== null && $selectedBranchId !== "") {
            // Super admin memilih cabang tertentu
            $evalBranchId = $selectedBranchId;
            $b = $allBranches->firstWhere("id", $selectedBranchId) ?: Branch::find($selectedBranchId);
            $branchLabel = $b?->name ?? "Cabang Terpilih";
            $isSpecificBranch = true;
        } elseif ($totalBranchesCount === 1) {
            // Hanya 1 cabang terdaftar di sistem: otomatis pakai cabang tersebut
            $firstBranch = $allBranches->first();
            $evalBranchId = $firstBranch->id;
            $branchLabel = $firstBranch->name;
            $isSpecificBranch = true;
        } else {
            // Multi cabang - mode konsolidasi nasional
            $evalBranchId = null;
            $branchLabel = "Semua Cabang ({$totalBranchesCount} Cabang)";
            $isSpecificBranch = false;
        }

        return [
            "branchId" => $evalBranchId,
            "branchLabel" => $branchLabel,
            "isSpecificBranch" => $isSpecificBranch,
            "totalBranchesCount" => $totalBranchesCount,
            "allBranches" => $allBranches,
        ];
    }

    /**
     * Ringkasan KPI untuk Widget Header.
     */
    public static function getPerformanceStats(Product $product, ?string $selectedBranchId = null): array
    {
        $context = self::resolveBranchContext($selectedBranchId);
        $branchId = $context["branchId"];
        $branchLabel = $context["branchLabel"];
        $unit = $product->unit_of_measure ?: "pcs";
        $leadTime = (int) ($product->lead_time_days ?: 3);
        $criticalDays = max($leadTime + 4, 7);

        // 1. Stok Fisik & Nilai Investasi
        $qoh = 0.0;
        try {
            if ($branchId) {
                $qoh = (float) (Stock::where("product_id", $product->id)->where("branch_id", $branchId)->value("quantity_on_hand") ?? 0);
            } else {
                $qoh = (float) (Stock::where("product_id", $product->id)->sum("quantity_on_hand") ?? 0);
            }
        } catch (\Throwable $e) {
            $qoh = 0.0;
        }

        $cogs = (float) ($product->cost_price_tax ?: $product->cost_price);
        $sellingPrice = (float) ($product->harga_jual_1 ?: $product->selling_price);
        $invValue = $qoh * $cogs;
        $profitPerUnit = max(0, $sellingPrice - $cogs);

        // 2. Sales Velocity & Sparkline 7 Hari
        $sales30Days = 0.0;
        $salesChart = [0, 0, 0, 0, 0, 0, 0];
        try {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $sales30Days = (float) TransactionItem::where("product_id", $product->id)
                ->whereHas("transaction", function ($q) use ($branchId, $thirtyDaysAgo) {
                    if ($branchId) {
                        $q->where("branch_id", $branchId);
                    }
                    $q->where("created_at", ">=", $thirtyDaysAgo)
                      ->where("is_voided", false);
                })
                ->sum("quantity");

            for ($i = 6; $i >= 0; $i--) {
                $targetDate = Carbon::now()->subDays($i)->toDateString();
                $qtyDay = (float) TransactionItem::where("product_id", $product->id)
                    ->whereHas("transaction", function ($q) use ($branchId, $targetDate) {
                        if ($branchId) {
                            $q->where("branch_id", $branchId);
                        }
                        $q->whereDate("created_at", $targetDate)
                          ->where("is_voided", false);
                    })
                    ->sum("quantity");
                $salesChart[6 - $i] = (int) $qtyDay;
            }
        } catch (\Throwable $e) {
            $sales30Days = 0.0;
            $salesChart = [0, 0, 0, 0, 0, 0, 0];
        }

        $dailyAvg = round($sales30Days / 30, 2);
        $doh = $dailyAvg > 0 ? (int) round($qoh / $dailyAvg) : ($qoh > 0 ? 999 : 0);

        // 3. GMROI
        $annualGrossProfit = ($sales30Days * $profitPerUnit) * 12;
        $gmroi = $invValue > 0 ? round($annualGrossProfit / $invValue, 2) : ($annualGrossProfit > 0 ? 3.2 : 0.0);

        // 4. Klasifikasi 4 Kuadran Cerdas
        if ($qoh <= 0 && $sales30Days == 0) {
            // Kuadran A: Stok 0 + Penjualan 0 (Produk Baru / Non-Aktif)
            $stockColor = "gray";
            $stockDesc = "Stok Kosong ({$branchLabel}) - Belum ada riwayat jual";
            $stockIcon = "heroicon-m-information-circle";
            $lajuLabel = "Belum Ada Penjualan";
            $lajuColor = "gray";
        } elseif ($qoh <= 0 && $sales30Days > 0) {
            // Kuadran B: Stok 0 + Penjualan > 0 (Barang Laris Habis / Stockout)
            $stockColor = "danger";
            $stockDesc = "⚠️ STOK HABIS ({$branchLabel}) - Potensi Kehilangan Penjualan!";
            $stockIcon = "heroicon-m-exclamation-triangle";
            $lajuLabel = "Barang Laris (Habis)";
            $lajuColor = "danger";
        } elseif ($qoh > 0 && $sales30Days == 0) {
            // Kuadran D: Stok Ada + Penjualan 0 (Dead Stock)
            $stockColor = "warning";
            $stockDesc = "Stok Tersedia {$qoh} {$unit} ({$branchLabel}) - Belum terjual 30 hari";
            $stockIcon = "heroicon-m-clock";
            $lajuLabel = "Barang Macet (Dead Stock)";
            $lajuColor = "danger";
        } elseif ($dailyAvg > 0 && $doh <= $criticalDays) {
            // Kuadran C: Stok Menipis
            $stockColor = "warning";
            $stockDesc = "⚠️ Kritis: Cukup ~{$doh} Hari ({$branchLabel})";
            $stockIcon = "heroicon-m-exclamation-triangle";
            $lajuLabel = $dailyAvg >= 5 ? "Sangat Laris (A)" : "Cukup Laris (B)";
            $lajuColor = "warning";
        } else {
            // Kuadran E: Stok Aman
            $stockColor = "success";
            $stockDesc = "Stok Aman untuk ~{$doh} Hari ({$branchLabel})";
            $stockIcon = "heroicon-m-check-circle";
            $lajuLabel = $dailyAvg >= 5 ? "Sangat Laris (A)" : ($dailyAvg >= 1 ? "Cukup Laris (B)" : "Kurang Laris (C)");
            $lajuColor = $dailyAvg >= 1 ? "success" : "gray";
        }

        // 5. Margin & Price Status
        $margin1 = (float) ($product->margin_gol_1 ?: 0);
        $isLoss = $cogs > 0 && $sellingPrice > 0 && $sellingPrice < $cogs;
        $marginColor = $isLoss ? "danger" : ($margin1 < 5 && $margin1 > 0 ? "warning" : "success");
        $marginDesc = $isLoss 
            ? "🔴 JUAL RUGI (Harga Jual di Bawah Modal)" 
            : "Modal: Rp " . number_format($cogs, 0, ",", ".") . " ➔ Jual: Rp " . number_format($sellingPrice, 0, ",", ".");

        // 6. Produktivitas Modal Stok (Tanpa Permisalan Rp 1.000 yang Rancu)
        if ($gmroi >= 2.0) {
            $modalLabel = "Sangat Produktif (" . number_format($gmroi, 1) . "x)";
            $modalDesc = "Modal belanja berputar cepat & menghasilkan laba tinggi setahun";
            $modalColor = "success";
        } elseif ($gmroi >= 1.0) {
            $modalLabel = "Cukup Stabil (" . number_format($gmroi, 1) . "x)";
            $modalDesc = "Modal belanja berputar normal dan stabil menghasilkan laba";
            $modalColor = "warning";
        } elseif ($gmroi > 0) {
            $modalLabel = "Kurang Produktif (" . number_format($gmroi, 1) . "x)";
            $modalDesc = "Perputaran modal lambat, perlu ditingkatkan penjualannya";
            $modalColor = "gray";
        } else {
            $modalLabel = "Belum Ada Putaran (0.0x)";
            $modalDesc = "Belum ada riwayat perputaran modal untuk barang ini";
            $modalColor = "gray";
        }

        return [
            "qoh" => $qoh,
            "unit" => $unit,
            "branchName" => $branchLabel,
            "stockDesc" => $stockDesc,
            "stockColor" => $stockColor,
            "stockIcon" => $stockIcon,
            "salesChart" => $salesChart,
            "sales30Days" => $sales30Days,
            "dailyAvg" => $dailyAvg,
            "lajuLabel" => $lajuLabel,
            "lajuColor" => $lajuColor,
            "gmroi" => $gmroi,
            "modalLabel" => $modalLabel,
            "modalDesc" => $modalDesc,
            "modalColor" => $modalColor,
            "margin1" => $margin1,
            "marginColor" => $marginColor,
            "marginDesc" => $marginDesc,
            "cogs" => $cogs,
            "sellingPrice" => $sellingPrice,
        ];
    }

    /**
     * Data Lengkap untuk Hub Retail Intelligence (3 Panel).
     */
    public static function getIntelligenceData(Product $product, ?string $selectedBranchId = null): array
    {
        $context = self::resolveBranchContext($selectedBranchId);
        $branchId = $context["branchId"];
        $branchLabel = $context["branchLabel"];
        $allBranches = $context["allBranches"];
        $totalBranchesCount = $context["totalBranchesCount"];

        $unit = $product->unit_of_measure ?: "pcs";
        $minStock = (int) ($product->reorder_point ?: 10);
        $leadTime = (int) ($product->lead_time_days ?: 3);
        $criticalDays = max($leadTime + 4, 7);
        $supplier = $product->supplier;

        // 1. Stok & Penjualan
        $qoh = 0.0;
        try {
            if ($branchId) {
                $qoh = (float) (Stock::where("product_id", $product->id)->where("branch_id", $branchId)->value("quantity_on_hand") ?? 0);
            } else {
                $qoh = (float) (Stock::where("product_id", $product->id)->sum("quantity_on_hand") ?? 0);
            }
        } catch (\Throwable $e) {
            $qoh = 0.0;
        }

        $cogs = (float) ($product->cost_price_tax ?: $product->cost_price);
        $sellingPrice = (float) ($product->harga_jual_1 ?: $product->selling_price);
        $invValue = $qoh * $cogs;
        $profitPerUnit = max(0, $sellingPrice - $cogs);

        $sales30Days = 0.0;
        try {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $sales30Days = (float) TransactionItem::where("product_id", $product->id)
                ->whereHas("transaction", function ($q) use ($branchId, $thirtyDaysAgo) {
                    if ($branchId) {
                        $q->where("branch_id", $branchId);
                    }
                    $q->where("created_at", ">=", $thirtyDaysAgo)
                      ->where("is_voided", false);
                })
                ->sum("quantity");
        } catch (\Throwable $e) {
            $sales30Days = 0.0;
        }

        $dailyAvg = round($sales30Days / 30, 2);
        $doh = $dailyAvg > 0 ? (int) round($qoh / $dailyAvg) : ($qoh > 0 ? 999 : 0);
        $annualGrossProfit = ($sales30Days * $profitPerUnit) * 12;
        $gmroi = $invValue > 0 ? round($annualGrossProfit / $invValue, 2) : ($annualGrossProfit > 0 ? 3.2 : 0.0);

        // 2. Tren Penjualan 6 Bulan
        $monthlyTrends = [];
        $maxMonthQty = 0;
        try {
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

                $monthQty = (float) TransactionItem::where("product_id", $product->id)
                    ->whereHas("transaction", function ($q) use ($branchId, $monthStart, $monthEnd) {
                        if ($branchId) {
                            $q->where("branch_id", $branchId);
                        }
                        $q->whereBetween("created_at", [$monthStart, $monthEnd])
                          ->where("is_voided", false);
                    })
                    ->sum("quantity");

                if ($monthQty > $maxMonthQty) {
                    $maxMonthQty = $monthQty;
                }

                $monthlyTrends[] = [
                    "month" => $monthStart->translatedFormat("M Y"),
                    "qty" => (int) $monthQty,
                    "price" => $sellingPrice,
                ];
            }
        } catch (\Throwable $e) {
            $monthlyTrends = [];
        }

        // 3. Kinerja Pengiriman Pemasok (OTIF) & In-Flight PO
        $otifScore = 100;
        $inFlightPOs = collect();
        try {
            $poItems = PurchaseOrderItem::with("purchaseOrder")
                ->where("product_id", $product->id)
                ->whereHas("purchaseOrder", function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where("branch_id", $branchId);
                    }
                })
                ->latest()
                ->take(10)
                ->get();

            $totalOrdered = $poItems->sum("quantity_ordered");
            $totalReceived = $poItems->sum("quantity_received");
            if ($totalOrdered > 0) {
                $otifScore = round(($totalReceived / $totalOrdered) * 100, 0);
            }

            $inFlightPOs = $poItems->filter(function ($item) {
                $status = strtolower($item->purchaseOrder?->status ?? "");
                return in_array($status, ["pending", "ordered", "sent", "partial", "draft", "approved"]);
            });
        } catch (\Throwable $e) {
            $otifScore = 100;
        }

        // 4. Barang yang Sering Dibeli Bersamaan (Market Basket)
        $topAffinityItems = [];
        try {
            $rules = MarketBasketRule::with("consequent")
                ->where("antecedent_id", $product->id)
                ->orderByDesc("confidence")
                ->take(3)
                ->get();

            if ($rules->isNotEmpty()) {
                foreach ($rules as $r) {
                    $topAffinityItems[] = [
                        "name" => $r->consequent_name ?: ($r->consequent?->name ?? "Produk Terkait"),
                        "sku" => $r->consequent?->sku ?? "-",
                        "confidence" => round($r->confidence * 100, 0) . "% Pembeli Membeli Bareng",
                        "lift" => round($r->lift, 1) . "x",
                    ];
                }
            } else {
                $txIds = TransactionItem::where("product_id", $product->id)
                    ->latest()
                    ->take(50)
                    ->pluck("transaction_id");

                if ($txIds->isNotEmpty()) {
                    $coItems = TransactionItem::with("product")
                        ->whereIn("transaction_id", $txIds)
                        ->where("product_id", "!=", $product->id)
                        ->selectRaw("product_id, COUNT(*) as frequency")
                        ->groupBy("product_id")
                        ->orderByDesc("frequency")
                        ->take(3)
                        ->get();

                    foreach ($coItems as $co) {
                        if ($co->product) {
                            $pct = round(($co->frequency / max(1, $txIds->count())) * 100);
                            $topAffinityItems[] = [
                                "name" => $co->product->name,
                                "sku" => $co->product->sku,
                                "confidence" => "{$pct}% Pembeli Membeli Bareng",
                                "lift" => $co->frequency . " Transaksi",
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $topAffinityItems = [];
        }

        // 5. Cek Peluang Transfer Antar Cabang (Inter-Branch Transfer Opportunity)
        $transferOpportunity = null;
        if ($totalBranchesCount > 1 && $branchId) {
            $isDeficit = ($qoh <= 0) || ($dailyAvg > 0 && $doh <= $criticalDays);
            if ($isDeficit) {
                // Cari cabang lain yang memiliki surplus stok
                $surplusStock = Stock::with("branch")
                    ->where("product_id", $product->id)
                    ->where("branch_id", "!=", $branchId)
                    ->where("quantity_on_hand", ">", 0)
                    ->orderByDesc("quantity_on_hand")
                    ->first();

                if ($surplusStock && $surplusStock->quantity_on_hand >= 3) {
                    $srcBranchName = $surplusStock->branch?->name ?? "Cabang Lain";
                    $srcQoh = (float) $surplusStock->quantity_on_hand;
                    $suggestedTransferQty = min($srcQoh, max(1, (int) ($product->reorder_qty ?: 10)));

                    $transferOpportunity = [
                        "source_branch_id" => $surplusStock->branch_id,
                        "source_branch_name" => $srcBranchName,
                        "source_qoh" => $srcQoh,
                        "destination_branch_id" => $branchId,
                        "destination_branch_name" => $branchLabel,
                        "suggested_qty" => $suggestedTransferQty,
                        "action_url" => route("filament.admin.resources.stock-transfers.create") . "?product_id={$product->id}&source_branch_id={$surplusStock->branch_id}&destination_branch_id={$branchId}&quantity={$suggestedTransferQty}",
                    ];
                }
            }
        }

        // ========================================================
        // 6. SARAN TINDAKAN OTOMATIS (4 KUADRAN LOGIS & CERDAS)
        // ========================================================
        $prescriptiveActions = [];

        // REKOMENDASI TRANSFER ANTAR CABANG (Prioritas Internal Pertama)
        if ($transferOpportunity) {
            $prescriptiveActions[] = [
                "type" => "transfer",
                "title" => "📦 Peluang Transfer Stok dari {$transferOpportunity['source_branch_name']}",
                "message" => "Stok di {$transferOpportunity['destination_branch_name']} menipis ({$qoh} {$unit}), sedangkan di {$transferOpportunity['source_branch_name']} tersedia surplus {$transferOpportunity['source_qoh']} {$unit}. Disarankan melakukan mutasi stok {$transferOpportunity['suggested_qty']} {$unit} untuk menghemat modal PO.",
                "action_label" => "Buat Surat Transfer Stok",
                "action_url" => $transferOpportunity["action_url"],
            ];
        }

        // EVALUASI KEBUTUHAN PO SUPLIER
        if ($inFlightPOs->isNotEmpty()) {
            $poLatest = $inFlightPOs->first();
            $poNumber = $poLatest->purchaseOrder?->po_number ?? "PO Aktif";
            $poQty = (int) $poLatest->quantity_ordered;
            $prescriptiveActions[] = [
                "type" => "info",
                "title" => "🚚 Barang Sedang Dalam Pengiriman Suplier",
                "message" => "Pesanan #{$poNumber} sebanyak {$poQty} {$unit} sedang dalam proses kirim dari suplier. Anda tidak perlu membuat pesanan ganda.",
                "action_label" => "Lihat Surat Pesanan / Terima Barang",
                "action_url" => route("filament.admin.resources.purchase-orders.edit", ["record" => $poLatest->purchase_order_id]),
            ];
        } elseif ($qoh <= 0 && $sales30Days == 0) {
            // KUADRAN A: Stok 0 + Penjualan 0 (Produk Baru / Non-Aktif)
            $prescriptiveActions[] = [
                "type" => "neutral",
                "title" => "⚪ Belum Ada Riwayat Penjualan (Stok Kosong)",
                "message" => "Produk ini belum memiliki riwayat transaksi di sistem dan stok fisik saat ini 0. Jika produk ini aktif ingin dipasarkan, silakan buat Pesanan Pembelian perdana. Jika produk sudah tidak dipasarkan, Anda dapat menonaktifkan status produk.",
                "action_label" => $product->supplier_id ? "Buat PO Inisialisasi Perdana" : null,
                "action_url" => $product->supplier_id ? route("filament.admin.resources.purchase-orders.create") . "?supplier_id={$product->supplier_id}" . ($branchId ? "&branch_id={$branchId}" : "") : null,
            ];
        } elseif ($qoh <= 0 && $sales30Days > 0) {
            // KUADRAN B: Stok 0 + Penjualan > 0 (Barang Laris Habis / Stockout Kritis)
            $prescriptiveActions[] = [
                "type" => "danger",
                "title" => "🚨 Stok Habis (Potensi Kehilangan Penjualan)",
                "message" => "Barang ini aktif terjual (~{$dailyAvg} {$unit}/hari) namun stok fisik habis! Segera buat pesanan ke suplier agar toko tidak kehilangan omset.",
                "action_label" => "Buat Draft Pesanan Pembelian (PO)",
                "action_url" => route("filament.admin.resources.purchase-orders.create") . "?supplier_id={$product->supplier_id}" . ($branchId ? "&branch_id={$branchId}" : ""),
            ];
        } elseif ($qoh > 0 && $dailyAvg > 0 && $doh <= $criticalDays) {
            // KUADRAN C: Stok Menipis
            $prescriptiveActions[] = [
                "type" => "warning",
                "title" => "⚠️ Stok Menipis (Waktunya Pesan Ulang)",
                "message" => "Sisa stok ({$qoh} {$unit}) diperkirakan hanya cukup untuk ~{$doh} hari, sedangkan pengiriman suplier butuh waktu {$leadTime} hari. Disarankan segera memesan ulang.",
                "action_label" => "Buat Draft Pesanan Pembelian (PO)",
                "action_url" => route("filament.admin.resources.purchase-orders.create") . "?supplier_id={$product->supplier_id}" . ($branchId ? "&branch_id={$branchId}" : ""),
            ];
        } elseif ($qoh > 0 && $sales30Days == 0) {
            // KUADRAN D: Stok Ada + Penjualan 0 (Dead Stock)
            $prescriptiveActions[] = [
                "type" => "warning",
                "title" => "⚠️ Barang Macet / Kurang Laku (Modal Mengendap)",
                "message" => "Ada {$qoh} {$unit} (Nilai modal: Rp " . number_format($invValue, 0, ",", ".") . ") belum terjual dalam 30 hari terakhir. Disarankan retur ke suplier atau buat promo tebus murah.",
                "action_label" => null,
                "action_url" => null,
            ];
        } else {
            // KUADRAN E: Stok Aman
            $prescriptiveActions[] = [
                "type" => "success",
                "title" => "🟢 Persediaan Stok Sangat Aman",
                "message" => "Sisa stok ({$qoh} {$unit}) diperkirakan mencukupi untuk ~{$doh} hari ke depan. Belum diperlukan pemesanan ulang ke suplier.",
                "action_label" => null,
                "action_url" => null,
            ];
        }

        // REKOMENDASI PELUANG BUNDLING / RAK
        if (!empty($topAffinityItems)) {
            $firstPair = $topAffinityItems[0]["name"];
            $prescriptiveActions[] = [
                "type" => "success",
                "title" => "💡 Peluang Jual Bersama di Kasir / Rak Toko",
                "message" => "Pelanggan sering membeli barang ini bersamaan dengan '{$firstPair}'. Letakkan berdampingan di rak atau tawarkan paket bundling.",
                "action_label" => null,
                "action_url" => null,
            ];
        }

        return [
            "hasData" => true,
            "product" => $product,
            "unit" => $unit,
            "qoh" => $qoh,
            "doh" => $doh,
            "dailyAvg" => $dailyAvg,
            "sales30Days" => $sales30Days,
            "gmroi" => $gmroi,
            "annualGrossProfit" => $annualGrossProfit,
            "invValue" => $invValue,
            "cogs" => $cogs,
            "sellingPrice" => $sellingPrice,
            "marginPct" => (float) ($product->margin_gol_1 ?: 0),
            "monthlyTrends" => $monthlyTrends,
            "maxMonthQty" => max(1, $maxMonthQty),
            "otifScore" => $otifScore,
            "supplierName" => $supplier?->name ?? "Pemasok Utama",
            "leadTime" => $leadTime,
            "topAffinityItems" => $topAffinityItems,
            "prescriptiveActions" => $prescriptiveActions,
            "transferOpportunity" => $transferOpportunity,
            "branchLabel" => $branchLabel,
        ];
    }
}
