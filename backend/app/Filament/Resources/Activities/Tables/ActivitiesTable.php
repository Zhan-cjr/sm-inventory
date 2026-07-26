<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        $userBranchId = auth()->user()->branch_id ?? null;

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) use ($userBranchId) {
                if ($userBranchId) {
                    $stockIds = \App\Models\Stock::where('branch_id', $userBranchId)->pluck('id');
                    $txIds = \App\Models\Transaction::where('branch_id', $userBranchId)->pluck('id');

                    $query->where(function ($q) use ($userBranchId, $stockIds, $txIds) {
                        $q->where('causer_id', auth()->id())
                            ->orWhere(function ($subQ) use ($stockIds) {
                                $subQ->where('subject_type', 'App\Models\Stock')
                                    ->whereIn('subject_id', $stockIds);
                            })
                            ->orWhere(function ($subQ) use ($txIds) {
                                $subQ->where('subject_type', 'App\Models\Transaction')
                                    ->whereIn('subject_id', $txIds);
                            });
                    });
                }
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Kejadian')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Pelaku (User)')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->default('Sistem / Otomatis'),
                TextColumn::make('subject_type')
                    ->label('Modul (Area)')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'App\Models\PurchaseOrder' => 'Pesanan Pembelian',
                            'App\Models\GoodsReceipt' => 'Penerimaan Barang',
                            'App\Models\PurchasePayment' => 'Pembayaran Hutang',
                            'App\Models\Kontrabon' => 'Tukar Faktur (Kontrabon)',
                            'App\Models\User' => 'Data Pengguna',
                            'App\Models\Product' => 'Master Produk',
                            'App\Models\Stock' => 'Stok & Harga Cabang',
                            'App\Models\Supplier' => 'Data Supplier',
                            'App\Models\Branch' => 'Data Cabang',
                            'App\Models\Transaction' => 'Transaksi Penjualan',
                            default => class_basename($state),
                        };
                    })
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Aksi')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'created' => 'Data Baru (Dibuat)',
                            'updated' => 'Perubahan (Diedit)',
                            'deleted' => 'Dihapus',
                            default => ucfirst($state),
                        };
                    })
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => strtolower($state) === 'created',
                        'warning' => fn ($state) => strtolower($state) === 'updated',
                        'danger' => fn ($state) => strtolower($state) === 'deleted',
                    ])
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Keterangan Singkat')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Filter Jenis Tindakan')
                    ->options([
                        'created' => 'Data Baru Dibuat',
                        'updated' => 'Perubahan Data (Edit)',
                        'deleted' => 'Data Dihapus',
                    ]),

                Filter::make('price_changes')
                    ->label('Khusus Perubahan Harga')
                    ->query(function (Builder $query) {
                        $query->whereIn('subject_type', ['App\Models\Stock', 'App\Models\Product'])
                            ->where(function ($q) {
                                $q->where('properties', 'like', '%selling_price%')
                                    ->orWhere('properties', 'like', '%cost_price%')
                                    ->orWhere('properties', 'like', '%price%')
                                    ->orWhere('properties', 'like', '%harga_jual_1%')
                                    ->orWhere('properties', 'like', '%harga_jual_2%')
                                    ->orWhere('properties', 'like', '%harga_jual_3%');
                            });
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Detail'),
            ])
            ->toolbarActions([]);
    }
}
