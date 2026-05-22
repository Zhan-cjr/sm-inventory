<div class="p-2">
    <style>
        .stock-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        .stock-table th { background: #f3f4f6; padding: 8px; border: 1px solid #d1d5db; text-align: left; font-weight: bold; color: #374151; }
        .stock-table td { padding: 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .stock-table tr:hover { background: #f9fafb; }
        .text-right { text-align: right; }
        .qty-box { font-weight: bold; }
        .qty-minus { background-color: #fee2e2; color: #991b1b; }
        /* Fix huge pagination icons */
        nav svg { width: 20px; height: 20px; display: inline; }
        .pagination-container { margin-top: 15px; display: flex; align-items: center; justify-content: space-between; font-size: 11px; }
    </style>

    @if($noBranchSelected)
        <div style="padding: 2.5rem; text-align: center; color: #6b7280;" class="dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto; height: 3.5rem; width: 3.5rem; color: #9ca3af; margin-bottom: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h3 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;" class="dark:text-gray-200">Cabang Belum Dipilih</h3>
            <p style="font-size: 0.875rem; color: #6b7280;" class="dark:text-gray-400">Silakan pilih salah satu cabang pada filter tabel Produk terlebih dahulu untuk dapat melihat Kartu Stok.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Tgl</th>
                        <th style="width: 150px;">No Trans</th>
                        <th>Keterangan</th>
                        <th class="text-right" style="width: 80px;">Qty Awal</th>
                        <th class="text-right" style="width: 70px; color: #059669;">Masuk</th>
                        <th class="text-right" style="width: 70px; color: #dc2626;">Keluar</th>
                        <th class="text-right" style="width: 80px;">Qty Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td style="font-family: monospace; font-size: 10px;">{{ $log->reference_doc_id ?? '-' }}</td>
                            <td>
                                <div style="font-weight: bold;">{{ $log->log_type }}</div>
                                <div style="font-size: 10px; color: #6b7280;">{{ $log->reason_code }} - {{ $log->notes }}</div>
                            </td>
                            <td class="text-right">{{ $log->quantity_before }}</td>
                            <td class="text-right" style="color: #059669; font-weight: bold;">
                                {{ $log->quantity_change > 0 ? $log->quantity_change : '' }}
                            </td>
                            <td class="text-right" style="color: #dc2626; font-weight: bold;">
                                {{ $log->quantity_change < 0 ? abs($log->quantity_change) : '' }}
                            </td>
                            <td class="text-right qty-box {{ $log->quantity_after < 0 ? 'qty-minus' : '' }}">
                                {{ $log->quantity_after }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">Belum ada riwayat stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            {{ $logs->links() }}
        </div>
    @endif
</div>
