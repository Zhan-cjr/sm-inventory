<?php

namespace App\Filament\Resources\PosDevices;

use App\Filament\Resources\PosDevices\Pages\CreatePosDevice;
use App\Filament\Resources\PosDevices\Pages\EditPosDevice;
use App\Filament\Resources\PosDevices\Pages\ListPosDevices;
use App\Filament\Resources\PosDevices\Schemas\PosDeviceForm;
use App\Filament\Resources\PosDevices\Tables\PosDevicesTable;
use App\Models\PosDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosDeviceResource extends Resource
{
    protected static ?string $model = PosDevice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Otorisasi Device';
    protected static ?string $modelLabel = 'Otorisasi Device';
    protected static ?string $pluralModelLabel = 'Otorisasi Device';
    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PosDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosDevicesTable::configure($table);
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
            'index' => ListPosDevices::route('/'),
            'create' => CreatePosDevice::route('/create'),
            'edit' => EditPosDevice::route('/{record}/edit'),
        ];
    }
}
