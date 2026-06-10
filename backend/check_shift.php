<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shift = App\Models\Shift::latest()->first();
if ($shift) {
    echo "Shift ID: " . $shift->id . " (" . $shift->shift_name . ")\n";
    echo "Status: " . $shift->status . "\n";
    echo "Starting Cash: " . $shift->starting_cash . "\n";
    echo "Total Cash Sales: " . $shift->total_cash_sales . "\n";
    echo "Total Card Sales: " . $shift->total_card_sales . "\n";
    echo "Total Voucher Sales: " . $shift->total_voucher_sales . "\n";
    echo "Expected Cash: " . $shift->expected_cash . "\n";
    echo "Actual Cash: " . $shift->actual_cash . "\n";
    echo "Difference: " . $shift->difference . "\n";
    
    $txs = App\Models\Transaction::where('shift_id', $shift->id)->where('is_voided', false)->get();
    echo "Transaction Count: " . $txs->count() . "\n";
    foreach ($txs as $tx) {
        echo "  Tx ID: " . $tx->id . " | Final: " . $tx->final_amount . " | Method: " . $tx->payment_method . " | Details: " . json_encode($tx->payment_details) . "\n";
    }
} else {
    echo "No shifts found.\n";
}
