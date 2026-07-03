<?php

namespace App\Filament\Resources\StockOpname;

use App\Models\StockOpnameRack;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasBranchScope;

class StockOpnameRackResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = StockOpnameRack::class;

    protected static \UnitEnum|string|null $navigationGroup = 'STOK OPNAME';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Manajemen Rak';
    protected static ?string $modelLabel = 'Rak';
    protected static ?string $pluralModelLabel = 'Data Rak';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Rak')->columns(2)->schema([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()->branch_id)
                    ->disabled(fn () => Auth::user()->branch_id !== null)
                    ->dehydrated(),

                TextInput::make('rack_code')
                    ->label('Kode Rak')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('Contoh: RAK-A01')
                    ->helperText('Kode ini yang akan di-encode ke QR/Barcode'),

                TextInput::make('rack_name')
                    ->label('Nama Rak')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Contoh: Rak Elektronik Lantai 1'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Textarea::make('location_description')
                    ->label('Deskripsi Lokasi')
                    ->columnSpanFull()
                    ->placeholder('Contoh: Area belakang dekat gudang, sebelah kiri pintu masuk'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rack_code')
                    ->label('Kode Rak')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('rack_name')
                    ->label('Nama Rak')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable(),
                TextColumn::make('location_description')
                    ->label('Deskripsi Lokasi')
                    ->limit(50)
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockOpnameRacks::route('/'),
            'create' => Pages\CreateStockOpnameRack::route('/create'),
            'edit'   => Pages\EditStockOpnameRack::route('/{record}/edit'),
            'unassigned' => Pages\UnassignedStocks::route('/unassigned'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with('branch');
        $user  = Auth::user();
        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        return $query;
    }
}
