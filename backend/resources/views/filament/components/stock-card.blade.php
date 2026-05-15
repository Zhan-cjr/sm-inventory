<div class="p-4 bg-white dark:bg-gray-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Riwayat Perubahan Stok</h3>
        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
            Stok Saat Ini: {{ $product->stocks->sum('quantity_on_hand') }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2">Tanggal</th>
                    <th class="px-4 py-2">Tipe</th>
                    <th class="px-4 py-2">Alasan</th>
                    <th class="px-4 py-2 text-right">Awal</th>
                    <th class="px-4 py-2 text-right">Perubahan</th>
                    <th class="px-4 py-2 text-right">Akhir</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $logs = \App\Models\InventoryLog::where('product_id', $product->id)
                        ->latest()
                        ->get();
                @endphp
                @forelse($logs as $log)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-2">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $log->log_type === 'SALE' ? 'bg-green-100 text-green-800' : ($log->log_type === 'ADJUSTMENT' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $log->log_type }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $log->reason_code }}</td>
                        <td class="px-4 py-2 text-right">{{ $log->quantity_before }}</td>
                        <td class="px-4 py-2 text-right {{ $log->quantity_after > $log->quantity_before ? 'text-green-600' : 'text-red-600' }}">
                            {{ $log->quantity_after - $log->quantity_before > 0 ? '+' : '' }}{{ $log->quantity_after - $log->quantity_before }}
                        </td>
                        <td class="px-4 py-2 text-right font-bold">{{ $log->quantity_after }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center">Belum ada riwayat stok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
