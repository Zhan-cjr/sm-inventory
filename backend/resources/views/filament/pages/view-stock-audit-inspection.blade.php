<x-filament-panels::page>
@php
    $record = $this->record;
    $record->load(['items.product', 'items.rack', 'branch', 'auditor', 'adjustment']);
@endphp

<div class="space-y-6">
    {{-- Card Header Metadata --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Sidak</div>
            <div class="text-lg font-bold text-primary-600 dark:text-primary-400 mt-1 font-mono">{{ $record->audit_number }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Tgl: {{ $record->inspection_date?->translatedFormat('d M Y') }}</div>
        </div>

        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Cabang & Auditor</div>
            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $record->branch?->name }}</div>
            <div class="text-xs text-gray-500">Pemeriksa: {{ $record->auditor?->name }}</div>
        </div>

        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mode & Saksi Toko</div>
            <div class="mt-1">
                @if($record->inspection_mode === 'FREE_SCAN')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">Scan Bebas (Spot Check)</span>
                @elseif($record->inspection_mode === 'RANDOM_LOTTERY')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">Undian Acak Sistem</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">Berbasis Risiko</span>
                @endif
            </div>
            <div class="text-xs text-gray-500 mt-1">Saksi: {{ $record->acknowledged_by ?: '-' }}</div>
        </div>

        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Dokumen</div>
            <div class="mt-1">
                @if($record->status === 'DRAFT')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">Sedang Berjalan</span>
                @elseif($record->status === 'INVESTIGATION_PENDING')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300">Menunggu Klarifikasi</span>
                @elseif($record->status === 'ADJUSTED')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Stok Disesuaikan</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Ditutup</span>
                @endif
            </div>
            @if($record->adjustment)
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">No Adj: {{ $record->adjustment->adjustment_number }}</div>
            @endif
        </div>
    </div>

    {{-- 4 Kotak Ringkasan Selisih --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="text-xs font-semibold text-gray-400 uppercase">Total Item Dicek</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $record->total_items_checked }} <span class="text-xs font-normal text-gray-500">Produk</span></div>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="text-xs font-semibold text-gray-400 uppercase">Item Selisih</div>
            <div class="text-2xl font-bold {{ $record->total_items_discrepancy > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} mt-1">
                {{ $record->total_items_discrepancy }} <span class="text-xs font-normal text-gray-500">Item</span>
            </div>
        </div>

        <div class="p-4 bg-red-50 dark:bg-red-950/30 rounded-xl border border-red-200 dark:border-red-900/50">
            <div class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase">Total Kerugian (Minus)</div>
            <div class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">
                -Rp {{ number_format($record->total_loss_nominal, 0, ',', '.') }}
            </div>
        </div>

        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl border border-emerald-200 dark:border-emerald-900/50">
            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase">Total Lebih (Plus)</div>
            <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                +Rp {{ number_format($record->total_gain_nominal, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Tabel Rincian Produk --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">Daftar Hasil Penghitungan Fisik</h3>
            <span class="text-xs text-gray-500">{{ $record->items->count() }} item tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-center">Rak</th>
                        <th class="px-4 py-3 text-right">Stok Sistem</th>
                        <th class="px-4 py-3 text-right">Mutasi Kasir</th>
                        <th class="px-4 py-3 text-right">Fisik Dihitung</th>
                        <th class="px-4 py-3 text-right">Selisih Qty</th>
                        <th class="px-4 py-3 text-right">HPP</th>
                        <th class="px-4 py-3 text-right">Selisih (Rp)</th>
                        <th class="px-4 py-3">Keterangan Toko</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-medium">
                    @forelse($record->items as $idx => $it)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                            <td class="px-4 py-3 text-center text-gray-400">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ $it->product?->name ?? 'Produk Dihapus' }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $it->product?->barcode ?? $it->product?->sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                {{ $it->rack?->rack_name ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($it->system_quantity, 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-400">
                                {{ $it->delta_sales_quantity != 0 ? ($it->delta_sales_quantity > 0 ? '+' : '') . number_format($it->delta_sales_quantity, 0) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($it->physical_quantity, 0) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold">
                                @if($it->discrepancy_quantity < 0)
                                    <span class="text-red-600 dark:text-red-400">{{ number_format($it->discrepancy_quantity, 0) }}</span>
                                @elseif($it->discrepancy_quantity > 0)
                                    <span class="text-blue-600 dark:text-blue-400">+{{ number_format($it->discrepancy_quantity, 0) }}</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs">Rp {{ number_format($it->unit_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold">
                                @if($it->discrepancy_cost < 0)
                                    <span class="text-red-600 dark:text-red-400">-Rp {{ number_format(abs($it->discrepancy_cost), 0, ',', '.') }}</span>
                                @elseif($it->discrepancy_cost > 0)
                                    <span class="text-blue-600 dark:text-blue-400">+Rp {{ number_format($it->discrepancy_cost, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-400">Rp 0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $it->clarification_notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                Belum ada item yang diperiksa dalam sesi sidak ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>

