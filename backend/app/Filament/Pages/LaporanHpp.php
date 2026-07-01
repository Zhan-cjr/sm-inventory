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
                ])
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
        
        $whereClause = "t.transaction_date >= ? AND t.transaction_date <= ?";
        $bindings = [$from, $until];
        
        if ($branchId) {
            $whereClause .= " AND t.branch_id = ?";
            $bindings[] = $branchId;
        }

        if ($this->activeTab === 'item') {
            return $this->getItemData($whereClause, $bindings);
        } elseif ($this->activeTab === 'category') {
            return $this->getCategoryData($whereClause, $bindings);
        } elseif ($this->activeTab === 'monthly') {
            return $this->getMonthlyData($whereClause, $bindings);
        }
        
        return [];
    }

    private function getItemData($whereClause, $bindings)
    {
        $sql = "
            SELECT 
                p.sku as barcode,
                p.name as item_name,
                p.unit as unit,
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity ELSE 0 END) as sales_qty,
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as sales_amount,
                
                SUM(CASE WHEN COALESCE(t.transaction_type, '') != 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity ELSE 0 END) as return_qty,
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY p.id, p.sku, p.name, p.unit
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
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY c.id, COALESCE(c.name, 'Tanpa Kategori')
            ORDER BY category_name ASC
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
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as cogs_amount,

                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN ti.quantity * (ti.unit_price - ti.discount_per_item) ELSE 0 END) as return_amount,
                
                SUM(CASE WHEN t.transaction_type = 'RETURN' THEN (
                    SELECT COALESCE(SUM(sbd.quantity * sb.cost_price), ti.quantity * COALESCE(p.cost_price_tax, p.cost_price, 0))
                    FROM stock_batch_deductions sbd
                    JOIN stock_batches sb ON sbd.stock_batch_id = sb.id
                    WHERE sbd.transaction_item_id = ti.id
                ) ELSE 0 END) as return_cogs_amount

            FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN products p ON ti.product_id = p.id
            WHERE t.is_voided = 0 AND $whereClause
            GROUP BY DATE(t.transaction_date)
            ORDER BY tgl ASC
        ";

        return collect(DB::select($sql, $bindings));
    }
}
