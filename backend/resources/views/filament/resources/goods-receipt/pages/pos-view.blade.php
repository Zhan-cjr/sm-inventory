<x-filament-panels::page>
    @if(isset($record))
        <livewire:goods-receipt-pos :goodsReceipt="$record" :readOnly="$this instanceof \Filament\Resources\Pages\ViewRecord" />
    @else
        <livewire:goods-receipt-pos />
    @endif
</x-filament-panels::page>
