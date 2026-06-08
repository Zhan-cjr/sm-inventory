<?php
foreach(\App\Models\PurchaseReturn::all() as $pr) { 
    \App\Models\SupplierDeduction::updateOrCreate([
        'deduction_type' => 'PURCHASE_RETURN', 
        'reference_id' => $pr->id
    ], [
        'supplier_id' => $pr->supplier_id, 
        'branch_id' => $pr->branch_id, 
        'amount' => $pr->total_amount, 
        'notes' => 'Otomatis dari Retur Pembelian ' . $pr->return_number
    ]); 
}
echo "Done sync\n";
