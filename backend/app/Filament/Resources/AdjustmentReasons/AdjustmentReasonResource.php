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
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdjustmentReasonResource extends Resource
{
    protected static ?string $model = AdjustmentReason::class;

    protected static \UnitEnum|string|null $navigationGroup = 'PENGATURAN';
    protected static ?int $navigationSort = 2;
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
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
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
                ToggleColumn::make('is_active')
                    ->label('Status Aktif')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
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
