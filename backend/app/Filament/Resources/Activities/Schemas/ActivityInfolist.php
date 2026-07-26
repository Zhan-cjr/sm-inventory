<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Log Aktivitas')
                    ->description('Rincian pelaku, modul, dan waktu kejadian perubahan data')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Waktu Kejadian')
                            ->dateTime('d M Y, H:i:s'),

                        TextEntry::make('causer.name')
                            ->label('Operator / Pelaku')
                            ->default('Sistem Otomatis / Background Process')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('event')
                            ->label('Jenis Tindakan')
                            ->badge()
                            ->color(fn ($state) => match (strtolower($state ?? '')) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match (strtolower($state ?? '')) {
                                'created' => 'Data Baru Dibuat',
                                'updated' => 'Perubahan Data (Edit)',
                                'deleted' => 'Data Dihapus',
                                default => ucfirst($state ?? '-'),
                            }),

                        TextEntry::make('subject_type')
                            ->label('Modul Sistem')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'App\Models\PurchaseOrder' => 'Pesanan Pembelian (PO)',
                                'App\Models\GoodsReceipt' => 'Penerimaan Barang',
                                'App\Models\PurchasePayment' => 'Pembayaran Hutang',
                                'App\Models\Kontrabon' => 'Tukar Faktur (Kontrabon)',
                                'App\Models\User' => 'Data Pengguna / Operator',
                                'App\Models\Product' => 'Master Produk',
                                'App\Models\Supplier' => 'Data Supplier',
                                'App\Models\Branch' => 'Data Cabang',
                                'App\Models\Transaction' => 'Transaksi Penjualan',
                                default => class_basename($state ?? '-'),
                            }),

                        TextEntry::make('description')
                            ->label('Keterangan Ringkas')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Visual Audit Log: Perbandingan Nilai (Before vs After)')
                    ->description('Rincian perubahan nilai data sebelum dan sesudah diedit oleh pengguna')
                    ->schema([
                        TextEntry::make('id')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->state(function ($record) {
                                $rawProps = $record->properties;
                                if ($rawProps instanceof \Illuminate\Support\Collection) {
                                    $properties = $rawProps->toArray();
                                } elseif (is_string($rawProps)) {
                                    $properties = json_decode($rawProps, true) ?? [];
                                } elseif (is_array($rawProps)) {
                                    $properties = $rawProps;
                                } elseif (is_object($rawProps)) {
                                    $properties = json_decode(json_encode($rawProps), true) ?? [];
                                } else {
                                    $properties = [];
                                }

                                $old = $properties['old'] ?? [];
                                $attributes = $properties['attributes'] ?? [];

                                if (empty($old) && empty($attributes) && !empty($properties)) {
                                    $attributes = $properties;
                                }

                                if (empty($old) && empty($attributes)) {
                                    return '<div style="padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 13px; color: #64748b; text-align: center;">Tidak ada rincian perubahan atribut spesifik yang terrekam.</div>';
                                }

                                $fieldLabels = [
                                    'name' => 'Nama',
                                    'title' => 'Judul',
                                    'sku' => 'Kode SKU',
                                    'barcode' => 'Barcode',
                                    'price' => 'Harga Jual Master',
                                    'selling_price' => 'Harga Jual Eceran Cabang',
                                    'cost_price' => 'Harga Modal / HPP',
                                    'cost_price_tax' => 'Harga Modal + Pajak',
                                    'harga_jual_1' => 'Harga Jual Grosir 1',
                                    'harga_jual_2' => 'Harga Jual Grosir 2',
                                    'harga_jual_3' => 'Harga Jual Grosir 3',
                                    'margin_gol_1' => 'Margin Grosir 1 (%)',
                                    'margin_gol_2' => 'Margin Grosir 2 (%)',
                                    'margin_gol_3' => 'Margin Grosir 3 (%)',
                                    'qty_min_gol_1' => 'Qty Min Grosir 1',
                                    'qty_min_gol_2' => 'Qty Min Grosir 2',
                                    'qty_min_gol_3' => 'Qty Min Grosir 3',
                                    'quantity_on_hand' => 'Stok Sisa',
                                    'min_qty' => 'Batas Minimum Stok',
                                    'max_qty' => 'Batas Maksimum Stok',
                                    'status' => 'Status Aktivasi',
                                    'is_active' => 'Status Aktif',
                                    'is_voided' => 'Status Pembatalan (Void)',
                                    'void_reason' => 'Alasan Void',
                                    'phone' => 'No. Telepon',
                                    'email' => 'Alamat Email',
                                    'address' => 'Alamat Lengkap',
                                    'discount_amount' => 'Nominal Diskon',
                                    'final_amount' => 'Total Akhir Nota',
                                    'total_amount' => 'Total Belanja',
                                    'notes' => 'Catatan / Keterangan',
                                    'supplier_id' => 'ID Supplier',
                                    'branch_id' => 'ID Cabang',
                                    'customer_id' => 'ID Pelanggan',
                                    'role' => 'Peran / Hak Akses',
                                ];

                                $currencyKeys = [
                                    'price', 'selling_price', 'cost_price', 'cost_price_tax',
                                    'harga_jual_1', 'harga_jual_2', 'harga_jual_3',
                                    'discount_amount', 'final_amount', 'total_amount'
                                ];

                                $allKeys = array_unique(array_merge(array_keys($old), array_keys($attributes)));
                                $rowsHtml = '';

                                foreach ($allKeys as $key) {
                                    // Skip timestamp tracking internal
                                    if (in_array($key, ['updated_at', 'created_at', 'remember_token', 'password'])) {
                                        continue;
                                    }

                                    $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                    $oldVal = $old[$key] ?? null;
                                    $newVal = $attributes[$key] ?? null;

                                    $formatVal = function ($val, $k) use ($currencyKeys) {
                                        if (is_null($val)) return '-';
                                        if (is_bool($val)) return $val ? 'Ya / Aktif' : 'Tidak / Nonaktif';
                                        if (is_array($val)) return json_encode($val);
                                        if (in_array($k, $currencyKeys) && is_numeric($val)) {
                                            return 'Rp ' . number_format((float)$val, 0, ',', '.');
                                        }
                                        return (string) $val;
                                    };

                                    $oldFormatted = $formatVal($oldVal, $key);
                                    $newFormatted = $formatVal($newVal, $key);

                                    $isChanged = ($oldVal != $newVal);

                                    $oldStyle = $isChanged 
                                        ? 'background: rgba(244, 63, 94, 0.12); color: #be123c; font-weight: 700; padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(244, 63, 94, 0.25); font-family: sans-serif;'
                                        : 'color: #64748b; font-family: sans-serif;';

                                    $newStyle = $isChanged 
                                        ? 'background: rgba(16, 185, 129, 0.12); color: #047857; font-weight: 700; padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.25); font-family: sans-serif;'
                                        : 'color: #334155; font-family: sans-serif;';

                                    $rowsHtml .= '
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 10px 12px; font-weight: 700; color: #334155; font-size: 13px;">' . e($label) . '</td>
                                            <td style="padding: 10px 12px; font-size: 13px;"><span style="' . $oldStyle . '">' . e($oldFormatted) . '</span></td>
                                            <td style="padding: 10px 12px; font-size: 13px;"><span style="' . $newStyle . '">' . e($newFormatted) . '</span></td>
                                        </tr>
                                    ';
                                }

                                if (empty($rowsHtml)) {
                                    return '<div style="padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 13px; color: #64748b; text-align: center;">Tidak ada perubahan field spesifik yang dideteksi.</div>';
                                }

                                return '
                                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #ffffff; width: 100%;">
                                        <table style="width: 100%; text-align: left; border-collapse: collapse;">
                                            <thead>
                                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                                    <th style="padding: 10px 12px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Parameter Data</th>
                                                    <th style="padding: 10px 12px; font-size: 12px; font-weight: 800; color: #be123c; text-transform: uppercase; letter-spacing: 0.05em;">Nilai Lama (Before)</th>
                                                    <th style="padding: 10px 12px; font-size: 12px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.05em;">Nilai Baru (After)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ' . $rowsHtml . '
                                            </tbody>
                                        </table>
                                    </div>
                                ';
                            }),
                    ]),
            ]);
    }
}
