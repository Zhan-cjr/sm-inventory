<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-information-circle" heading="Informasi Tutup Buku">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Fitur <strong>Tutup Buku Tahunan</strong> digunakan di akhir tahun akuntansi untuk menihilkan (membuat nol) seluruh saldo akun nominal (Pendapatan dan Pengeluaran).
                Selisih antara Pendapatan dan Pengeluaran (Laba/Rugi) akan dipindahkan ke akun <strong>Laba Ditahan (Retained Earnings)</strong>.
            </p>
            <ul class="list-disc list-inside mt-2 text-sm text-gray-600 dark:text-gray-400">
                <li>Sistem akan mencari seluruh transaksi pada tahun yang Anda pilih.</li>
                <li>Jurnal Penutup akan otomatis dibuat pada tanggal 31 Desember tahun tersebut.</li>
                <li>Jika Anda melakukan Tutup Buku ulang untuk tahun yang sama, sistem akan menghapus jurnal penutup yang lama dan menghitung ulang.</li>
            </ul>
        </x-filament::section>

        <x-filament::section>
            <form wire:submit="prosesTutupBuku" class="space-y-6">
                {{ $this->form }}
                
                <div class="flex justify-end mt-4">
                    <x-filament::button type="submit" color="danger" icon="heroicon-o-lock-closed" wire:confirm="Apakah Anda yakin ingin melakukan proses Tutup Buku Tahunan? Jurnal penyesuaian akan otomatis dibuat.">
                        Proses Tutup Buku
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
