<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardResource\Pages\ManageRewards;
use App\Models\Reward;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static \UnitEnum|string|null $navigationGroup = 'DATA MASTER';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Katalog Hadiah';

    protected static ?string $pluralModelLabel = 'Katalog Hadiah';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->label('Foto Hadiah')
                    ->image()
                    ->disk('public')
                    ->directory('rewards')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nama Hadiah')
                    ->required(),
                TextInput::make('points_required')
                    ->label('Poin Dibutuhkan')
                    ->numeric()
                    ->required()
                    ->default(100),
                TextInput::make('stock')
                    ->label('Stok Hadiah')
                    ->numeric()
                    ->required()
                    ->default(10),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nama Hadiah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('points_required')
                    ->label('Poin Dibutuhkan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok Hadiah')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
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
            'index' => ManageRewards::route('/'),
        ];
    }
}
