<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanHpp extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;
    use \Livewire\WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan HPP';
    protected static ?string $title = 'Rekapitulasi Harga Pokok Penjualan (HPP)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 5;

    public function getSubheading(): ?string
    {
        return 'Catatan: Nilai HPP yang tercantum pada laporan ini sudah termasuk PPN (HPP + PPN).';
    }

    protected string $view = 'filament.pages.laporan-hpp';

    public ?array $data = [];
    public array $appliedFilters = [];
    public array $cachedTotals = [
        'sales' => 0, 'cogs' => 0, 'return' => 0, 'return_cogs' => 0, 'profit' => 0, 'margin' => 0
    ];
    public string $activeTab = 'item';
    public bool $isReportReady = false;

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'today',
            'branch_id' => Auth::user()->branch_id,
            'transaction_source' => 'ALL',
        ]);
        // Also initialize appliedFilters to match default state, although not ready yet
        $this->appliedFilters = $this->data;
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    Select::make('period')
                        ->label('Periode Tanggal')
                        ->options([
                            'today' => 'Hari Ini',
                            'yesterday' => 'Kemarin',
                            'this_week' => 'Minggu Ini',
                            'last_week' => 'Minggu Kemarin',
                            'this_month' => 'Bulan Ini',
                            'last_month' => 'Bulan Kemarin',
                            'custom' => 'Custom Pilih Tanggal',
                        ])
                        ->default('today')
                        ->live(), // Just to toggle visibility of custom dates
                    DatePicker::make('created_from')
                        ->label('Dari Tanggal')
                        ->visible(fn ($get) => $get('period') === 'custom'),
                    DatePicker::make('created_until')
                        ->label('Sampai Tanggal')
                        ->visible(fn ($get) => $get('period') === 'custom'),
                    Select::make('branch_id')
                        ->label('Cabang')
                        ->options(Branch::pluck('name', 'id'))
                        ->hidden(fn () => Auth::user()->branch_id !== null),
                ]),
                Grid::make(2)->schema([
                    Select::make('transaction_source')
                        ->label('Sumber Penjualan')
                        ->options([
                            'ALL' => 'Semua (Kasir & Online)',
                            'OFFLINE' => 'Kasir Offline',
                            'ONLINE' => 'Online E-Commerce',
                        ])
                        ->default('ALL'),
                    \Filament\Forms\Components\TextInput::make('search')
                        ->label('Cari Barang / Kategori')
                        ->placeholder('Ketik pencarian...'),
                ]),
            ])
            ->statePath('data');
    }

    public function processReport()
    {
        $this->appliedFilters = $this->data; // Snapshot the filters!
        $this->isReportReady = true;
        $this->cachedTotals = $this->calculateGrandTotals(); // Cache totals to avoid recalculating on tab switch!
        $this->resetPage(); // Reset pagination when new report is processed
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination on tab switch
    }

    protected function getDateRange()
    {
        $period = $this->appliedFilters['period'] ?? 'today';
        $from = null;
        $until = null;

        if ($period === 'today') {
            $from = Carbon::today();
            $until = Carbon::today()->endOfDay();
        } elseif ($period === 'yesterday') {
            $from = Carbon::yesterday();
            $until = Carbon::yesterday()->endOfDay();
        } elseif ($period === 'this_week') {
            $from = Carbon::now()->startOfWeek();
            $until = Carbon::now()->endOfWeek();
        } elseif ($period === 'last_week') {
            $from = Carbon::now()->subWeek()->startOfWeek();
            $until = Carbon::now()->subWeek()->endOfWeek();
        } elseif ($period === 'this_month') {
            $from = Carbon::now()->startOfMonth();
            $until = Carbon::now()->endOfMonth();
        } elseif ($period === 'last_month') {
            $from = Carbon::now()->subMonth()->startOfMonth();
            $until = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($period === 'custom') {
            $from = !empty($this->appliedFilters['created_from']) ? Carbon::parse($this->appliedFilters['created_from'])->startOfDay() : Carbon::parse('2000-01-01');
            $until = !empty($this->appliedFilters['created_until']) ? Carbon::parse($this->appliedFilters['created_until'])->endOfDay() : Carbon::now()->endOfDay();
        }

        return [$from, $until];
    }

    private function getBaseQuery()
    {
        [$from, $until] = $this->getDateRange();
        $branchId = $this->appliedFilters['branch_id'] ?? Auth::user()->branch_id;
        $search = $this->appliedFilters['search'] ?? null;
        $sourceFilter = $this->appliedFilters['transaction_source'] ?? 'ALL';

        $offlineQuery = DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->selectRaw("
                ti.id as item_id,
                ti.product_id,
                ti.quantity as ti_quantity,
                ti.unit_price as ti_unit_price,
                ti.discount_per_item as ti_discount_per_item,
                t.branch_id,
                t.transaction_date,
                t.transaction_type,
                'OFFLINE' as transaction_source
            ")
            ->where('t.is_voided', 0)
            ->whereBetween('t.transaction_date', [$from, $until]);
            
        if ($branchId) {
            $offlineQuery->where('t.branch_id', $branchId);
        }

        $onlineQuery = DB::table('ecommerce_order_items as ei')
            ->join('ecommerce_orders as eo', 'ei.ecommerce_order_id', '=', 'eo.id')
            ->selectRaw("
                ei.id as item_id,
                ei.product_id,
                ei.quantity as ti_quantity,
                ei.price as ti_unit_price,
                0 as ti_discount_per_item,
                eo.branch_id,
                eo.created_at as transaction_date,
                'SALE' as transaction_type,
                'ONLINE' as transaction_source
            ")
            ->where('eo.status', 'COMPLETED')
            ->whereBetween('eo.created_at', [$from, $until]);

        if ($branchId) {
            $onlineQuery->where('eo.branch_id', $branchId);
        }

        $subquery = null;
        if ($sourceFilter === 'OFFLINE') {
            $subquery = $offlineQuery;
        } elseif ($sourceFilter === 'ONLINE') {
            $subquery = $onlineQuery;
        } else {
            $subquery = $offlineQuery->unionAll($onlineQuery);
        }

        // We wrap the subquery and merge its bindings so we can JOIN on the filtered, reduced result set!
        $query = DB::table(DB::raw("({$subquery->toSql()}) as t"))
            ->mergeBindings($subquery)
            ->leftJoin('products as p', 't.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('stocks as st', function($join) {
                $join->on('st.product_id', '=', 'p.id')
                     ->on('st.branch_id', '=', 't.branch_id');
            })
            // Optimize COGS calculation by joining pre-aggregated deductions instead of correlated subqueries
            ->leftJoin(DB::raw('(
                SELECT sbd.transaction_item_id, SUM(sbd.quantity * sb.cost_price) as actual_cogs
                FROM stock_batch_deductions sbd
                JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                WHERE sbd.transaction_item_id IS NOT NULL
                GROUP BY sbd.transaction_item_id
            ) as offline_cogs'), function($join) {
                $join->on('t.transaction_source', '=', DB::raw("'OFFLINE'"))
                     ->on('t.item_id', '=', 'offline_cogs.transaction_item_id');
            })
            ->leftJoin(DB::raw('(
                SELECT sbd.ecommerce_order_item_id, SUM(sbd.quantity * sb.cost_price) as actual_cogs
                FROM stock_batch_deductions sbd
                JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                WHERE sbd.ecommerce_order_item_id IS NOT NULL
                GROUP BY sbd.ecommerce_order_item_id
            ) as online_cogs'), function($join) {
                $join->on('t.transaction_source', '=', DB::raw("'ONLINE'"))
                     ->on('t.item_id', '=', 'online_cogs.ecommerce_order_item_id');
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('p.name', 'LIKE', "%{$search}%")
                  ->orWhere('p.sku', 'LIKE', "%{$search}%")
                  ->orWhere('c.name', 'LIKE', "%{$search}%")
                  ->orWhere('p.sub_category', 'LIKE', "%{$search}%");
            });
        }
        
        return $query;
    }

    public function getReportData($isExport = false)
    {
        if (!$this->isReportReady && !$isExport) return collect([]);

        if ($this->activeTab === 'item') {
            return $this->getItemData($isExport);
        } elseif ($this->activeTab === 'category') {
            return $this->getCategoryData($isExport);
        } elseif ($this->activeTab === 'subcategory') {
            return $this->getSubCategoryData($isExport);
        } elseif ($this->activeTab === 'monthly') {
            return $this->getMonthlyData($isExport);
        } elseif ($this->activeTab === 'yearly') {
            return $this->getYearlyData($isExport);
        }
        
        return collect([]);
    }

    public function getGrandTotals()
    {
        return $this->cachedTotals;
    }

    private function calculateGrandTotals()
    {
        if (!$this->isReportReady) {
            return [
                'sales' => 0, 'cogs' => 0, 'return' => 0, 'return_cogs' => 0, 'profit' => 0, 'margin' => 0
            ];
        }

        $query = $this->getBaseQuery();
        $result = $query->selectRaw("
                SUM(CASE WHEN t.ti_quantity > 0 THEN t.ti_quantity * (t.ti_unit_price - t.ti_discount_per_item) ELSE 0 END) as sales_amount,
                SUM(CASE WHEN t.ti_quantity > 0 THEN (
                    COALESCE(offline_cogs.actual_cogs, online_cogs.actual_cogs, t.ti_quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0))
                ) ELSE 0 END) as cogs_amount,
                SUM(CASE WHEN t.ti_quantity < 0 THEN ABS(t.ti_quantity) * (t.ti_unit_price - t.ti_discount_per_item) ELSE 0 END) as return_amount,
                SUM(CASE WHEN t.ti_quantity < 0 THEN ABS(
                    COALESCE(offline_cogs.actual_cogs, online_cogs.actual_cogs, t.ti_quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0))
                ) ELSE 0 END) as return_cogs_amount
        ")->first();

        $sales = $result->sales_amount ?? 0;
        $cogs = $result->cogs_amount ?? 0;
        $return = $result->return_amount ?? 0;
        $returnCogs = $result->return_cogs_amount ?? 0;
        
        $profit = ($sales - $return) - ($cogs - $returnCogs);
        $margin = $sales > 0 ? ($profit / $sales) * 100 : 0;

        return [
            'sales' => $sales,
            'cogs' => $cogs,
            'return' => $return,
            'return_cogs' => $returnCogs,
            'profit' => $profit,
            'margin' => $margin,
        ];
    }

    private function getCommonSelectRaw()
    {
        return "
            SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN t.ti_quantity ELSE 0 END) as sales_qty,
            SUM(CASE WHEN t.ti_quantity > 0 THEN t.ti_quantity * (t.ti_unit_price - t.ti_discount_per_item) ELSE 0 END) as sales_amount,
            SUM(CASE WHEN t.ti_quantity > 0 THEN (
                COALESCE(offline_cogs.actual_cogs, online_cogs.actual_cogs, t.ti_quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0))
            ) ELSE 0 END) as cogs_amount,
            SUM(CASE WHEN t.transaction_type = 'RETURN' THEN t.ti_quantity ELSE 0 END) as return_qty,
            SUM(CASE WHEN t.ti_quantity < 0 THEN ABS(t.ti_quantity) * (t.ti_unit_price - t.ti_discount_per_item) ELSE 0 END) as return_amount,
            SUM(CASE WHEN t.ti_quantity < 0 THEN ABS(
                COALESCE(offline_cogs.actual_cogs, online_cogs.actual_cogs, t.ti_quantity * COALESCE(NULLIF(st.cost_price_tax, 0), NULLIF(st.cost_price, 0), NULLIF(p.cost_price_tax, 0), p.cost_price, 0))
            ) ELSE 0 END) as return_cogs_amount
        ";
    }

    private function getItemData($isExport = false)
    {
        $query = $this->getBaseQuery()
            ->selectRaw("p.sku as barcode, p.barcode as product_barcode, p.name as item_name, p.unit_of_measure as unit, " . $this->getCommonSelectRaw())
            ->groupBy('p.id', 'p.sku', 'p.barcode', 'p.name', 'p.unit_of_measure')
            ->orderBy('p.name', 'ASC');
            
        return $isExport ? $query->get() : $query->paginate(50);
    }
    
    private function getCategoryData($isExport = false)
    {
        $query = $this->getBaseQuery()
            ->selectRaw("c.id as category_id, COALESCE(c.name, 'Tanpa Kategori') as category_name, " . $this->getCommonSelectRaw())
            ->groupBy('c.id', DB::raw("COALESCE(c.name, 'Tanpa Kategori')"))
            ->orderBy('category_name', 'ASC');
            
        return $isExport ? $query->get() : $query->paginate(50);
    }

    private function getSubCategoryData($isExport = false)
    {
        $query = $this->getBaseQuery()
            ->selectRaw("c.id as category_id, COALESCE(c.name, 'Tanpa Kategori') as category_name, p.sub_category, " . $this->getCommonSelectRaw())
            ->groupBy('c.id', DB::raw("COALESCE(c.name, 'Tanpa Kategori')"), 'p.sub_category')
            ->orderBy('category_name', 'ASC')
            ->orderBy('p.sub_category', 'ASC');
            
        return $isExport ? $query->get() : $query->paginate(50);
    }
    
    private function getMonthlyData($isExport = false)
    {
        $query = $this->getBaseQuery()
            ->selectRaw("DATE(t.transaction_date) as tgl, " . $this->getCommonSelectRaw())
            ->groupBy(DB::raw("DATE(t.transaction_date)"))
            ->orderBy('tgl', 'ASC');
            
        return $isExport ? $query->get() : $query->paginate(50);
    }

    private function getYearlyData($isExport = false)
    {
        $query = $this->getBaseQuery()
            ->selectRaw("DATE_FORMAT(t.transaction_date, '%Y-%m') as bulan, " . $this->getCommonSelectRaw())
            ->groupBy(DB::raw("DATE_FORMAT(t.transaction_date, '%Y-%m')"))
            ->orderBy('bulan', 'ASC');
            
        return $isExport ? $query->get() : $query->paginate(50);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('print.report', [
                    'type' => 'laporan-hpp',
                    'activeTab' => $this->activeTab,
                    'tableFilters' => [
                        'date_filter' => [
                            'period' => $this->data['period'] ?? 'today',
                            'created_from' => $this->data['created_from'] ?? null,
                            'created_until' => $this->data['created_until'] ?? null,
                        ],
                        'branch_id' => [
                            'value' => $this->data['branch_id'] ?? null
                        ],
                        'search' => [
                            'value' => $this->data['search'] ?? null
                        ],
                        'transaction_source' => [
                            'value' => $this->data['transaction_source'] ?? 'ALL'
                        ]
                    ]
                ]))
                ->openUrlInNewTab(),
            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('export_csv')
                    ->label('Export Csv (Raw Data)')
                    ->icon('heroicon-o-table-cells')
                    ->action(function () {
                        $data = $this->getReportData(true);
                        $filename = "Laporan_HPP_" . $this->activeTab . "_" . date('Y-m-d') . ".csv";


                    $headers = array(
                        "Content-type"        => "text/csv",
                        "Content-Disposition" => "attachment; filename=$filename",
                        "Pragma"              => "no-cache",
                        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                        "Expires"             => "0"
                    );

                    $callback = function() use($data) {
                        $file = fopen('php://output', 'w');
                        
                        // Header row
                        if ($this->activeTab === 'item') {
                            fputcsv($file, ['SKU', 'Barcode', 'Nama Item', 'Satuan', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
                        } elseif ($this->activeTab === 'category') {
                            fputcsv($file, ['Kode Kategori', 'Kelompok Barang', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
                        } elseif ($this->activeTab === 'subcategory') {
                            fputcsv($file, ['Sub Kategori', 'Kategori Induk', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
                        } elseif ($this->activeTab === 'monthly') {
                            fputcsv($file, ['Tanggal', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
                        } elseif ($this->activeTab === 'yearly') {
                            fputcsv($file, ['Bulan', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
                        }

                        // Data rows
                        foreach ($data as $row) {
                            $netSales = $row->sales_amount - $row->return_amount;
                            $netCogs = $row->cogs_amount - $row->return_cogs_amount;
                            $profit = $netSales - $netCogs;
                            $margin = $row->sales_amount > 0 ? round(($profit / $row->sales_amount) * 100, 2) : 0;

                            if ($this->activeTab === 'item') {
                                fputcsv($file, [$row->barcode, $row->product_barcode, $row->item_name, $row->unit, $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
                            } elseif ($this->activeTab === 'category') {
                                fputcsv($file, [$row->category_id, $row->category_name, $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
                            } elseif ($this->activeTab === 'subcategory') {
                                fputcsv($file, [$row->sub_category ?: '-', $row->category_name, $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
                            } elseif ($this->activeTab === 'monthly') {
                                fputcsv($file, [\Carbon\Carbon::parse($row->tgl)->format('Y-m-d'), $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
                            } elseif ($this->activeTab === 'yearly') {
                                fputcsv($file, [\Carbon\Carbon::parse($row->bulan . '-01')->format('Y-m'), $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
                            }
                        }
                        
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }),
                \Filament\Actions\Action::make('export_xls')
                    ->label('Export Xls (Format Cetak)')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('print.report', [
                        'type' => 'laporan-hpp',
                        'export' => 'xls',
                        'activeTab' => $this->activeTab,
                        'tableFilters' => [
                            'date_filter' => [
                                'period' => $this->data['period'] ?? 'today',
                                'created_from' => $this->data['created_from'] ?? null,
                                'created_until' => $this->data['created_until'] ?? null,
                            ],
                            'branch_id' => [
                                'value' => $this->data['branch_id'] ?? null
                            ],
                            'search' => [
                                'value' => $this->data['search'] ?? null
                            ],
                            'transaction_source' => [
                                'value' => $this->data['transaction_source'] ?? 'ALL'
                            ]
                        ]
                    ]), true)
            ])
            ->label('Export')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->button(),
        ];
    }
}
