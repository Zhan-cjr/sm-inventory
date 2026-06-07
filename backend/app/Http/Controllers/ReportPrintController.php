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
            case 'rekap-total-stok':
                return $this->printRekapTotalStok($filters);
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
            case 'laporan-stok-opname-ringkas':
                return $this->printLaporanStokOpnameRingkas($filters);
            case 'laporan-stok-opname-detail':
                return $this->printLaporanStokOpnameDetail($filters);
            case 'pesanan-pembelian':
                return $this->printPesananPembelian($filters);
            case 'penerimaan-barang':
                return $this->printPenerimaanBarang($filters);
            case 'retur-pembelian':
                return $this->printReturPembelian($filters);
            case 'koreksi-stok':
                return $this->printKoreksiStok($filters);
            case 'stock-transfer':
                return $this->printStockTransfer($filters);
            case 'expense_list':
                return $this->printExpenseList($filters);
            case 'laporan_keuangan':
                return $this->printLaporanKeuangan($request);
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

        $applyClause = function($q, $period, $col, $from, $until) {
            if ($period === 'today') {
                return $q->whereDate($col, Carbon::today());
            } elseif ($period === 'yesterday') {
                return $q->whereDate($col, Carbon::yesterday());
            } elseif ($period === 'this_week') {
                return $q->whereBetween($col, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($period === 'last_week') {
                return $q->whereBetween($col, [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            } elseif ($period === 'this_month') {
                return $q->whereMonth($col, Carbon::now()->month)->whereYear($col, Carbon::now()->year);
            } elseif ($period === 'last_month') {
                return $q->whereMonth($col, Carbon::now()->subMonth()->month)->whereYear($col, Carbon::now()->subMonth()->year);
            } elseif ($period === 'custom' || $from || $until) {
                if ($from) $q->whereDate($col, '>=', $from);
                if ($until) $q->whereDate($col, '<=', $until);
                return $q;
            }
            return $q;
        };

        if (str_contains($dateColumn, '.')) {
            $parts = explode('.', $dateColumn);
            $relColumn = array_pop($parts);
            $relation = implode('.', $parts);
            return $query->whereHas($relation, function($q) use ($applyClause, $period, $relColumn, $from, $until) {
                return $applyClause($q, $period, $relColumn, $from, $until);
            });
        } else {
            return $applyClause($query, $period, $dateColumn, $from, $until);
        }
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
            ->with(['cashier', 'shift']);

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
        $banks = \App\Models\Bank::where('is_active', true)->orderBy('name')->get();

        // Agregasi per Tanggal, Kasir, dan Shift
        $data = [];
        foreach ($transactions as $t) {
            $date = \Carbon\Carbon::parse($t->transaction_date)->format('d-m-Y');
            $kasir = $t->cashier ? $t->cashier->name : 'Unk';
            $shift = $t->shift ? $t->shift->shift_name : 'Unk';
            $key = $date . '|' . $kasir . '|' . $shift;

            if (!isset($data[$key])) {
                $data[$key] = [
                    'tanggal' => $date,
                    'kasir' => $kasir,
                    'shift' => $shift,
                    'jml_nota' => 0,
                    'penjualan' => 0,
                    'tunai' => 0,
                    'voucher' => 0,
                    'diskon' => 0,
                    'retur' => 0,
                    'jual_netto' => 0,
                ];
                foreach ($banks as $bank) {
                    $data[$key]['bank_'.$bank->id] = 0;
                }
            }

            $data[$key]['jml_nota'] += 1;
            $isReturn = $t->transaction_type === 'RETURN';
            
            if ($isReturn) {
                // Untuk retur, gross penjualan tidak bertambah, dicatat sebagai pengurang di retur
                $data[$key]['retur'] += abs($t->final_amount);
                $data[$key]['jual_netto'] += $t->final_amount; // final_amount is negative
            } else {
                $data[$key]['penjualan'] += $t->total_amount;
                // Total diskon meliputi discount item, manual diskon total, dan promo
                $total_discount = ($t->discount_amount ?? 0) + ($t->manual_discount ?? 0) + ($t->promo_discount ?? 0);
                $data[$key]['diskon'] += $total_discount;
                $data[$key]['jual_netto'] += $t->final_amount;
            }

            // Memproses Pembayaran (mendukung multi payment dan single payment fallback)
            if (!empty($t->payment_details) && is_array($t->payment_details)) {
                foreach ($t->payment_details as $p) {
                    $pMethod = strtoupper($p['method'] ?? 'CASH');
                    $pAmt = floatval($p['amount'] ?? 0);
                    
                    if ($isReturn && $pAmt > 0) {
                        $pAmt = -$pAmt; // pastikan pengurang karena ini retur
                    }

                    if ($pMethod === 'CASH') {
                        $data[$key]['tunai'] += $pAmt;
                    } elseif ($pMethod === 'VOUCHER') {
                        $data[$key]['voucher'] += $pAmt;
                    } elseif ($pMethod === 'CARD') {
                        $bId = $p['bankId'] ?? null;
                        if ($bId && isset($data[$key]['bank_'.$bId])) {
                            $data[$key]['bank_'.$bId] += $pAmt;
                        } else {
                            $data[$key]['tunai'] += $pAmt; // Fallback jika bank tak dikenal / tak aktif
                        }
                    } else {
                        $data[$key]['tunai'] += $pAmt;
                    }
                }
            } else {
                $method = strtoupper($t->payment_method);
                $amt = $t->final_amount; // final_amount includes negative sign for return

                if ($method === 'CASH') {
                    $data[$key]['tunai'] += $amt;
                } elseif ($method === 'VOUCHER') {
                    $data[$key]['voucher'] += $amt;
                } elseif (in_array($method, ['CARD', 'DEBIT', 'CREDIT', 'TRANSFER', 'QRIS'])) {
                    $bId = $t->bank_id;
                    if ($bId && isset($data[$key]['bank_'.$bId])) {
                        $data[$key]['bank_'.$bId] += $amt;
                    } else {
                        $data[$key]['tunai'] += $amt;
                    }
                } else {
                    $data[$key]['tunai'] += $amt;
                }
            }
        }

        // Sort by Date, then by Kasir
        usort($data, function($a, $b) {
            $dateCompare = strtotime($a['tanggal']) - strtotime($b['tanggal']);
            if ($dateCompare === 0) {
                return strcmp($a['kasir'], $b['kasir']);
            }
            return $dateCompare;
        });

        $period = $this->getPeriodString($filters);

        return view('print.reports.penjualan-kasir', [
            'data' => $data,
            'period' => $period,
            'banks' => $banks
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

        $columns = ['Cabang', 'Produk', 'Kategori', 'Sisa Stok', 'Harga Pokok (+PPN)', 'Valuasi Stok'];
        $rows = [];
        foreach ($stocks as $s) {
            $costPriceTax = $s->cost_price_tax > 0 ? $s->cost_price_tax : ($s->product->cost_price_tax ?? $s->product->cost_price ?? 0);
            $rows[] = [
                $s->branch ? $s->branch->name : 'Pusat / Global',
                $s->product ? $s->product->name : '-',
                ($s->product && $s->product->category) ? $s->product->category->name : '-',
                $s->quantity_on_hand,
                number_format($costPriceTax, 0, ',', '.'),
                number_format($s->quantity_on_hand * $costPriceTax, 0, ',', '.')
            ];
        }

        return view('print.reports.generic', ['title' => 'Laporan Persediaan', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printRekapTotalStok($filters)
    {
        $query = \App\Models\Stock::query()->with(['branch', 'product', 'product.category']);
        // Apply branch filter if any
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        // Category filter
        if (isset($filters['category_id']['value']) && !empty($filters['category_id']['value'])) {
            $query->whereHas('product', function($q) use ($filters) {
                $q->where('category_id', $filters['category_id']['value']);
            });
        }

        $stocks = $query->get();
        
        $data = [];
        $totalValuation = 0;
        
        foreach ($stocks as $stock) {
            $catName = $stock->product && $stock->product->category ? $stock->product->category->name : 'Uncategorized';
            $subCatName = $stock->product && $stock->product->sub_category ? $stock->product->sub_category : 'General';
            $key = $catName . '|' . $subCatName;
            
            if (!isset($data[$key])) {
                $data[$key] = [
                    'kategori' => $catName,
                    'sub_kategori' => $subCatName,
                    'total_qty' => 0,
                    'total_valuation' => 0,
                ];
            }
            
            $costPriceTax = $stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->product->cost_price_tax ?? $stock->product->cost_price ?? 0);
            $valuation = $stock->quantity_on_hand * $costPriceTax;
            
            $data[$key]['total_qty'] += $stock->quantity_on_hand;
            $data[$key]['total_valuation'] += $valuation;
            $totalValuation += $valuation;
        }

        // Hitung rata-rata
        foreach ($data as $key => $row) {
            if ($row['total_qty'] > 0) {
                $data[$key]['avg_price'] = $row['total_valuation'] / $row['total_qty'];
            } else {
                $data[$key]['avg_price'] = 0;
            }
        }

        // Sort and group by category
        usort($data, function($a, $b) {
            $cmp = strcmp($a['kategori'], $b['kategori']);
            if ($cmp === 0) {
                return strcmp($a['sub_kategori'], $b['sub_kategori']);
            }
            return $cmp;
        });
        
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row['kategori']][] = $row;
        }

        $period = date('d-m-Y H:i'); // Current time for recap

        return view('print.reports.rekap-stok', [
            'groupedData' => $groupedData,
            'totalValuation' => $totalValuation,
            'period' => $period,
            'title' => 'Rekap Total Stok'
        ]);
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

    private function printLaporanStokOpnameRingkas($filters)
    {
        $query = \App\Models\StockOpnameSession::query()->with(['branch', 'creator', 'approver']);
        $query = $this->applyDateFilters($query, $filters, 'opname_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        if (isset($filters['status']['value']) && !empty($filters['status']['value'])) {
            $query->where('status', $filters['status']['value']);
        }
        
        $sessions = $query->orderBy('created_at', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['No Sesi', 'Cabang', 'Tgl Opname', 'Status', 'Progress Hitung', 'Item Selisih', 'Nominal (+)', 'Nominal (-)'];
        $rows = [];
        
        $grandTotalPlus = 0;
        $grandTotalMinus = 0;
        $grandTotalItems = 0;
        
        foreach ($sessions as $s) {
            $c1 = $s->count1_progress;
            $c2 = $s->count2_progress;
            
            $summary = $s->getProductSummary();
            $grandTotalItems += count($summary);
            
            $totalNominalPlus = 0;
            $totalNominalMinus = 0;
            $stocks = [];
            if ($s->branch_id) {
                $productIds = $summary->pluck('product_id')->filter()->toArray();
                if (!empty($productIds)) {
                    $stocks = \App\Models\Stock::where('branch_id', $s->branch_id)
                                ->whereIn('product_id', $productIds)
                                ->with('product')
                                ->get()
                                ->keyBy('product_id');
                }
            }

            foreach($summary as $prodSummary) {
                $diff = $prodSummary['final_disc'];
                if ($diff == 0) continue;
                
                $pid = $prodSummary['product_id'];
                $price = 0;
                if ($s->branch_id && isset($stocks[$pid])) {
                    $st = $stocks[$pid];
                    $price = $st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->product->cost_price_tax ?? $st->product->cost_price ?? 0);
                } else {
                    $p = \App\Models\Product::find($pid);
                    if ($p) {
                        $price = $p->cost_price_tax ?? $p->cost_price ?? 0;
                    }
                }
                if ($diff > 0) {
                    $totalNominalPlus += ($diff * $price);
                } else {
                    $totalNominalMinus += (abs($diff) * $price);
                }
            }
            
            $grandTotalPlus += $totalNominalPlus;
            $grandTotalMinus += $totalNominalMinus;
            
            $rows[] = [
                $s->session_number,
                $s->branch ? $s->branch->name : 'Pusat / Global',
                \Carbon\Carbon::parse($s->opname_date)->format('d M Y'),
                $s->status,
                "H1: {$c1['done']}/{$c1['total']} | H2: {$c2['done']}/{$c2['total']}",
                $s->discrepancy_count,
                number_format($totalNominalPlus, 0, ',', '.'),
                number_format($totalNominalMinus, 0, ',', '.')
            ];
        }

        $summaryBox = [
            'Total Sesi Opname' => count($sessions) . ' Sesi',
            'Total Item Diopname' => $grandTotalItems . ' Item',
            'Total Nominal (+)' => 'Rp ' . number_format($grandTotalPlus, 0, ',', '.'),
            'Total Nominal (-)' => 'Rp ' . number_format($grandTotalMinus, 0, ',', '.')
        ];

        return view('print.reports.generic', [
            'title' => 'Laporan Stok Opname (Ringkas)', 
            'period' => $period, 
            'columns' => $columns, 
            'rows' => $rows,
            'summaryBox' => $summaryBox
        ]);
    }

    private function printLaporanStokOpnameDetail($filters)
    {
        $query = \App\Models\StockOpnameItem::query()->with(['session.branch', 'product', 'rackSession.rack']);
        $query = $this->applyDateFilters($query, $filters, 'session.opname_date', 'date_filter');
        
        // Filter by branch
        if (auth()->user()->branch_id !== null) {
            $query->whereHas('session', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->whereHas('session', fn($q) => $q->where('branch_id', $filters['branch_id']['value']));
        }
        
        if (isset($filters['status']['value']) && !empty($filters['status']['value'])) {
            $query->where('status', $filters['status']['value']);
        }
        
        $items = $query->orderBy('created_at', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['No Sesi', 'Cabang', 'Rak', 'SKU', 'Barang', 'Stok Sistem', 'Hitung 1', 'Hitung 2', 'Akhir', 'S.Plus', 'Nom.Plus', 'S.Minus', 'Nom.Minus'];
        $rows = [];
        
        $grandTotalPlus = 0;
        $grandTotalMinus = 0;
        
        // Eager load stocks if any branch opname exists in items
        $branchIds = $items->pluck('session.branch_id')->filter()->unique()->toArray();
        $productIds = $items->pluck('product_id')->filter()->unique()->toArray();
        $stocks = [];
        if (!empty($branchIds) && !empty($productIds)) {
            $stocksList = \App\Models\Stock::whereIn('branch_id', $branchIds)
                        ->whereIn('product_id', $productIds)
                        ->with('product')
                        ->get();
            foreach ($stocksList as $st) {
                $stocks[$st->branch_id . '-' . $st->product_id] = $st;
            }
        }
        
        foreach ($items as $i) {
            $selisih = 0;
            if ($i->final_quantity !== null) {
                $selisih = $i->final_quantity - $i->system_quantity;
            } elseif ($i->count2_quantity !== null) {
                $selisih = $i->count2_quantity - $i->system_quantity;
            } elseif ($i->count1_quantity !== null) {
                $selisih = $i->count1_quantity - $i->system_quantity;
            }
            
            $nominalPlus = 0;
            $nominalMinus = 0;
            if ($selisih != 0) {
                $price = 0;
                if ($i->session && $i->session->branch_id) {
                    $key = $i->session->branch_id . '-' . $i->product_id;
                    if (isset($stocks[$key])) {
                        $st = $stocks[$key];
                        $price = $st->cost_price_tax > 0 ? $st->cost_price_tax : ($st->product->cost_price_tax ?? $st->product->cost_price ?? 0);
                    } else {
                        $price = $i->product->cost_price_tax ?? $i->product->cost_price ?? 0;
                    }
                } else {
                    $price = $i->product->cost_price_tax ?? $i->product->cost_price ?? 0;
                }
                
                if ($selisih > 0) {
                    $nominalPlus = $selisih * $price;
                } else {
                    $nominalMinus = abs($selisih) * $price;
                }
            }
            
            $grandTotalPlus += $nominalPlus;
            $grandTotalMinus += $nominalMinus;
            
            $rows[] = [
                $i->session ? $i->session->session_number : '-',
                ($i->session && $i->session->branch) ? $i->session->branch->name : 'Pusat',
                ($i->rackSession && $i->rackSession->rack) ? $i->rackSession->rack->rack_code : '-',
                $i->product ? $i->product->sku : '-',
                $i->product ? $i->product->name : '-',
                (float)$i->system_quantity,
                $i->count1_quantity !== null ? (float)$i->count1_quantity : '-',
                $i->count2_quantity !== null ? (float)$i->count2_quantity : '-',
                $i->final_quantity !== null ? (float)$i->final_quantity : '-',
                $selisih > 0 ? $selisih : '-',
                $nominalPlus > 0 ? number_format($nominalPlus, 0, ',', '.') : '-',
                $selisih < 0 ? abs($selisih) : '-',
                $nominalMinus > 0 ? number_format($nominalMinus, 0, ',', '.') : '-'
            ];
        }

        $summaryBox = [
            'Total Item Teropname' => count($items) . ' Item',
            'Total Nominal (+)' => 'Rp ' . number_format($grandTotalPlus, 0, ',', '.'),
            'Total Nominal (-)' => 'Rp ' . number_format($grandTotalMinus, 0, ',', '.')
        ];

        return view('print.reports.generic', [
            'title' => 'Laporan Stok Opname (Detail)', 
            'period' => $period, 
            'columns' => $columns, 
            'rows' => $rows,
            'summaryBox' => $summaryBox
        ]);
    }

    private function printPesananPembelian($filters)
    {
        $query = \App\Models\PurchaseOrder::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'po_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $orders = $query->orderBy('po_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tanggal PO', 'No. PO', 'Supplier', 'Cabang', 'Total', 'Status'];
        $rows = [];
        $t_total = 0;
        foreach ($orders as $o) {
            $t_total += $o->total_amount;
            $rows[] = [
                \Carbon\Carbon::parse($o->po_date)->format('d-m-Y'),
                $o->po_number,
                $o->supplier ? $o->supplier->name : '-',
                $o->branch ? $o->branch->name : 'Pusat',
                number_format($o->total_amount, 0, ',', '.'),
                strtoupper($o->status)
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '<strong>Rp ' . number_format($t_total, 0, ',', '.') . '</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Pesanan Pembelian', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printPenerimaanBarang($filters)
    {
        $query = \App\Models\GoodsReceipt::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'receipt_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $receipts = $query->orderBy('receipt_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Terima', 'No. Terima', 'No. PO', 'Supplier', 'Cabang', 'Total', 'Status'];
        $rows = [];
        $t_total = 0;
        foreach ($receipts as $r) {
            $t_total += $r->total_amount;
            $rows[] = [
                \Carbon\Carbon::parse($r->receipt_date)->format('d-m-Y'),
                $r->receipt_number,
                $r->purchaseOrder ? $r->purchaseOrder->po_number : '-',
                $r->supplier ? $r->supplier->name : '-',
                $r->branch ? $r->branch->name : 'Pusat',
                number_format($r->total_amount, 0, ',', '.'),
                strtoupper($r->status)
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '', '<strong>Rp ' . number_format($t_total, 0, ',', '.') . '</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Penerimaan Barang', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printReturPembelian($filters)
    {
        $query = \App\Models\PurchaseReturn::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'return_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $returns = $query->orderBy('return_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Retur', 'No. Retur', 'Supplier', 'Cabang', 'Total', 'Status'];
        $rows = [];
        $t_total = 0;
        foreach ($returns as $r) {
            $t_total += $r->total_amount;
            $rows[] = [
                \Carbon\Carbon::parse($r->return_date)->format('d-m-Y'),
                $r->return_number,
                $r->supplier ? $r->supplier->name : '-',
                $r->branch ? $r->branch->name : 'Pusat',
                number_format($r->total_amount, 0, ',', '.'),
                strtoupper($r->status)
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '<strong>Rp ' . number_format($t_total, 0, ',', '.') . '</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Retur Pembelian', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printKoreksiStok($filters)
    {
        $query = \App\Models\StockAdjustment::query()->with(['branch', 'recorder', 'adjustmentReason']);
        $query = $this->applyDateFilters($query, $filters, 'adjustment_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $adjustments = $query->orderBy('adjustment_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Koreksi', 'Cabang', 'Sifat', 'Nominal (+)', 'Nominal (-)', 'Dibuat Oleh', 'Status'];
        $rows = [];
        $t_plus = 0;
        $t_minus = 0;
        foreach ($adjustments as $a) {
            $type = $a->adjustmentReason ? strtoupper($a->adjustmentReason->type) : 'PLUS';
            $sifat = $a->adjustmentReason ? $a->adjustmentReason->name : '-';
            
            $nominal_plus = ($type === 'PLUS') ? $a->total_value : 0;
            $nominal_minus = ($type === 'MINUS') ? $a->total_value : 0;
            
            $t_plus += $nominal_plus;
            $t_minus += $nominal_minus;
            
            $rows[] = [
                \Carbon\Carbon::parse($a->adjustment_date)->format('d-m-Y'),
                $a->branch ? $a->branch->name : 'Pusat',
                $sifat,
                $nominal_plus > 0 ? number_format($nominal_plus, 0, ',', '.') : '-',
                $nominal_minus > 0 ? number_format($nominal_minus, 0, ',', '.') : '-',
                $a->recorder ? $a->recorder->name : '-',
                strtoupper($a->status)
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '<strong>Rp ' . number_format($t_plus, 0, ',', '.') . '</strong>', '<strong>Rp ' . number_format($t_minus, 0, ',', '.') . '</strong>', '', ''];

        return view('print.reports.generic', ['title' => 'Daftar Koreksi Stok', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printStockTransfer($filters)
    {
        $query = \App\Models\StockTransfer::query()->with(['fromBranch', 'toBranch']);
        $query = $this->applyDateFilters($query, $filters, 'transfer_date', 'date_filter');
        
        if (auth()->user()->branch_id !== null) {
            $query->where(function($q) {
                $q->where('from_branch_id', auth()->user()->branch_id)
                  ->orWhere('to_branch_id', auth()->user()->branch_id);
            });
        } elseif (isset($filters['from_branch_id']['value']) && !empty($filters['from_branch_id']['value'])) {
            $query->where('from_branch_id', $filters['from_branch_id']['value']);
        }
        
        $transfers = $query->orderBy('transfer_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Transfer', 'No. Transfer', 'Cabang Asal', 'Cabang Tujuan', 'Nominal', 'Status'];
        $rows = [];
        $t_total = 0;
        foreach ($transfers as $t) {
            $t_total += $t->total_amount;
            $rows[] = [
                \Carbon\Carbon::parse($t->transfer_date)->format('d-m-Y'),
                $t->reference_number,
                $t->fromBranch ? $t->fromBranch->name : 'Pusat',
                $t->toBranch ? $t->toBranch->name : 'Pusat',
                number_format($t->total_amount, 0, ',', '.'),
                strtoupper($t->status)
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '<strong>Rp ' . number_format($t_total, 0, ',', '.') . '</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Stock Transfer', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printExpenseList($filters)
    {
        $query = \App\Models\Expense::query()->with(['branch', 'expenseAccount', 'paymentAccount', 'creator']);
        $query = $this->applyDateFilters($query, $filters, 'expense_date', 'expense_date');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('created_at', 'desc')->get();
        $period = $this->getPeriodString($filters, 'expense_date');

        $columns = ['Tgl', 'No. Ref', 'Cabang', 'Akun Pengeluaran (Debit)', 'Sumber Dana (Kredit)', 'Nominal', 'Keterangan'];
        $rows = [];
        $t_total = 0;
        foreach ($expenses as $e) {
            $t_total += $e->amount;
            $rows[] = [
                \Carbon\Carbon::parse($e->expense_date)->format('d-m-Y'),
                $e->reference_number,
                $e->branch ? $e->branch->name : 'Pusat',
                $e->expenseAccount ? $e->expenseAccount->name : '-',
                $e->paymentAccount ? $e->paymentAccount->name : '-',
                number_format($e->amount, 0, ',', '.'),
                $e->description ?: '-'
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '', '<strong>Rp ' . number_format($t_total, 0, ',', '.') . '</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Pengeluaran (Expenses)', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanKeuangan(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        
        $organizationId = auth()->user()->organization_id ?? \App\Models\Organization::first()?->id;

        if (!$organizationId) {
            abort(404, 'Organisasi tidak ditemukan');
        }

        $accounts = \App\Models\Account::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $accountBalances = [];
        $netProfit = 0;
        $retainedEarnings = 0;

        foreach ($accounts as $account) {
            // 1. Current Period (untuk Laba Rugi)
            $linesCurrent = \App\Models\JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate, $branchId) {
                    $query->where('status', 'posted');
                    if ($startDate) {
                        $query->whereDate('entry_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->whereDate('entry_date', '<=', $endDate);
                    }
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    }
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debitCurrent = $linesCurrent->total_debit ?? 0;
            $creditCurrent = $linesCurrent->total_credit ?? 0;

            // 2. Prior Period (untuk Laba Ditahan dari Laba Rugi masa lalu)
            if (in_array($account->type, ['revenue', 'expense']) && $startDate) {
                $linesPrior = \App\Models\JournalEntryLine::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($query) use ($startDate, $branchId) {
                        $query->where('status', 'posted');
                        $query->whereDate('entry_date', '<', $startDate);
                        if ($branchId) {
                            $query->where('branch_id', $branchId);
                        }
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();
                $debitPrior = $linesPrior->total_debit ?? 0;
                $creditPrior = $linesPrior->total_credit ?? 0;

                if ($account->type === 'revenue') {
                    $retainedEarnings += ($creditPrior - $debitPrior);
                } else { // expense
                    $retainedEarnings -= ($debitPrior - $creditPrior);
                }
            }

            // 3. Total Balance (untuk Neraca)
            $linesTotal = \App\Models\JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($endDate, $branchId) {
                    $query->where('status', 'posted');
                    if ($endDate) {
                        $query->whereDate('entry_date', '<=', $endDate);
                    }
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    }
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debitTotal = $linesTotal->total_debit ?? 0;
            $creditTotal = $linesTotal->total_credit ?? 0;

            $balance = 0;
            if (in_array($account->type, ['asset', 'expense'])) {
                if ($account->type === 'asset') {
                    $balance = $debitTotal - $creditTotal; // Neraca mengambil saldo akumulasi total
                } else {
                    $balance = $debitCurrent - $creditCurrent; // Laba Rugi mengambil saldo periode ini
                }
            } else {
                if (in_array($account->type, ['liability', 'equity'])) {
                    $balance = $creditTotal - $debitTotal; // Neraca mengambil saldo akumulasi total
                } else {
                    $balance = $creditCurrent - $debitCurrent; // Laba Rugi mengambil saldo periode ini
                }
            }

            if ($account->type === 'revenue') {
                $netProfit += $balance;
            } elseif ($account->type === 'expense') {
                $netProfit -= $balance;
            }

            $accountBalances[$account->id] = [
                'account' => $account,
                'debit' => $debitCurrent,
                'credit' => $creditCurrent,
                'balance' => $balance
            ];
        }

        $assets = array_filter($accountBalances, fn($item) => $item['account']->type === 'asset');
        $liabilities = array_filter($accountBalances, fn($item) => $item['account']->type === 'liability');
        $equities = array_filter($accountBalances, fn($item) => $item['account']->type === 'equity');
        $revenues = array_filter($accountBalances, fn($item) => $item['account']->type === 'revenue');
        $expenses = array_filter($accountBalances, fn($item) => $item['account']->type === 'expense');

        $period = ($startDate && $endDate) ? \Carbon\Carbon::parse($startDate)->format('d M Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Semua Waktu';
        $branchName = 'Semua Cabang (Global)';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) $branchName = $branch->name;
        }

        return view('print.reports.laporan-keuangan', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'netProfit' => $netProfit,
            'retainedEarnings' => $retainedEarnings,
            'period' => $period,
            'branchName' => $branchName,
            'title' => 'Laporan Keuangan'
        ]);
    }
}
