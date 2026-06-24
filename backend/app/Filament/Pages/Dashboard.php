<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public static function canAccess(): bool
    {
        return true; // Allow route resolution to prevent 403
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('View:Dashboard');
    }

    public function mount()
    {
        if (!auth()->user()->can('View:Dashboard')) {
            $resources = filament()->getCurrentPanel()->getResources();
            foreach ($resources as $resource) {
                if ($resource::canViewAny()) {
                    $this->redirect($resource::getUrl('index'));
                    return;
                }
            }
        }
    }

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
