<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Transaction;

class AccountingService
{
    /**
     * Otomatis mencatat jurnal akuntansi saat transaksi selesai
     */
    public function recordTransactionJournal(Transaction $transaction)
    {
        // 1. Dapatkan COA yang sesuai
        $kasAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '1110')->first();
        $pendapatanAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '4110')->first();
        $diskonAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '4130')->first();
        $hppAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '5110')->first();
        $persediaanAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '1140')->first();
        $pajakAccount = Account::where('organization_id', $transaction->organization_id)
            ->where('account_code', '2120')->first();
            
        // Akun-akun Enterprise untuk PPOB dan Layanan
        $pendapatanLayananAccount = Account::firstOrCreate([
            'organization_id' => $transaction->organization_id,
            'account_code' => '4120'
        ], [
            'name' => 'Pendapatan Layanan (Service)', 'type' => 'revenue', 'description' => 'Pendapatan dari layanan/PPOB', 'is_active' => true
        ]);
        
        $hppLayananAccount = Account::firstOrCreate([
            'organization_id' => $transaction->organization_id,
            'account_code' => '5130'
        ], [
            'name' => 'Harga Pokok Layanan/PPOB', 'type' => 'expense', 'description' => 'Harga pokok untuk layanan atau produk digital PPOB', 'is_active' => true
        ]);
        
        $saldoPpobAccount = Account::firstOrCreate([
            'organization_id' => $transaction->organization_id,
            'account_code' => '1150'
        ], [
            'name' => 'Saldo Deposit PPOB', 'type' => 'asset', 'description' => 'Saldo deposit pada provider PPOB (Digiflazz, dll)', 'is_active' => true
        ]);

        // Jika akun penting tidak ada, lewati pencatatan jurnal
        if (!$kasAccount || !$pendapatanAccount) {
            return false;
        }

        // Cek jika Jurnal sudah pernah dibuat (untuk mencegah ganda di SyncController)
        $existingJournal = JournalEntry::where('journalable_id', $transaction->id)
            ->where('journalable_type', Transaction::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        // 2. Buat Header Jurnal
        $journal = JournalEntry::create([
            'organization_id' => $transaction->organization_id,
            'branch_id' => $transaction->branch_id,
            'reference_number' => 'JV-' . $transaction->receipt_number,
            'entry_date' => $transaction->transaction_date,
            'description' => 'Penjualan Kasir POS: ' . $transaction->receipt_number,
            'status' => 'posted',
            'created_by' => $transaction->cashier_id,
            'journalable_id' => $transaction->id,
            'journalable_type' => Transaction::class,
        ]);

        // 3. Catat Jurnal Kas & Diskon
        // DEBIT: Kas (senilai uang yang sebenarnya dibayarkan / final_amount)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $kasAccount->id,
            'description' => 'Kas masuk dari penjualan',
            'debit' => $transaction->final_amount,
            'credit' => 0,
        ]);

        // DEBIT: Diskon (Jika Ada)
        $totalDiscount = max(0, $transaction->total_amount - $transaction->final_amount);
        
        if ($totalDiscount > 0 && $diskonAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $diskonAccount->id,
                'description' => 'Diskon penjualan',
                'debit' => $totalDiscount,
                'credit' => 0,
            ]);
        }

        // Kalkulasi Pendapatan dan HPP Terpisah (Produk vs Layanan)
        $taxRate = \App\Models\Organization::find($transaction->organization_id)->tax_rate ?? 11;
        $taxMultiplier = 1 + ($taxRate / 100);
        
        $taxAmount = 0;
        $revenueProduct = 0;
        $revenueService = 0;
        $cogsProduct = 0;
        $cogsService = 0;

        foreach ($transaction->items as $item) {
            $itemGross = ($item->unit_price - $item->discount_per_item) * $item->quantity;
            
            // Terapkan proporsi global discount
            $itemProportion = $transaction->total_amount > 0 ? ($itemGross / $transaction->total_amount) : 0;
            $itemFinalDiscount = $totalDiscount * $itemProportion;
            $itemNet = $itemGross - $itemFinalDiscount;

            $isService = $item->service_id !== null || ($item->product && !empty($item->product->ppob_sku));

            // PPN Kalkulasi
            if ($item->product && $item->product->is_taxable) {
                $dpp = round($itemNet / $taxMultiplier, 2);
                $taxAmount += ($itemNet - $dpp);
                if ($isService) {
                    $revenueService += $dpp;
                } else {
                    $revenueProduct += $dpp;
                }
            } else {
                if ($isService) {
                    $revenueService += $itemNet;
                } else {
                    $revenueProduct += $itemNet;
                }
            }
            
            // COGS Kalkulasi
            if ($isService) {
                if ($item->product) {
                    // PPOB cost is based on Digiflazz price or product cost price
                    // We assume product->cost_price is kept relatively updated, or we use transaction's PPobTransaction
                    $cogsService += ($item->product->cost_price * $item->quantity);
                }
            } else {
                if ($item->product) {
                    $cogsProduct += ($item->product->cost_price * $item->quantity);
                }
            }
        }

        // KREDIT: Pendapatan Penjualan (Produk Fisik)
        if ($revenueProduct > 0 && $pendapatanAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pendapatanAccount->id,
                'description' => 'Pendapatan atas penjualan produk fisik',
                'debit' => 0,
                'credit' => $revenueProduct,
            ]);
        }

        // KREDIT: Pendapatan Layanan/PPOB
        if ($revenueService > 0 && $pendapatanLayananAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pendapatanLayananAccount->id,
                'description' => 'Pendapatan atas layanan / PPOB',
                'debit' => 0,
                'credit' => $revenueService,
            ]);
        }

        // KREDIT: Hutang Pajak / PPN Keluaran
        if ($taxAmount > 0 && $pajakAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pajakAccount->id,
                'description' => 'PPN Keluaran atas penjualan',
                'debit' => 0,
                'credit' => $taxAmount,
            ]);
        } elseif ($taxAmount > 0) {
            // Jika akun pajak tidak ada, masukkan semua ke pendapatan produk
            $line = JournalEntryLine::where('journal_entry_id', $journal->id)
                ->where('account_id', $pendapatanAccount->id)
                ->first();
            if ($line) {
                $line->credit += $taxAmount;
                $line->save();
            }
        }

        // 4. Catat Harga Pokok Penjualan (HPP) & Persediaan (Produk Fisik)
        if ($cogsProduct > 0 && $hppAccount && $persediaanAccount) {
            // DEBIT: HPP Produk
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $hppAccount->id,
                'description' => 'HPP barang fisik terjual',
                'debit' => $cogsProduct,
                'credit' => 0,
            ]);

            // KREDIT: Persediaan Barang Dagang
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Pengurangan persediaan barang fisik',
                'debit' => 0,
                'credit' => $cogsProduct,
            ]);
        }

        // 5. Catat Harga Pokok Penjualan (HPP) & Pengurangan Saldo (PPOB/Layanan)
        if ($cogsService > 0 && $hppLayananAccount && $saldoPpobAccount) {
            // DEBIT: HPP Layanan
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $hppLayananAccount->id,
                'description' => 'HPP Layanan/PPOB terjual',
                'debit' => $cogsService,
                'credit' => 0,
            ]);

            // KREDIT: Saldo Deposit PPOB
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $saldoPpobAccount->id,
                'description' => 'Pengurangan saldo deposit PPOB',
                'debit' => 0,
                'credit' => $cogsService,
            ]);
        }

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Penerimaan Barang (GR) selesai
     */
    public function recordGoodsReceiptJournal(\App\Models\GoodsReceipt $receipt)
    {
        // 1. Dapatkan COA yang sesuai
        $organizationId = $receipt->branch->organization_id ?? 1;

        $persediaanAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '1140')->first();
        $hutangAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '2110')->first();
        $pajakAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '2120')->first();

        // Jika akun penting tidak ada, lewati
        if (!$persediaanAccount || !$hutangAccount) {
            return false;
        }

        // Cek jika Jurnal sudah pernah dibuat
        $existingJournal = JournalEntry::where('journalable_id', $receipt->id)
            ->where('journalable_type', \App\Models\GoodsReceipt::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        // 2. Buat Header Jurnal
        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $receipt->branch_id,
            'reference_number' => 'JV-' . $receipt->receipt_number,
            'entry_date' => $receipt->receipt_date ?? now(),
            'description' => 'Penerimaan Barang: ' . $receipt->receipt_number,
            'status' => 'posted',
            'created_by' => auth()->check() ? auth()->id() : 1,
            'journalable_id' => $receipt->id,
            'journalable_type' => \App\Models\GoodsReceipt::class,
        ]);

        // Kalkulasi Pajak Masukan
        $taxAmount = $receipt->include_tax ? ($receipt->tax_amount ?? 0) : 0;
        $inventoryAmount = ($receipt->total_amount ?? 0) - $taxAmount;

        // 3. Catat Jurnal
        // DEBIT: Persediaan Barang Dagang
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $persediaanAccount->id,
            'description' => 'Penambahan persediaan dari Penerimaan',
            'debit' => $inventoryAmount,
            'credit' => 0,
        ]);

        // DEBIT: Hutang Pajak / PPN Masukan
        if ($taxAmount > 0 && $pajakAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pajakAccount->id,
                'description' => 'PPN Masukan atas pembelian',
                'debit' => $taxAmount,
                'credit' => 0,
            ]);
        } elseif ($taxAmount > 0) {
            // Jika akun pajak tidak ada, tambahkan kembali ke Persediaan
            $line = JournalEntryLine::where('journal_entry_id', $journal->id)
                ->where('account_id', $persediaanAccount->id)
                ->first();
            $line->debit += $taxAmount;
            $line->save();
        }

        // KREDIT: Hutang Usaha
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $hutangAccount->id,
            'description' => 'Hutang pembelian barang',
            'debit' => 0,
            'credit' => $receipt->total_amount ?? 0,
        ]);

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Retur Pembelian (PRT) selesai
     */
    public function recordPurchaseReturnJournal(\App\Models\PurchaseReturn $return)
    {
        // 1. Dapatkan COA yang sesuai
        $organizationId = $return->branch->organization_id ?? 1;
        
        $persediaanAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '1140')->first();
        $hutangAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '2110')->first();
        $pajakAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '2120')->first();

        // Jika akun penting tidak ada, lewati
        if (!$persediaanAccount || (!$hutangAccount && $return->total_amount > 0)) {
            return false;
        }

        // Cek jika Jurnal sudah pernah dibuat
        $existingJournal = JournalEntry::where('journalable_id', $return->id)
            ->where('journalable_type', \App\Models\PurchaseReturn::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        // 2. Buat Header Jurnal
        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $return->branch_id,
            'reference_number' => 'JV-' . $return->return_number,
            'entry_date' => $return->return_date ?? now(),
            'description' => 'Retur Pembelian: ' . $return->return_number,
            'status' => 'posted',
            'created_by' => $return->created_by ?? (auth()->check() ? auth()->id() : 1),
            'journalable_id' => $return->id,
            'journalable_type' => \App\Models\PurchaseReturn::class,
        ]);

        // Kalkulasi PPN berdasarkan Goods Receipt referensi
        $taxAmount = 0;
        $gr = $return->goodsReceipt;
        if ($gr && $gr->include_tax) {
            $taxRate = \App\Models\Organization::find($organizationId)->tax_rate ?? 11;
            $taxMultiplier = 1 + ($taxRate / 100);
            
            $dpp = round($return->total_amount / $taxMultiplier, 2);
            $taxAmount = $return->total_amount - $dpp;
        }

        $inventoryAmount = $return->total_amount - $taxAmount;

        // 3. Catat Jurnal (Kebalikan dari Penerimaan Barang)
        
        // DEBIT: Hutang Usaha (Hutang berkurang sebesar total nominal retur termasuk pajak)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $hutangAccount->id,
            'description' => 'Pengurangan hutang karena retur',
            'debit' => $return->total_amount ?? 0,
            'credit' => 0,
        ]);

        // KREDIT: Persediaan Barang Dagang (Stok berkurang sebesar nilai barang/DPP)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $persediaanAccount->id,
            'description' => 'Pengurangan persediaan karena retur',
            'debit' => 0,
            'credit' => $inventoryAmount,
        ]);

        // KREDIT: Hutang Pajak / PPN Masukan (PPN Masukan dikurangi/dikredit)
        if ($taxAmount > 0 && $pajakAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pajakAccount->id,
                'description' => 'Penyesuaian PPN Masukan atas retur',
                'debit' => 0,
                'credit' => $taxAmount,
            ]);
        } elseif ($taxAmount > 0) {
            // Jika tidak ada akun pajak, kembalikan ke akun persediaan
            $line = JournalEntryLine::where('journal_entry_id', $journal->id)
                ->where('account_id', $persediaanAccount->id)
                ->first();
            $line->credit += $taxAmount;
            $line->save();
        }

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Pesanan E-Commerce selesai (COMPLETED)
     */
    public function recordEcommerceOrderJournal(\App\Models\EcommerceOrder $order)
    {
        // 1. Dapatkan COA yang sesuai
        $organizationId = $order->organization_id ?? 1;

        $kasAccount = Account::where('organization_id', $organizationId)->where('account_code', '1110')->first();
        $pendapatanAccount = Account::where('organization_id', $organizationId)->where('account_code', '4110')->first();
        $diskonAccount = Account::where('organization_id', $organizationId)->where('account_code', '4130')->first();
        $hppAccount = Account::where('organization_id', $organizationId)->where('account_code', '5110')->first();
        $persediaanAccount = Account::where('organization_id', $organizationId)->where('account_code', '1140')->first();
        $pajakAccount = Account::where('organization_id', $organizationId)->where('account_code', '2120')->first();

        // Jika akun penting tidak ada, lewati
        if (!$kasAccount || !$pendapatanAccount) {
            return false;
        }

        // Cek jika Jurnal sudah pernah dibuat
        $existingJournal = JournalEntry::where('journalable_id', $order->id)
            ->where('journalable_type', \App\Models\EcommerceOrder::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        // 2. Buat Header Jurnal
        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $order->branch_id,
            'reference_number' => 'JV-ECOMM-' . strtoupper(substr($order->id, 0, 8)),
            'entry_date' => $order->updated_at ?? now(),
            'description' => 'Penjualan E-Commerce: ' . strtoupper(substr($order->id, 0, 8)),
            'status' => 'posted',
            'created_by' => $order->processed_by ?? 1,
            'journalable_id' => $order->id,
            'journalable_type' => \App\Models\EcommerceOrder::class,
        ]);

        // 3. Catat Jurnal Kas & Pendapatan
        // DEBIT: Kas (senilai final_amount yang dibayar customer)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $kasAccount->id,
            'description' => 'Kas masuk dari penjualan E-Commerce',
            'debit' => $order->total_amount,
            'credit' => 0,
        ]);

        // DEBIT: Diskon (Tukar Poin)
        $totalDiscount = $order->points_redeemed_discount ?? 0;
        if ($totalDiscount > 0 && $diskonAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $diskonAccount->id,
                'description' => 'Diskon tukar poin E-Commerce',
                'debit' => $totalDiscount,
                'credit' => 0,
            ]);
        }

        // Kalkulasi PPN Keluaran berdasarkan item
        $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
        $taxMultiplier = 1 + ($taxRate / 100);
        $taxAmount = 0;
        $revenueAmount = 0;

        $grossTotal = $order->total_amount + $totalDiscount;

        foreach ($order->items as $item) {
            $itemGross = $item->subtotal;
            
            // Terapkan proporsi diskon poin ke item ini
            $itemProportion = $grossTotal > 0 ? ($itemGross / $grossTotal) : 0;
            $itemFinalDiscount = $totalDiscount * $itemProportion;
            $itemNet = $itemGross - $itemFinalDiscount;

            if ($item->product && $item->product->is_taxable) {
                $dpp = round($itemNet / $taxMultiplier, 2);
                $taxAmount += ($itemNet - $dpp);
                $revenueAmount += $dpp;
            } else {
                $revenueAmount += $itemNet;
            }
        }

        // KREDIT: Pendapatan Penjualan
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $pendapatanAccount->id,
            'description' => 'Pendapatan atas penjualan produk E-Commerce',
            'debit' => 0,
            'credit' => $revenueAmount,
        ]);

        // KREDIT: Hutang Pajak / PPN Keluaran
        if ($taxAmount > 0 && $pajakAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pajakAccount->id,
                'description' => 'PPN Keluaran atas penjualan E-Commerce',
                'debit' => 0,
                'credit' => $taxAmount,
            ]);
        } elseif ($taxAmount > 0) {
            $line = JournalEntryLine::where('journal_entry_id', $journal->id)
                ->where('account_id', $pendapatanAccount->id)
                ->first();
            $line->credit += $taxAmount;
            $line->save();
        }

        // 4. Catat Harga Pokok Penjualan (HPP) & Persediaan
        $cogs = $order->items->sum(function ($item) {
            return $item->product ? ($item->product->cost_price * $item->quantity) : 0;
        });

        if ($cogs > 0 && $hppAccount && $persediaanAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $hppAccount->id,
                'description' => 'HPP barang terjual E-Commerce',
                'debit' => $cogs,
                'credit' => 0,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Pengurangan persediaan barang E-Commerce',
                'debit' => 0,
                'credit' => $cogs,
            ]);
        }

        return true;
    }
    /**
     * Otomatis mencatat jurnal saat Penyesuaian Stok (Stock Adjustment / Opname) selesai
     */
    public function recordStockAdjustmentJournal(\App\Models\StockAdjustment $adjustment)
    {
        $organizationId = $adjustment->branch->organization_id ?? 1;

        $persediaanAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '1140')->first();
            
        // Buat akun selisih stok jika belum ada, sebagai bagian dari fitur Enterprise
        $bebanSelisihAccount = Account::firstOrCreate([
            'organization_id' => $organizationId,
            'account_code' => '5120'
        ], [
            'name' => 'Beban Penyesuaian Stok (Stock Discrepancy)',
            'type' => 'expense',
            'description' => 'Beban akibat barang hilang atau rusak dari opname',
            'is_active' => true,
        ]);

        $pendapatanSelisihAccount = Account::firstOrCreate([
            'organization_id' => $organizationId,
            'account_code' => '4210'
        ], [
            'name' => 'Pendapatan Lain-lain (Other Income)',
            'type' => 'revenue',
            'description' => 'Pendapatan dari surplus stok atau hal lainnya',
            'is_active' => true,
        ]);

        if (!$persediaanAccount) {
            return false;
        }

        $existingJournal = JournalEntry::where('journalable_id', $adjustment->id)
            ->where('journalable_type', \App\Models\StockAdjustment::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        // Kalkulasi Total Plus dan Minus berdasarkan Harga Pokok
        $totalLoss = 0; // Barang hilang -> Beban
        $totalGain = 0; // Barang lebih -> Pendapatan

        foreach ($adjustment->items as $item) {
            $diff = $item->new_quantity - $item->previous_quantity;
            $cogs = $item->product->cost_price ?? 0;
            $value = abs($diff * $cogs);

            if ($diff < 0) {
                $totalLoss += $value;
            } elseif ($diff > 0) {
                $totalGain += $value;
            }
        }

        if ($totalLoss == 0 && $totalGain == 0) {
            return false; // Tidak ada perubahan finansial
        }

        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $adjustment->branch_id,
            'reference_number' => 'JV-' . $adjustment->adjustment_number,
            'entry_date' => $adjustment->adjustment_date ?? now(),
            'description' => 'Penyesuaian Stok: ' . $adjustment->adjustment_number,
            'status' => 'posted',
            'created_by' => $adjustment->recorded_by ?? (auth()->check() ? auth()->id() : 1),
            'journalable_id' => $adjustment->id,
            'journalable_type' => \App\Models\StockAdjustment::class,
        ]);

        // Catat kerugian stok (Minus)
        if ($totalLoss > 0) {
            // DEBIT: Beban Selisih Stok
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $bebanSelisihAccount->id,
                'description' => 'Kerugian penyesuaian stok (Barang Hilang/Rusak)',
                'debit' => $totalLoss,
                'credit' => 0,
            ]);

            // KREDIT: Persediaan
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Pengurangan persediaan karena penyesuaian',
                'debit' => 0,
                'credit' => $totalLoss,
            ]);
        }

        // Catat surplus stok (Plus)
        if ($totalGain > 0) {
            // DEBIT: Persediaan
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Penambahan persediaan karena penyesuaian',
                'debit' => $totalGain,
                'credit' => 0,
            ]);

            // KREDIT: Pendapatan Lain-lain
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pendapatanSelisihAccount->id,
                'description' => 'Surplus penyesuaian stok (Barang Lebih)',
                'debit' => 0,
                'credit' => $totalGain,
            ]);
        }

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Stok Opname selesai
     */
    public function recordStockOpnameJournal(\App\Models\StockOpnameSession $session)
    {
        $organizationId = $session->branch->organization_id ?? 1;

        $persediaanAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '1140')->first();
            
        $bebanSelisihAccount = Account::firstOrCreate([
            'organization_id' => $organizationId,
            'account_code' => '5120'
        ], [
            'name' => 'Beban Penyesuaian Stok (Stock Discrepancy)',
            'type' => 'expense',
            'description' => 'Beban akibat barang hilang atau rusak dari opname',
            'is_active' => true,
        ]);

        $pendapatanSelisihAccount = Account::firstOrCreate([
            'organization_id' => $organizationId,
            'account_code' => '4210'
        ], [
            'name' => 'Pendapatan Lain-lain (Other Income)',
            'type' => 'revenue',
            'description' => 'Pendapatan dari surplus stok atau hal lainnya',
            'is_active' => true,
        ]);

        if (!$persediaanAccount) {
            return false;
        }

        $existingJournal = JournalEntry::where('journalable_id', $session->id)
            ->where('journalable_type', \App\Models\StockOpnameSession::class)
            ->first();
            
        if ($existingJournal) {
            return false;
        }

        $totalLoss = 0; 
        $totalGain = 0; 

        $productSummary = $session->getProductSummary();

        foreach ($productSummary as $summary) {
            $productId = $summary['product_id'];
            if (!$productId) continue;

            $diff = $summary['final_disc'];
            if ($diff == 0) continue;

            $product = \App\Models\Product::find($productId);
            $cogs = $product->cost_price ?? 0;
            $value = abs($diff * $cogs);

            if ($diff < 0) {
                $totalLoss += $value;
            } elseif ($diff > 0) {
                $totalGain += $value;
            }
        }

        if ($totalLoss == 0 && $totalGain == 0) {
            return false;
        }

        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $session->branch_id,
            'reference_number' => 'JV-' . $session->session_number,
            'entry_date' => $session->opname_date ?? now(),
            'description' => 'Stok Opname: ' . $session->session_number,
            'status' => 'posted',
            'created_by' => $session->approved_by ?? (auth()->check() ? auth()->id() : 1),
            'journalable_id' => $session->id,
            'journalable_type' => \App\Models\StockOpnameSession::class,
        ]);

        if ($totalLoss > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $bebanSelisihAccount->id,
                'description' => 'Kerugian selisih stok opname (Barang Hilang)',
                'debit' => $totalLoss,
                'credit' => 0,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Pengurangan persediaan karena opname',
                'debit' => 0,
                'credit' => $totalLoss,
            ]);
        }

        if ($totalGain > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Penambahan persediaan karena opname',
                'debit' => $totalGain,
                'credit' => 0,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $pendapatanSelisihAccount->id,
                'description' => 'Surplus stok opname (Barang Lebih)',
                'debit' => 0,
                'credit' => $totalGain,
            ]);
        }

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Transfer Stok (Stock Transfer) selesai/diterima
     */
    public function recordStockTransferJournal(\App\Models\StockTransfer $transfer)
    {
        // Pastikan statusnya received
        if ($transfer->status !== 'received') {
            return false;
        }

        $organizationId = $transfer->fromBranch->organization_id ?? 1;

        $persediaanAccount = Account::where('organization_id', $organizationId)
            ->where('account_code', '1140')->first();
            
        // Akun Perantara (Clearing) Mutasi Antar Cabang
        $mutasiAccount = Account::firstOrCreate([
            'organization_id' => $organizationId,
            'account_code' => '3110'
        ], [
            'name' => 'Mutasi Antar Cabang',
            'type' => 'equity',
            'description' => 'Akun perantara untuk mutasi transfer barang antar cabang',
            'is_active' => true,
        ]);

        if (!$persediaanAccount) {
            return false;
        }

        // Hitung total nilai persediaan yang ditransfer berdasarkan HPP
        $totalValue = 0;
        foreach ($transfer->items as $item) {
            $cogs = $item->product->cost_price ?? 0;
            $totalValue += ($item->quantity * $cogs);
        }

        if ($totalValue <= 0) {
            return false;
        }

        // ============================================
        // 1. Jurnal untuk Cabang Asal (Pengurang Stok)
        // ============================================
        $existingJournalOut = JournalEntry::where('journalable_id', $transfer->id)
            ->where('journalable_type', \App\Models\StockTransfer::class)
            ->where('branch_id', $transfer->from_branch_id)
            ->first();

        if (!$existingJournalOut) {
            $journalOut = JournalEntry::create([
                'organization_id' => $organizationId,
                'branch_id' => $transfer->from_branch_id,
                'reference_number' => 'JV-TF-OUT-' . $transfer->reference_number,
                'entry_date' => $transfer->received_date ?? now(),
                'description' => 'Transfer Keluar Stok ke Cabang Tujuan: ' . $transfer->reference_number,
                'status' => 'posted',
                'created_by' => $transfer->received_by ?? (auth()->check() ? auth()->id() : 1),
                'journalable_id' => $transfer->id,
                'journalable_type' => \App\Models\StockTransfer::class,
            ]);

            // DEBIT: Mutasi Antar Cabang
            JournalEntryLine::create([
                'journal_entry_id' => $journalOut->id,
                'account_id' => $mutasiAccount->id,
                'description' => 'Mutasi transfer keluar',
                'debit' => $totalValue,
                'credit' => 0,
            ]);

            // KREDIT: Persediaan Barang (Berkurang)
            JournalEntryLine::create([
                'journal_entry_id' => $journalOut->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Pengurangan persediaan transfer keluar',
                'debit' => 0,
                'credit' => $totalValue,
            ]);
        }

        // ============================================
        // 2. Jurnal untuk Cabang Tujuan (Penambah Stok)
        // ============================================
        $existingJournalIn = JournalEntry::where('journalable_id', $transfer->id)
            ->where('journalable_type', \App\Models\StockTransfer::class)
            ->where('branch_id', $transfer->to_branch_id)
            ->first();

        if (!$existingJournalIn) {
            $journalIn = JournalEntry::create([
                'organization_id' => $organizationId,
                'branch_id' => $transfer->to_branch_id,
                'reference_number' => 'JV-TF-IN-' . $transfer->reference_number,
                'entry_date' => $transfer->received_date ?? now(),
                'description' => 'Transfer Masuk Stok dari Cabang Asal: ' . $transfer->reference_number,
                'status' => 'posted',
                'created_by' => $transfer->received_by ?? (auth()->check() ? auth()->id() : 1),
                'journalable_id' => $transfer->id,
                'journalable_type' => \App\Models\StockTransfer::class,
            ]);

            // DEBIT: Persediaan Barang (Bertambah)
            JournalEntryLine::create([
                'journal_entry_id' => $journalIn->id,
                'account_id' => $persediaanAccount->id,
                'description' => 'Penambahan persediaan transfer masuk',
                'debit' => $totalValue,
                'credit' => 0,
            ]);

            // KREDIT: Mutasi Antar Cabang
            JournalEntryLine::create([
                'journal_entry_id' => $journalIn->id,
                'account_id' => $mutasiAccount->id,
                'description' => 'Mutasi transfer masuk',
                'debit' => 0,
                'credit' => $totalValue,
            ]);
        }

        return true;
    }

    /**
     * Otomatis mencatat jurnal saat Pengeluaran/Expense dibuat
     */
    public function recordExpenseJournal(\App\Models\Expense $expense)
    {
        $organizationId = $expense->organization_id ?? 1;

        $existingJournal = JournalEntry::where('journalable_id', $expense->id)
            ->where('journalable_type', \App\Models\Expense::class)
            ->first();

        if ($existingJournal) {
            return false;
        }

        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $expense->branch_id,
            'reference_number' => 'JV-' . $expense->reference_number,
            'entry_date' => $expense->expense_date ?? now(),
            'description' => 'Pengeluaran/Expense: ' . ($expense->description ?? $expense->reference_number),
            'status' => 'posted',
            'created_by' => $expense->created_by ?? (auth()->check() ? auth()->id() : 1),
            'journalable_id' => $expense->id,
            'journalable_type' => \App\Models\Expense::class,
        ]);

        // DEBIT: Akun Pengeluaran (Beban / Prive / dll)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $expense->expense_account_id,
            'description' => 'Debit ' . ($expense->description ?? 'Pengeluaran'),
            'debit' => $expense->amount,
            'credit' => 0,
        ]);

        // KREDIT: Akun Pembayaran (Kas / Bank)
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $expense->payment_account_id,
            'description' => 'Kredit Pembayaran ' . ($expense->description ?? 'Pengeluaran'),
            'debit' => 0,
            'credit' => $expense->amount,
        ]);

        return true;
    }
}
