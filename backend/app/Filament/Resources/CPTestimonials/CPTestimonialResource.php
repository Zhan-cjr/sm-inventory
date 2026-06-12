<?php

namespace App\Filament\Resources\CPTestimonials;

use App\Filament\Resources\CPTestimonials\Pages\CreateCPTestimonial;
use App\Filament\Resources\CPTestimonials\Pages\EditCPTestimonial;
use App\Filament\Resources\CPTestimonials\Pages\ListCPTestimonials;
use App\Filament\Resources\CPTestimonials\Schemas\CPTestimonialForm;
use App\Filament\Resources\CPTestimonials\Tables\CPTestimonialsTable;
use App\Models\CPTestimonial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CPTestimonialResource extends Resource
{
    protected static ?string $model = CPTestimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'COMPANY PROFILE';

    protected static ?string $recordTitleAttribute = 'customer_name';

    public static function form(Schema $schema): Schema
    {
        return CPTestimonialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CPTestimonialsTable::configure($table);
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
            'index' => ListCPTestimonials::route('/'),
            'create' => CreateCPTestimonial::route('/create'),
            'edit' => EditCPTestimonial::route('/{record}/edit'),
        ];
    }
}
