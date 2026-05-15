<?php

namespace App\Filament\Resources\AdjustmentReasons;

use App\Filament\Resources\AdjustmentReasons\Pages\ManageAdjustmentReasons;
use App\Models\AdjustmentReason;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdjustmentReasonResource extends Resource
{
    protected static ?string $model = AdjustmentReason::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Sistem & Pengaturan';
    protected static ?string $navigationLabel = 'Alasan Koreksi';
    protected static ?string $modelLabel = 'Alasan Koreksi';
    protected static ?string $pluralModelLabel = 'Alasan Koreksi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Alasan')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->label('Sifat')
                    ->options([
                        'PLUS' => 'Koreksi Plus (+)',
                        'MINUS' => 'Koreksi Minus (-)',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Alasan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Sifat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PLUS' => 'success',
                        'MINUS' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getPages(): array
    {
        return [
            'index' => ManageAdjustmentReasons::route('/'),
        ];
    }
}
