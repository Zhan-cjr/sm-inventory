<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
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
                //
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Detail'),
            ])
            ->toolbarActions([
                // No bulk actions allowed for logs
            ]);
    }
}
