<?php

namespace App\Filament\Pages;

use App\Models\Shift;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\ShiftExporter;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class LaporanShiftKasir extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Laporan Shift Kasir';
    protected static ?string $title = 'Laporan Shift Kasir (Rekap Kas)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shift::query()
                    ->when(Auth::user()->branch_id !== null, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                    ->with(['branch', 'user', 'terminal'])
            )
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('terminal.name')
                    ->label('Kassa'),
                TextColumn::make('user.name')
                    ->label('Kasir'),
                TextColumn::make('shift_name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'OPEN' ? 'success' : 'gray'),
                TextColumn::make('starting_cash')
                    ->label('Kas Awal')
                    ->money('IDR', true),
                TextColumn::make('total_sales')
                    ->label('Pendapatan')
                    ->money('IDR', true)
                    ->state(fn (Shift $record): float => ($record->total_cash_sales ?? 0) + ($record->total_card_sales ?? 0)),
                TextColumn::make('expected_ending_cash')
                    ->label('Kas Harapan')
                    ->money('IDR', true)
                    ->state(fn (Shift $record): float => ($record->starting_cash ?? 0) + ($record->total_cash_sales ?? 0)),
                TextColumn::make('actual_cash')
                    ->label('Kas Aktual')
                    ->money('IDR', true),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->money('IDR', true)
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('start_time', 'start_time'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name'),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-shift-kasir',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                ExportAction::make()
                    ->exporter(ShiftExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }
}






