<?php

$productsUpdated = 0;
\App\Models\Product::chunk(100, function ($products) use (&$productsUpdated) {
    foreach ($products as $product) {
        if ($product->cost_price > 0 && ($product->cost_price_tax == 0 || $product->cost_price_tax == null)) {
            $product->cost_price_tax = $product->cost_price;
            $product->save();
            $productsUpdated++;
        }
    }
});

$stocksUpdated = 0;
\App\Models\Stock::chunk(100, function ($stocks) use (&$stocksUpdated) {
    foreach ($stocks as $stock) {
        if ($stock->cost_price > 0 && ($stock->cost_price_tax == 0 || $stock->cost_price_tax == null)) {
            $stock->cost_price_tax = $stock->cost_price;
            $stock->save();
            $stocksUpdated++;
        }
    }
});

echo "Products cost_price_tax updated: $productsUpdated\n";
echo "Stocks cost_price_tax updated: $stocksUpdated\n";
