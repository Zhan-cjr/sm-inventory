<x-filament-panels::page>
    @if(isset($record))
        <livewire:goods-receipt-pos :goodsReceipt="$record" />
    @else
        <livewire:goods-receipt-pos />
    @endif
</x-filament-panels::page>
