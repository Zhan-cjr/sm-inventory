<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportPrintController extends Controller
{
    public function print($type, Request $request)
    {
        $filters = $request->input('tableFilters', []);

        switch ($type) {
            case 'laporan-penjualan-kasir':
                return $this->printPenjualanKasir($filters);
            case 'arsip-transaksi':
                return $this->printArsipTransaksi($filters);
            case 'laporan-penjualan':
                return $this->printLaporanPenjualan($filters);
            case 'laporan-persediaan':
                return $this->printLaporanPersediaan($filters);
            case 'laporan-shift-kasir':
                return $this->printLaporanShiftKasir($filters);
            case 'laporan-laba-rugi':
                return $this->printLaporanLabaRugi($filters);
            case 'laporan-barang-dijual':
                return $this->printLaporanBarangDijual($filters);
            case 'laporan-jasa-terjual':
                return $this->printLaporanJasaTerjual($filters);
            case 'laporan-barang-dibeli':
                return $this->printLaporanBarangDibeli($filters);
            default:
                abort(404, 'Tipe laporan tidak ditemukan');
        }
    }

    private function applyDateFilters($query, $filters, $dateColumn = 'transaction_date', $filterName = 'date_filter')
    {
        // Try new standardized format or fallback to legacy format
        $filterData = $filters[$filterName] ?? $filters['transaction_date'] ?? null;
        if (!$filterData) return $query;

        $period = $filterData['period'] ?? null;
        $from = $filterData['created_from'] ?? null;
        $until = $filterData['created_until'] ?? null;

        if ($period === 'today') {
            return $query->whereDate($dateColumn, Carbon::today());
        } elseif ($period === 'yesterday') {
            return $query->whereDate($dateColumn, Carbon::yesterday());
        } elseif ($period === 'this_week') {
            return $query->whereBetween($dateColumn, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($period === 'last_week') {
            return $query->whereBetween($dateColumn, [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            return $query->whereMonth($dateColumn, Carbon::now()->month)->whereYear($dateColumn, Carbon::now()->year);
        } elseif ($period === 'last_month') {
            return $query->whereMonth($dateColumn, Carbon::now()->subMonth()->month)->whereYear($dateColumn, Carbon::now()->subMonth()->year);
        } elseif ($period === 'custom' || $from || $until) {
            if ($from) $query->whereDate($dateColumn, '>=', $from);
            if ($until) $query->whereDate($dateColumn, '<=', $until);
            return $query;
        }

        return $query;
    }

    private function getPeriodString($filters, $filterName = 'date_filter')
    {
        $filterData = $filters[$filterName] ?? $filters['transaction_date'] ?? null;
        if (!$filterData) return 'Semua Waktu';

        $period = $filterData['period'] ?? null;
        $labels = [
            'today' => 'Hari Ini',
            'yesterday' => 'Kemarin',
            'this_week' => 'Minggu Ini',
            'last_week' => 'Minggu Kemarin',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Kemarin',
        ];

        if ($period && array_key_exists($period, $labels)) {
            return $labels[$period];
        }

        $from = $filterData['created_from'] ?? null;
        $until = $filterData['created_until'] ?? null;

        if ($from && $until) {
            return \Carbon\Carbon::parse($from)->format('d-m-Y') . ' s/d ' . \Carbon\Carbon::parse($until)->format('d-m-Y');
        } elseif ($from) {
            return 'Sejak ' . \Carbon\Carbon::parse($from)->format('d-m-Y');
        } elseif ($until) {
            return 'Hingga ' . \Carbon\Carbon::parse($until)->format('d-m-Y');
        }

        return 'Semua Waktu';
    }

    private function printPenjualanKasir($filters)
    {
        $query = Transaction::query()
            ->where('is_voided', false)
            ->with(['terminal']);

        $query = $this->applyDateFilters($query, $filters);

        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        if (isset($filters['terminal_id']['value']) && !empty($filters['terminal_id']['value'])) {
            $query->where('terminal_id', $filters['terminal_id']['value']);
        }
        if (isset($filters['cashier_id']['value']) && !empty($filters['cashier_id']['value'])) {
            $query->where('cashier_id', $filters['cashier_id']['value']);
        }

        $transactions = $query->get();

        // Agregasi per Tanggal dan Terminal (KS)
        $data = [];
        foreach ($transactions as $t) {
            $date = \Carbon\Carbon::parse($t->transaction_date)->format('d-m-Y');
            $terminal = $t->terminal ? $t->terminal->name : 'Unk';
            $key = $date . '|' . $terminal;

            if (!isset($data[$key])) {
                $data[$key] = [
                    'tanggal' => $date,
                    'ks' => $terminal,
                    'jml_nota' => 0,
                    'penjualan' => 0,
                    'tunai' => 0,
                    'kredit' => 0,
                    'card' => 0,
                    'charge' => 0,
                    'voucher' => 0,
                    'gift' => 0,
                    'diskon' => 0,
                    'retur' => 0,
                    'jual_netto' => 0,
                ];
            }

            $data[$key]['jml_nota'] += 1;

            if ($t->transaction_type === 'RETURN') {
                $abs_amount = abs($t->final_amount);
                $data[$key]['retur'] += $abs_amount;
                $data[$key]['jual_netto'] += $t->final_amount; // final_amount is negative, so adding it reduces net sales
                
                $method = strtolower($t->payment_method);
                if ($method === 'cash') {
                    $data[$key]['tunai'] += $t->final_amount;
                } elseif (in_array($method, ['card', 'debit', 'credit', 'transfer', 'qris'])) {
                    $data[$key]['card'] += $t->final_amount;
                } else {
                    $data[$key]['tunai'] += $t->final_amount;
                }
            } else {
                $data[$key]['penjualan'] += $t->total_amount;
                $data[$key]['diskon'] += $t->discount_amount;
                $data[$key]['jual_netto'] += $t->final_amount;

                $method = strtolower($t->payment_method);
                if ($method === 'cash') {
                    $data[$key]['tunai'] += $t->final_amount;
                } elseif (in_array($method, ['card', 'debit', 'credit', 'transfer', 'qris'])) {
                    $data[$key]['card'] += $t->final_amount;
                } else {
                    $data[$key]['tunai'] += $t->final_amount;
                }
            }
        }

        // Sort by Date, then by KS
        usort($data, function($a, $b) {
            $dateCompare = strtotime($a['tanggal']) - strtotime($b['tanggal']);
            if ($dateCompare === 0) {
                return strcmp($a['ks'], $b['ks']);
            }
            return $dateCompare;
        });

        $period = $this->getPeriodString($filters);

        return view('print.reports.penjualan-kasir', [
            'data' => $data,
            'period' => $period
        ]);
    }

    private function printArsipTransaksi($filters)
    {
        $query = Transaction::query()->with(['branch', 'cashier']);
        $query = $this->applyDateFilters($query, $filters);
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();
        $period = $this->getPeriodString($filters);

        return view('print.reports.arsip-transaksi', [
            'transactions' => $transactions,
            'period' => $period
        ]);
    }

    private function printLaporanPenjualan($filters)
    {
        $query = Transaction::query()->where('is_voided', false)->with(['branch', 'cashier']);
        $query = $this->applyDateFilters($query, $filters);
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $transactions = $query->orderBy('transaction_date', 'asc')->get();
        $period = $this->getPeriodString($filters);

        $columns = ['Tanggal', 'No Transaksi', 'Kasir', 'Metode', 'Total', 'Diskon', 'Pendapatan'];
        $rows = [];
        foreach ($transactions as $t) {
            $rows[] = [
                \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i'),
                $t->local_transaction_id,
                $t->cashier ? $t->cashier->name : '-',
                strtoupper($t->payment_method),
                number_format($t->total_amount, 0, ',', '.'),
                number_format($t->discount_amount, 0, ',', '.'),
                number_format($t->final_amount, 0, ',', '.')
            ];
        }

        return view('print.reports.generic', ['title' => 'Laporan Penjualan', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanPersediaan($filters)
    {
        // Laporan Persediaan uses Stock model as per LaporanPersediaan.php
        $query = \App\Models\Stock::query()->with(['branch', 'product', 'product.category']);
        $query = $this->applyDateFilters($query, $filters, 'created_at');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $stocks = $query->orderBy('created_at', 'desc')->get();
        $period = $this->getPeriodString($filters);

        $columns = ['Cabang', 'Produk', 'Kategori', 'Qty Saat Ini'];
        $rows = [];
        foreach ($stocks as $s) {
            $rows[] = [
                $s->branch ? $s->branch->name : '-',
                $s->product ? $s->product->name : '-',
                ($s->product && $s->product->category) ? $s->product->category->name : '-',
                $s->quantity
            ];
        }

        return view('print.reports.generic', ['title' => 'Laporan Persediaan', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanShiftKasir($filters)
    {
        $query = \App\Models\Shift::query()->with(['branch', 'user', 'terminal']);
        $query = $this->applyDateFilters($query, $filters, 'start_time', 'start_time'); // LaporanShiftKasir uses start_time and end_time
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        $shifts = $query->orderBy('start_time', 'desc')->get();
        $period = $this->getPeriodString($filters, 'start_time');

        $columns = ['Mulai', 'Selesai', 'Kasir', 'Terminal', 'Status', 'Saldo Awal', 'Pendapatan', 'Kas Harapan', 'Aktual', 'Selisih'];
        $rows = [];
        foreach ($shifts as $s) {
            $pendapatan = ($s->total_cash_sales ?? 0) + ($s->total_card_sales ?? 0);
            $kas_harapan = ($s->starting_cash ?? 0) + ($s->total_cash_sales ?? 0);
            $rows[] = [
                \Carbon\Carbon::parse($s->start_time)->format('d M H:i'),
                $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('d M H:i') : '-',
                $s->user ? $s->user->name : '-',
                $s->terminal ? $s->terminal->name : '-',
                ucfirst($s->status),
                number_format($s->starting_cash, 0, ',', '.'),
                number_format($pendapatan, 0, ',', '.'),
                number_format($kas_harapan, 0, ',', '.'),
                number_format($s->actual_cash, 0, ',', '.'),
                number_format($s->difference, 0, ',', '.')
            ];
        }

        return view('print.reports.generic', ['title' => 'Laporan Shift Kasir', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanLabaRugi($filters)
    {
        $query = Transaction::query()->where('is_voided', false);
        $query = $this->applyDateFilters($query, $filters);
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $transactions = $query->orderBy('transaction_date', 'asc')->get();
        $period = $this->getPeriodString($filters);

        $columns = ['Tanggal', 'No Transaksi', 'Omset/Pendapatan', 'HPP (Modal)', 'Laba Kotor'];
        $rows = [];
        $t_omset = 0; $t_hpp = 0; $t_laba = 0;

        foreach ($transactions as $t) {
            $omset = $t->final_amount;
            $hpp = $t->total_amount * 0.7; // Fallback
            $laba = $omset - $hpp;

            $t_omset += $omset; $t_hpp += $hpp; $t_laba += $laba;

            $rows[] = [
                \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i'),
                $t->local_transaction_id,
                number_format($omset, 0, ',', '.'),
                number_format($hpp, 0, ',', '.'),
                number_format($laba, 0, ',', '.')
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '<strong>'.number_format($t_omset, 0, ',', '.').'</strong>', '<strong>'.number_format($t_hpp, 0, ',', '.').'</strong>', '<strong>'.number_format($t_laba, 0, ',', '.').'</strong>'];

        return view('print.reports.generic', ['title' => 'Laporan Laba Rugi (Estimasi)', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanBarangDijual($filters)
    {
        $query = \App\Models\TransactionItem::query()->whereHas('transaction', function($q) use ($filters) { 
            $q->where('is_voided', false);
            
            if (auth()->user()->branch_id !== null) {
                $q->where('branch_id', auth()->user()->branch_id);
            } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
                $q->where('branch_id', $filters['branch_id']['value']);
            }

            $q = $this->applyDateFilters($q, $filters, 'transaction_date', 'transaction_date');
        })->whereNotNull('product_id')->with(['product', 'transaction']);
        
        $items = $query->get();
        $period = $this->getPeriodString($filters);

        $columns = ['SKU', 'Produk', 'Qty Jual', 'Qty Retur', 'Harga Beli', 'Harga Jual', 'Total Beli', 'Total Jual'];
        $rows = [];
        
        $grouped = [];
        foreach($items as $i) {
            $product_id = $i->product_id;
            
            $cost_price = 0;
            $branch_id = $i->transaction ? $i->transaction->branch_id : null;
            if ($branch_id) {
                $stock = \App\Models\Stock::where('product_id', $i->product_id)->where('branch_id', $branch_id)->first();
                $cost_price = ($stock && $stock->cost_price > 0) ? $stock->cost_price : ($i->product ? $i->product->cost_price : 0);
            } else {
                $cost_price = $i->product ? $i->product->cost_price : 0;
            }

            if(!isset($grouped[$product_id])) {
                $grouped[$product_id] = [
                    'sku' => $i->product ? $i->product->sku : '-',
                    'name' => $i->product ? $i->product->name : '-',
                    'qty_terjual' => 0,
                    'qty_retur' => 0,
                    'harga_beli' => $cost_price,
                    'harga_jual' => $i->unit_price,
                    'total_beli' => 0,
                    'total_jual' => 0,
                ];
            }
            
            $subtotal = ($i->unit_price - $i->discount_per_item) * $i->quantity;
            
            if ($i->quantity < 0) {
                $grouped[$product_id]['qty_retur'] += abs($i->quantity);
            } else {
                $grouped[$product_id]['qty_terjual'] += $i->quantity;
            }
            
            $grouped[$product_id]['total_beli'] += ($cost_price * $i->quantity);
            $grouped[$product_id]['total_jual'] += $subtotal;
        }

        $t_terjual = 0; $t_retur = 0; $t_beli = 0; $t_jual = 0;
        
        usort($grouped, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        foreach($grouped as $g) {
            $t_terjual += $g['qty_terjual'];
            $t_retur += $g['qty_retur'];
            $t_beli += $g['total_beli'];
            $t_jual += $g['total_jual'];
            
            $rows[] = [
                $g['sku'],
                '<div style="max-width: 150px; word-wrap: break-word; white-space: normal;">' . $g['name'] . '</div>',
                $g['qty_terjual'],
                $g['qty_retur'],
                number_format($g['harga_beli'], 0, ',', '.'),
                number_format($g['harga_jual'], 0, ',', '.'),
                number_format($g['total_beli'], 0, ',', '.'),
                number_format($g['total_jual'], 0, ',', '.')
            ];
        }

        $summaryBox = [
            'Total Qty Jual' => $t_terjual,
            'Total Qty Retur' => $t_retur,
            'Total Nilai Beli (Net)' => 'Rp ' . number_format($t_beli, 0, ',', '.'),
            'Total Nilai Jual (Net)' => 'Rp ' . number_format($t_jual, 0, ',', '.'),
            'Estimasi Laba Kotor' => 'Rp ' . number_format($t_jual - $t_beli, 0, ',', '.')
        ];

        return view('print.reports.generic', [
            'title' => 'Laporan Barang Dijual', 
            'period' => $period, 
            'columns' => $columns, 
            'rows' => $rows,
            'summaryBox' => $summaryBox
        ]);
    }

    private function printLaporanJasaTerjual($filters)
    {
        $query = \App\Models\TransactionItem::query()->whereHas('transaction', function($q) use ($filters) { 
            $q->where('is_voided', false);
            
            if (auth()->user()->branch_id !== null) {
                $q->where('branch_id', auth()->user()->branch_id);
            } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
                $q->where('branch_id', $filters['branch_id']['value']);
            }

            $q = $this->applyDateFilters($q, $filters, 'transaction_date', 'transaction_date');
        })->whereNotNull('service_id')->with(['service', 'transaction']);
        
        $items = $query->get();
        $period = $this->getPeriodString($filters);

        $columns = ['Tanggal', 'Jasa', 'Kode Jasa', 'Qty Terjual', 'Harga Satuan', 'Total Penjualan'];
        $rows = [];
        $t_qty = 0; $t_penjualan = 0;
        foreach($items as $i) {
            $subtotal = ($i->unit_price - $i->discount_per_item) * $i->quantity;
            $t_qty += $i->quantity;
            $t_penjualan += $subtotal;
            $rows[] = [
                $i->transaction ? \Carbon\Carbon::parse($i->transaction->transaction_date)->format('d-m-Y') : '-',
                $i->service ? $i->service->name : '-',
                $i->service ? $i->service->code : '-',
                $i->quantity,
                number_format($i->unit_price, 0, ',', '.'),
                number_format($subtotal, 0, ',', '.')
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '<strong>'.$t_qty.'</strong>', '', '<strong>'.number_format($t_penjualan, 0, ',', '.').'</strong>'];

        return view('print.reports.generic', ['title' => 'Laporan Jasa Terjual', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanBarangDibeli($filters)
    {
        $query = \App\Models\GoodsReceiptItem::query()->whereHas('goodsReceipt', function($q) use ($filters) {
            $q = $this->applyDateFilters($q, $filters, 'receipt_date', 'receipt_date');
            if (isset($filters['supplier_id']['value']) && !empty($filters['supplier_id']['value'])) {
                $q->where('supplier_id', $filters['supplier_id']['value']);
            }
            if (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
                $q->where('branch_id', $filters['branch_id']['value']);
            }
        })->with(['product', 'goodsReceipt', 'goodsReceipt.supplier']);

        $items = $query->get();
        $period = $this->getPeriodString($filters, 'receipt_date');

        $columns = ['Tanggal', 'Supplier', 'Produk', 'Qty', 'Harga Beli', 'Total'];
        $rows = [];
        $t_qty = 0; $t_total = 0;
        foreach($items as $i) {
            $qty = $i->quantity_received ?? 0;
            $t_qty += $qty;
            $t_total += $i->subtotal;
            $rows[] = [
                $i->goodsReceipt ? \Carbon\Carbon::parse($i->goodsReceipt->receipt_date)->format('d-m-Y') : '-',
                ($i->goodsReceipt && $i->goodsReceipt->supplier) ? $i->goodsReceipt->supplier->name : '-',
                $i->product ? $i->product->name : '-',
                $qty,
                number_format($i->unit_price, 0, ',', '.'),
                number_format($i->subtotal, 0, ',', '.')
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '<strong>'.$t_qty.'</strong>', '', '<strong>'.number_format($t_total, 0, ',', '.').'</strong>'];

        return view('print.reports.generic', ['title' => 'Laporan Barang Dibeli', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }
}
