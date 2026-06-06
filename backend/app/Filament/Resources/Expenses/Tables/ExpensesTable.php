<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference_number')->label('No. Ref')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('expense_date')->label('Tanggal')->date()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('expenseAccount.name')->label('Kategori (Beban/Prive)')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('paymentAccount.name')->label('Sumber Dana (Kas/Bank)'),
                \Filament\Tables\Columns\TextColumn::make('amount')->label('Nominal')->numeric()->money('IDR', true)->sortable(),
                \Filament\Tables\Columns\TextColumn::make('description')->label('Keterangan')->limit(30),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()?->branch_id === null),
                \App\Filament\Filters\DateFilterHelper::make('expense_date'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('cetak_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\Expense $record) => route('print.document', ['type' => 'expense', 'id' => $record->id]))
                    ->openUrlInNewTab(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_laporan')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'expense_list',
                        'tableFilters' => $livewire->tableFilters
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
