<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\LabaRugiExporter;
use Illuminate\Support\Facades\Auth;

class LaporanLabaRugi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Laporan Laba Rugi';
    protected static ?string $title = 'Laporan Laba Rugi (Kotor)';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Arsip';

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('is_voided', false)
                    ->with(['branch', 'items.product'])
            )
            ->columns([
                TextColumn::make('local_transaction_id')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('final_amount')
                    ->label('Penjualan Bersih')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->money('IDR', true)->label('Total Penjualan'))
                    ->sortable(),
                TextColumn::make('cogs')
                    ->label('Total HPP')
                    ->money('IDR', true)
                    ->state(fn (Transaction $record): float => $record->cogs)
                    // Note: Cannot summarize computed column easily with native Sum without custom summarizer, but we can do it via a custom Query summarizer if needed.
                    // For now, we'll display it per row.
                    ->sortable(),
                TextColumn::make('gross_profit')
                    ->label('Laba Kotor')
                    ->money('IDR', true)
                    ->state(fn (Transaction $record): float => $record->gross_profit)
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(LabaRugiExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}
