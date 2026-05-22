<x-filament-panels::page>
    <x-filament::card class="max-w-xl mx-auto">
        <form wire:submit.prevent="open" class="space-y-6">
            <div>
                <label for="selected_db" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Pilih Database Aktif
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="selected_db" wire:model="selected_db">
                        @foreach ($this->getAvailableDatabases() as $dbName)
                            <option value="{{ $dbName }}">
                                {{ $dbName }} {{ $dbName === env('DB_DATABASE', 'hypermarket_pos') ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Pilih salah satu database yang terdaftar pada host Anda. Sesi Anda akan beralih ke database yang dipilih.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" color="primary">
                    Buka & Aktifkan Database
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
