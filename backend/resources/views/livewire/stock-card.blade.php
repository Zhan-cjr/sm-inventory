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
</div>
