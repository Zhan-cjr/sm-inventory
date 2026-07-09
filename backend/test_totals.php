<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$totalKassa = DB::table('transactions')->where('is_voided', false)->sum('final_amount');
$totalJasa = DB::table('transaction_items')->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')->where('transactions.is_voided', false)->whereNotNull('service_id')->sum(DB::raw('quantity * (unit_price - discount_per_item)'));
$rekapJual = DB::table('transaction_items as ti')->join('transactions as t', 'ti.transaction_id', '=', 't.id')->join('products as p', 'ti.product_id', '=', 'p.id')->where('t.is_voided', false)->sum(DB::raw('CASE WHEN ti.quantity > 0 THEN ti.quantity * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END'));
$rekapRetur = DB::table('transaction_items as ti')->join('transactions as t', 'ti.transaction_id', '=', 't.id')->join('products as p', 'ti.product_id', '=', 'p.id')->where('t.is_voided', false)->sum(DB::raw('CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END'));

echo json_encode([
    'totalKassa' => $totalKassa,
    'totalJasa' => $totalJasa,
    'kassa_min_jasa' => $totalKassa - $totalJasa,
    'rekapJual' => $rekapJual,
    'rekapRetur' => $rekapRetur,
    'rekapNet' => $rekapJual - $rekapRetur
], JSON_PRETTY_PRINT);
