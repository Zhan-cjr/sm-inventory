<?php

namespace App\Filament\Pages;

use App\Services\ProductVelocityAnalysisService;
use App\Models\{Branch, Supplier, Category, Product, PurchaseOrder, PurchaseOrderItem, User};
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AnalisisPergerakanProduk extends Page
{
    use HasPageShield;

    public static function canAccess(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }

        if ($u->hasRole(['super_admin', 'super-admin', 'superadmin', 'Admin'])) {
            return true;
        }

        return $u->can('View:AnalisisPergerakanProduk')
            || $u->can('page_AnalisisPergerakanProduk')
            || (static::getPagePermission() && $u->can(static::getPagePermission()));
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Fast, Slow & Dead Stock';
    protected static ?string $title = 'Analisis Pergerakan Produk (Fast, Slow & Dead Stock)';
    protected static string|\UnitEnum|null $navigationGroup = 'ANALISA AI';
    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.analisis-pergerakan-produk';

    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $date_preset = '30_DAYS';
    public string $branch_id = 'ALL';
    public string $supplier_id = 'ALL';
    public string $category_id = 'ALL';
    public string $quadrant = 'ALL';
    public string $sort_by = 'doh_asc';
    public string $search = '';
    public int $per_page = 15;
    public int $page = 1;

    public function mount(): void
    {
        $u = Auth::user();
        if ($u && $u->branch_id) $this->branch_id = $u->branch_id;
        $this->applyDatePreset('30_DAYS');
    }

    public function applyDatePreset(string $preset): void
    {
        $this->date_preset = $preset;
        $today = Carbon::now()->toDateString();
        $this->start_date = match ($preset) {
            'TODAY' => $today,
            '7_DAYS' => Carbon::now()->subDays(6)->toDateString(),
            '30_DAYS' => Carbon::now()->subDays(29)->toDateString(),
            '90_DAYS' => Carbon::now()->subDays(89)->toDateString(),
            '365_DAYS' => Carbon::now()->subDays(364)->toDateString(),
            default => $this->start_date,
        };
        $this->end_date = $today;
        $this->page = 1;
    }

    public function updatedStartDate(): void { $this->date_preset = 'CUSTOM'; $this->page = 1; }
    public function updatedEndDate(): void { $this->date_preset = 'CUSTOM'; $this->page = 1; }
    public function updatedBranchId(): void { $this->page = 1; }
    public function updatedSupplierId(): void { $this->page = 1; }
    public function updatedCategoryId(): void { $this->page = 1; }
    public function updatedSortBy(): void { $this->page = 1; }
    public function updatedPerPage(): void { $this->page = 1; }
    public function updatedSearch(): void { $this->page = 1; }

    public function selectSupplier(string $id): void { $this->supplier_id = $id; $this->page = 1; }
    public function setQuadrant(string $q): void { $this->quadrant = $q; $this->page = 1; }
    public function setPage(int $p): void { $this->page = max(1, $p); }
    public function nextPage(): void { $this->page++; }
    public function prevPage(): void { if ($this->page > 1) $this->page--; }

    public function resetFilters(): void
    {
        $u = Auth::user();
        $this->branch_id = ($u && $u->branch_id) ? $u->branch_id : 'ALL';
        $this->supplier_id = 'ALL';
        $this->category_id = 'ALL';
        $this->quadrant = 'ALL';
        $this->sort_by = 'doh_asc';
        $this->search = '';
        $this->per_page = 15;
        $this->applyDatePreset('30_DAYS');
    }

    public function getSelectedSupplierNameProperty(): string
    {
        if ($this->supplier_id === 'ALL' || empty($this->supplier_id)) return 'Semua Supplier';
        return Supplier::where('id', $this->supplier_id)->value('name') ?? 'Semua Supplier';
    }

    public function getVelocityReportProperty(): array
    {
        return app(ProductVelocityAnalysisService::class)->getVelocityReport([
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

    public function createDraftPoForProduct(string $productId)
    {
        $product = Product::with('supplier')->find($productId);
        if (!$product || !$product->supplier_id) {
            Notification::make()->title('Produk belum terhubung dengan Pemasok')->warning()->send();
            return null;
        }

        $branchId = ($this->branch_id !== 'ALL' && !empty($this->branch_id))
            ? $this->branch_id
            : (auth()->user()?->branch_id ?? Branch::where('is_active', true)->value('id'));

        $itemData = collect($this->velocity_report['items'])->firstWhere('product_id', $productId);
        $ads = (float) ($itemData['ads'] ?? 0);
        $stock = (float) ($itemData['current_stock'] ?? 0);
        $cost = (float) ($itemData['cost_price'] ?: ($product->cost_price_tax ?: $product->cost_price ?: 0));
        $qty = max(1, (int) ceil(($ads * 14) - $stock));
        $sub = $qty * $cost;

        $po = PurchaseOrder::create([
            'organization_id' => $product->organization_id, 'branch_id' => $branchId,
            'supplier_id' => $product->supplier_id, 'supplier_division_id' => $product->supplier_division_id,
            'po_number' => $this->generateUniquePoNumber(), 'po_date' => now()->toDateString(),
            'status' => 'DRAFT', 'total_amount' => $sub, 'created_by' => auth()->id() ?? User::first()?->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id, 'product_id' => $product->id,
            'quantity_suggested' => $qty, 'quantity_ordered' => $qty,
            'unit_cost' => $cost, 'subtotal' => $sub,
        ]);

        Notification::make()
            ->title("Draft {$po->po_number} Berhasil Dibuat")
            ->body("{$product->name} (Qty: {$qty}) ke {$product->supplier?->name}")
            ->success()
            ->send();

        return redirect()->to(route('filament.admin.resources.purchase-orders.edit', $po));
    }

    protected function generateUniquePoNumber(int $seq = 0): string
    {
        do {
            $suffix = $seq > 0 ? sprintf('%02d', $seq) . '-' : '';
            $num = 'PO-' . date('YmdHis') . '-' . $suffix . strtoupper(\Illuminate\Support\Str::random(4));
        } while (PurchaseOrder::where('po_number', $num)->exists());
        return $num;
    }

    public function exportExcel()
    {
        $report = app(ProductVelocityAnalysisService::class)->getVelocityReport([
            'start_date' => $this->start_date ?: Carbon::now()->subDays(29)->toDateString(),
            'end_date' => $this->end_date ?: Carbon::now()->toDateString(),
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'category_id' => $this->category_id,
            'quadrant' => $this->quadrant,
            'sort_by' => $this->sort_by,
            'search' => $this->search,
            'per_page' => 999999,
            'page' => 1,
        ]);

        $items = $report['items'];
        $daysCount = $report['days_count'];
        $filename = 'analisis_pergerakan_produk_' . ($this->start_date ?: 'start') . '_sd_' . ($this->end_date ?: 'end') . '.csv';

        return response()->streamDownload(function () use ($items, $daysCount) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'No', 'SKU', 'Barcode Utama', 'Multi Barcode', 'Nama Produk',
                'Kategori', 'Supplier', "Penjualan ({$daysCount} Hari)", 'ADS (Laju/Hari)',
                'Total Omset (Rp)', 'Stok Fisik (Pcs)', 'Modal Tertahan (Rp)',
                'DOH (Hari)', 'Status Kuadran', 'Rekomendasi Tindakan'
            ]);

            foreach ($items as $idx => $it) {
                $multi = !empty($it['additional_barcodes']) ? implode(', ', $it['additional_barcodes']) : '-';
                fputcsv($handle, [
                    $idx + 1, $it['sku'] ?? '-', $it['barcode'] ?? '-', $multi,
                    $it['product_name'] ?? '-', $it['category_name'] ?? '-', $it['supplier_name'] ?? '-',
                    $it['qty_sold'] ?? 0, $it['ads'] ?? 0, $it['revenue'] ?? 0,
                    $it['current_stock'] ?? 0, $it['capital_tied'] ?? 0, $it['doh_display'] ?? '-',
                    $it['quadrant_label'] ?? '-', $it['recommended_action'] ?? '-'
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function getBranchesProperty() { return Branch::where('is_active', true)->orderBy('name')->get(); }
    public function getSuppliersProperty() { return Supplier::where('is_active', true)->orderBy('name')->get(); }
    public function getCategoriesProperty() { return Category::orderBy('name')->get(); }
}
