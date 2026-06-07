<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Look for unbalanced journals exactly like PerbaikanNeraca.php
$unbalanced = \App\Models\JournalEntry::select('journal_entries.*')
    ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
    ->groupBy('journal_entries.id')
    ->havingRaw('ROUND(SUM(journal_entry_lines.debit), 2) != ROUND(SUM(journal_entry_lines.credit), 2)')
    ->get();

echo 'Unbalanced count from DB query: ' . $unbalanced->count() . "\n";
foreach ($unbalanced as $j) {
    $d = $j->lines()->sum('debit');
    $c = $j->lines()->sum('credit');
    echo 'Journal ' . $j->id . ' (' . $j->journalable_type . ' ' . $j->journalable_id . '): D=' . $d . ' C=' . $c . ' Diff=' . ($d - $c) . "\n";
}

// Now check if ANY journal is unbalanced by calculating manually in PHP just to be sure!
$allJournals = \App\Models\JournalEntry::with('lines')->get();
$phpUnbalancedCount = 0;
foreach($allJournals as $j) {
    $d = $j->lines->sum('debit');
    $c = $j->lines->sum('credit');
    if (round($d, 2) != round($c, 2)) {
        echo "MANUAL FIND -> Journal {$j->id} D=$d C=$c Diff=" . ($d-$c) . "\n";
        $phpUnbalancedCount++;
    }
}
echo "Total Manual Unbalanced: $phpUnbalancedCount\n";
