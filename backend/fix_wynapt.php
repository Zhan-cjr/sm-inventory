<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Move PH-WYNAPT to the correct shift (July 8th Shift 1)
$tx = \App\Models\Transaction::where('receipt_number', 'PH-WYNAPT')->first();
if ($tx) {
    $tx->shift_id = '019f3f28-4679-71a8-92fb-dd690a780bbf';
    $tx->save();
}

// 2. Recalculate Shift 019f3f28-4679-71a8-92fb-dd690a780bbf
$shift = \App\Models\Shift::find('019f3f28-4679-71a8-92fb-dd690a780bbf');
if ($shift) {
    $transactions = \App\Models\Transaction::where('shift_id', $shift->id)
        ->where('is_voided', false)
        ->get();

    $cash_sales = 0;
    $card_sales = 0;
    $voucher_sales = 0;
    $cash_returns = 0;
    $card_returns = 0;

    foreach ($transactions as $t) {
        $amount = $t->final_amount;
        $tx_sales = 0;
        $tx_returns = 0;
        foreach ($t->items as $item) {
            if ($item->quantity > 0) {
                $tx_sales += ($item->quantity * ($item->unit_price - $item->discount_per_item));
            } else {
                $tx_returns += (abs($item->quantity) * ($item->unit_price - $item->discount_per_item));
            }
        }
        $tx_sales -= ($t->manual_discount + $t->promo_discount);
        if ($tx_sales < 0) $tx_sales = 0;

        $method = strtoupper($t->payment_method);
        
        if ($method === 'CASH' || $method === 'POINT') {
            $cash_sales += $tx_sales;
            $cash_returns += $tx_returns;
        } elseif ($method === 'CARD') {
            $card_sales += $tx_sales;
            $card_returns += $tx_returns;
        } elseif ($method === 'VOUCHER') {
            $voucher_sales += $tx_sales;
        } elseif ($method === 'MULTI') {
            $details = $t->payment_details;
            if (is_string($details)) $details = json_decode($details, true);
            if (is_array($details)) {
                $cash_amt = collect($details)->where('method', 'CASH')->sum('amount');
                if ($cash_amt > 0) $cash_amt = max(0, $cash_amt - $t->change_amount);
                
                $voucher_amt = collect($details)->where('method', 'VOUCHER')->sum('amount');
                $voucher_sales += $voucher_amt;
                
                $cash_returns += $tx_returns;
                $cash_sales += ($cash_amt + $tx_returns);
                
                $cardDetails = collect($details)->where('method', 'CARD');
                foreach ($cardDetails as $c) {
                    $card_sales += $c['amount'];
                }
            }
        }
    }

    $shift->total_cash_sales = $cash_sales;
    $shift->total_card_sales = $card_sales;
    $shift->total_voucher_sales = $voucher_sales;
    $shift->total_cash_returns = $cash_returns;
    $shift->total_card_returns = $card_returns;

    $expectedCash = $shift->starting_cash + $cash_sales - $cash_returns + $shift->total_cash_in - $shift->total_cash_out;
    
    // We do not overwrite actual_cash, but we recalculate difference
    if ($shift->actual_cash !== null) {
        $shift->difference = $shift->actual_cash - $expectedCash;
    }
    
    // Unset dynamic property if it exists
    unset($shift->card_sales_by_bank);
    unset($shift->expected_cash); // accessor
    $shift->save();
    
    echo "Shift 019f3f28-4679-71a8-92fb-dd690a780bbf recalculated successfully!\n";
    echo "Expected Cash: $expectedCash\n";
    echo "Difference: " . $shift->difference . "\n";
}
