<?php

namespace App\Filament\Resources\Kontrabons;

use App\Filament\Resources\Kontrabons\Pages\CreateKontrabon;
use App\Filament\Resources\Kontrabons\Pages\EditKontrabon;
use App\Filament\Resources\Kontrabons\Pages\ListKontrabons;
use App\Filament\Resources\Kontrabons\Schemas\KontrabonForm;
use App\Filament\Resources\Kontrabons\Tables\KontrabonsTable;
use App\Models\Kontrabon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;

class KontrabonResource extends Resource
{
    protected static ?string $model = Kontrabon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static \UnitEnum|string|null $navigationGroup = 'Keuangan';
    protected static ?string $modelLabel = 'Tukar Faktur (Kontrabon)';
    protected static ?string $pluralModelLabel = 'Tukar Faktur (Kontrabon)';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kontrabon_number')->label('No. Kontrabon')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_kontrabon')->label('Tgl Kontrabon')->date()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date()->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Pemasok')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Tagihan')->money('IDR'),
                Tables\Columns\TextColumn::make('paid_amount')->label('Sudah Dibayar')->money('IDR'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->colors([
                        'danger' => 'UNPAID',
                        'warning' => 'PARTIAL',
                        'success' => 'PAID',
                        'gray' => 'CANCELLED',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->modalHeading('Batalkan Kontrabon')
                    ->action(function (Kontrabon $record) {
                        $record->status = 'CANCELLED';
                        $record->save();
                        // Optional: release GR billing status
                    })
                    ->hidden(fn (Kontrabon $record) => $record->status === 'CANCELLED' || $record->paid_amount > 0),
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
            'index' => ListKontrabons::route('/'),
            'create' => CreateKontrabon::route('/create'),
            'edit' => EditKontrabon::route('/{record}/edit'),
        ];
    }
}
