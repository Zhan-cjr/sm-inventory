<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\SuggestedStockTransferService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetupDummyBranchCommand extends Command
{
    protected $signature = "app:setup-dummy-branch {--rollback : Hapus cabang dummy dan seluruh data simulasinya}";
    protected $description = "Menyiapkan atau menghapus cabang dummy SELAMAT BLK untuk pengujian fitur Saran Mutasi Antar Cabang Cerdas";

    public function handle(): int
    {
        if ($this->option("rollback")) {
            return $this->rollbackDummyBranch();
        }

        return $this->setupDummyBranch();
    }

    protected function setupDummyBranch(): int
    {
        $this->info("Menyiapkan Cabang Dummy SELAMAT BLK untuk pengujian Rebalancing Stok AI...");

        $existingBranch = Branch::where("is_active", true)->first();
        if (!$existingBranch) {
            $this->error("Cabang utama aktif tidak ditemukan.");
            return 1;
        }

        $orgId = $existingBranch->organization_id;

        // 1. Buat atau aktifkan Cabang Dummy SELAMAT BLK
        $dummyBranch = Branch::firstOrNew(["code" => "BLK"]);
        if (!$dummyBranch->exists) {
            $dummyBranch->id = (string) Str::uuid();
        }
        $dummyBranch->organization_id = $orgId;
        $dummyBranch->name = "SELAMAT BLK";
        $dummyBranch->code = "BLK";
        $dummyBranch->address = "Jl. KH Abdullah Bin Nuh No. 88, Cianjur";
        $dummyBranch->phone = "0263-2281234";
        $dummyBranch->is_active = true;
        $dummyBranch->sort_order = 1;
        $dummyBranch->receipt_header_line1 = "{org_name}";
        $dummyBranch->receipt_header_line2 = "{branch_name}";
        $dummyBranch->receipt_header_line3 = "{branch_address}";
        $dummyBranch->save();

        $this->info("✓ Cabang Dummy dibuat/diperbarui: {$dummyBranch->name} [ID: {$dummyBranch->id}]");

        // 2. Setup Skenario Rute 1: SELAMAT BLK ➔ SELAMAT PASIR HAYAM (BLK Surplus, Pasir Hayam Kritis)
        $route1Items = [
            "019f1890-2cc5-7389-bb17-66c5aed4edc4" => ["name" => "LE MINERALE 330/24", "stock_blk" => 120],
            "019f1895-f5bb-7160-8c04-7346b34a7471" => ["name" => "ULTRA FULLCREAM 125/40", "stock_blk" => 80],
            "019f188e-8e25-701a-a522-9a3352c83826" => ["name" => "GULAKU PREM 1KG/24", "stock_blk" => 60],
            "019f1891-26a6-700d-9c83-57f53e6dbec0" => ["name" => "MIE SEDAP CHEESE BULDAK /40", "stock_blk" => 70],
        ];

        Product::withoutSyncingToSearch(function () use ($route1Items, $dummyBranch) {
            Stock::withoutEvents(function () use ($route1Items, $dummyBranch) {
                foreach ($route1Items as $productId => $info) {
                    $stock = Stock::firstOrNew([
                        "branch_id" => $dummyBranch->id,
                        "product_id" => $productId,
                    ]);
                    if (!$stock->exists) {
                        $stock->id = (string) Str::uuid();
                    }
                    $stock->quantity_on_hand = $info["stock_blk"];
                    $stock->is_active = true;
                    $stock->saveQuietly();
                    $this->line("  - [Rute BLK ➔ PH] {$info['name']}: Stok BLK diset {$info['stock_blk']} PCS (Surplus)");
                }
            });
        });

        // 3. Setup Skenario Rute 2: SELAMAT PASIR HAYAM ➔ SELAMAT BLK (Pasir Hayam Surplus ratusan pcs, BLK Kritis butuh pasokan)
        $route2Items = [
            "019f188f-3643-73b6-8670-b42593b8f73b" => ["name" => "INDOMIE GR SPECIAL (40)", "stock_blk" => 3, "sold_blk" => 25],
            "019f1890-2cfd-736f-9992-57618d01ef2e" => ["name" => "LE MINERALE 600/24", "stock_blk" => 4, "sold_blk" => 30],
        ];

        $user = User::first();
        $sampleTrx = Transaction::first();

        Product::withoutSyncingToSearch(function () use ($route2Items, $dummyBranch, $sampleTrx, $user, $orgId) {
            Stock::withoutEvents(function () use ($route2Items, $dummyBranch) {
                foreach ($route2Items as $productId => $info) {
                    $stock = Stock::firstOrNew([
                        "branch_id" => $dummyBranch->id,
                        "product_id" => $productId,
                    ]);
                    if (!$stock->exists) {
                        $stock->id = (string) Str::uuid();
                    }
                    $stock->quantity_on_hand = $info["stock_blk"];
                    $stock->is_active = true;
                    $stock->saveQuietly();
                }
            });

            foreach ($route2Items as $productId => $info) {

            // Buat transaksi simulasi penjualan di cabang BLK dalam 10 hari terakhir
            for ($i = 0; $i < 3; $i++) {
                $qtyPart = (int) round($info["sold_blk"] / 3);
                $trxDate = now()->subDays($i * 2 + 1);
                $trxId = (string) Str::uuid();

                $product = Product::find($productId);
                $price = $product ? (float) $product->selling_price : 3000;
                $totalAmt = $qtyPart * $price;

                DB::table("transactions")->insert([
                    "id" => $trxId,
                    "receipt_number" => "BLK-SIM-" . strtoupper(Str::random(6)),
                    "organization_id" => $orgId,
                    "branch_id" => $dummyBranch->id,
                    "terminal_id" => $sampleTrx?->terminal_id,
                    "shift_id" => $sampleTrx?->shift_id,
                    "transaction_type" => "SALES",
                    "transaction_date" => $trxDate->toDateTimeString(),
                    "cashier_id" => $user?->id ?? 1,
                    "total_amount" => $totalAmt,
                    "discount_amount" => 0,
                    "final_amount" => $totalAmt,
                    "payment_method" => "CASH",
                    "received_amount" => $totalAmt,
                    "change_amount" => 0,
                    "is_voided" => 0,
                    "created_at" => $trxDate,
                    "updated_at" => $trxDate,
                ]);

                DB::table("transaction_items")->insert([
                    "id" => (string) Str::uuid(),
                    "transaction_id" => $trxId,
                    "product_id" => $productId,
                    "quantity" => $qtyPart,
                    "unit_price" => $price,
                    "discount_per_item" => 0,
                    "created_at" => $trxDate,
                    "updated_at" => $trxDate,
                ]);
            }

            $this->line("  - [Rute PH ➔ BLK] {$info['name']}: Stok BLK {$info['stock_blk']} PCS (Kritis, ada histori penjualan {$info['sold_blk']} PCS)");
            }
        });

        // 4. Kalkulasi hasil rekomendasi
        $service = app(SuggestedStockTransferService::class);
        $candidateStockIds = $service->getTransferCandidateStockIds();
        $this->info("✓ Selesai! Ditemukan " . count($candidateStockIds) . " rekomendasi mutasi stok antar cabang aktif.");

        $candidateStocks = Stock::whereIn("id", $candidateStockIds)->get();
        $fleet = $service->getFleetSummary($candidateStocks);
        $this->info("Ringkasan Armada:");
        $this->line("  * Total SKU: {$fleet['total_items']} SKU");
        $this->line("  * Total Unit Fisik: {$fleet['total_units']} PCS");
        $this->line("  * Modal Terbebaskan: Rp " . number_format($fleet['total_capital_freed'], 0, ",", "."));
        $this->line("  * Total Rute Aktif: {$fleet['total_routes']} Rute");
        foreach ($fleet['routes'] as $r) {
            $this->line("    - {$r['route_label']}: {$r['item_count']} SKU ({$r['total_units']} PCS) - Rp " . number_format($r['capital_freed'], 0, ",", "."));
        }

        return 0;
    }

    protected function rollbackDummyBranch(): int
    {
        $this->info("Menghapus Cabang Dummy SELAMAT BLK dan seluruh data simulasinya...");

        $branch = Branch::where("code", "BLK")->orWhere("name", "like", "%BLK%")->first();
        if (!$branch) {
            $this->warn("Cabang Dummy SELAMAT BLK tidak ditemukan atau sudah dibersihkan.");
            return 0;
        }

        DB::transaction(function () use ($branch) {
            // Hapus transaksi & transaction_items terkait BLK
            $trxIds = Transaction::where("branch_id", $branch->id)->pluck("id");
            if ($trxIds->isNotEmpty()) {
                TransactionItem::whereIn("transaction_id", $trxIds)->delete();
                Transaction::whereIn("id", $trxIds)->delete();
            }

            // Hapus stok mutasi yang pernah dibuat untuk cabang ini jika ada
            $transferIds = StockTransfer::where("from_branch_id", $branch->id)
                ->orWhere("to_branch_id", $branch->id)
                ->pluck("id");
            if ($transferIds->isNotEmpty()) {
                StockTransferItem::whereIn("stock_transfer_id", $transferIds)->delete();
                StockTransfer::whereIn("id", $transferIds)->delete();
            }

            // Hapus stok di cabang BLK
            Stock::where("branch_id", $branch->id)->delete();

            // Hapus branch
            $branch->delete();
        });

        $this->info("✓ Cabang Dummy SELAMAT BLK dan seluruh data simulasinya berhasil dibersihkan.");
        return 0;
    }
}
