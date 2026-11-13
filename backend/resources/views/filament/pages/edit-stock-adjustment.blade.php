<x-filament-panels::page>
    <div class="-mx-4 -my-4 lg:-mx-8 lg:-my-8 h-[calc(100vh-8rem)]">
        @livewire('stock-adjustment-pos', [
            'stockAdjustment' => $record,
            'readOnly' => $this instanceof \App\Filament\Resources\StockAdjustments\Pages\ViewStockAdjustment,
        ])
    </div>
</x-filament-panels::page>
