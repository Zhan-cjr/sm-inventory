<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Perusahaan/Organisasi')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Cabang'),
                TextInput::make('reference_number')
                    ->label('No. Referensi')
                    ->required(),
                DatePicker::make('entry_date')
                    ->label('Tanggal Jurnal')
                    ->required(),
                TextInput::make('description')
                    ->label('Keterangan'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Diposting',
                    ])
                    ->default('draft')
                    ->required(),
                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Repeater::make('lines')
                    ->relationship()
                    ->label('Baris Jurnal (Debit & Kredit)')
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('account_id')
                                ->relationship('account', 'name')
                                ->label('Akun (COA)')
                                ->searchable()
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('description')
                                ->label('Keterangan Baris')
                                ->columnSpan(1),
                            TextInput::make('debit')
                                ->label('Debit')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(1),
                            TextInput::make('credit')
                                ->label('Kredit')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(1),
                        ]),
                    ])
                    ->defaultItems(2)
                    ->columnSpanFull(),
            ]);
    }
}
