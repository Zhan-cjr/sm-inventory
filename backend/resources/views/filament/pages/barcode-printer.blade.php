<x-filament-panels::page>
    <div>
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-4 items-center border-t pt-4 border-gray-200 dark:border-gray-700">
            <x-filament::button wire:click="printLabel" color="success" icon="heroicon-o-qr-code" size="lg">
                Cetak Label Barcode
            </x-filament::button>

            <x-filament::button wire:click="printPricecard" color="warning" icon="heroicon-o-tag" size="lg">
                Cetak Pricecard Rak
            </x-filament::button>

            <x-filament::button wire:click="clearAll" color="danger" icon="heroicon-o-trash" size="lg" wire:confirm="Apakah Anda yakin ingin mengosongkan semua data antrean?">
                Kosongkan Antrean
            </x-filament::button>
            
            <div class="text-sm text-gray-500 ml-4">
                * Pastikan popup blocker tidak memblokir tab baru saat mencetak.
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('open-url-new-tab', (event) => {
            let url = event[0] ? event[0].url : event.url;
            if(url) {
                window.open(url, '_blank');
            }
        });
    </script>
    @endscript
</x-filament-panels::page>
