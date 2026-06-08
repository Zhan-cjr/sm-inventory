<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('settlePromos')
                ->label('Rekap & Tutup Promo (Settlement)')
                ->color('success')
                ->icon('heroicon-o-calculator')
                ->requiresConfirmation()
                ->action(function () {
                    $expiredPromos = \App\Models\Promotion::whereNotNull('supplier_id')
                        ->where('is_settled', false)
                        ->where('valid_until', '<', now())
                        ->get();
                        
                    $settledCount = 0;
                    
                    foreach ($expiredPromos as $promo) {
                        $items = \Illuminate\Support\Facades\DB::table('transaction_items')
                            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                            ->where('transaction_items.promotion_id', $promo->id)
                            ->select('transactions.branch_id', \Illuminate\Support\Facades\DB::raw('SUM(transaction_items.discount_per_item * transaction_items.quantity) as total_discount'))
                            ->groupBy('transactions.branch_id')
                            ->get();
                            
                        foreach ($items as $item) {
                            if ($item->total_discount > 0) {
                                $deductionAmount = $item->total_discount * ($promo->supplier_sponsorship_percent / 100);
                                
                                if ($deductionAmount > 0) {
                                    \App\Models\SupplierDeduction::create([
                                        'supplier_id' => $promo->supplier_id,
                                        'branch_id' => $item->branch_id,
                                        'deduction_type' => 'PROMO_RAFAKSI',
                                        'reference_id' => $promo->id,
                                        'amount' => $deductionAmount,
                                        'status' => 'OPEN',
                                        'notes' => 'Settlement promo: ' . $promo->name,
                                    ]);
                                }
                            }
                        }
                        
                        $promo->update(['is_settled' => true]);
                        $settledCount++;
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Settlement Berhasil')
                        ->body("$settledCount promo telah direkap menjadi potongan supplier.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
