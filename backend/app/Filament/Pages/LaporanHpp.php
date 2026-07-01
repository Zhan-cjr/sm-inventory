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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan HPP';
    protected static ?string $title = 'Rekapitulasi Harga Pokok Penjualan (HPP)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.laporan-hpp';

    public ?array $data = [];
    public string $activeTab = 'item';

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'today',
            'branch_id' => Auth::user()->branch_id,
        ]);
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
                        ->live()
                        ->afterStateUpdated(fn () => $this->dispatch('filterUpdated')),
                    DatePicker::make('created_from')
                        ->label('Dari Tanggal')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->live()
                        ->afterStateUpdated(fn () => $this->dispatch('filterUpdated')),
                    DatePicker::make('created_until')
                        ->label('Sampai Tanggal')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->live()
                        ->afterStateUpdated(fn () => $this->dispatch('filterUpdated')),
                    Select::make('branch_id')
                        ->label('Cabang')
                        ->options(Branch::pluck('name', 'id'))
                        ->hidden(fn () => Auth::user()->branch_id !== null)
                        ->live()
                        ->afterStateUpdated(fn () => $this->dispatch('filterUpdated')),
                ]),
                Grid::make(1)->schema([
                    \Filament\Forms\Components\TextInput::make('search')
                        ->label('Cari Barang / Kategori')
                        ->placeholder('Ketik pencarian...')
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn () => $this->dispatch('filterUpdated')),
                ]),
            ])
            ->statePath('data');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    protected function getDateRange()
    {
        $period = $this->data['period'] ?? 'today';
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
            $from = !empty($this->data['created_from']) ? Carbon::parse($this->data['created_from'])->startOfDay() : Carbon::parse('2000-01-01');
            $until = !empty($this->data['created_until']) ? Carbon::parse($this->data['created_until'])->endOfDay() : Carbon::now()->endOfDay();
        }

        return [$from, $until];
    }

    public function getReportData()
    {
        [$from, $until] = $this->getDateRange();
        $branchId = $this->data['branch_id'] ?? Auth::user()->branch_id;
        $search = $this->data['search'] ?? null;
        
        $whereClause = "t.transaction_date >= ? AND t.transaction_date <= ?";
        $bindings = [$from, $until];
        
        if ($branchId) {
            $whereClause .= " AND t.branch_id = ?";
            $bindings[] = $branchId;
        }

        if ($search) {
            $whereClause .= " AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR p.sub_category LIKE ?)";
            $searchPattern = '%' . $search . '%';
            $bindings[] = $searchPattern;
            $bindings[] = $searchPattern;
            $bindings[] = $searchPattern;
            $bindings[] = $searchPattern;
        }

        if ($this->activeTab === 'item') {
            return $this->getItemData($whereClause, $bindings);
        } elseif ($this->activeTab === 'category') {
            return $this->getCategoryData($whereClause, $bindings);
        } elseif ($this->activeTab === 'subcategory') {
            return $this->getSubCategoryData($whereClause, $bindings);
        } elseif ($this->activeTab === 'monthly') {
            return $this->getMonthlyData($whereClause, $bindings);
        } elseif ($this->activeTab === 'yearly') {
            return $this->getYearlyData($whereClause, $bindings);
        }
        
        return collect([]);
    }

    private function getItemData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                p.sku as barcode,
                p.name as item_name,
                p.unit_of_measure as unit,
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity ELSE 0 END) as sales_qty,
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity ELSE 0 END) as return_qty,
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY p.id, p.sku, p.name, p.unit_of_measure
            ORDER BY p.name ASC
        ";

        return collect(DB::select($sql, $bindings));
    }
    
    private function getCategoryData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                c.id as category_id,
                COALESCE(c.name, 'Tanpa Kategori') as category_name,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY c.id, COALESCE(c.name, 'Tanpa Kategori')
            ORDER BY category_name ASC
        ";

        return collect(DB::select($sql, $bindings));
    }

    private function getSubCategoryData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                c.id as category_id,
                COALESCE(c.name, 'Tanpa Kategori') as category_name,
                p.sub_category,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY c.id, COALESCE(c.name, 'Tanpa Kategori'), p.sub_category
            ORDER BY category_name ASC, p.sub_category ASC
        ";

        return collect(DB::select($sql, $bindings));
    }
    
    private function getMonthlyData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                DATE(t.transaction_date) as tgl,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY DATE(t.transaction_date)
            ORDER BY tgl ASC
        ";

        return collect(DB::select($sql, $bindings));
    }

    private function getYearlyData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                DATE_FORMAT(t.transaction_date, '%Y-%m') as bulan,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(st.cost_price_tax, st.cost_price, p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stocks st ON st.product_id = p.id AND st.branch_id = t.branch_id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
            ORDER BY bulan ASC
        ";

        return collect(DB::select($sql, $bindings));
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
                        ]
                    ]
                ]))
                ->openUrlInNewTab(),
            \Filament\Actions\Action::make('export_csv')
                ->label('Export Excel (CSV)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $data = $this->getReportData();
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
                            fputcsv($file, ['Barcode', 'Nama Item', 'Satuan', 'Penjualan', 'HPP', 'Retur', 'HPP Retur', 'Profit', 'Margin %']);
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
                                fputcsv($file, [$row->barcode, $row->item_name, $row->unit, $row->sales_amount, $row->cogs_amount, $row->return_amount, $row->return_cogs_amount, $profit, $margin . '%']);
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
        ];
    }
}
