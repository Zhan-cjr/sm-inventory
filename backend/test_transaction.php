<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transactionId = '019ea01b-d884-7298-beae-e10d2e2eca05';
$transaction = \App\Models\Transaction::with(['items'])->find($transactionId);

echo "Transaction Total: {$transaction->total_amount}\n";
echo "Transaction Subtotal: {$transaction->subtotal}\n";
echo "Transaction Discount: {$transaction->discount}\n";
echo "Transaction Tax: {$transaction->tax_amount}\n";

echo "Transaction Items:\n";
$hppTotal = 0;
foreach($transaction->items as $item) {
    echo "- {$item->product_name} | Qty: {$item->quantity} | Price: {$item->unit_price} | Sub: {$item->subtotal} | HPP: {$item->cogs}\n";
    $hppTotal += ($item->cogs * $item->quantity);
}
echo "Total HPP: $hppTotal\n";

echo "Journal Lines generated for this transaction:\n";
$journal = \App\Models\JournalEntry::where('journalable_type', \App\Models\Transaction::class)->where('journalable_id', $transactionId)->first();
if ($journal) {
    $totalD = 0; $totalC = 0;
    foreach($journal->lines as $line) {
        echo "Account: {$line->account->name} ({$line->account->account_code}) | D: {$line->debit} | C: {$line->credit}\n";
        $totalD += $line->debit;
        $totalC += $line->credit;
    }
    echo "Total Debit: $totalD | Total Credit: $totalC | Diff: " . ($totalD - $totalC) . "\n";
} else {
    echo "NO JOURNAL FOUND!\n";
}
