<?php

namespace App\Filament\Resources\AdjustmentReasons\Pages;

use App\Filament\Resources\AdjustmentReasons\AdjustmentReasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAdjustmentReasons extends ManageRecords
{
    protected static string $resource = AdjustmentReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
