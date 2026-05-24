<?php

namespace App\Filament\Resources\Vouchers;

use App\Filament\Resources\Vouchers\Pages\ManageVouchers;
use App\Models\Voucher;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static \UnitEnum|string|null $navigationGroup = 'DATA MASTER';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('nominal_value')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('valid_until'),
                Toggle::make('is_used')
                    ->required(),
                DateTimePicker::make('used_at'),
                TextInput::make('transaction_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('nominal_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_used')
                    ->boolean(),
                TextColumn::make('used_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transaction_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
            'index' => ManageVouchers::route('/'),
        ];
    }
}
