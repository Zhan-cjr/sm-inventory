<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Shift;
use App\Models\Transaction;

try {
    $shift = Shift::latest()->first();
    if (!$shift) {
        throw new \Exception("No shift found");
    }
    echo "Testing shift ID: " . $shift->id . "\n";
    
    $sales = Transaction::where('shift_id', $shift->id)
        ->where('is_voided', false)
        ->select(
            DB::raw("SUM(CASE WHEN payment_method = 'CASH' AND final_amount > 0 THEN final_amount ELSE 0 END) as cash_sales"),
            DB::raw("SUM(CASE WHEN payment_method = 'CARD' AND final_amount > 0 THEN final_amount ELSE 0 END) as card_sales"),
            DB::raw("SUM(CASE WHEN payment_method = 'CASH' AND final_amount < 0 THEN ABS(final_amount) ELSE 0 END) as cash_returns"),
            DB::raw("SUM(CASE WHEN payment_method = 'CARD' AND final_amount < 0 THEN ABS(final_amount) ELSE 0 END) as card_returns")
        )->first();
    
    echo "Sales query success!\n";
    print_r($sales->toArray());
    
    $cardSalesByBank = DB::table('transactions')
        ->join('banks', 'transactions.bank_id', '=', 'banks.id')
        ->where('transactions.shift_id', $shift->id)
        ->where('transactions.payment_method', 'CARD')
        ->where('transactions.is_voided', false)
        ->select('banks.name', DB::raw('SUM(transactions.final_amount) as total_amount'))
        ->groupBy('banks.id', 'banks.name')
        ->get();
        
    echo "Card sales by bank query success!\n";
    print_r($cardSalesByBank->toArray());
    
    $returns = Transaction::with(['items.product'])
        ->where('shift_id', $shift->id)
        ->where('is_voided', false)
        ->where('final_amount', '<', 0)
        ->get();
        
    echo "Returns query success!\n";
    
    $totalDiscounts = Transaction::where('shift_id', $shift->id)
        ->where('is_voided', false)
        ->sum('discount_amount');
    
    echo "Total discounts query success: " . $totalDiscounts . "\n";
    
    echo "All queries executed successfully without error!\n";
} catch (\Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
