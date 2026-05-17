<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Cabang')
                ->options(\App\Models\Branch::pluck('name', 'id'))
                ->searchable()
                ->hidden(fn () => auth()->user()->branch_id !== null)
        ]);
    }
}
