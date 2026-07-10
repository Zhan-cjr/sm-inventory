<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Transaction;
use App\Models\EcommerceOrder;
use App\Models\Account;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class FixJournalHpp extends Command
{
    protected $signature = 'fix:journal-hpp';
    protected $description = 'Perbaiki nominal HPP pada Jurnal Umum historis menggunakan harga dari batch';

    public function handle()
    {
        $this->info('Memulai perbaikan jurnal HPP...');
        
        $journals = JournalEntry::whereIn('journalable_type', [Transaction::class, EcommerceOrder::class])->get();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($journals as $journal) {
                // Find HPP account line
                $hppLine = JournalEntryLine::where('journal_entry_id', $journal->id)
                    ->whereHas('account', function($q) { $q->where('account_code', '5110'); })
                    ->first();
                    
                $persediaanLine = JournalEntryLine::where('journal_entry_id', $journal->id)
                    ->whereHas('account', function($q) { $q->where('account_code', '1140'); })
                    ->first();
                    
                $newCogs = 0;

                if ($journal->journalable_type === Transaction::class) {
                    $transaction = Transaction::with('items.product')->find($journal->journalable_id);
                    if (!$transaction) continue;
                    
                    $batchCogsQuery = DB::table('stock_batch_deductions as sbd')
                        ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                        ->whereIn('sbd.transaction_item_id', $transaction->items->pluck('id'))
                        ->select('sbd.transaction_item_id', DB::raw('SUM(sbd.quantity * sb.cost_price) as total_cogs'))
                        ->groupBy('sbd.transaction_item_id')
                        ->pluck('total_cogs', 'transaction_item_id');
                        
                    $stocksFallback = Stock::where('branch_id', $transaction->branch_id)
                        ->whereIn('product_id', $transaction->items->pluck('product_id')->filter())
                        ->get()->keyBy('product_id');

                    foreach ($transaction->items as $item) {
                        if ($item->is_assembly_component) continue;
                        $isService = ($item->service_id !== null) || ($item->product && !empty($item->product->ppob_sku));
                        
                        if ($item->product && !$isService) {
                            if (isset($batchCogsQuery[$item->id])) {
                                $newCogs += $batchCogsQuery[$item->id];
                            } else {
                                $stock = $stocksFallback[$item->product_id] ?? null;
                                $fallbackPrice = $stock && $stock->cost_price_tax > 0 ? $stock->cost_price_tax :
                                                 ($stock && $stock->cost_price > 0 ? $stock->cost_price :
                                                 ($item->product->cost_price_tax > 0 ? $item->product->cost_price_tax : 
                                                  $item->product->cost_price));
                                $newCogs += (float)$fallbackPrice * (float)$item->quantity;
                            }
                        }
                    }

                } elseif ($journal->journalable_type === EcommerceOrder::class) {
                    $order = EcommerceOrder::with('items.product')->find($journal->journalable_id);
                    if (!$order) continue;
                    
                    $batchCogsQuery = DB::table('stock_batch_deductions as sbd')
                        ->join('stock_batches as sb', 'sbd.stock_batch_id', '=', 'sb.id')
                        ->whereIn('sbd.ecommerce_order_item_id', $order->items->pluck('id'))
                        ->select('sbd.ecommerce_order_item_id', DB::raw('SUM(sbd.quantity * sb.cost_price) as total_cogs'))
                        ->groupBy('sbd.ecommerce_order_item_id')
                        ->pluck('total_cogs', 'ecommerce_order_item_id');
                        
                    $stocksFallback = Stock::where('branch_id', $order->branch_id)
                        ->whereIn('product_id', $order->items->pluck('product_id')->filter())
                        ->get()->keyBy('product_id');
                        
                    foreach ($order->items as $item) {
                        if (!$item->product) continue;
                        if (isset($batchCogsQuery[$item->id])) {
                            $newCogs += $batchCogsQuery[$item->id];
                        } else {
                            $stock = $stocksFallback[$item->product_id] ?? null;
                            $fallbackPrice = $stock && $stock->cost_price_tax > 0 ? $stock->cost_price_tax :
                                             ($stock && $stock->cost_price > 0 ? $stock->cost_price :
                                             ($item->product->cost_price_tax > 0 ? $item->product->cost_price_tax : 
                                              $item->product->cost_price));
                            $newCogs += (float)$fallbackPrice * (float)$item->quantity;
                        }
                    }
                }
                
                $newCogs = round($newCogs, 2);
                
                if ($newCogs > 0) {
                    if (!$hppLine) {
                        $hppAccount = Account::where('account_code', '5110')->first();
                        if ($hppAccount) {
                            $hppLine = new JournalEntryLine([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $hppAccount->id,
                                'description' => 'Harga Pokok Penjualan',
                                'debit' => 0,
                                'credit' => 0,
                            ]);
                        }
                    }
                    if (!$persediaanLine) {
                        $persediaanAccount = Account::where('account_code', '1140')->first();
                        if ($persediaanAccount) {
                            $persediaanLine = new JournalEntryLine([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $persediaanAccount->id,
                                'description' => 'Persediaan Barang Dagang',
                                'debit' => 0,
                                'credit' => 0,
                            ]);
                        }
                    }
                    
                    if ($hppLine && $persediaanLine && round($hppLine->debit, 2) != $newCogs) {
                        $hppLine->debit = $newCogs;
                        $hppLine->save();
                        
                        $persediaanLine->credit = $newCogs;
                        $persediaanLine->save();
                        
                        $updatedCount++;
                    }
                }
            }

            DB::commit();
            $this->info("Berhasil memperbaiki HPP di {$updatedCount} Jurnal Umum historis.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
