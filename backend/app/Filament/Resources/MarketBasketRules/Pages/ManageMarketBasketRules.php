<?php

namespace App\Filament\Resources\MarketBasketRules\Pages;

use App\Filament\Resources\MarketBasketRules\MarketBasketRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMarketBasketRules extends ManageRecords
{
    protected static string $resource = MarketBasketRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('trainAI')
                ->label('Latih Ulang AI (Update Pola)')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Latih Ulang Pola Belanja')
                ->modalDescription('Apakah Anda yakin ingin menjalankan ulang algoritma AI? Proses ini mungkin memakan waktu beberapa saat tergantung pada jumlah data transaksi.')
                ->action(function () {
                    try {
                        $response = \Illuminate\Support\Facades\Http::timeout(120)->post('http://localhost:8001/api/v1/ai/train-market-basket');
                        if ($response->successful()) {
                            $data = $response->json();
                            \Filament\Notifications\Notification::make()
                                ->title('AI Selesai Dilatih!')
                                ->body('Ditemukan ' . ($data['rules_count'] ?? 0) . ' aturan/pola belanja baru.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Melatih AI')
                                ->body('Server AI merespons dengan error: ' . $response->status())
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Menghubungi AI Service')
                            ->body('Pastikan AI Service sedang berjalan di port 8001. Pesan error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
