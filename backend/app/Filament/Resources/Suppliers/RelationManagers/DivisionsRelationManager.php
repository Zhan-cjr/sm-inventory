<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class DivisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'divisions';

    protected static ?string $title = 'Sub-Divisi Pemasok';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Divisi')
                    ->placeholder('Contoh: Divisi Food, Divisi Snack, Divisi Minuman')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->label('Kontak Person / Salesman')
                    ->placeholder('Nama Salesman / KAM')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon / WA')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('email')
                    ->label('Email Divisi')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan Operasional')
                    ->placeholder('Misal: Jadwal kunjungan, ketentuan khusus divisi')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Divisi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Kontak Person / Sales')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon / WA')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Sub-Divisi')
                    ->modalHeading('Tambah Sub-Divisi Pemasok')
                    ->modalWidth('lg'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
