<?php

namespace App\Filament\Resources\ListingProduks\Tables;

use App\Filament\Pages\PerformaListing;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ListingProduksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['supplier', 'supplierDivision', 'products', 'listingDeduction']);
                return $query;
            })
            ->columns([
                TextColumn::make('listing_number')
                    ->label('No. Listing')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier.name')
                    ->label('Pemasok')
                    ->description(fn (ProductListing $record) => $record->supplier?->is_consignment ? 'Konsinyasi (Titip Jual)' : 'Beli Putus')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('products_count')
                    ->label('Item Produk')
                    ->state(function (ProductListing $record) {
                        $count = $record->products->count();
                        $names = $record->products->pluck('name')->join(', ');
                        return $count . ' Produk: ' . Str::limit($names, 35);
                    })
                    ->tooltip(fn (ProductListing $record) => $record->products->map(fn ($p) => "• {$p->sku} - {$p->name}")->join("\n"))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('listing_fee')
                    ->label('Total Listing Fee')
                    ->money('IDR')
                    ->sortable()
                    ->description(function (ProductListing $record) {
                        if ($record->listing_fee <= 0) {
                            return 'Tanpa Biaya (Rp 0)';
                        }
                        $deduction = $record->listingDeduction;
                        if (!$deduction) return 'Belum Dicatat';
                        return 'Klaim: ' . $deduction->status;
                    }),

                TextColumn::make('status')
                    ->label('Status Listing')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'TRIAL' => 'Uji Coba (Trial)',
                        'PASSED' => 'Lolos / Rollout',
                        'DELISTED' => 'Dihentikan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'TRIAL' => 'warning',
                        'PASSED' => 'success',
                        'DELISTED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('trial_period')
                    ->label('Sisa Masa Uji Coba')
                    ->state(function (ProductListing $record): string {
                        if ($record->status !== 'TRIAL') {
                            return match ($record->status) {
                                'PASSED' => 'Selesai (Lolos)',
                                'DELISTED' => 'Selesai (Dihentikan)',
                                default => '-',
                            };
                        }

                        $remaining = $record->days_remaining;
                        if ($remaining === null) return '-';
                        if ($remaining < 0) return 'Jatuh Tempo (' . abs($remaining) . ' hari lalu)';
                        if ($remaining === 0) return 'Hari Ini Terakhir';
                        return 'Sisa ' . $remaining . ' Hari';
                    })
                    ->badge()
                    ->color(function (ProductListing $record): string {
                        if ($record->status !== 'TRIAL') return 'gray';
                        $remaining = $record->days_remaining;
                        if ($remaining === null) return 'gray';
                        if ($remaining < 0) return 'danger';
                        if ($remaining <= 14) return 'warning';
                        return 'success';
                    }),

                TextColumn::make('allowed_branches_display')
                    ->label('Cabang Pilot')
                    ->state(function (ProductListing $record) {
                        $branchIds = (array) ($record->allowed_branch_ids ?? []);
                        if (empty($branchIds)) return 'Belum Ditentukan';
                        $totalActiveBranches = Branch::where('is_active', true)->count();
                        if (count($branchIds) >= $totalActiveBranches && $totalActiveBranches > 0) {
                            return 'Semua Cabang (' . count($branchIds) . ')';
                        }
                        $names = Branch::whereIn('id', $branchIds)->pluck('name')->join(', ');
                        return count($branchIds) . ' Cabang: ' . Str::limit($names, 25);
                    })
                    ->tooltip(function (ProductListing $record) {
                        $branchIds = (array) ($record->allowed_branch_ids ?? []);
                        if (empty($branchIds)) return 'Belum ada cabang yang diizinkan';
                        return Branch::whereIn('id', $branchIds)->pluck('name')->join(', ');
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->recordActions([
                Action::make('view_evaluation')
                    ->label('Evaluasi Performa')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('info')
                    ->url(fn (ProductListing $record) => PerformaListing::getUrl(['listing_id' => $record->id])),

                Action::make('manage_branches')
                    ->label('Kelola Cabang Order')
                    ->icon('heroicon-o-building-storefront')
                    ->color('gray')
                    ->modalHeading(fn (ProductListing $record) => "Kelola Cabang Berhak Order: {$record->listing_number}")
                    ->modalDescription('Tentukan cabang mana saja yang diizinkan untuk mengorder dan memajang produk-produk dalam perjanjian listing ini. Cabang yang baru dicentang akan otomatis dibuatkan data stoknya.')
                    ->modalWidth('2xl')
                    ->form([
                        CheckboxList::make('allowed_branch_ids')
                            ->label('Pilih Cabang yang Diizinkan Order')
                            ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                            ->columns(2)
                            ->default(fn (ProductListing $record) => (array) ($record->allowed_branch_ids ?? []))
                            ->required(),
                    ])
                    ->action(function (ProductListing $record, array $data) {
                        $selectedBranchIds = array_values(array_filter((array) ($data['allowed_branch_ids'] ?? [])));
                        $record->allowed_branch_ids = $selectedBranchIds;
                        $record->save();

                        $now = now();
                        foreach ($record->products as $product) {
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
                            ->title('Cabang Berhak Order Berhasil Diperbarui')
                            ->body("Produk pada listing '{$record->listing_number}' kini dapat diorder oleh " . count($selectedBranchIds) . " cabang.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}
