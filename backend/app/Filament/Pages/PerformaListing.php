<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ListingProduks\ListingProdukResource;
use App\Models\Branch;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Stock;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PerformaListing extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Performa Listing';
    protected static ?string $title = 'Dashboard Performa Listing';
    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'performa-listing';

    protected string $view = 'filament.pages.performa-listing';

    public ?string $listing_id = null;
    public string $active_tab = 'breakdown'; // 'breakdown', 'product_summary', 'branch_summary', 'recent_transactions', 'contract_details'
    public string $search = '';
    public string $selected_branch_id = 'ALL';
    public string $performance_filter = 'ALL'; // ALL, HIGH (>=70%), MODERATE (40-69%), LOW (<40%), OUT_OF_STOCK

    protected $queryString = [
        'listing_id' => ['except' => ''],
        'active_tab' => ['except' => 'breakdown'],
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole(['superadmin', 'super_admin', 'super-admin'])) {
            return true;
        }

        return true;
    }

    public function mount(): void
    {
        $requestedId = request()->query('listing_id');
        if ($requestedId && ProductListing::where('id', $requestedId)->exists()) {
            $this->listing_id = $requestedId;
        } else {
            // Default ke listing trial aktif terbaru jika ada, atau listing terbaru
            $defaultListing = ProductListing::where('status', 'TRIAL')
                ->latest()
                ->first() ?? ProductListing::latest()->first();

            $this->listing_id = $defaultListing?->id;
        }

        $tab = request()->query('active_tab');
        if ($tab && in_array($tab, ['breakdown', 'product_summary', 'branch_summary', 'recent_transactions', 'contract_details'])) {
            $this->active_tab = $tab;
        }
    }

    public function switchListing(string $id): void
    {
        $this->listing_id = $id;
        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selected_branch_id = 'ALL';
        $this->performance_filter = 'ALL';
    }

    public function getListingProperty(): ?ProductListing
    {
        if (!$this->listing_id) {
            return null;
        }

        return ProductListing::with([
            'supplier',
            'supplierDivision',
            'products',
            'listingDeduction',
            'organization',
        ])->find($this->listing_id);
    }

    public function getAllListingsProperty()
    {
        return ProductListing::with(['supplier', 'products'])
            ->latest()
            ->get();
    }

    public function getMetricsProperty(): array
    {
        $listing = $this->listing;
        if (!$listing) {
            return [
                'total_products' => 0,
                'total_bought' => 0,
                'total_sold' => 0,
                'total_stock' => 0,
                'total_omset' => 0,
                'total_stock_value' => 0,
                'sell_through' => 0,
                'breakdown' => [],
            ];
        }

        $metrics = $listing->getPerformanceMetrics();

        // Hitung estimasi nilai sisa stok (valuasi HPP / harga beli modal)
        $totalStockValue = 0;
        foreach ($listing->products as $product) {
            $cost = (float) ($product->cost_price ?? 0);
            $stockQty = (float) Stock::where('product_id', $product->id)
                ->whereIn('branch_id', (array) ($listing->allowed_branch_ids ?? []))
                ->sum('quantity_on_hand');
            $totalStockValue += ($stockQty * $cost);
        }
        $metrics['total_stock_value'] = $totalStockValue;

        // Terapkan filter lokal untuk breakdown jika ada
        if (!empty($metrics['breakdown'])) {
            $filteredBreakdown = $metrics['breakdown'];

            // Filter cabang
            if ($this->selected_branch_id !== 'ALL') {
                $filteredBreakdown = array_filter($filteredBreakdown, function ($row) {
                    $branch = Branch::where('id', $this->selected_branch_id)->first();
                    return $branch && $row['branch_name'] === $branch->name;
                });
            }

            // Filter pencarian SKU / Nama
            if (!empty(trim($this->search))) {
                $searchTerm = strtolower(trim($this->search));
                $filteredBreakdown = array_filter($filteredBreakdown, function ($row) use ($searchTerm) {
                    return str_contains(strtolower($row['product_sku']), $searchTerm) ||
                           str_contains(strtolower($row['product_name']), $searchTerm);
                });
            }

            // Filter performa
            if ($this->performance_filter === 'HIGH') {
                $filteredBreakdown = array_filter($filteredBreakdown, fn ($row) => $row['sell_through'] >= 70);
            } elseif ($this->performance_filter === 'MODERATE') {
                $filteredBreakdown = array_filter($filteredBreakdown, fn ($row) => $row['sell_through'] >= 40 && $row['sell_through'] < 70);
            } elseif ($this->performance_filter === 'LOW') {
                $filteredBreakdown = array_filter($filteredBreakdown, fn ($row) => $row['sell_through'] < 40 && $row['current_stock'] > 0);
            } elseif ($this->performance_filter === 'OUT_OF_STOCK') {
                $filteredBreakdown = array_filter($filteredBreakdown, fn ($row) => $row['current_stock'] <= 0 && $row['qty_received'] > 0);
            }

            $metrics['filtered_breakdown'] = array_values($filteredBreakdown);
        } else {
            $metrics['filtered_breakdown'] = [];
        }

        return $metrics;
    }

    public function getProductsSummaryProperty(): array
    {
        $listing = $this->listing;
        if (!$listing) {
            return [];
        }

        $metrics = $listing->getPerformanceMetrics();
        $rawBreakdown = $metrics['breakdown'] ?? [];

        $summary = [];
        foreach ($listing->products as $prod) {
            $sku = $prod->sku;
            $rowsForProd = array_filter($rawBreakdown, fn ($r) => $r['product_sku'] === $sku);

            $totReceived = array_sum(array_column($rowsForProd, 'qty_received'));
            $totSold = array_sum(array_column($rowsForProd, 'qty_sold'));
            $totStock = array_sum(array_column($rowsForProd, 'current_stock'));
            $totOmset = array_sum(array_column($rowsForProd, 'omset'));

            $base = ($totReceived > 0) ? $totReceived : ($totSold + $totStock);
            $st = $base > 0 ? round(($totSold / $base) * 100, 1) : 0;

            // Cari cabang dengan penjualan terbanyak untuk produk ini
            $bestBranch = '-';
            $maxSold = 0;
            foreach ($rowsForProd as $r) {
                if ($r['qty_sold'] > $maxSold) {
                    $maxSold = $r['qty_sold'];
                    $bestBranch = $r['branch_name'] . ' (' . number_format($r['qty_sold']) . ' pcs)';
                }
            }

            $summary[] = [
                'id' => $prod->id,
                'sku' => $prod->sku,
                'name' => $prod->name,
                'cost_price' => $prod->cost_price,
                'selling_price' => $prod->selling_price,
                'qty_received' => $totReceived,
                'qty_sold' => $totSold,
                'current_stock' => $totStock,
                'sell_through' => $st,
                'omset' => $totOmset,
                'best_branch' => $bestBranch,
            ];
        }

        // Urutkan dari omset terbesar
        usort($summary, fn ($a, $b) => $b['omset'] <=> $a['omset']);

        return $summary;
    }

    public function getBranchesSummaryProperty(): array
    {
        $listing = $this->listing;
        if (!$listing) {
            return [];
        }

        $metrics = $listing->getPerformanceMetrics();
        $rawBreakdown = $metrics['breakdown'] ?? [];

        $branchIds = (array) ($listing->allowed_branch_ids ?? []);
        $branches = Branch::whereIn('id', $branchIds)->get();

        $summary = [];
        foreach ($branches as $branch) {
            $rowsForBranch = array_filter($rawBreakdown, fn ($r) => $r['branch_name'] === $branch->name);

            $totReceived = array_sum(array_column($rowsForBranch, 'qty_received'));
            $totSold = array_sum(array_column($rowsForBranch, 'qty_sold'));
            $totStock = array_sum(array_column($rowsForBranch, 'current_stock'));
            $totOmset = array_sum(array_column($rowsForBranch, 'omset'));

            $base = ($totReceived > 0) ? $totReceived : ($totSold + $totStock);
            $st = $base > 0 ? round(($totSold / $base) * 100, 1) : 0;

            // Produk terlaris di cabang ini
            $bestProd = '-';
            $maxSold = 0;
            foreach ($rowsForBranch as $r) {
                if ($r['qty_sold'] > $maxSold) {
                    $maxSold = $r['qty_sold'];
                    $bestProd = $r['product_name'] . ' (' . number_format($r['qty_sold']) . ' pcs)';
                }
            }

            $summary[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city ?? '-',
                'qty_received' => $totReceived,
                'qty_sold' => $totSold,
                'current_stock' => $totStock,
                'sell_through' => $st,
                'omset' => $totOmset,
                'best_product' => $bestProd,
            ];
        }

        // Urutkan dari omset terbesar
        usort($summary, fn ($a, $b) => $b['omset'] <=> $a['omset']);

        return $summary;
    }

    public function getRecentTransactionsProperty()
    {
        $listing = $this->listing;
        if (!$listing) {
            return collect();
        }

        $productIds = $listing->products->pluck('id')->toArray();
        $branchIds = (array) ($listing->allowed_branch_ids ?? []);
        $startDate = $listing->trial_start_date ?? $listing->created_at;

        if (empty($productIds) || empty($branchIds)) {
            return collect();
        }

        return TransactionItem::with(['transaction', 'transaction.branch', 'transaction.cashier', 'product'])
            ->whereIn('product_id', $productIds)
            ->whereHas('transaction', function ($q) use ($branchIds, $startDate) {
                $q->whereIn('branch_id', $branchIds)
                  ->where('is_voided', false)
                  ->where('created_at', '>=', $startDate);
            })
            ->latest()
            ->take(30)
            ->get();
    }

    protected function getViewData(): array
    {
        return [
            'listing' => $this->listing,
            'allListings' => $this->allListings,
            'metrics' => $this->metrics,
            'productsSummary' => $this->productsSummary,
            'branchesSummary' => $this->branchesSummary,
            'recentTransactions' => $this->recentTransactions,
            'remaining' => $this->listing?->days_remaining,
        ];
    }

    protected function getHeaderActions(): array
    {
        $listing = $this->listing;

        if (!$listing) {
            return [
                Action::make('create_listing')
                    ->label('Buat Listing Baru')
                    ->icon('heroicon-o-plus')
                    ->url(ListingProdukResource::getUrl('create')),
            ];
        }

        $actions = [];

        // Tombol Evaluasi Kelulusan / Rollout
        if ($listing->status === 'TRIAL') {
            $actions[] = Action::make('luluskan_rollout')
                ->label('Luluskan & Rollout Cabang')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->modalHeading("Luluskan Uji Coba: {$listing->listing_number}")
                ->modalDescription("Produk dalam listing ini akan ditetapkan berstatus LULUS (PASSED). Pilih cabang-cabang yang ingin dibuka izin ordernya.")
                ->modalWidth('2xl')
                ->form([
                    CheckboxList::make('rollout_branches')
                        ->label('Pilih Cabang untuk Rollout (Buka Izin Order)')
                        ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Branch::where('is_active', true)->pluck('id')->toArray())
                        ->columns(2)
                        ->required()
                        ->helperText('Pilih cabang-cabang yang akan dibuka akses ordernya (bisa sebagian atau seluruh cabang).'),

                    Textarea::make('notes')
                        ->label('Catatan Kelulusan (Opsional)')
                        ->placeholder('Contoh: Terbukti memiliki sell-through tinggi dan disetujui rollout nasional.'),
                ])
                ->action(function (array $data) use ($listing) {
                    $rolloutBranchIds = array_values(array_filter((array) ($data['rollout_branches'] ?? [])));

                    $listing->status = 'PASSED';
                    $listing->allowed_branch_ids = $rolloutBranchIds;
                    if (!empty($data['notes'])) {
                        $listing->notes = ($listing->notes ? $listing->notes . "\n\n" : '') .
                            "[Evaluasi Lulus " . date('d/m/Y') . "]: " . $data['notes'];
                    }
                    $listing->save();

                    $now = now();
                    foreach ($listing->products as $product) {
                        $product->listing_status = 'PASSED';
                        $product->allowed_branch_ids = $rolloutBranchIds;
                        $product->save();

                        foreach ($rolloutBranchIds as $branchId) {
                            Stock::firstOrCreate(
                                [
                                    'branch_id' => $branchId,
                                    'product_id' => $product->id,
                                ],
                                [
                                    'quantity_on_hand' => 0,
                                    'quantity_reserved' => 0,
                                    'cost_price' => $product->cost_price,
                                    'cost_price_tax' => $product->cost_price_tax,
                                    'selling_price' => $product->selling_price,
                                    'margin_gol_1' => $product->margin_gol_1,
                                    'harga_jual_1' => $product->harga_jual_1,
                                    'qty_min_gol_1' => $product->qty_min_gol_1 ?? 1,
                                    'is_active' => true,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]
                            );
                        }
                    }

                    Cache::forget('ecommerce_products_all');
                    foreach ($rolloutBranchIds as $branchId) {
                        Cache::forget('ecommerce_products_' . $branchId);
                        Cache::forget('pos_products_json_gz_branch_' . $branchId);
                    }

                    Notification::make()
                        ->title('Perjanjian Listing Berhasil Diluluskan!')
                        ->body("Produk pada listing '{$listing->listing_number}' kini aktif di " . count($rolloutBranchIds) . " cabang.")
                        ->success()
                        ->send();

                    $this->dispatch('$refresh');
                });

            // Tombol Hentikan / Delist
            $actions[] = Action::make('hentikan_delist')
                ->label('Hentikan / Delist')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading("Hentikan Perjanjian Listing: {$listing->listing_number}")
                ->modalDescription('Apakah Anda yakin ingin menghentikan seluruh produk dalam perjanjian listing ini? Status akan diubah menjadi DELISTED dan dinonaktifkan dari pemesanan cabang.')
                ->action(function () use ($listing) {
                    $listing->status = 'DELISTED';
                    $listing->save();

                    foreach ($listing->products as $product) {
                        $product->listing_status = 'DELISTED';
                        $product->is_active = false;
                        $product->save();
                    }

                    Notification::make()
                        ->title('Perjanjian Listing Dihentikan (Delisted)')
                        ->body("Seluruh produk pada listing '{$listing->listing_number}' telah dinonaktifkan.")
                        ->warning()
                        ->send();

                    $this->dispatch('$refresh');
                });
        }

        // Tombol Kelola Cabang Pilot
        $actions[] = Action::make('manage_branches')
            ->label('Kelola Cabang Pilot')
            ->icon('heroicon-o-building-storefront')
            ->color('gray')
            ->modalHeading("Kelola Cabang Pilot: {$listing->listing_number}")
            ->modalDescription('Pilih cabang mana saja yang berhak mengorder dan memajang produk selama masa uji coba ini.')
            ->modalWidth('2xl')
            ->form([
                CheckboxList::make('allowed_branch_ids')
                    ->label('Pilih Cabang Pilot')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                    ->columns(2)
                    ->default(fn () => (array) ($listing->allowed_branch_ids ?? []))
                    ->required(),
            ])
            ->action(function (array $data) use ($listing) {
                $selectedBranchIds = array_values(array_filter((array) ($data['allowed_branch_ids'] ?? [])));
                $listing->allowed_branch_ids = $selectedBranchIds;
                $listing->save();

                $now = now();
                foreach ($listing->products as $product) {
                    $product->allowed_branch_ids = $selectedBranchIds;
                    $product->save();

                    foreach ($selectedBranchIds as $branchId) {
                        Stock::firstOrCreate(
                            [
                                'branch_id' => $branchId,
                                'product_id' => $product->id,
                            ],
                            [
                                'quantity_on_hand' => 0,
                                'quantity_reserved' => 0,
                                'cost_price' => $product->cost_price,
                                'cost_price_tax' => $product->cost_price_tax,
                                'selling_price' => $product->selling_price,
                                'margin_gol_1' => $product->margin_gol_1,
                                'harga_jual_1' => $product->harga_jual_1,
                                'qty_min_gol_1' => $product->qty_min_gol_1 ?? 1,
                                'is_active' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                }

                Cache::forget('ecommerce_products_all');
                foreach ($selectedBranchIds as $branchId) {
                    Cache::forget('ecommerce_products_' . $branchId);
                    Cache::forget('pos_products_json_gz_branch_' . $branchId);
                }

                Notification::make()
                    ->title('Cabang Pilot Berhasil Diperbarui')
                    ->body("Produk pada listing '{$listing->listing_number}' kini aktif di " . count($selectedBranchIds) . " cabang.")
                    ->success()
                    ->send();

                $this->dispatch('$refresh');
            });

        // Tombol Edit Listing
        $actions[] = Action::make('edit_listing')
            ->label('Edit Listing')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->url(ListingProdukResource::getUrl('edit', ['record' => $listing->id]));

        // Tombol Daftar Listing
        $actions[] = Action::make('all_listings')
            ->label('Daftar Listing')
            ->icon('heroicon-o-list-bullet')
            ->color('gray')
            ->url(ListingProdukResource::getUrl('index'));

        return $actions;
    }
}
