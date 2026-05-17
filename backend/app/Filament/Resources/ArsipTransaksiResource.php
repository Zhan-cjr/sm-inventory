<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArsipTransaksiResource\Pages;
use App\Models\Transaction;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasBranchScope;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use App\Filament\Exports\TransactionExporter;

class ArsipTransaksiResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Arsip Transaksi';
    protected static ?string $pluralModelLabel = 'Arsip Transaksi';
    protected static ?string $modelLabel = 'Arsip Transaksi';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only schema if we want to show a view page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Kasir'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('IDR', true),
                Tables\Columns\TextColumn::make('is_voided')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Void/Batal' : 'Berhasil')
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('void_reason')
                    ->label('Alasan Void')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('void_date')
                    ->label('Tanggal Void')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_voided')
                    ->label('Status Transaksi')
                    ->placeholder('Semua Transaksi')
                    ->trueLabel('Transaksi Batal (Void)')
                    ->falseLabel('Transaksi Berhasil'),
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(TransactionExporter::class),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransactionExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArsipTransaksis::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Arsip is read-only
    }
}
