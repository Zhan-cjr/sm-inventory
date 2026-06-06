<x-filament-panels::page>
    @if(isset($record))
        <livewire:purchase-return-pos :purchaseReturn="$record" />
    @else
        <livewire:purchase-return-pos />
    @endif
</x-filament-panels::page>
