<?php

namespace App\Filament\Resources\TaxInvoices\Pages;

use App\Filament\Resources\TaxInvoices\TaxInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxInvoice extends EditRecord
{
    protected static string $resource = TaxInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (\App\Models\TaxInvoice $record): string => url('/print/document/tax-invoice?id=' . $record->id))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
