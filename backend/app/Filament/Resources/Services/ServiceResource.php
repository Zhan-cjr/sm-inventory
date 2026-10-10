<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Jasa / Layanan';
    protected static ?string $modelLabel = 'Jasa';
    protected static ?string $pluralModelLabel = 'Master Jasa';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label('Organisasi')
                    ->relationship('organization', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('code')
                    ->label('Kode Jasa')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('name')
                    ->label('Nama Jasa')
                    ->required()
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->rupiah()
                    ->minValue(0),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull()
                    ->maxLength(500),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Jasa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
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
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan Jasa / Layanan')
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan seluruh jasa/layanan yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Nonaktifkan')
                        ->action(function (Collection $records): void {
                            $count = $records->count();
                            $records->each(fn (Service $record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Berhasil Menonaktifkan')
                                ->body("{$count} jasa/layanan berhasil dinonaktifkan.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('activate')
                        ->label('Aktifkan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Jasa / Layanan')
                        ->modalDescription('Apakah Anda yakin ingin mengaktifkan seluruh jasa/layanan yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Aktifkan')
                        ->action(function (Collection $records): void {
                            $count = $records->count();
                            $records->each(fn (Service $record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Berhasil Mengaktifkan')
                                ->body("{$count} jasa/layanan berhasil diaktifkan.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageServices::route('/'),
        ];
    }
}
