<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start_date = '2026-06-01'; 
$end_date = '2026-06-30'; 
$branch_id = null;

$organizationId = \App\Models\Organization::first()->id;
$accounts = \App\Models\Account::where('organization_id', $organizationId)->where('is_active', true)->get();

$netProfit = 0; 
$retainedEarnings = 0; 
$assets = 0; 
$liab = 0; 
$equity = 0;

foreach($accounts as $account) {
    // Current period
    $linesCurrent = \App\Models\JournalEntryLine::where('account_id', $account->id)
        ->whereHas('journalEntry', function ($q) use ($start_date, $end_date) { 
            $q->where('status', 'posted')
              ->whereDate('entry_date', '>=', $start_date)
              ->whereDate('entry_date', '<=', $end_date); 
        })->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
    $dCur = $linesCurrent->d ?? 0; 
    $cCur = $linesCurrent->c ?? 0;

    // Prior period
    $linesPrior = \App\Models\JournalEntryLine::where('account_id', $account->id)
        ->whereHas('journalEntry', function ($q) use ($start_date) { 
            $q->where('status', 'posted')
              ->whereDate('entry_date', '<', $start_date); 
        })->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
    $dPri = $linesPrior->d ?? 0; 
    $cPri = $linesPrior->c ?? 0;

    // Total up to end_date
    $linesTotal = \App\Models\JournalEntryLine::where('account_id', $account->id)
        ->whereHas('journalEntry', function ($q) use ($end_date) { 
            $q->where('status', 'posted')
              ->whereDate('entry_date', '<=', $end_date); 
        })->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
    $dTot = $linesTotal->d ?? 0; 
    $cTot = $linesTotal->c ?? 0;

    if ($account->type == 'asset') {
        $assets += ($dTot - $cTot);
    }
    if ($account->type == 'liability') {
        $liab += ($cTot - $dTot);
    }
    if ($account->type == 'equity') {
        $equity += ($cTot - $dTot);
    }
    if ($account->type == 'revenue') { 
        $netProfit += ($cCur - $dCur); 
        $retainedEarnings += ($cPri - $dPri); 
    }
    if ($account->type == 'expense') { 
        $netProfit -= ($dCur - $cCur); 
        $retainedEarnings -= ($dPri - $cPri); 
    }
}

echo "Assets: $assets\n";
echo "Liabilities: $liab\n";
echo "Equity: $equity\n";
echo "NetProfit (Current): $netProfit\n";
echo "RetainedEarnings (Prior): $retainedEarnings\n";
echo "Total Liab + Equity + NetProfit + RetainedEarnings: " . ($liab + $equity + $netProfit + $retainedEarnings) . "\n";
echo "Difference: " . ($assets - ($liab + $equity + $netProfit + $retainedEarnings)) . "\n";

// Check total debits and credits of ALL journals
$allJournals = \App\Models\JournalEntry::where('status', 'posted')->get();
$totalD = 0; $totalC = 0;
foreach($allJournals as $j) {
    $totalD += $j->lines()->sum('debit');
    $totalC += $j->lines()->sum('credit');
}
echo "Total All Journal Debits: $totalD\n";
echo "Total All Journal Credits: $totalC\n";
echo "Journal DB vs CR Diff: " . ($totalD - $totalC) . "\n";
