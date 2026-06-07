<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$unbalanced = \App\Models\JournalEntry::select('journal_entries.*')
    ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
    ->groupBy('journal_entries.id')
    ->havingRaw('ROUND(SUM(journal_entry_lines.debit), 2) != ROUND(SUM(journal_entry_lines.credit), 2)')
    ->get();

$service = app(\App\Services\AccountingService::class);
foreach($unbalanced as $journal) {
    if ($journal->journalable_type === \App\Models\Transaction::class) {
        $tx = \App\Models\Transaction::find($journal->journalable_id);
        if ($tx) {
            $journal->lines()->delete();
            $journal->delete();
            $service->recordTransactionJournal($tx);
            echo 'Fixed Transaction ' . $tx->id . "\n";
        }
    } elseif ($journal->journalable_type === \App\Models\EcommerceOrder::class) {
        $order = \App\Models\EcommerceOrder::find($journal->journalable_id);
        if ($order) {
            $journal->lines()->delete();
            $journal->delete();
            $service->recordEcommerceOrderJournal($order);
            echo 'Fixed Ecommerce ' . $order->id . "\n";
        }
    }
}
