<x-filament-panels::page>
    @if(method_exists($this, 'getReportTabs') && count($tabs = $this->getReportTabs()))
        <div class="mb-4">
            <x-filament::tabs>
                @foreach($tabs as $tabKey => $tab)
                    <x-filament::tabs.item
                        :active="($activeTab ?? 'detail') === $tabKey"
                        wire:click="$set('activeTab', '{{ $tabKey }}')"
                        :icon="$tab['icon'] ?? null"
                    >
                        {{ $tab['label'] }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
