<style>
    .custom-detail-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem; /* text-sm */
    }
    .custom-detail-table th,
    .custom-detail-table td {
        border: 1px solid #4b5563; /* border-gray-600 */
        padding: 0.5rem 1rem; /* px-4 py-2 */
        white-space: nowrap;
    }
    .custom-detail-table th {
        background-color: #374151; /* bg-gray-700 */
        color: #d1d5db; /* text-gray-300 */
        text-transform: uppercase;
        font-weight: 600;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
    }
    .custom-detail-table th.text-right,
    .custom-detail-table td.text-right {
        text-align: right;
    }
    .custom-detail-table tbody tr {
        background-color: #1f2937; /* bg-gray-800 */
    }
    .custom-detail-table tbody tr:hover {
        background-color: #374151; /* hover:bg-gray-700 */
    }
    .custom-detail-table tfoot td {
        background-color: #374151; /* bg-gray-700 */
        font-weight: 600;
        position: sticky;
        bottom: 0;
        z-index: 10;
        box-shadow: 0 -2px 2px -1px rgba(0, 0, 0, 0.4);
    }
    .custom-detail-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 65vh;
        border-radius: 0.5rem;
        border: 1px solid #4b5563;
    }
</style>

<div class="custom-detail-container">
    <table class="custom-detail-table text-gray-300">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Barcode</th>
                <th>Nama Barang</th>
                <th class="text-right">Qty Jual</th>
                <th class="text-right">Qty Retur</th>
                <th class="text-right">Jual</th>
                <th class="text-right">HPP</th>
                <th class="text-right">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                @php
                    $selisih = ($item->jual - $item->hpp) - ($item->retur - $item->hpp_retur);
                @endphp
                <tr>
                    <td class="font-medium text-white">{{ $item->sku }}</td>
                    <td>{{ $item->barcode }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-right">{{ number_format($item->qty_jual, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->qty_retur, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->jual, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->hpp, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($selisih, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 1rem;">Tidak ada data barang.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total:</td>
                <td class="text-right">{{ number_format($data->sum('qty_jual'), 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($data->sum('qty_retur'), 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($data->sum('jual'), 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($data->sum('hpp'), 0, ',', '.') }}</td>
                <td class="text-right">
                    @php
                        $totalSelisih = ($data->sum('jual') - $data->sum('hpp')) - ($data->sum('retur') - $data->sum('hpp_retur'));
                    @endphp
                    Rp {{ number_format($totalSelisih, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
