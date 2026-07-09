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
                return $this->printExpenseList($request);
            case 'laporan_keuangan':
                return $this->printLaporanKeuangan($request);
            case 'jurnal_umum':
                return $this->printJurnalUmum($request);
            case 'kontrabon':
                return $this->printKontrabon($filters);
            case 'pembayaran-hutang':
                return $this->printPembayaranHutang($filters);
            case 'kontrabon-nota':
                return $this->printKontrabonNota($request);
            case 'promo-sellout':
                return $this->printPromoSellout($request);
            case 'supplier-deductions':
                return $this->printSupplierDeductions($filters);
            case 'rekapitulasi_transaksi':
                return $this->printRekapitulasiTransaksi($request);
            case 'laporan-hpp':
                return $this->printLaporanHpp($request);
            case 'laporan-rekap-tipe-suplier':
                return $this->printLaporanRekapTipeSuplier($filters);
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

    private function printRekapitulasiTransaksi(Request $request)
    {
        $filters = $request->input('tableFilters', []);
        
        $branchQuery = \App\Models\Branch::query();
        if (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $branchQuery->where('id', $filters['branch_id']['value']);
        }
        $branches = $branchQuery->get();

        $startDateTime = Carbon::now()->startOfMonth();
        $endDateTime = Carbon::now()->endOfMonth();

        $dateData = $filters['transaction_date'] ?? null;
        if ($dateData) {
            $period = $dateData['period'] ?? null;
            if ($period === 'today') {
                $startDateTime = Carbon::today();
                $endDateTime = Carbon::today()->endOfDay();
            } elseif ($period === 'yesterday') {
                $startDateTime = Carbon::yesterday();
                $endDateTime = Carbon::yesterday()->endOfDay();
            } elseif ($period === 'this_week') {
                $startDateTime = Carbon::now()->startOfWeek();
                $endDateTime = Carbon::now()->endOfWeek();
            } elseif ($period === 'last_week') {
                $startDateTime = Carbon::now()->subWeek()->startOfWeek();
                $endDateTime = Carbon::now()->subWeek()->endOfWeek();
            } elseif ($period === 'this_month') {
                $startDateTime = Carbon::now()->startOfMonth();
                $endDateTime = Carbon::now()->endOfMonth();
            } elseif ($period === 'last_month') {
                $startDateTime = Carbon::now()->subMonth()->startOfMonth();
                $endDateTime = Carbon::now()->subMonth()->endOfMonth();
            } elseif ($period === 'custom') {
                if (!empty($dateData['created_from'])) $startDateTime = Carbon::parse($dateData['created_from'])->startOfDay();
                if (!empty($dateData['created_until'])) $endDateTime = Carbon::parse($dateData['created_until'])->endOfDay();
            }
        }
        $branches = $branchQuery->get();

        $returReasonIds = \App\Models\AdjustmentReason::where('name', 'like', '%retur%')->pluck('id');

        $resultBranches = [];
        $grandTotals = [
            'penerimaan' => 0,
            'retur_beli' => 0,
            'koreksi_retur' => 0,
            'penjualan' => 0,
            'retur_jual' => 0,
            'pengeluaran' => 0,
        ];

        foreach ($branches as $branch) {
            $bId = $branch->id;

            $penerimaan = \App\Models\GoodsReceipt::whereIn('status', ['RECEIVED', 'COMPLETED', 'completed', 'approved'])
                ->where('branch_id', $bId)
                ->whereBetween('receipt_date', [$startDateTime, $endDateTime])
                ->sum('total_amount');

            $returBeli = \App\Models\PurchaseReturn::whereIn('status', ['completed', 'approved'])
                ->where('branch_id', $bId)
                ->whereBetween('return_date', [$startDateTime, $endDateTime])
                ->sum('total_amount');

            $koreksiReturIds = \App\Models\StockAdjustment::whereIn('status', ['COMPLETED', 'completed', 'APPROVED', 'approved'])
                ->whereIn('adjustment_reason_id', $returReasonIds)
                ->where('branch_id', $bId)
                ->whereBetween('adjustment_date', [$startDateTime, $endDateTime])
                ->pluck('id');

            $koreksiRetur = 0;
            if (!$koreksiReturIds->isEmpty()) {
                $koreksiRetur = \App\Models\StockAdjustmentItem::whereIn('stock_adjustment_id', $koreksiReturIds)
                    ->sum(\Illuminate\Support\Facades\DB::raw('ABS(adjustment_quantity * unit_cost)'));
            }

            $penjualan = Transaction::where('transaction_type', 'SALES')
                ->where('is_voided', false)
                ->where('branch_id', $bId)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                ->sum('final_amount');

            $returJual = abs((float) Transaction::where('transaction_type', 'RETURN')
                ->where('is_voided', false)
                ->where('branch_id', $bId)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                ->sum('final_amount'));

            $pengeluaran = \App\Models\Expense::where('branch_id', $bId)
                ->whereBetween('expense_date', [$startDateTime, $endDateTime])
                ->sum('amount');

            $resultBranches[] = [
                'branch_name' => $branch->name,
                'penerimaan' => $penerimaan,
                'retur_beli' => $returBeli,
                'koreksi_retur' => $koreksiRetur,
                'penjualan' => $penjualan,
                'retur_jual' => $returJual,
                'pengeluaran' => $pengeluaran,
            ];

            $grandTotals['penerimaan'] += $penerimaan;
            $grandTotals['retur_beli'] += $returBeli;
            $grandTotals['koreksi_retur'] += $koreksiRetur;
            $grandTotals['penjualan'] += $penjualan;
            $grandTotals['retur_jual'] += $returJual;
            $grandTotals['pengeluaran'] += $pengeluaran;
        }

        $period = $this->getPeriodString($filters, 'transaction_date');

        return view('print.reports.rekapitulasi-transaksi', [
            'branches' => $resultBranches,
            'totals' => $grandTotals,
            'period' => $period,
            'title' => 'Rekapitulasi Transaksi'
        ]);
    }

    private function printPenjualanKasir($filters)
    {
        $query = Transaction::query()
            ->where('is_voided', false)
            ->with(['cashier', 'shift', 'items']);

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
                    'point' => 0,
                    'diskon' => 0,
                    'retur' => 0,
                    'jual_netto' => 0,
                ];
                foreach ($banks as $bank) {
                    $data[$key]['bank_'.$bank->id] = 0;
                }
            }

            $data[$key]['jml_nota'] += 1;
            $gross_sales = 0;
            $gross_returns = 0;
            
            foreach ($t->items as $item) {
                if ($item->quantity > 0) {
                    $gross_sales += ($item->quantity * $item->unit_price);
                } else {
                    $gross_returns += (abs($item->quantity) * $item->unit_price);
                }
            }
            
            $data[$key]['penjualan'] += $gross_sales;
            $data[$key]['retur'] += $gross_returns;
            
            $total_discount = ($t->discount_amount ?? 0) + ($t->manual_discount ?? 0) + ($t->promo_discount ?? 0);
            $data[$key]['diskon'] += $total_discount;
            $data[$key]['jual_netto'] += $t->final_amount;
            
            $isReturn = $t->final_amount < 0;

            // Memproses Pembayaran (mendukung multi payment dan single payment fallback)
            $details = $t->payment_details;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }

            if (!empty($details) && is_array($details)) {
                $cashAmt = 0.0;
                $hasCash = false;
                foreach ($details as $p) {
                    $pMethod = strtoupper($p['method'] ?? 'CASH');
                    $pAmt = floatval($p['amount'] ?? 0);
                    
                    if ($isReturn && $pAmt > 0) {
                        $pAmt = -$pAmt; // pastikan pengurang karena ini retur
                    }

                    if ($pMethod === 'CASH') {
                        $cashAmt += $pAmt;
                        $hasCash = true;
                    } elseif ($pMethod === 'VOUCHER') {
                        $data[$key]['voucher'] += $pAmt;
                    } elseif ($pMethod === 'POINT') {
                        $data[$key]['point'] += $pAmt;
                    } elseif ($pMethod === 'CARD') {
                        $bId = $p['bankId'] ?? null;
                        if ($bId && isset($data[$key]['bank_'.$bId])) {
                            $data[$key]['bank_'.$bId] += $pAmt;
                        } else {
                            $cashAmt += $pAmt; // Fallback jika bank tak dikenal / tak aktif
                            $hasCash = true;
                        }
                    } else {
                        $cashAmt += $pAmt;
                        $hasCash = true;
                    }
                }
                if ($hasCash) {
                    $change = floatval($t->change_amount ?? 0);
                    if ($isReturn) {
                        $change = -$change;
                    }
                    $netCash = $cashAmt - $change;
                    $data[$key]['tunai'] += ($isReturn ? $netCash : max(0.0, $netCash));
                }
            } else {
                $method = strtoupper($t->payment_method);
                $amt = $t->final_amount; // final_amount includes negative sign for return

                if ($method === 'CASH') {
                    $data[$key]['tunai'] += $amt;
                } elseif ($method === 'VOUCHER') {
                    $data[$key]['voucher'] += $amt;
                } elseif ($method === 'POINT') {
                    $data[$key]['point'] += $amt;
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
            $pointPayment = 0.0;
            if (!empty($t->payment_details)) {
                $details = $t->payment_details;
                if (is_string($details)) $details = json_decode($details, true);
                if (is_array($details)) {
                    $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                }
            } elseif (strtoupper($t->payment_method) === 'POINT') {
                $pointPayment = (float) $t->final_amount;
            }
            $netRevenue = (float) $t->final_amount - $pointPayment;

            $rows[] = [
                \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i'),
                $t->local_transaction_id,
                $t->cashier ? $t->cashier->name : '-',
                strtoupper($t->payment_method),
                number_format($t->total_amount, 0, ',', '.'),
                number_format($t->discount_amount, 0, ',', '.'),
                number_format($netRevenue, 0, ',', '.')
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
        $subquery = \Illuminate\Support\Facades\DB::table('transactions as t')
            ->select([
                't.id',
                't.local_transaction_id',
                't.transaction_date',
                't.branch_id',
                't.final_amount',
                't.payment_method',
                't.payment_details',
                \Illuminate\Support\Facades\DB::raw("'OFFLINE' as transaction_source"),
                \Illuminate\Support\Facades\DB::raw("COALESCE((
                    SELECT SUM(
                        COALESCE(
                            (SELECT SUM(sbd.quantity * sb.cost_price) 
                             FROM stock_batch_deductions sbd 
                             JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                             WHERE sbd.transaction_item_id = ti.id),
                            ti.quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0)
                        )
                    )
                    FROM transaction_items ti
                    JOIN products p ON ti.product_id = p.id
                    LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
                    WHERE ti.transaction_id = t.id
                ), 0) as raw_cogs")
            ])
            ->where('t.is_voided', false)
            ->unionAll(
                \Illuminate\Support\Facades\DB::table('ecommerce_orders as eo')
                    ->select([
                        'eo.id',
                        'eo.id as local_transaction_id',
                        'eo.created_at as transaction_date',
                        'eo.branch_id',
                        'eo.total_amount as final_amount',
                        'eo.payment_method',
                        \Illuminate\Support\Facades\DB::raw("NULL as payment_details"),
                        \Illuminate\Support\Facades\DB::raw("'ONLINE' as transaction_source"),
                        \Illuminate\Support\Facades\DB::raw("COALESCE((
                            SELECT SUM(
                                COALESCE(
                                    (SELECT SUM(sbd.quantity * sb.cost_price) 
                                     FROM stock_batch_deductions sbd 
                                     JOIN stock_batches sb ON sbd.stock_batch_id = sb.id 
                                     WHERE sbd.ecommerce_order_item_id = ei.id),
                                    ei.quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0)
                                )
                            )
                            FROM ecommerce_order_items ei
                            JOIN products p ON ei.product_id = p.id
                            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = eo.branch_id
                            WHERE ei.ecommerce_order_id = eo.id
                        ), 0) as raw_cogs")
                    ])
                    ->where('eo.status', 'COMPLETED')
            );

        $query = Transaction::query()->fromSub($subquery, 'transactions');
        $query = $this->applyDateFilters($query, $filters, 'transaction_date');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        if (isset($filters['transaction_source']['value']) && !empty($filters['transaction_source']['value'])) {
            $query->where('transaction_source', $filters['transaction_source']['value']);
        }
        
        $transactions = $query->orderBy('transaction_date', 'asc')->get();
        $period = $this->getPeriodString($filters);

        $columns = ['Tanggal', 'No Transaksi', 'Sumber', 'Omset/Pendapatan', 'HPP (Modal)', 'Laba Kotor'];
        $rows = [];
        $t_omset = 0; $t_hpp = 0; $t_laba = 0;

        foreach ($transactions as $t) {
            $pointPayment = 0.0;
            if (!empty($t->payment_details)) {
                $details = $t->payment_details;
                if (is_string($details)) $details = json_decode($details, true);
                if (is_array($details)) {
                    $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                }
            } elseif (strtoupper($t->payment_method) === 'POINT') {
                $pointPayment = (float) $t->final_amount;
            }
            $omset = (float) $t->final_amount - $pointPayment;
            $hpp = (float) $t->raw_cogs;
            $laba = $omset - $hpp;

            $t_omset += $omset; $t_hpp += $hpp; $t_laba += $laba;

            $rows[] = [
                \Carbon\Carbon::parse($t->transaction_date)->format('d M Y H:i'),
                $t->local_transaction_id,
                $t->transaction_source,
                number_format($omset, 0, ',', '.'),
                number_format($hpp, 0, ',', '.'),
                number_format($laba, 0, ',', '.')
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '<strong>'.number_format($t_omset, 0, ',', '.').'</strong>', '<strong>'.number_format($t_hpp, 0, ',', '.').'</strong>', '<strong>'.number_format($t_laba, 0, ',', '.').'</strong>'];

        return view('print.reports.generic', [
            'title' => 'Laporan Laba Rugi (Estimasi)', 
            'period' => $period, 
            'columns' => $columns, 
            'rows' => $rows,
            'note' => 'Catatan: Nilai HPP yang tercantum pada laporan ini sudah termasuk PPN (HPP + PPN).'
        ]);
    }

    private function printLaporanBarangDijual($filters)
    {
        $query = \App\Models\AllSalesItem::query()->whereNotNull('product_id');
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }
        
        if (isset($filters['source']['value']) && !empty($filters['source']['value'])) {
            $query->where('source', $filters['source']['value']);
        }

        $query = $this->applyDateFilters($query, $filters, 'transaction_date', 'transaction_date');
        
        if (isset($filters['supplier_id']['value']) && !empty($filters['supplier_id']['value'])) {
            $query->whereHas('product', function($q) use ($filters) {
                $q->where('supplier_id', $filters['supplier_id']['value']);
            });
        }
        
        $query->with(['product', 'branch']);
        
        $items = $query->get();
        $period = $this->getPeriodString($filters);

        $columns = ['SKU', 'Produk', 'Qty Jual', 'Qty Retur', 'Harga Beli', 'Harga Jual', 'Total Beli', 'Total Jual'];
        $rows = [];
        
        $grouped = [];
        foreach($items as $i) {
            $product_id = $i->product_id;
            
            $cost_price = 0;
            $branch_id = $i->branch_id;
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

    private function printExpenseList(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        
        $query = \App\Models\Expense::query()->with(['branch', 'expenseAccount', 'paymentAccount', 'creator']);
        
        if ($startDate) {
            $query->whereDate('expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('expense_date', '<=', $endDate);
        }
        
        if (auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('created_at', 'desc')->get();
        $period = ($startDate && $endDate) ? \Carbon\Carbon::parse($startDate)->format('d M Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Semua Waktu';

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

        $branchName = 'Semua Cabang (Global)';
        if (auth()->user()->branch_id !== null) {
            $branch = \App\Models\Branch::find(auth()->user()->branch_id);
            if ($branch) $branchName = $branch->name;
        } elseif ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) $branchName = $branch->name;
        }
        
        $organization = \App\Models\Organization::find(auth()->user()->organization_id ?? \App\Models\Organization::first()?->id);

        return view('print.reports.generic', [
            'title' => 'Daftar Pengeluaran (Expenses)', 
            'period' => $period, 
            'columns' => $columns, 
            'rows' => $rows,
            'branchName' => $branchName,
            'organization' => $organization
        ]);
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

        $organization = \App\Models\Organization::find($organizationId);

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
            'organization' => $organization,
            'title' => 'Laporan Keuangan'
        ]);
    }

    private function printJurnalUmum(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        
        $organizationId = auth()->user()->organization_id ?? \App\Models\Organization::first()?->id;

        if (!$organizationId) {
            abort(404, 'Organisasi tidak ditemukan');
        }
        
        $organization = \App\Models\Organization::find($organizationId);

        $query = \App\Models\JournalEntry::where('organization_id', $organizationId)
            ->with(['lines.account', 'creator', 'branch'])
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $journals = $query->get();

        $period = ($startDate && $endDate) ? \Carbon\Carbon::parse($startDate)->format('d M Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Semua Waktu';
        $branchName = 'Semua Cabang (Global)';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) $branchName = $branch->name;
        }

        return view('print.reports.laporan-jurnal-umum', [
            'journals' => $journals,
            'period' => $period,
            'branchName' => $branchName,
            'organization' => $organization,
            'title' => 'Jurnal Umum'
        ]);
    }

    private function printKontrabon($filters)
    {
        $query = \App\Models\Kontrabon::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'tanggal_kontrabon', 'date_filter');
        
        if (auth()->user() && auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        $records = $query->orderBy('tanggal_kontrabon', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Kontrabon', 'No. Kontrabon', 'Pemasok', 'Cabang', 'Jatuh Tempo', 'Total Tagihan', 'Sudah Dibayar', 'Status'];
        $rows = [];
        $t_tagihan = 0; $t_dibayar = 0;
        
        foreach ($records as $r) {
            $t_tagihan += $r->total_amount;
            $t_dibayar += $r->paid_amount;
            $rows[] = [
                \Carbon\Carbon::parse($r->tanggal_kontrabon)->format('d-m-Y'),
                $r->kontrabon_number,
                $r->supplier ? $r->supplier->name : '-',
                $r->branch ? $r->branch->name : 'Pusat / Global',
                \Carbon\Carbon::parse($r->tanggal_jatuh_tempo)->format('d-m-Y'),
                number_format($r->total_amount, 0, ',', '.'),
                number_format($r->paid_amount, 0, ',', '.'),
                $r->status
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '', '<strong>'.number_format($t_tagihan, 0, ',', '.').'</strong>', '<strong>'.number_format($t_dibayar, 0, ',', '.').'</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Tukar Faktur (Kontrabon)', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printPembayaranHutang($filters)
    {
        $query = \App\Models\PurchasePayment::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'payment_date', 'date_filter');
        
        if (auth()->user() && auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        $records = $query->orderBy('payment_date', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tgl Bayar', 'No. Pembayaran', 'Pemasok', 'Cabang', 'Metode Bayar', 'Nominal Bayar', 'Status'];
        $rows = [];
        $t_bayar = 0;
        
        foreach ($records as $r) {
            $t_bayar += $r->total_amount;
            $rows[] = [
                \Carbon\Carbon::parse($r->payment_date)->format('d-m-Y'),
                $r->payment_number,
                $r->supplier ? $r->supplier->name : '-',
                $r->branch ? $r->branch->name : 'Pusat / Global',
                $r->payment_method,
                number_format($r->total_amount, 0, ',', '.'),
                $r->status
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '', '<strong>'.number_format($t_bayar, 0, ',', '.').'</strong>', ''];

        return view('print.reports.generic', ['title' => 'Daftar Pembayaran Hutang', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printKontrabonNota(Request $request)
    {
        $id = $request->query('id');
        $kontrabon = \App\Models\Kontrabon::with(['supplier', 'branch', 'goodsReceipts', 'kontrabonDeductions.supplierDeduction'])->findOrFail($id);
        
        $organizationId = auth()->user()?->organization_id ?? \App\Models\Organization::first()?->id;
        $organization = \App\Models\Organization::find($organizationId);
        
        // Aggregate Consignment Sellout if applicable
        $selloutItems = \App\Models\TransactionItem::with('product')
            ->where('kontrabon_id', $kontrabon->id)
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'sku' => $first->product->sku ?? '-',
                    'name' => $first->product->name ?? '-',
                    'qty' => $items->sum('quantity'),
                    'cost_price' => $first->product->cost_price ?? 0,
                    'subtotal' => $items->sum('quantity') * ($first->product->cost_price ?? 0),
                ];
            })->values();

        return view('print.documents.kontrabon-nota', [
            'kontrabon' => $kontrabon,
            'organization' => $organization,
            'selloutItems' => $selloutItems,
            'title' => 'Nota Kontrabon'
        ]);
    }

    private function printPromoSellout(Request $request)
    {
        $promoId = $request->query('id');
        $promo = \App\Models\Promotion::with('supplier')->findOrFail($promoId);
        
        $items = \App\Models\TransactionItem::where('promotion_id', $promoId)
                    ->with(['transaction.branch', 'product'])
                    ->get();

        if ($items->isEmpty()) {
            $query = \App\Models\TransactionItem::whereHas('transaction', function ($q) use ($promo) {
                $q->whereBetween('transaction_date', [$promo->valid_from, $promo->valid_until])
                  ->where('is_voided', false);
            });

            if ($promo->applicable_to === 'PRODUCT') {
                $query->whereIn('product_id', $promo->target_ids ?? []);
            } elseif ($promo->applicable_to === 'CATEGORY') {
                $query->whereHas('product', function ($q) use ($promo) {
                    $q->whereIn('category_id', $promo->target_ids ?? []);
                });
            }

            $itemsWithDiscount = (clone $query)->where('discount_per_item', '>', 0)
                ->with(['transaction.branch', 'product'])
                ->get();

            if ($itemsWithDiscount->isNotEmpty()) {
                $items = $itemsWithDiscount;
            } else {
                $items = $query->with(['transaction.branch', 'product'])->get();
            }
        }
                    
        $organizationId = auth()->user()?->organization_id ?? \App\Models\Organization::first()?->id;
        $organization = \App\Models\Organization::find($organizationId);
        
        return view('print.reports.promo-sellout', [
            'promo' => $promo,
            'items' => $items,
            'organization' => $organization,
            'title' => 'Laporan Sellout Promo'
        ]);
    }

    private function printSupplierDeductions($filters)
    {
        $query = \App\Models\SupplierDeduction::query()->with(['supplier', 'branch']);
        $query = $this->applyDateFilters($query, $filters, 'created_at', 'date_filter');
        
        if (auth()->user() && auth()->user()->branch_id !== null) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif (isset($filters['branch_id']['value']) && !empty($filters['branch_id']['value'])) {
            $query->where('branch_id', $filters['branch_id']['value']);
        }

        $records = $query->orderBy('created_at', 'desc')->get();
        $period = $this->getPeriodString($filters, 'date_filter');

        $columns = ['Tanggal', 'Jenis', 'Pemasok', 'Cabang', 'Nominal', 'Terpakai', 'Status', 'Catatan'];
        $rows = [];
        $t_nominal = 0; $t_terpakai = 0;
        
        foreach ($records as $r) {
            $t_nominal += $r->amount;
            $t_terpakai += $r->claimed_amount;
            $rows[] = [
                \Carbon\Carbon::parse($r->created_at)->format('d-m-Y'),
                $r->deduction_type,
                $r->supplier ? $r->supplier->name : '-',
                $r->branch ? $r->branch->name : 'Pusat / Global',
                number_format($r->amount, 0, ',', '.'),
                number_format($r->claimed_amount, 0, ',', '.'),
                $r->status,
                $r->notes
            ];
        }
        $rows[] = ['<strong>TOTAL</strong>', '', '', '', '<strong>'.number_format($t_nominal, 0, ',', '.').'</strong>', '<strong>'.number_format($t_terpakai, 0, ',', '.').'</strong>', '', ''];

        return view('print.reports.generic', ['title' => 'Daftar Klaim & Potongan Pemasok', 'period' => $period, 'columns' => $columns, 'rows' => $rows]);
    }

    private function printLaporanHpp(Request $request)
    {
        $filters = $request->input('tableFilters', []);
        $activeTab = $request->input('activeTab', 'item');
        
        $hppPage = app(\App\Filament\Pages\LaporanHpp::class);
        $hppPage->activeTab = $activeTab;
        
        $dateFilter = $filters['date_filter'] ?? [];
        
        $hppPage->data = [
            'period' => $dateFilter['period'] ?? 'today',
            'created_from' => $dateFilter['created_from'] ?? null,
            'created_until' => $dateFilter['created_until'] ?? null,
            'branch_id' => $filters['branch_id']['value'] ?? auth()->user()->branch_id,
            'search' => $filters['search']['value'] ?? null,
            'transaction_source' => $filters['transaction_source']['value'] ?? 'ALL',
        ];
        
        $results = $hppPage->getReportData();
        
        if ($activeTab === 'item') {
            $columns = ['Barcode', 'Nama Item', 'Satuan', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %'];
        } elseif ($activeTab === 'category') {
            $columns = ['Kode Kategori', 'Kelompok Barang', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %'];
        } elseif ($activeTab === 'subcategory') {
            $columns = ['Sub Kategori', 'Kategori Induk', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %'];
        } elseif ($activeTab === 'yearly') {
            $columns = ['Bulan', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %'];
        } else {
            $columns = ['Tanggal', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %'];
        }
        $rows = [];
        $t_sales = 0; $t_cogs = 0; $t_return = 0; $t_return_cogs = 0; $t_profit = 0;

        foreach ($results as $row) {
            $netSales = $row->sales_amount - $row->return_amount;
            $netCogs = $row->cogs_amount - $row->return_cogs_amount;
            $profit = $netSales - $netCogs;
            $margin = $row->sales_amount > 0 ? round(($profit / $row->sales_amount) * 100, 2) : 0;
            
            $t_sales += $row->sales_amount;
            $t_cogs += $row->cogs_amount;
            $t_return += $row->return_amount;
            $t_return_cogs += $row->return_cogs_amount;
            $t_profit += $profit;

            $rowData = [];
            if ($activeTab === 'item') {
                $rowData = [$row->barcode, $row->item_name, $row->unit];
            } elseif ($activeTab === 'category') {
                $rowData = [$row->category_id, $row->category_name];
            } elseif ($activeTab === 'subcategory') {
                $rowData = [$row->sub_category ?: '-', $row->category_name];
            } elseif ($activeTab === 'monthly') {
                $rowData = [Carbon::parse($row->tgl)->translatedFormat('l, d-F-Y')];
            } elseif ($activeTab === 'yearly') {
                $rowData = [Carbon::parse($row->tgl . '-01')->translatedFormat('F Y')];
            }
            
            $rowData = array_merge($rowData, [
                number_format($row->sales_amount, 0, ',', '.'),
                number_format($row->cogs_amount, 0, ',', '.'),
                number_format($row->return_amount, 0, ',', '.'),
                number_format($row->return_cogs_amount, 0, ',', '.'),
                number_format($profit, 0, ',', '.'),
                $margin . '%'
            ]);
            $rows[] = $rowData;
        }
        
        $totalMargin = $t_sales > 0 ? round(($t_profit / $t_sales) * 100, 2) : 0;
        $totalRow = ['<strong>TOTAL KESELURUHAN</strong>'];
        if ($activeTab === 'item') { $totalRow[] = ''; $totalRow[] = ''; }
        elseif ($activeTab === 'category' || $activeTab === 'subcategory') { $totalRow[] = ''; }

        $totalRow = array_merge($totalRow, [
            '<strong>'.number_format($t_sales, 0, ',', '.').'</strong>',
            '<strong>'.number_format($t_cogs, 0, ',', '.').'</strong>',
            '<strong>'.number_format($t_return, 0, ',', '.').'</strong>',
            '<strong>'.number_format($t_return_cogs, 0, ',', '.').'</strong>',
            '<strong>'.number_format($t_profit, 0, ',', '.').'</strong>',
            '<strong>'.$totalMargin.'%</strong>'
        ]);
        $rows[] = $totalRow;

        return view('print.reports.generic', [
            'title' => 'Rekapitulasi Harga Pokok Penjualan (HPP)',
            'period' => $this->getPeriodString(['date_filter' => $dateFilter], 'date_filter'),
            'columns' => $columns,
            'rows' => $rows,
            'note' => 'Catatan: Nilai HPP yang tercantum pada laporan ini sudah termasuk PPN (HPP + PPN).'
        ]);
    }

    public function printLaporanRekapTipeSuplier($filters)
    {
        $organization = \App\Models\Organization::first();
        $taxRate = $organization->tax_rate ?? 11;
        $branchId = $filters['branch_id']['value'] ?? null;
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $subquery = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('products as p', 'ti.product_id', '=', 'p.id')
            ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->where('t.is_voided', false)
            ->selectRaw("
                CASE 
                    WHEN s.id IS NULL THEN 'TANPA SUPLIER'
                    WHEN s.name LIKE '[%]%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ']', 1), '[', -1)
                    WHEN s.name LIKE '(%)%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, ')', 1), '(', -1)
                    WHEN s.name LIKE '{%}%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(s.name, '}', 1), '{', -1)
                    ELSE SUBSTRING_INDEX(s.name, ' ', 1)
                END as tipe_suplier,
                t.transaction_date,
                t.branch_id,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as jual,
                CASE WHEN ti.quantity > 0 THEN ti.quantity * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * (ti.unit_price - COALESCE(ti.discount_per_item, 0)) ELSE 0 END as retur,
                CASE WHEN ti.quantity < 0 THEN ABS(ti.quantity) * COALESCE(NULLIF(p.cost_price_tax, 0), p.cost_price, 0) ELSE 0 END as hpp_retur
            ");
            
        $query = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$subquery->toSql()}) as sub"))
            ->mergeBindings($subquery)
            ->selectRaw("
                tipe_suplier,
                SUM(jual) as jual,
                SUM(hpp) as hpp,
                SUM(retur) as retur,
                SUM(hpp_retur) as hpp_retur
            ")
            ->groupBy('tipe_suplier');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Apply date filter manually
        $dateFilter = $filters['date_filter'] ?? [];
        $period = $dateFilter['period'] ?? null;
        $periodString = 'Semua Periode';
        if ($period === 'today') {
            $query->whereDate('transaction_date', Carbon::today());
            $periodString = Carbon::today()->translatedFormat('d-m-Y');
        } elseif ($period === 'yesterday') {
            $query->whereDate('transaction_date', Carbon::yesterday());
            $periodString = Carbon::yesterday()->translatedFormat('d-m-Y');
        } elseif ($period === 'this_week') {
            $query->whereBetween('transaction_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            $periodString = Carbon::now()->startOfWeek()->translatedFormat('d-m-Y') . ' - ' . Carbon::now()->endOfWeek()->translatedFormat('d-m-Y');
        } elseif ($period === 'last_week') {
            $query->whereBetween('transaction_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            $periodString = Carbon::now()->subWeek()->startOfWeek()->translatedFormat('d-m-Y') . ' - ' . Carbon::now()->subWeek()->endOfWeek()->translatedFormat('d-m-Y');
        } elseif ($period === 'this_month') {
            $query->whereMonth('transaction_date', Carbon::now()->month)->whereYear('transaction_date', Carbon::now()->year);
            $periodString = Carbon::now()->startOfMonth()->translatedFormat('d-m-Y') . ' - ' . Carbon::now()->endOfMonth()->translatedFormat('d-m-Y');
        } elseif ($period === 'last_month') {
            $query->whereMonth('transaction_date', Carbon::now()->subMonth()->month)->whereYear('transaction_date', Carbon::now()->subMonth()->year);
            $periodString = Carbon::now()->subMonth()->startOfMonth()->translatedFormat('d-m-Y') . ' - ' . Carbon::now()->subMonth()->endOfMonth()->translatedFormat('d-m-Y');
        } elseif ($period === 'custom') {
            if (!empty($dateFilter['created_from'])) {
                $query->whereDate('transaction_date', '>=', $dateFilter['created_from']);
            }
            if (!empty($dateFilter['created_until'])) {
                $query->whereDate('transaction_date', '<=', $dateFilter['created_until']);
            }
            $from = !empty($dateFilter['created_from']) ? Carbon::parse($dateFilter['created_from'])->translatedFormat('d-m-Y') : 'Awal';
            $until = !empty($dateFilter['created_until']) ? Carbon::parse($dateFilter['created_until'])->translatedFormat('d-m-Y') : 'Akhir';
            $periodString = "$from - $until";
        }

        $data = $query->orderBy('tipe_suplier')->get()->map(function($row) {
            $row->selisih = ($row->jual - $row->hpp) - ($row->retur - $row->hpp_retur);
            return $row;
        });
        
        $fpRow = $data->firstWhere('tipe_suplier', 'FP');
        $fpHakPpn = 0;
        $fpMargin = 0;
        if ($fpRow) {
            $fpHakPpn = $fpRow->selisih * ($taxRate / 100);
            if ($fpRow->jual > 0) {
                $fpMargin = ($fpRow->selisih / $fpRow->jual) * 100;
            }
        }

        $totalJual = $data->sum('jual');
        $totalHpp = $data->sum('hpp');
        $totalRetur = $data->sum('retur');
        $totalHppRetur = $data->sum('hpp_retur');
        $totalSelisih = $data->sum('selisih');

        return view('print.laporan-rekap-tipe-suplier', compact(
            'data', 'periodString', 'branch', 'organization', 'taxRate',
            'fpRow', 'fpHakPpn', 'fpMargin',
            'totalJual', 'totalHpp', 'totalRetur', 'totalHppRetur', 'totalSelisih'
        ));
    }
}
