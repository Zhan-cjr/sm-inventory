@extends('print.documents.layout')

@section('content')
<div class="page-container">
    <div class="org-info">
        @php
            $orgName = \App\Models\Organization::first()->name ?? 'NAMA ORGANISASI';
            $branch = $audit->branch;
            $branchName = $branch ? $branch->name : 'Pusat / Global';
            $branchAddress = $branch ? $branch->address : (\App\Models\Organization::first()->address ?? '');
        @endphp
        <h2>{{ $orgName }}</h2>
        <p>{{ $branchName }}<br>{{ $branchAddress }}</p>
    </div>
    <div class="document-title">
        <h1>BERITA ACARA AUDIT KERANJANG KARANTINA BS</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">No. Audit</td>
            <td class="separator">:</td>
            <td><strong>{{ $audit->audit_number }}</strong></td>
            <td class="label" style="text-align: right; width: 100px;">Tanggal Audit</td>
            <td class="separator">:</td>
            <td>{{ \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Cabang Toko</td>
            <td class="separator">:</td>
            <td>{{ $branchName }}</td>
            <td class="label" style="text-align: right;">Status Dokumen</td>
            <td class="separator">:</td>
            <td><strong>{{ $audit->status }}</strong></td>
        </tr>
        <tr>
            <td class="label">Auditor / SPV</td>
            <td class="separator">:</td>
            <td>{{ $audit->auditor ? $audit->auditor->name : '-' }}</td>
            <td class="label" style="text-align: right;">Disetujui Oleh</td>
            <td class="separator">:</td>
            <td>{{ $audit->approver ? $audit->approver->name : '-' }}</td>
        </tr>
        @if($audit->notes)
        <tr>
            <td class="label">Catatan</td>
            <td class="separator">:</td>
            <td colspan="4">{{ $audit->notes }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 120px;">Barcode / SKU</th>
                <th>Nama Barang</th>
                <th class="text-right" style="width: 70px;">Sistem BS</th>
                <th class="text-right" style="width: 70px;">Fisik Riil</th>
                <th class="text-right" style="width: 70px;">Selisih</th>
                <th style="width: 130px;">Tindakan</th>
                <th class="text-right" style="width: 100px;">Valuasi (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totSys = 0; $totAct = 0; $totDisc = 0; $totCost = 0; 
            @endphp
            @foreach($audit->items as $index => $item)
            @php 
                $totSys += (float)$item->system_damaged_qty;
                $totAct += (float)$item->actual_damaged_qty;
                $totDisc += (float)$item->discrepancy_qty;
                $totCost += (float)$item->total_cost;
                $diff = (float)$item->discrepancy_qty;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 0.9em;">
                    {{ $item->product ? ($item->product->barcode ?: $item->product->sku) : '-' }}
                </td>
                <td>
                    <strong>{{ $item->product ? $item->product->name : '-' }}</strong>
                    @if($item->notes)
                        <br><small style="color: #6b7280; font-style: italic;">Note: {{ $item->notes }}</small>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->system_damaged_qty, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($item->actual_damaged_qty, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: bold; color: {{ $diff < 0 ? '#b91c1c' : ($diff > 0 ? '#15803d' : '#374151') }};">
                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', '.') }}
                </td>
                <td>
                    @if($item->action_taken === 'WRITE_OFF_DISPOSAL')
                        <span style="color: #b91c1c; font-weight: 600;">Pemusnahan (Hilang)</span>
                    @elseif($item->action_taken === 'RESTORE_TO_SALABLE')
                        <span style="color: #1d4ed8; font-weight: 600;">Kembalikan ke Rak</span>
                    @elseif($item->action_taken === 'ADJUST_INCREASE')
                        <span style="color: #d97706; font-weight: 600;">Tambah ke BS</span>
                    @else
                        <span>Sesuai</span>
                    @endif
                </td>
                <td class="text-right">
                    Rp {{ number_format(abs($item->total_cost), 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f3f4f6;">
                <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-right">{{ number_format($totSys, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totAct, 0, ',', '.') }}</td>
                <td class="text-right" style="color: {{ $totDisc < 0 ? '#b91c1c' : '#15803d' }};">
                    {{ $totDisc > 0 ? '+' : '' }}{{ number_format($totDisc, 0, ',', '.') }}
                </td>
                <td></td>
                <td class="text-right">Rp {{ number_format(abs($totCost), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section" style="margin-top: 40px; display: flex; justify-content: space-between; text-align: center;">
        <div style="width: 30%;">
            <p>Petugas Penghitung (Auditor)</p>
            <div style="height: 60px;"></div>
            <p><strong>( {{ $audit->auditor ? $audit->auditor->name : '........................' }} )</strong></p>
        </div>
        <div style="width: 30%;">
            <p>Saksi / Petugas Toko</p>
            <div style="height: 60px;"></div>
            <p><strong>( ........................ )</strong></p>
        </div>
        <div style="width: 30%;">
            <p>Menyetujui (Kepala Toko / SPV)</p>
            <div style="height: 60px;"></div>
            <p><strong>( {{ $audit->approver ? $audit->approver->name : '........................' }} )</strong></p>
        </div>
    </div>
</div>
@endsection
