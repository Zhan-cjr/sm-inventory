<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\GoodsReceipt;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentItem;
use App\Models\Branch;
use App\Models\Kontrabon;
use Illuminate\Support\Facades\DB;

class PurchasePaymentPos extends Component
{
    public $supplier_id = '';
    public $branch_id = '';
    public $payment_number = '';
    public $payment_date = '';
    public $payment_method = 'CASH';
    public $reference_number = '';
    public $notes = '';
    
    public $suppliers = [];
    public $branches = [];
    public $unpaid_invoices = [];
    
    public $total_amount = 0;

    public function mount()
    {
        $this->payment_number = 'PAY-' . date('YmdHis');
        $this->payment_date = date('Y-m-d');
        $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;
        $this->suppliers = Supplier::where('is_active', true)->select('id', 'name', 'code', 'address')->get()->toArray();
        $this->branches = Branch::all();
    }
    
    public function updatedSupplierId($value)
    {
        $this->loadUnpaidInvoices();
    }
    
    public function loadUnpaidInvoices()
    {
        if (!$this->supplier_id) {
            $this->unpaid_invoices = [];
            return;
        }
        
        $kontrabons = Kontrabon::where('supplier_id', $this->supplier_id)
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->get();
            
        $this->unpaid_invoices = [];
        foreach($kontrabons as $kb) {
            $remaining = $kb->total_amount - $kb->paid_amount;
            $this->unpaid_invoices[] = [
                'id' => $kb->id,
                'receipt_number' => $kb->kontrabon_number,
                'receipt_date' => $kb->tanggal_kontrabon,
                'due_date' => $kb->tanggal_jatuh_tempo,
                'total_amount' => $kb->total_amount,
                'paid_amount' => $kb->paid_amount,
                'remaining_amount' => $remaining,
                'pay_amount' => 0,
                'is_selected' => false
            ];
        }
        $this->calculateTotal();
    }
    
    public function toggleInvoice($index)
    {
        if ($this->unpaid_invoices[$index]['is_selected']) {
            $this->unpaid_invoices[$index]['pay_amount'] = $this->unpaid_invoices[$index]['remaining_amount'];
        } else {
            $this->unpaid_invoices[$index]['pay_amount'] = 0;
        }
        $this->calculateTotal();
    }
    
    public function updatedUnpaidInvoices($value, $key)
    {
        $this->calculateTotal();
    }
    
    public function calculateTotal()
    {
        $total = 0;
        foreach($this->unpaid_invoices as $inv) {
            if ($inv['is_selected']) {
                $total += floatval($inv['pay_amount']);
            }
        }
        $this->total_amount = $total;
    }
    
    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'payment_date' => 'required',
            'payment_method' => 'required',
            'total_amount' => 'required|numeric|min:1',
        ]);
        
        $selectedInvoices = collect($this->unpaid_invoices)->filter(function($inv) {
            return $inv['is_selected'] && floatval($inv['pay_amount']) > 0;
        });
        
        if ($selectedInvoices->isEmpty()) {
            session()->flash('error', 'Pilih minimal 1 faktur/kontrabon untuk dibayar.');
            return;
        }
        
        DB::beginTransaction();
        try {
            $payment = PurchasePayment::create([
                'payment_number' => $this->payment_number,
                'payment_date' => $this->payment_date,
                'supplier_id' => $this->supplier_id,
                'branch_id' => $this->branch_id,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'total_amount' => $this->total_amount,
                'notes' => $this->notes,
                'status' => 'COMPLETED',
                'created_by_id' => auth()->id(),
            ]);
            
            foreach ($selectedInvoices as $invData) {
                // Determine if we are paying a Kontrabon
                $kb = Kontrabon::find($invData['id']);
                if ($kb) {
                    $kb->paid_amount += floatval($invData['pay_amount']);
                    if ($kb->paid_amount >= ($kb->total_amount - 0.01)) {
                        $kb->status = 'PAID';
                    } else {
                        $kb->status = 'PARTIAL';
                    }
                    $kb->save();
                    
                    // Distribute payment to GRs inside Kontrabon
                    $remainingPayment = floatval($invData['pay_amount']);
                    
                    if ($kb->items->count() === 0) {
                        // Consignment Kontrabon (no GRs attached)
                        PurchasePaymentItem::create([
                            'purchase_payment_id' => $payment->id,
                            'kontrabon_id' => $kb->id,
                            'amount_paid' => $remainingPayment,
                        ]);
                    } else {
                        foreach ($kb->items as $kbItem) {
                            if ($remainingPayment <= 0) break;
                            $gr = $kbItem->goodsReceipt;
                            $grUnpaid = $gr->total_amount - $gr->paid_amount;
                            if ($grUnpaid > 0) {
                                $payToGr = min($grUnpaid, $remainingPayment);
                                $gr->paid_amount += $payToGr;
                                $remainingPayment -= $payToGr;
                                
                                if ($gr->paid_amount >= ($gr->total_amount - 0.01)) {
                                    $gr->payment_status = 'PAID';
                                } else {
                                    $gr->payment_status = 'PARTIAL_PAID';
                                }
                                $gr->save();
                                
                                // Create Payment Item for the GR
                                PurchasePaymentItem::create([
                                    'purchase_payment_id' => $payment->id,
                                    'goods_receipt_id' => $gr->id,
                                    'kontrabon_id' => $kb->id,
                                    'amount_paid' => $payToGr,
                                ]);
                            }
                        }
                    }
                }
            }
            
            DB::commit();
            return redirect()->to('/admin/purchase-payments');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.purchase-payment-pos');
    }
}


