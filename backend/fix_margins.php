<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::all();
$count = 0;
foreach($products as $p) {
    $c = $p->cost_price_tax;
    if ($c > 0) {
        $p->margin_gol_1 = $p->harga_jual_1 > 0 ? round((($p->harga_jual_1 - $c) / $c) * 100, 2) : 0;
        $p->margin_gol_2 = $p->harga_jual_2 > 0 ? round((($p->harga_jual_2 - $c) / $c) * 100, 2) : 0;
        $p->margin_gol_3 = $p->harga_jual_3 > 0 ? round((($p->harga_jual_3 - $c) / $c) * 100, 2) : 0;
        $p->saveQuietly();
        $count++;
    }
}
echo "Products updated: $count\n";

$stocks = \App\Models\Stock::all();
$scount = 0;
foreach($stocks as $s) {
    $c = $s->cost_price_tax ?? $s->product->cost_price_tax;
    if ($c > 0) {
        $s->margin_gol_1 = $s->harga_jual_1 > 0 ? round((($s->harga_jual_1 - $c) / $c) * 100, 2) : 0;
        $s->margin_gol_2 = $s->harga_jual_2 > 0 ? round((($s->harga_jual_2 - $c) / $c) * 100, 2) : 0;
        $s->margin_gol_3 = $s->harga_jual_3 > 0 ? round((($s->harga_jual_3 - $c) / $c) * 100, 2) : 0;
        $s->saveQuietly();
        $scount++;
    }
}
echo "Stocks updated: $scount\n";
