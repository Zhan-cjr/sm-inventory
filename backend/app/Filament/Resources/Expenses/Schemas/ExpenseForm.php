<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Pengeluaran')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('expense_date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),

                        \Filament\Forms\Components\Select::make('expense_account_id')
                            ->label('Kategori Pengeluaran / Prive')
                            ->options(\App\Models\Account::whereIn('type', ['expense', 'equity'])
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        \Filament\Forms\Components\Select::make('payment_account_id')
                            ->label('Sumber Dana (Kas / Bank)')
                            ->options(\App\Models\Account::where('type', 'asset')
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        \Filament\Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Select::make('branch_id')
                            ->label('Cabang')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->branch_id)
                            ->disabled(fn () => auth()->user()?->branch_id !== null)
                            ->dehydrated()
                            ->columnSpanFull(),
                        
                        \Filament\Forms\Components\Hidden::make('organization_id')
                            ->default(fn () => auth()->user()?->organization_id ?? \App\Models\Organization::first()?->id),

                        \Filament\Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ])->columns(2),
            ]);
    }
}
