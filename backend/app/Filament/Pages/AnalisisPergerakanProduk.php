<?php

namespace App\Filament\Pages;

use App\Services\ProductVelocityAnalysisService;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Category;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AnalisisPergerakanProduk extends Page
{
    use HasPageShield;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('superadmin') || $user->hasRole('Admin'))) {
            return true;
        }
        return $user->can('page_AnalisisPergerakanProduk');
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Fast, Slow & Dead Stock';
    protected static ?string $title = 'Analisis Pergerakan Produk (Fast, Slow & Dead Stock)';
    protected static string|\UnitEnum|null $navigationGroup = 'ANALISA AI';
    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.analisis-pergerakan-produk';

    // Filter States
    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $date_preset = '30_DAYS'; // TODAY, 7_DAYS, 30_DAYS, 90_DAYS, 365_DAYS, CUSTOM
    public string $branch_id = 'ALL';
    public string $supplier_id = 'ALL';
    public string $category_id = 'ALL';
    public string $quadrant = 'ALL'; // ALL, CRITICAL_FAST, OVERSTOCK_SLOW, DEAD_STOCK, OPTIMAL
    public string $sort_by = 'doh_asc'; // doh_asc, doh_desc, revenue_desc, qty_desc, capital_desc, name_asc
    public string $search = '';
    public int $per_page = 15;
    public int $page = 1;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }

        $this->applyDatePreset('30_DAYS');
    }

    public function applyDatePreset(string $preset): void
    {
        $this->date_preset = $preset;
        $today = Carbon::now()->toDateString();

        switch ($preset) {
            case 'TODAY':
                $this->start_date = $today;
                $this->end_date = $today;
                break;
            case '7_DAYS':
                $this->start_date = Carbon::now()->subDays(6)->toDateString();
                $this->end_date = $today;
                break;
            case '30_DAYS':
                $this->start_date = Carbon::now()->subDays(29)->toDateString();
                $this->end_date = $today;
                break;
            case '90_DAYS':
                $this->start_date = Carbon::now()->subDays(89)->toDateString();
                $this->end_date = $today;
                break;
            case '365_DAYS':
                $this->start_date = Carbon::now()->subDays(364)->toDateString();
                $this->end_date = $today;
                break;
            case 'CUSTOM':
            default:
                // Biarkan tanggal yang dipilih manual
                break;
        }

        $this->page = 1;
    }

    public function updatedStartDate(): void
    {
        $this->date_preset = 'CUSTOM';
        $this->page = 1;
    }

    public function updatedEndDate(): void
    {
        $this->date_preset = 'CUSTOM';
        $this->page = 1;
    }

    public function updatedBranchId(): void
    {
        $this->page = 1;
    }

    public function updatedSupplierId(): void
    {
        $this->page = 1;
    }

    public function updatedCategoryId(): void
    {
        $this->page = 1;
    }

    public function updatedSortBy(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function setQuadrant(string $quadrant): void
    {
        $this->quadrant = $quadrant;
        $this->page = 1;
    }

    public function setSort(string $sort): void
    {
        $this->sort_by = $sort;
        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function resetFilters(): void
    {
        $user = Auth::user();
        $this->branch_id = ($user && $user->branch_id) ? $user->branch_id : 'ALL';
        $this->supplier_id = 'ALL';
        $this->category_id = 'ALL';
        $this->quadrant = 'ALL';
        $this->sort_by = 'doh_asc';
        $this->search = '';
        $this->per_page = 15;
        $this->applyDatePreset('30_DAYS');
    }

    public function getVelocityReportProperty(): array
    {
        $service = app(ProductVelocityAnalysisService::class);

        return $service->getVelocityReport([
            'start_date' => $this->start_date ?: Carbon::now()->subDays(29)->toDateString(),
            'end_date' => $this->end_date ?: Carbon::now()->toDateString(),
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'quadrant' => $this->quadrant,
            'sort_by' => $this->sort_by,
            'search' => $this->search,
            'per_page' => $this->per_page,
            'page' => $this->page,
        ]);
    }

    public function getBranchesProperty()
    {
        return Branch::where('is_active', true)->orderBy('name')->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        return Category::orderBy('name')->get();
    }
}
