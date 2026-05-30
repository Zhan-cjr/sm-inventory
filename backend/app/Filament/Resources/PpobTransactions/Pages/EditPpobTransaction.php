<?php

namespace App\Filament\Resources\PpobTransactions\Pages;

use App\Filament\Resources\PpobTransactions\PpobTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPpobTransaction extends EditRecord
{
    protected static string $resource = PpobTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
