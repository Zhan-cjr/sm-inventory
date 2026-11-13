<?php

namespace App\Filament\Resources\StockOpname;

use App\Models\StockOpnameRack;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                Action::make('toggle_status')
                    ->label(fn (StockOpnameRack $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (StockOpnameRack $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-check-circle')
                    ->color(fn (StockOpnameRack $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (StockOpnameRack $record) => $record->is_active ? 'Nonaktifkan Rak?' : 'Aktifkan Rak?')
                    ->modalDescription(fn (StockOpnameRack $record) => $record->is_active
                        ? 'Rak yang dinonaktifkan tidak akan muncul pada pilihan sesi stok opname baru, namun riwayat sesi sebelumnya tetap aman.'
                        : 'Rak akan diaktifkan kembali dan dapat dipilih pada sesi stok opname baru.')
                    ->action(fn (StockOpnameRack $record) => $record->update(['is_active' => !$record->is_active])),
                DeleteAction::make()
                    ->modalHeading(fn (StockOpnameRack $record) => $record->rackSessions()->exists() ? 'Nonaktifkan Rak?' : 'Hapus Rak?')
                    ->modalDescription(fn (StockOpnameRack $record) => $record->rackSessions()->exists()
                        ? "Rak '{$record->rack_name}' tidak dapat dihapus permanen karena sudah memiliki riwayat sesi opname. Melanjutkan tindakan ini akan mengubah status rak menjadi Nonaktif agar data histori opname masa lalu tetap aman."
                        : "Apakah Anda yakin ingin menghapus rak '{$record->rack_name}'?"
                    )
                    ->modalSubmitActionLabel(fn (StockOpnameRack $record) => $record->rackSessions()->exists() ? 'Ya, Nonaktifkan' : 'Hapus')
                    ->action(function (DeleteAction $action, StockOpnameRack $record) {
                        if ($record->rackSessions()->exists()) {
                            $record->update(['is_active' => false]);

                            Notification::make()
                                ->warning()
                                ->title('Rak Dinonaktifkan')
                                ->body("Rak '{$record->rack_name}' tidak dapat dihapus karena sudah memiliki riwayat sesi opname. Status rak telah diubah menjadi nonaktif.")
                                ->persistent()
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Rak Berhasil Dihapus')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $deleted = 0;
                            $deactivated = 0;
                            foreach ($records as $record) {
                                if ($record->rackSessions()->exists()) {
                                    $record->update(['is_active' => false]);
                                    $deactivated++;
                                } else {
                                    $record->delete();
                                    $deleted++;
                                }
                            }

                            $msg = [];
                            if ($deleted > 0) $msg[] = "{$deleted} rak berhasil dihapus";
                            if ($deactivated > 0) $msg[] = "{$deactivated} rak dinonaktifkan (karena memiliki riwayat opname)";

                            \Filament\Notifications\Notification::make()
                                ->title(implode(', ', $msg))
                                ->success()
                                ->send();
                        }),
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
