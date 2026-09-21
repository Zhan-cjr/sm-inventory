<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Daftar Barang';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        $currentSupplierId = $this->getOwnerRecord()->id;
        $user = auth()->user();
        $userBranchId = $user?->branch_id;

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($currentSupplierId, $userBranchId) {
                if ($userBranchId) {
                    // User Cabang: Hanya tampilkan barang yang aktif dipasok oleh supplier ini ke cabangnya
                    $query->where(function ($q) use ($userBranchId, $currentSupplierId) {
                        $q->whereHas('stocks', function ($sq) use ($userBranchId, $currentSupplierId) {
                            $sq->where('branch_id', $userBranchId)->where('supplier_id', $currentSupplierId);
                        })
                        ->orWhere(function ($sq) use ($userBranchId, $currentSupplierId) {
                            $sq->where('products.supplier_id', $currentSupplierId)
                               ->whereDoesntHave('stocks', function ($stq) use ($userBranchId, $currentSupplierId) {
                                   $stq->where('branch_id', $userBranchId)
                                       ->whereNotNull('supplier_id')
                                       ->where('supplier_id', '!=', $currentSupplierId);
                               });
                        });
                    });
                } else {
                    // User Pusat: Tampilkan barang master atau barang yang di-override di cabang mana pun
                    $query->where(function ($q) use ($currentSupplierId) {
                        $q->where('products.supplier_id', $currentSupplierId)
                          ->orWhereHas('stocks', fn ($sq) => $sq->where('supplier_id', $currentSupplierId));
                    });
                }

                $query->with(['stocks.branch', 'supplierDivision']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplierDivision.name')
                    ->label('Sub Divisi')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Lingkup Pemasok')
                    ->state(function ($record) use ($currentSupplierId, $userBranchId) {
                        if ($userBranchId) {
                            $branchStock = $record->stocks->firstWhere('branch_id', $userBranchId);
                            if ($branchStock && $branchStock->supplier_id === $currentSupplierId) {
                                return 'Khusus Cabang Saya';
                            }
                            return 'Utama (Pusat)';
                        }

                        $isMaster = ($record->supplier_id === $currentSupplierId);
                        $branchStocks = $record->stocks->where('supplier_id', $currentSupplierId);
                        
                        if ($isMaster) {
                            $overrideStocks = $record->stocks->whereNotNull('supplier_id')->where('supplier_id', '!=', $currentSupplierId);
                            if ($overrideStocks->isNotEmpty()) {
                                return 'Utama (di-override di ' . $overrideStocks->pluck('branch.name')->filter()->join(', ') . ')';
                            }
                            return 'Utama (Semua Cabang)';
                        }
                        
                        if ($branchStocks->isNotEmpty()) {
                            return 'Khusus Cabang: ' . $branchStocks->pluck('branch.name')->filter()->join(', ');
                        }
                        
                        return '-';
                    })
                    ->badge()
                    ->color(function ($record) use ($currentSupplierId, $userBranchId) {
                        if ($userBranchId) {
                            $branchStock = $record->stocks->firstWhere('branch_id', $userBranchId);
                            return ($branchStock && $branchStock->supplier_id === $currentSupplierId) ? 'info' : 'success';
                        }
                        return $record->supplier_id === $currentSupplierId ? 'success' : 'info';
                    }),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter Cabang')
                    ->options(fn () => \App\Models\Branch::pluck('name', 'id'))
                    ->hidden(fn () => auth()->user()->branch_id !== null)
                    ->query(function (Builder $query, array $data) use ($currentSupplierId) {
                        $branchId = $data['value'] ?? null;
                        if (!$branchId) return $query;
                        
                        return $query->where(function ($q) use ($branchId, $currentSupplierId) {
                            $q->whereHas('stocks', function ($sq) use ($branchId, $currentSupplierId) {
                                $sq->where('branch_id', $branchId)->where('supplier_id', $currentSupplierId);
                            })
                            ->orWhere(function ($sq) use ($branchId, $currentSupplierId) {
                                $sq->where('products.supplier_id', $currentSupplierId)
                                   ->whereDoesntHave('stocks', function ($stq) use ($branchId, $currentSupplierId) {
                                       $stq->where('branch_id', $branchId)
                                           ->whereNotNull('supplier_id')
                                           ->where('supplier_id', '!=', $currentSupplierId);
                                   });
                            });
                        });
                    }),
                Tables\Filters\SelectFilter::make('supplier_division_id')
                    ->label('Sub Divisi')
                    ->options(function ($livewire) {
                        $currentSupplierId = $livewire->getOwnerRecord()->id;
                        return \App\Models\SupplierDivision::where('supplier_id', $currentSupplierId)
                            ->pluck('name', 'id');
                    }),
            ])
            ->headerActions([
                Action::make('tambahkan_barang')
                    ->label('Tambah Barang')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form(function ($livewire) use ($currentSupplierId) {
                        $user = auth()->user();
                        $isBranchUser = ($user->branch_id !== null);

                        $fields = [];

                        if (!$isBranchUser) {
                            $fields[] = Forms\Components\Radio::make('scope_type')
                                ->label('Cakupan Pemasok')
                                ->options([
                                    'branch' => 'Pemasok Khusus Cabang Tertentu',
                                    'global' => 'Pemasok Utama (Semua Cabang)',
                                ])
                                ->default(fn ($livewire) => !empty($livewire->tableFilters['branch_id']['value']) ? 'branch' : 'global')
                                ->live();

                            $fields[] = Forms\Components\Select::make('branch_id')
                                ->label('Pilih Cabang Target')
                                ->options(fn () => \App\Models\Branch::pluck('name', 'id'))
                                ->default(fn ($livewire) => $livewire->tableFilters['branch_id']['value'] ?? null)
                                ->visible(fn ($get) => $get('scope_type') === 'branch')
                                ->required(fn ($get) => $get('scope_type') === 'branch');
                        } else {
                            $branchName = $user->branch?->name ?? 'Cabang Anda';
                            $fields[] = Forms\Components\Placeholder::make('info')
                                ->label('Cakupan')
                                ->content("Menambahkan barang sebagai Pemasok Khusus untuk {$branchName}");

                            $fields[] = Forms\Components\Hidden::make('scope_type')
                                ->default('branch');

                            $fields[] = Forms\Components\Hidden::make('branch_id')
                                ->default($user->branch_id);
                        }

                        $fields[] = Forms\Components\Select::make('product_ids')
                            ->label('Pilih Barang')
                            ->multiple()
                            ->searchable()
                            ->options(function () {
                                return \App\Models\Product::orderBy('name')->pluck('name', 'id');
                            })
                            ->required();

                        $fields[] = Forms\Components\Select::make('supplier_division_id')
                            ->label('Tetapkan Sub Divisi')
                            ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                            ->options(function ($livewire) {
                                $currentSupplierId = $livewire->getOwnerRecord()->id;
                                return \App\Models\SupplierDivision::where('supplier_id', $currentSupplierId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable();

                        return $fields;
                    })
                    ->action(function (array $data, $livewire) {
                        $supplierId = $livewire->getOwnerRecord()->id;
                        $scopeType = $data['scope_type'] ?? 'global';

                        if ($scopeType === 'branch') {
                            $branchId = $data['branch_id'] ?? auth()->user()->branch_id;
                            foreach ($data['product_ids'] as $productId) {
                                \App\Models\Stock::updateOrCreate(
                                    ['branch_id' => $branchId, 'product_id' => $productId],
                                    [
                                        'supplier_id' => $supplierId,
                                        'supplier_division_id' => $data['supplier_division_id'] ?? null,
                                    ]
                                );
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil menetapkan barang ke pemasok khusus cabang')
                                ->success()
                                ->send();
                        } else {
                            \App\Models\Product::whereIn('id', $data['product_ids'])
                                ->update([
                                    'supplier_id' => $supplierId,
                                    'supplier_division_id' => $data['supplier_division_id'] ?? null,
                                ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil menambahkan barang sebagai pemasok utama')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('pindah_pemasok')
                    ->label('Pindah Pemasok / Sub Divisi')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->fillForm(function ($record, $livewire) {
                        $user = auth()->user();
                        $activeBranchFilter = $livewire->tableFilters['branch_id']['value'] ?? null;
                        $defaultBranch = $user->branch_id ?? $activeBranchFilter;
                        $defaultScope = $defaultBranch ? 'branch' : 'global';

                        return [
                            'scope_type' => $defaultScope,
                            'branch_id' => $defaultBranch,
                            'new_supplier_id' => null,
                            'new_supplier_division_id' => null,
                        ];
                    })
                    ->form(function ($record, $livewire) use ($currentSupplierId) {
                        $user = auth()->user();
                        $isBranchUser = ($user->branch_id !== null);

                        $fields = [];

                        if (!$isBranchUser) {
                            $hasOverrides = $record->stocks->where('supplier_id', $currentSupplierId)->isNotEmpty();
                            
                            $scopeOptions = [
                                'branch' => 'Khusus Cabang Tertentu',
                                'global' => 'Pemasok Utama (Semua Cabang)',
                            ];

                            if ($hasOverrides) {
                                $scopeOptions['revert_to_global'] = 'Kembalikan ke Pemasok Utama Produk (Hapus Override Cabang)';
                            }

                            $fields[] = Forms\Components\Radio::make('scope_type')
                                ->label('Cakupan Pemindahan')
                                ->options($scopeOptions)
                                ->default(fn ($livewire) => !empty($livewire->tableFilters['branch_id']['value']) ? 'branch' : 'global')
                                ->live()
                                ->required();

                            $fields[] = Forms\Components\Select::make('branch_id')
                                ->label('Pilih Cabang Target')
                                ->options(fn () => \App\Models\Branch::pluck('name', 'id'))
                                ->default(fn ($livewire) => $livewire->tableFilters['branch_id']['value'] ?? null)
                                ->visible(fn ($get) => in_array($get('scope_type'), ['branch', 'revert_to_global']))
                                ->required(fn ($get) => in_array($get('scope_type'), ['branch', 'revert_to_global']))
                                ->searchable();
                        } else {
                            // Branch User: Hanya bisa memindahkan untuk cabangnya
                            $branchName = $user->branch?->name ?? 'Cabang Anda';
                            $branchStock = $record->stocks->firstWhere('branch_id', $user->branch_id);
                            $isOverriddenInBranch = ($branchStock && $branchStock->supplier_id === $currentSupplierId);

                            $scopeOptions = [
                                'branch' => "Pemasok Khusus {$branchName}",
                            ];
                            if ($isOverriddenInBranch) {
                                $scopeOptions['revert_to_global'] = 'Kembalikan ke Pemasok Utama Produk (Hapus Khusus Cabang)';
                            }

                            $fields[] = Forms\Components\Radio::make('scope_type')
                                ->label('Tindakan')
                                ->options($scopeOptions)
                                ->default('branch')
                                ->live()
                                ->required();

                            $fields[] = Forms\Components\Hidden::make('branch_id')
                                ->default($user->branch_id);
                        }

                        $fields[] = Forms\Components\Select::make('new_supplier_id')
                            ->label('Pilih Pemasok Tujuan')
                            ->options(function () use ($currentSupplierId) {
                                return \App\Models\Supplier::where('is_active', true)
                                    ->where('id', '!=', $currentSupplierId)
                                    ->pluck('name', 'id');
                            })
                            ->visible(fn ($get) => $get('scope_type') !== 'revert_to_global')
                            ->required(fn ($get) => $get('scope_type') !== 'revert_to_global')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('new_supplier_division_id', null));

                        $fields[] = Forms\Components\Select::make('new_supplier_division_id')
                            ->label('Pilih Sub Divisi Tujuan')
                            ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                            ->options(function (callable $get) {
                                $supplierId = $get('new_supplier_id');
                                if (!$supplierId) return [];
                                return \App\Models\SupplierDivision::where('supplier_id', $supplierId)->pluck('name', 'id');
                            })
                            ->visible(fn ($get) => $get('scope_type') !== 'revert_to_global')
                            ->searchable();

                        return $fields;
                    })
                    ->action(function (array $data, $record, $livewire) use ($currentSupplierId) {
                        $scopeType = $data['scope_type'] ?? 'global';
                        $targetBranchId = $data['branch_id'] ?? auth()->user()->branch_id;

                        if ($scopeType === 'revert_to_global') {
                            // Hapus override cabang (jadikan null agar ikut master produk)
                            \App\Models\Stock::where('product_id', $record->id)
                                ->where('branch_id', $targetBranchId)
                                ->update([
                                    'supplier_id' => null,
                                    'supplier_division_id' => null,
                                ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil mengembalikan barang mengikuti Pemasok Utama produk')
                                ->success()
                                ->send();
                            return;
                        }

                        if ($scopeType === 'branch') {
                            // Update atau buat stok override untuk cabang target
                            \App\Models\Stock::updateOrCreate(
                                ['product_id' => $record->id, 'branch_id' => $targetBranchId],
                                [
                                    'supplier_id' => $data['new_supplier_id'],
                                    'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                                ]
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil memindahkan pemasok khusus cabang')
                                ->success()
                                ->send();
                            return;
                        }

                        // Global update: Ubah supplier master produk
                        $record->update([
                            'supplier_id' => $data['new_supplier_id'],
                            'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil memindahkan Pemasok Utama (Semua Cabang)')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('pindah_pemasok_bulk')
                        ->label('Pindah Pemasok / Sub Divisi (Terpilih)')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->fillForm(function ($livewire) {
                            $user = auth()->user();
                            $activeBranchFilter = $livewire->tableFilters['branch_id']['value'] ?? null;
                            $defaultBranch = $user->branch_id ?? $activeBranchFilter;
                            return [
                                'scope_type' => $defaultBranch ? 'branch' : 'global',
                                'branch_id' => $defaultBranch,
                            ];
                        })
                        ->form(function ($livewire) use ($currentSupplierId) {
                            $user = auth()->user();
                            $isBranchUser = ($user->branch_id !== null);

                            $fields = [];

                            if (!$isBranchUser) {
                                $fields[] = Forms\Components\Radio::make('scope_type')
                                    ->label('Cakupan Pemindahan')
                                    ->options([
                                        'branch' => 'Khusus Cabang Tertentu',
                                        'global' => 'Pemasok Utama (Semua Cabang)',
                                        'revert_to_global' => 'Kembalikan ke Pemasok Utama Produk (Hapus Override Cabang)',
                                    ])
                                    ->default(fn ($livewire) => !empty($livewire->tableFilters['branch_id']['value']) ? 'branch' : 'global')
                                    ->live()
                                    ->required();

                                $fields[] = Forms\Components\Select::make('branch_id')
                                    ->label('Pilih Cabang Target')
                                    ->options(fn () => \App\Models\Branch::pluck('name', 'id'))
                                    ->default(fn ($livewire) => $livewire->tableFilters['branch_id']['value'] ?? null)
                                    ->visible(fn ($get) => in_array($get('scope_type'), ['branch', 'revert_to_global']))
                                    ->required(fn ($get) => in_array($get('scope_type'), ['branch', 'revert_to_global']))
                                    ->searchable();
                            } else {
                                $branchName = $user->branch?->name ?? 'Cabang Anda';
                                $fields[] = Forms\Components\Radio::make('scope_type')
                                    ->label('Tindakan')
                                    ->options([
                                        'branch' => "Pemasok Khusus {$branchName}",
                                        'revert_to_global' => 'Kembalikan ke Pemasok Utama Produk (Hapus Khusus Cabang)',
                                    ])
                                    ->default('branch')
                                    ->live()
                                    ->required();

                                $fields[] = Forms\Components\Hidden::make('branch_id')
                                    ->default($user->branch_id);
                            }

                            $fields[] = Forms\Components\Select::make('new_supplier_id')
                                ->label('Pilih Pemasok Tujuan')
                                ->options(function () use ($currentSupplierId) {
                                    return \App\Models\Supplier::where('is_active', true)
                                        ->where('id', '!=', $currentSupplierId)
                                        ->pluck('name', 'id');
                                })
                                ->visible(fn ($get) => $get('scope_type') !== 'revert_to_global')
                                ->required(fn ($get) => $get('scope_type') !== 'revert_to_global')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('new_supplier_division_id', null));

                            $fields[] = Forms\Components\Select::make('new_supplier_division_id')
                                ->label('Pilih Sub Divisi Tujuan')
                                ->placeholder('Tanpa Sub Divisi (Pemasok Global)')
                                ->options(function (callable $get) {
                                    $supplierId = $get('new_supplier_id');
                                    if (!$supplierId) return [];
                                    return \App\Models\SupplierDivision::where('supplier_id', $supplierId)->pluck('name', 'id');
                                })
                                ->visible(fn ($get) => $get('scope_type') !== 'revert_to_global')
                                ->searchable();

                            return $fields;
                        })
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            $scopeType = $data['scope_type'] ?? 'global';
                            $targetBranchId = $data['branch_id'] ?? auth()->user()->branch_id;

                            if ($scopeType === 'revert_to_global') {
                                foreach ($records as $record) {
                                    \App\Models\Stock::where('product_id', $record->id)
                                        ->where('branch_id', $targetBranchId)
                                        ->update([
                                            'supplier_id' => null,
                                            'supplier_division_id' => null,
                                        ]);
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil mengembalikan barang terpilih ke Pemasok Utama')
                                    ->success()
                                    ->send();
                                return;
                            }

                            if ($scopeType === 'branch') {
                                foreach ($records as $record) {
                                    \App\Models\Stock::updateOrCreate(
                                        ['product_id' => $record->id, 'branch_id' => $targetBranchId],
                                        [
                                            'supplier_id' => $data['new_supplier_id'],
                                            'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                                        ]
                                    );
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil memindahkan pemasok khusus cabang untuk barang terpilih')
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Global update: Master products
                            foreach ($records as $record) {
                                $record->update([
                                    'supplier_id' => $data['new_supplier_id'],
                                    'supplier_division_id' => $data['new_supplier_division_id'] ?? null,
                                ]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Berhasil memindahkan Pemasok Utama untuk barang terpilih')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
