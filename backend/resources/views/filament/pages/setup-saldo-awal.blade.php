<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-information-circle" heading="Petunjuk Pengisian Saldo Awal">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Menu ini digunakan untuk memasukkan saldo akhir pembukuan lama Anda menjadi <strong>Saldo Awal</strong> di sistem baru ini.
            </p>
            <ul class="list-disc list-inside mt-2 text-sm text-gray-600 dark:text-gray-400">
                <li><strong>Harta (Assets)</strong>: Biasanya berada di posisi Debit (Kas, Bank, Piutang, Nilai Persediaan).</li>
                <li><strong>Hutang (Liabilities)</strong>: Biasanya berada di posisi Kredit (Hutang Bank, Hutang Usaha).</li>
                <li><strong>Modal (Equity)</strong>: Biasanya berada di posisi Kredit (Modal Pemilik, Laba Ditahan).</li>
                <li>Sistem hanya akan menyimpan jika Total Debit dan Total Kredit bernilai sama persis (Seimbang/Balance).</li>
            </ul>
        </x-filament::section>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">Total Debit</h3>
                    <p class="text-2xl font-bold text-green-600">Rp {{ number_format($total_debit, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">Total Kredit</h3>
                    <p class="text-2xl font-bold text-red-600">Rp {{ number_format($total_credit, 0, ',', '.') }}</p>
                </div>
            </div>
            
            @if(round($total_debit, 2) !== round($total_credit, 2))
                <div class="p-4 rounded-xl bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-500 font-bold border border-danger-200 dark:border-danger-700">
                    ⚠️ Peringatan: Total Debit dan Kredit belum seimbang (Selisih: Rp {{ number_format(abs($total_debit - $total_credit), 0, ',', '.') }}). Anda belum bisa menyimpan data ini. Silakan sesuaikan angka Anda (biasanya diseimbangkan dengan membuang selisih ke akun Modal Pemilik).
                </div>
            @else
                <div class="p-4 rounded-xl bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-500 font-bold border border-success-200 dark:border-success-700">
                    ✅ Selamat! Jurnal sudah seimbang (Balance) dan siap disimpan.
                </div>
            @endif

            <div class="flex justify-end mt-6">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check-circle" size="lg" :disabled="round($total_debit, 2) !== round($total_credit, 2)">
                    Simpan Saldo Awal
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
