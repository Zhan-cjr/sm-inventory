@extends('print.documents.layout')

@section('content')
    <div class="page-container">
        <div class="org-info">
            @php
                $orgName = $organization->name ?? 'NAMA ORGANISASI';
                $branchName = $kontrabon->branch ? $kontrabon->branch->name : 'Pusat / Global';
                $branchAddress = $kontrabon->branch ? $kontrabon->branch->address : ($organization->address ?? '');
            @endphp
            <h2>{{ $orgName }}</h2>
            <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
        </div>
        <div class="document-title">
            <h1>Tukar Faktur (Kontrabon)</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">No. Kontrabon</td>
                <td class="separator">:</td>
                <td>{{ $kontrabon->kontrabon_number }}</td>
                <td class="label" style="text-align: right; width: 100px;">Tgl Kontrabon</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($kontrabon->tanggal_kontrabon)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td class="separator">:</td>
                <td>{{ $kontrabon->supplier ? $kontrabon->supplier->name : '-' }}</td>
                <td class="label" style="text-align: right;">Jatuh Tempo</td>
                <td class="separator">:</td>
                <td>{{ \Carbon\Carbon::parse($kontrabon->tanggal_jatuh_tempo)->format('d-m-Y') }}</td>
            </tr>
            @if(isset($minDate) && isset($maxDate))
            <tr>
                <td colspan="3"></td>
                <td class="label" style="text-align: right;">Periode Penagihan</td>
                <td class="separator">:</td>
                <td>{{ $minDate }} s/d {{ $maxDate }}</td>
            </tr>
            @endif
        </table>

        @if($kontrabon->goodsReceipts->count() > 0)
        <h3>Faktur Tagihan (Penerimaan Barang)</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>No. Surat Jalan / GR</th>
                    <th>Tgl Faktur</th>
                    <th class="text-right" style="width: 150px;">Nominal Faktur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kontrabon->goodsReceipts as $index => $gr)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $gr->receipt_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($gr->receipt_date)->format('d-m-Y') }}</td>
                    <td class="text-right">Rp {{ number_format($gr->total_amount ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @elseif(isset($selloutItems) && $selloutItems->count() > 0)
        <h3>Rincian Barang Terjual (Sellout Konsinyasi)</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th style="width: 100px;">Barcode</th>
                    <th>SKU / Nama Produk</th>
                    <th class="text-right" style="width: 75px;">HPP (Beli)</th>
                    <th class="text-right" style="width: 75px;">Harga Jual</th>
                    <th class="text-center" style="width: 45px;">Qty Jual</th>
                    <th class="text-center" style="width: 45px;">Qty Retur</th>
                    <th class="text-center" style="width: 45px;">Qty Net</th>
                    <th class="text-right" style="width: 75px;">Total Jual</th>
                    <th class="text-right" style="width: 75px;">Total Beli HPP</th>
                    <th class="text-right" style="width: 75px;">Total Retur (HPP)</th>
                    <th class="text-right" style="width: 85px;">Subtotal Tagihan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($selloutItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-size: 8pt;">{{ $item['barcode'] }}</td>
                    <td style="font-size: 8pt;">
                        <strong>{{ $item['sku'] }}</strong><br>{{ $item['name'] }}
                        @if(!empty($item['prior_unbilled_qty']) && $item['prior_unbilled_qty'] > 0)
                            <br><span style="color: #b45309; font-size: 7.5pt; font-weight: 500;">(Termasuk {{ number_format($item['prior_unbilled_qty']) }} pcs unbilled s/d cut-off)</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-size: 8pt;">Rp {{ number_format($item['cost_price'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-size: 8pt;">
                        Rp {{ number_format($item['selling_price'], 0, ',', '.') }}
                        @if(!empty($item['has_price_variance']))
                            <br><span style="color: #64748b; font-size: 7pt; font-weight: normal;">(Avg: Rp {{ number_format($item['average_selling_price'], 0, ',', '.') }})</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-size: 8pt;">{{ number_format($item['qty_jual']) }}</td>
                    <td class="text-center" style="font-size: 8pt;">{{ number_format($item['qty_retur']) }}</td>
                    <td class="text-center" style="font-size: 8pt; font-weight: bold;">{{ number_format($item['qty_jual_net']) }}</td>
                    <td class="text-right" style="font-size: 8pt;">Rp {{ number_format($item['total_jual'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-size: 8pt;">Rp {{ number_format($item['total_beli_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-size: 8pt;">Rp {{ number_format($item['total_retur_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-size: 8pt; font-weight: bold;">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(isset($unbilledPriorDetails) && count($unbilledPriorDetails['items']) > 0)
        <div style="margin-top: 10px; margin-bottom: 12px; padding: 8px 12px; background-color: #fffbeb; border: 1px dashed #f59e0b; border-radius: 4px; font-size: 8pt; color: #92400e;">
            <div style="font-weight: bold; margin-bottom: 3px;">
                Catatan Saldo Tertinggal (Unbilled Sebelum Periode {{ $minDate }}):
            </div>
            <div>
                Tagihan ini mencakup penjualan berstatus <em>unbilled</em> sebelum periode berjalan sebanyak <strong>{{ number_format($unbilledPriorDetails['total_qty']) }} pcs</strong> senilai <strong>Rp {{ number_format($unbilledPriorDetails['total_amount'], 0, ',', '.') }}</strong> dari transaksi:
                <ul style="margin: 2px 0 3px 16px; padding: 0;">
                    @foreach($unbilledPriorDetails['items'] as $prior)
                        <li>Struk <strong>{{ $prior['receipt_number'] }}</strong> (Tgl {{ $prior['date'] }}) &bull; {{ $prior['product_name'] }} ({{ number_format($prior['qty']) }} pcs &times; Rp {{ number_format($prior['cost_price'], 0, ',', '.') }})</li>
                    @endforeach
                </ul>
                Penjualan murni pada periode berjalan ({{ $minDate }} s/d {{ $maxDate }}) adalah <strong>{{ number_format($unbilledPriorDetails['current_qty']) }} pcs</strong> senilai <strong>Rp {{ number_format($unbilledPriorDetails['current_amount'], 0, ',', '.') }}</strong>.
            </div>
        </div>
        @endif
        @endif

        @if($kontrabon->kontrabonDeductions->count() > 0)
        <h3 style="margin-top: 15px;">Potongan & Klaim</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">No</th>
                    <th>Keterangan / ID Promo</th>
                    <th class="text-right" style="width: 150px;">Nominal Terpotong</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kontrabon->kontrabonDeductions as $index => $deduction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $deduction->supplierDeduction ? $deduction->supplierDeduction->notes : '-' }}</td>
                    <td class="text-right">- Rp {{ number_format($deduction->amount_applied ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table class="summary-box" style="margin-top: 15px;">
            @if(isset($selloutItems) && $selloutItems->count() > 0)
            @php
                $sumTotalJual = collect($selloutItems)->sum('total_jual');
                $sumTotalReturJual = collect($selloutItems)->sum('total_retur_jual');
                $netSales = $sumTotalJual - $sumTotalReturJual;
            @endphp
            <tr>
                <td class="label" style="font-size: 10pt;">TOTAL JUAL</td>
                <td class="value" style="font-size: 10pt;">Rp {{ number_format($sumTotalJual, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label" style="font-size: 10pt;">TOTAL RETUR JUAL</td>
                <td class="value" style="font-size: 10pt;">Rp {{ number_format($sumTotalReturJual, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label" style="font-size: 10pt;">NET SALES</td>
                <td class="value" style="font-size: 10pt; font-weight: bold;">Rp {{ number_format($netSales, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="label" style="font-size: 12pt;">TOTAL TAGIHAN BERSIH</td>
                <td class="value" style="font-size: 12pt; font-weight: bold;">Rp {{ number_format($kontrabon->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="clear: both; margin-top: 20px;">
            <p><strong>Catatan:</strong><br>{{ $kontrabon->notes ?: '-' }}</p>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    Dibuat Oleh<br>
                    ({{ auth()->user() ? auth()->user()->name : 'Admin' }})
                </td>
                <td>
                    <div class="signature-line"></div>
                    Disetujui Oleh<br>
                    (............................)
                </td>
                <td>
                    <div class="signature-line"></div>
                    Supplier<br>
                    ({{ $kontrabon->supplier ? $kontrabon->supplier->name : '............................' }})
                </td>
            </tr>
        </table>
    </div>
@endsection
