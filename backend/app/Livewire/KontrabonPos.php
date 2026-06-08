<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\GoodsReceipt;
use App\Models\Kontrabon;
use App\Models\KontrabonItem;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class KontrabonPos extends Component
{
    public $supplier_id = '';
    public $branch_id = '';
    public $kontrabon_number = '';
    public $tanggal_kontrabon = '';
    public $tanggal_jatuh_tempo = '';
    public $notes = '';
    
    public $suppliers = [];
    public $branches = [];
    public $unbilled_invoices = [];
    
    public $total_amount = 0;
    public $total_deduction = 0;
    public $manual_deduction_amount = 0;
    public $manual_deduction_notes = '';
    public $grand_total = 0;
    public $available_deductions = [];

    public function mount()
    {
        $this->kontrabon_number = 'KB-' . date('YmdHis');
        $this->tanggal_kontrabon = date('Y-m-d');
        // Default jatuh tempo 14 hari dari sekarang jika belum ada supplier
        $this->tanggal_jatuh_tempo = date('Y-m-d', strtotime('+14 days'));
        
        $this->branch_id = auth()->user()->branch_id ?? \App\Models\Branch::first()?->id;
        $this->suppliers = Supplier::where('is_active', true)->select('id', 'name', 'code', 'address', 'default_due_days')->get()->toArray();
        $this->branches = Branch::all();
    }
    
    public function updatedSupplierId($value)
    {
        if ($value) {
            $sup = collect($this->suppliers)->firstWhere('id', $value);
            if ($sup && isset($sup['default_due_days'])) {
                $this->tanggal_jatuh_tempo = date('Y-m-d', strtotime('+' . $sup['default_due_days'] . ' days'));
            }
        }
        $this->loadUnbilledInvoices();
        $this->loadDeductions();
    }
    
    public function updatedBranchId()
    {
        $this->loadUnbilledInvoices();
        $this->loadDeductions();
    }
    
    public function loadDeductions()
    {
        if (!$this->supplier_id) {
            $this->available_deductions = [];
            $this->total_deduction = 0;
            return;
        }

        $deductions = \App\Models\SupplierDeduction::where('supplier_id', $this->supplier_id)
            ->whereIn('status', ['OPEN', 'PARTIAL'])
            ->where(function($query) {
                $query->whereNull('branch_id')->orWhere('branch_id', $this->branch_id);
            })
            ->get();

        $this->available_deductions = [];
        $total = 0;
        foreach($deductions as $d) {
            $sisa = floatval($d->amount) - floatval($d->claimed_amount);
            $this->available_deductions[] = [
                'id' => $d->id,
                'type' => $d->deduction_type,
                'branch_id' => $d->branch_id,
                'sisa' => $sisa,
                'notes' => $d->notes,
                'is_selected' => false
            ];
        }
        $this->calculateTotal();
    }
    
    public function toggleDeduction($index)
    {
        $this->calculateTotal();
    }
    
    public function loadUnbilledInvoices()
    {
        if (!$this->supplier_id) {
            $this->unbilled_invoices = [];
            return;
        }
        
        // Find GRs that are not yet billed in any active Kontrabon
        // Or we can just add a column `is_billed` to GoodsReceipt, but querying relationships is safer.
        $query = GoodsReceipt::where('supplier_id', $this->supplier_id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIAL_PAID'])
            ->whereDoesntHave('kontrabonItems', function ($query) {
                // exclude invoices that are already in a NON-CANCELLED kontrabon
                $query->whereHas('kontrabon', function ($q) {
                    $q->where('status', '!=', 'CANCELLED');
                });
            });
            
        if (empty($this->branch_id)) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $this->branch_id);
        }
            
        $invoices = $query->get();
            
        $this->unbilled_invoices = [];
        foreach($invoices as $inv) {
            $remaining = $inv->total_amount - $inv->paid_amount;
            $this->unbilled_invoices[] = [
                'id' => $inv->id,
                'receipt_number' => $inv->receipt_number,
                'receipt_date' => $inv->receipt_date,
                'due_date' => $inv->due_date,
                'total_amount' => $inv->total_amount,
                'paid_amount' => $inv->paid_amount,
                'remaining_amount' => $remaining,
                'is_selected' => false
            ];
        }
        $this->calculateTotal();
    }
    
    public function toggleInvoice($index)
    {
        $this->calculateTotal();
    }
    
    public function updatedManualDeductionAmount()
    {
        $this->calculateTotal();
    }
    
    public function calculateTotal()
    {
        $total = 0;
        foreach($this->unbilled_invoices as $inv) {
            if ($inv['is_selected']) {
                $total += floatval($inv['remaining_amount']);
            }
        }
        $this->total_amount = $total;
        
        $totalDeduction = 0;
        foreach($this->available_deductions as $d) {
            if ($d['is_selected']) {
                $totalDeduction += floatval($d['sisa']);
            }
        }
        $this->total_deduction = $totalDeduction + floatval($this->manual_deduction_amount);
        
        $this->grand_total = max(0, $this->total_amount - $this->total_deduction);
    }
    
    public function save()
    {
        $this->validate([
            'supplier_id' => 'required',
            'tanggal_kontrabon' => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date',
            'total_amount' => 'required|numeric|min:1',
        ]);
        
        $selectedInvoices = collect($this->unbilled_invoices)->filter(function($inv) {
            return $inv['is_selected'];
        });
        
        if ($selectedInvoices->isEmpty()) {
            session()->flash('error', 'Pilih minimal 1 faktur untuk dikontrabonkan.');
            return;
        }
        
        DB::beginTransaction();
        try {
            $kontrabon = Kontrabon::create([
                'kontrabon_number' => $this->kontrabon_number,
                'tanggal_kontrabon' => $this->tanggal_kontrabon,
                'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo,
                'supplier_id' => $this->supplier_id,
                'branch_id' => $this->branch_id,
                'total_amount' => $this->grand_total,
                'paid_amount' => 0,
                'notes' => $this->notes,
                'status' => 'UNPAID',
                'created_by_id' => auth()->id(),
            ]);
            
            foreach ($selectedInvoices as $invData) {
                KontrabonItem::create([
                    'kontrabon_id' => $kontrabon->id,
                    'goods_receipt_id' => $invData['id'],
                    'amount' => floatval($invData['remaining_amount']),
                ]);
            }
            
            // Apply selected deductions
            $selectedDeductions = collect($this->available_deductions)->filter(function($d) {
                return $d['is_selected'];
            });
            
            if ($selectedDeductions->isNotEmpty() && $this->total_amount > 0) {
                $amountToDeduct = $this->total_amount;
                
                foreach ($selectedDeductions as $deduction) {
                    if ($amountToDeduct <= 0) break;
                    
                    $applied = min($amountToDeduct, $deduction['sisa']);
                    
                    \App\Models\KontrabonDeduction::create([
                        'kontrabon_id' => $kontrabon->id,
                        'supplier_deduction_id' => $deduction['id'],
                        'amount_applied' => $applied,
                    ]);
                    
                    $dbDeduction = \App\Models\SupplierDeduction::find($deduction['id']);
                    $newClaimed = floatval($dbDeduction->claimed_amount) + $applied;
                    $status = ($newClaimed >= floatval($dbDeduction->amount) - 0.01) ? 'COMPLETED' : 'PARTIAL';
                    
                    $dbDeduction->update([
                        'claimed_amount' => $newClaimed,
                        'status' => $status
                    ]);
                    
                    $amountToDeduct -= $applied;
                }
            }

            // Simpan potongan manual jika ada
            if (floatval($this->manual_deduction_amount) > 0) {
                \App\Models\KontrabonDeduction::create([
                    'kontrabon_id' => $kontrabon->id,
                    'supplier_deduction_id' => null,
                    'amount_applied' => floatval($this->manual_deduction_amount),
                    'notes' => $this->manual_deduction_notes ?: 'Potongan Lainnya',
                ]);
            }
            
            DB::commit();
            return redirect()->to('/admin/kontrabons');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.kontrabon-pos');
    }
}


