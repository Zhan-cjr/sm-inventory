<x-filament-panels::page>
    @if(isset($record))
        <livewire:stock-transfer-pos :stockTransfer="$record" :readOnly="$this instanceof \Filament\Resources\Pages\ViewRecord" />
    @else
        <livewire:stock-transfer-pos />
    @endif
</x-filament-panels::page>
