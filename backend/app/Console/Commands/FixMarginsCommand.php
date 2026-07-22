<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Stock;

class FixMarginsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-margins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and fix all corrupted margins (-100 or wrong values) in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix margins for all Products...');
        
        $products = Product::all();
        $count = 0;
        foreach($products as $p) {
            $c = (float)$p->cost_price_tax;
            if ($c > 0) {
                $p->margin_gol_1 = $p->harga_jual_1 > 0 ? round((($p->harga_jual_1 - $c) / $c) * 100, 2) : 0;
                $p->margin_gol_2 = $p->harga_jual_2 > 0 ? round((($p->harga_jual_2 - $c) / $c) * 100, 2) : 0;
                $p->margin_gol_3 = $p->harga_jual_3 > 0 ? round((($p->harga_jual_3 - $c) / $c) * 100, 2) : 0;
                $p->saveQuietly();
                $count++;
            }
        }
        $this->info("Products updated: $count");

        $this->info('Starting to fix margins for all Stocks...');
        $stocks = Stock::with('product')->get();
        $scount = 0;
        foreach($stocks as $s) {
            $c = (float)($s->cost_price_tax ?? ($s->product ? $s->product->cost_price_tax : 0));
            if ($c > 0) {
                $s->margin_gol_1 = $s->harga_jual_1 > 0 ? round((($s->harga_jual_1 - $c) / $c) * 100, 2) : 0;
                $s->margin_gol_2 = $s->harga_jual_2 > 0 ? round((($s->harga_jual_2 - $c) / $c) * 100, 2) : 0;
                $s->margin_gol_3 = $s->harga_jual_3 > 0 ? round((($s->harga_jual_3 - $c) / $c) * 100, 2) : 0;
                $s->saveQuietly();
                $scount++;
            }
        }
        $this->info("Stocks updated: $scount");
        
        $this->info('All corrupted margins have been successfully fixed!');
    }
}
