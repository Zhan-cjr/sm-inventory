<x-filament-panels::page>
    <x-filament::card class="max-w-xl mx-auto">
        <form wire:submit.prevent="create" class="space-y-6">
            <div>
                <label for="db_name" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Nama Database Baru
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        id="db_name"
                        wire:model="db_name"
                        placeholder="contoh: hypermarket_pos_cabang2"
                        required
                        autofocus
                    />
                </x-filament::input.wrapper>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Gunakan hanya huruf, angka, dan underscore (_). Database baru akan otomatis di-migrate dan di-seed dengan data awal.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" color="success">
                    Buat & Aktifkan Database
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
