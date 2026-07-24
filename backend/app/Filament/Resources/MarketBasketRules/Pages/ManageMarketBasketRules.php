<?php

namespace App\Filament\Resources\MarketBasketRules\Pages;

use App\Filament\Resources\MarketBasketRules\MarketBasketRuleResource;
use App\Models\MarketBasketRule;
use App\Models\Product;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ManageMarketBasketRules extends ManageRecords
{
    protected static string $resource = MarketBasketRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('trainAI')
                ->label('Latih Ulang Pola Belanja (MBA)')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Analisis Pola Kombinasi Belanja')
                ->modalDescription('Proses ini akan menganalisis riwayat transaksi penjualan untuk menemukan pasangan produk yang paling sering dibeli secara bersamaan.')
                ->action(function () {
                    $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001') . '/api/v1/ai/train-market-basket';
                    
                    try {
                        $response = Http::timeout(5)->post($aiUrl);
                        if ($response->successful()) {
                            $data = $response->json();
                            $count = $data['rules_count'] ?? 0;
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('AI Selesai Menganalisis!')
                                    ->body("Ditemukan {$count} aturan pola bundling baru.")
                                    ->success()
                                    ->send();
                                return;
                            }
                        }
                    } catch (\Exception $e) {
                        // AI Microservice is offline, run native database Apriori analysis fallback!
                    }

                    // Native Database Apriori Fallback
                    $rulesCount = $this->runDatabaseMarketBasketAnalysis();

                    \Filament\Notifications\Notification::make()
                        ->title('Analisis Pola Belanja (Database Engine) Selesai!')
                        ->body("Berhasil memperbarui {$rulesCount} pasangan pola produk bundling berdasarkan transaksi penjualan.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function runDatabaseMarketBasketAnalysis(): int
    {
        $transactions = DB::table('transaction_items')
            ->select('transaction_id', 'product_id')
            ->where('created_at', '>=', now()->subDays(90))
            ->get()
            ->groupBy('transaction_id');

        $totalTransactions = count($transactions);
        if ($totalTransactions === 0) return 0;

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
