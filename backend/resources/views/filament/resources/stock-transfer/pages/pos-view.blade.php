<x-filament-panels::page>
    @if(isset($record))
        <livewire:stock-transfer-pos :stockTransfer="$record" />
    @else
        <livewire:stock-transfer-pos />
    @endif
</x-filament-panels::page>
