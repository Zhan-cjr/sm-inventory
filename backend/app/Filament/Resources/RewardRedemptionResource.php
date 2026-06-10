<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardRedemptionResource\Pages\ManageRewardRedemptions;
use App\Models\RewardRedemption;
use App\Models\Customer;
use App\Models\Reward;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class RewardRedemptionResource extends Resource
{
    protected static ?string $model = RewardRedemption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static \UnitEnum|string|null $navigationGroup = 'TRANSAKSI';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Penukaran Hadiah';

    protected static ?string $pluralModelLabel = 'Penukaran Hadiah';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Pelanggan (Member)')
                    ->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->name} ({$record->phone}) - {$record->points} Pts")
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('reward_id')
                    ->label('Hadiah')
                    ->relationship('reward', 'name', function ($query) {
                        return $query->where('is_active', true)->where('stock', '>', 0);
                    })
                    ->getOptionLabelFromRecordUsing(fn (Reward $record) => "{$record->name} - {$record->points_required} Pts (Stok: {$record->stock})")
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Jumlah (Qty)')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1),
                Select::make('branch_id')
                    ->label('Cabang Penukaran')
                    ->relationship('branch', 'name')
                    ->default(fn () => auth()->user()?->branch_id)
                    ->disabled(fn () => auth()->user()?->branch_id !== null)
                    ->dehydrated()
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'COMPLETED' => 'Selesai',
                        'CANCELLED' => 'Dibatalkan',
                    ])
                    ->default('COMPLETED')
                    ->required()
                    ->disabledOn('create'), // Only editable when updating
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                $user = auth()->user();
                if ($user && $user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                }
                return $query;
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reward.name')
                    ->label('Hadiah')
                    ->searchable(),
                TextColumn::make('points_redeemed')
                    ->label('Poin Ditukar')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'COMPLETED' => 'Selesai',
                        'CANCELLED' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                // Delete is not allowed to prevent data tampering
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRewardRedemptions::route('/'),
        ];
    }
}
