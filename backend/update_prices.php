<?php

$productsUpdated = 0;
\App\Models\Product::chunk(100, function ($products) use (&$productsUpdated) {
    foreach ($products as $product) {
        if (($product->harga_jual_1 == 0 || $product->harga_jual_1 == null) && $product->selling_price > 0) {
            $product->harga_jual_1 = $product->selling_price;
            if ($product->cost_price > 0) {
                $product->margin_gol_1 = round((($product->selling_price - $product->cost_price) / $product->cost_price) * 100, 2);
            } else {
                $product->margin_gol_1 = 100;
            }
            $product->qty_min_gol_1 = 1;
            $product->save();
            $productsUpdated++;
        }
    }
});

$stocksUpdated = 0;
\App\Models\Stock::chunk(100, function ($stocks) use (&$stocksUpdated) {
    foreach ($stocks as $stock) {
        if (($stock->harga_jual_1 == 0 || $stock->harga_jual_1 == null) && $stock->selling_price > 0) {
            $stock->harga_jual_1 = $stock->selling_price;
            if ($stock->cost_price > 0) {
                $stock->margin_gol_1 = round((($stock->selling_price - $stock->cost_price) / $stock->cost_price) * 100, 2);
            } else {
                $productCost = $stock->product->cost_price ?? 0;
                if ($productCost > 0) {
                    $stock->margin_gol_1 = round((($stock->selling_price - $productCost) / $productCost) * 100, 2);
                } else {
                    $stock->margin_gol_1 = 100;
                }
            }
            $stock->qty_min_gol_1 = 1;
            $stock->save();
            $stocksUpdated++;
        }
    }
});

echo "Products updated: $productsUpdated\n";
echo "Stocks updated: $stocksUpdated\n";
