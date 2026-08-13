<?php

namespace App\Filament\Resources\PurchaseReturns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Filters\DateFilterHelper;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->label('Nomor Retur')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('return_date')
                    ->label('Tanggal Retur')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Diajukan',
                        'approved' => 'Disetujui',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Nilai')
                    ->numeric()
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric()),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateFilterHelper::make('return_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (\App\Models\PurchaseReturn $record) => route('print.document', ['type' => 'purchase-return', 'ids' => [$record->id]]))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_daftar')
                    ->label('Cetak Daftar')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'retur-pembelian',
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
                    ]), true),
                \Filament\Actions\ExportAction::make('export_excel')
                    ->label('Export Excel')
                    ->exporter(\App\Filament\Exports\PurchaseReturnExporter::class)
                    ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx, \Filament\Actions\Exports\Enums\ExportFormat::Csv])
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->modalHeading('Pilih Kolom Export')
                    ->modalSubmitActionLabel('Proses Export'),
            ]);
    }
}
