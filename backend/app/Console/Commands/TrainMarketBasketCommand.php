<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketBasketRule;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrainMarketBasketCommand extends Command
{
    protected $signature = 'ai:train-mba';

    protected $description = 'Jalankan analisis Market Basket Analysis (MBA) untuk menemukan pola rekomendasi bundling produk.';

    public function handle()
    {
        $this->info('Memulai analisis pola belanja (Market Basket Analysis)...');

        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001') . '/api/v1/ai/train-market-basket';

        try {
            $response = Http::timeout(10)->post($aiUrl);
            if ($response->successful()) {
                $data = $response->json();
                $count = $data['rules_count'] ?? 0;
                $this->info("AI Microservice selesai menganalisis. Ditemukan {$count} pola bundling.");
                Log::info("AI MBA Training completed via microservice: {$count} rules found.");
                return 0;
            }
        } catch (\Exception $e) {
            $this->warn('AI Microservice tidak merespons. Menggunakan analisis Apriori Database Native...');
        }

        $savedCount = $this->runDatabaseMarketBasketAnalysis();
        $this->info("Analisis Database Native selesai. Ditemukan {$savedCount} pola bundling produk.");
        Log::info("AI MBA Training completed via database engine: {$savedCount} rules found.");

        return 0;
    }

    protected function runDatabaseMarketBasketAnalysis(): int
    {
        $transactions = DB::table('transaction_items')
            ->select('transaction_id', 'product_id')
            ->where('created_at', '>=', now()->subDays(90))
            ->get()
            ->groupBy('transaction_id');

        $totalTransactions = count($transactions);
        if ($totalTransactions === 0) {
            $this->warn('Tidak ada data transaksi dalam 90 hari terakhir.');
            return 0;
        }

        $itemCounts = [];
        $pairCounts = [];

        foreach ($transactions as $txId => $items) {
            $productIds = $items->pluck('product_id')->unique()->values()->toArray();
            
            foreach ($productIds as $pId) {
                $itemCounts[$pId] = ($itemCounts[$pId] ?? 0) + 1;
            }

            $count = count($productIds);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $p1 = $productIds[$i];
                    $p2 = $productIds[$j];

                    $pairKey1 = $p1 . '___' . $p2;
                    $pairKey2 = $p2 . '___' . $p1;

                    $pairCounts[$pairKey1] = ($pairCounts[$pairKey1] ?? 0) + 1;
                    $pairCounts[$pairKey2] = ($pairCounts[$pairKey2] ?? 0) + 1;
                }
            }
        }

        $productsMap = Product::pluck('name', 'id')->toArray();
        $savedCount = 0;

        foreach ($pairCounts as $key => $pairFreq) {
            if ($pairFreq < 2) continue;

            [$antId, $conId] = explode('___', $key);

            if (!isset($productsMap[$antId]) || !isset($productsMap[$conId])) continue;

            $support = round($pairFreq / $totalTransactions, 4);
            $antFreq = $itemCounts[$antId] ?? 1;
            $conFreq = $itemCounts[$conId] ?? 1;

            $confidence = round($pairFreq / $antFreq, 4);
            $conSupport = $conFreq / $totalTransactions;
            $lift = round($confidence / ($conSupport > 0 ? $conSupport : 1), 2);

            if ($confidence >= 0.15 && $lift >= 1.0) {
                MarketBasketRule::updateOrCreate(
                    [
                        'antecedent_id' => $antId,
                        'consequent_id' => $conId,
                    ],
                    [
                        'antecedent_name' => $productsMap[$antId],
                        'consequent_name' => $productsMap[$conId],
                        'support' => $support,
                        'confidence' => $confidence,
                        'lift' => $lift,
                    ]
                );
                $savedCount++;
            }
        }

        return $savedCount;
    }
}
