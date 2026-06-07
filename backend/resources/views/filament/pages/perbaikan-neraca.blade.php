<x-filament-panels::page>
    <div class="mb-4">
        <p class="text-gray-600 dark:text-gray-400">
            Halaman ini mendiagnosa seluruh jurnal yang tidak seimbang (Debit tidak sama dengan Kredit). 
            Jurnal yang tidak seimbang dapat menyebabkan Laporan Laba Rugi dan Neraca Saldo menjadi tidak akurat. 
            Klik tombol <strong>Proses Perbaikan</strong> pada jurnal yang rusak untuk menghitung dan membuat ulang jurnal secara otomatis dari dokumen sumbernya.
        </p>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
