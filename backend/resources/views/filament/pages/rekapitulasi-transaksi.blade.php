<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}
    </form>

    @php
        $rekap = $this->rekapData;
        $branches = $rekap['branches'] ?? [];
        $totals = $rekap['totals'] ?? [];
    @endphp

    <div class="mt-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm">
            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr class="divide-x divide-gray-200 dark:divide-white/10">
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">Nama Cabang</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Penerimaan Brg</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Retur Beli</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Koreksi Retur</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Penjualan Kasir</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Retur Penjualan</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap text-right">Pengeluaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
                    @forelse($branches as $b)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 divide-x divide-gray-200 dark:divide-white/10">
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-200 font-medium">
                                {{ $b['branch_name'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['penerimaan'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['retur_beli'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['koreksi_retur'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['penjualan'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['retur_jual'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                {{ number_format($b['pengeluaran'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data cabang yang ditemukan pada rentang tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($branches) > 0)
                    <tfoot class="bg-gray-50 dark:bg-white/5 border-t-2 border-gray-200 dark:border-white/10 font-bold">
                        <tr class="divide-x divide-gray-200 dark:divide-white/10">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">TOTAL KESELURUHAN</td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['penerimaan'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['retur_beli'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['koreksi_retur'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['penjualan'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['retur_jual'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ number_format($totals['pengeluaran'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
