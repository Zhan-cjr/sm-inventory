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
        //
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
        Stock::observe(StockObserver::class);
        Transaction::observe(TransactionObserver::class);
        TransactionItem::observe(TransactionItemObserver::class);
        GoodsReceipt::observe(GoodsReceiptObserver::class);
        GoodsReceiptItem::observe(GoodsReceiptItemObserver::class);
        EcommerceOrder::observe(EcommerceOrderObserver::class);
    }
}
