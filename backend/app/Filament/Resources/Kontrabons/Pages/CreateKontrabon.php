<?php

namespace App\Filament\Resources\Kontrabons\Pages;

use App\Filament\Resources\Kontrabons\KontrabonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKontrabon extends CreateRecord
{
    protected static string $resource = KontrabonResource::class;

    protected static bool $canCreateAnother = false;
    protected string $view = 'filament.resources.kontrabons.pages.create-kontrabon';
}
