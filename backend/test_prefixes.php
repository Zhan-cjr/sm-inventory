<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = \App\Models\TransactionItem::with('product', 'service')
    ->whereHas('transaction', function($q) { 
        $q->whereMonth('transaction_date', date('m'))
          ->whereYear('transaction_date', date('Y'))
          ->where('is_voided', false); 
    })
    ->where(function($q) { 
        $q->whereNull('product_id')
          ->orWhereHas('product', function($q2) { 
              $q2->whereNull('supplier_id'); 
          }); 
    })->get();

$total_product_no_supplier = 0;
$total_service = 0;

foreach($items as $ti) { 
    if ($ti->quantity > 0) { 
        $v = $ti->quantity * ($ti->unit_price - ($ti->discount_per_item ?? 0)); 
        if ($ti->product_id) {
            echo "Product without supplier: {$ti->product->name} - Value: $v\n";
            $total_product_no_supplier += $v;
        } else if ($ti->service_id) {
            echo "Service: {$ti->service->name} - Value: $v\n";
            $total_service += $v;
        }
    } 
}
echo "Total Product without supplier: $total_product_no_supplier\n";
echo "Total Service: $total_service\n";
