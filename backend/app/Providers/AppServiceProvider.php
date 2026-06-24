<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\TransactionItem;
use App\Models\EcommerceOrder;
use App\Observers\StockObserver;
use App\Observers\TransactionObserver;
use App\Observers\TransactionItemObserver;
use App\Observers\GoodsReceiptObserver;
use App\Observers\GoodsReceiptItemObserver;
use App\Observers\EcommerceOrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SuggestedOrderService::class, function ($app) {
            return new \App\Services\SuggestedOrderService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_contains(request()->header('X-Forwarded-Proto', ''), 'https') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        ini_set('memory_limit', '512M');
        
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table->filtersTriggerAction(
                fn (\Filament\Actions\Action $action) => $action->slideOver(),
            );
        });

        \Filament\Forms\Components\TextInput::macro('rupiah', function () {
            /** @var \Filament\Forms\Components\TextInput $this */
            return $this
                ->prefix('Rp')
                ->extraAlpineAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.')"])
                ->formatStateUsing(function ($state) {
                    if ($state === null || $state === '') return '';
                    $floatState = (float) $state;
                    $decimals = (floor($floatState) == $floatState) ? 0 : 2;
                    return number_format($floatState, $decimals, ',', '.');
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') return null;
                    $clean = str_replace('.', '', $state);
                    $clean = str_replace(',', '.', $clean);
                    return (float) $clean;
                });
        });

        \Filament\Forms\Components\TextInput::macro('ribuan', function () {
            /** @var \Filament\Forms\Components\TextInput $this */
            return $this
                ->extraAlpineAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.')"])
                ->formatStateUsing(fn ($state) => $state !== null ? number_format((float)$state, 0, ',', '.') : '')
                ->dehydrateStateUsing(fn ($state) => $state !== null ? (float) str_replace('.', '', $state) : null);
        });

        \Filament\Forms\Components\TextInput::macro('ribuan_desimal', function () {
            /** @var \Filament\Forms\Components\TextInput $this */
            return $this
                ->extraAlpineAttributes(['x-mask:dynamic' => "\$money(\$input, ',', '.')"])
                ->formatStateUsing(function ($state) {
                    if ($state === null || $state === '') return '';
                    $floatState = (float) $state;
                    $decimals = (floor($floatState) == $floatState) ? 0 : 2;
                    return number_format($floatState, $decimals, ',', '.');
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') return null;
                    $clean = str_replace('.', '', $state);
                    $clean = str_replace(',', '.', $clean);
                    return (float) $clean;
                });
        });

        Stock::observe(StockObserver::class);
        Transaction::observe(TransactionObserver::class);
        TransactionItem::observe(TransactionItemObserver::class);
        GoodsReceipt::observe(GoodsReceiptObserver::class);
        GoodsReceiptItem::observe(GoodsReceiptItemObserver::class);
        EcommerceOrder::observe(EcommerceOrderObserver::class);
    }
}
