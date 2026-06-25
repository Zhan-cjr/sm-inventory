<?php

namespace App\Filament\Resources\TaxInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('organization_id')
                    ->default(fn () => \Illuminate\Support\Facades\Auth::user()->organization_id ?? 1)
                    ->required(),
                Select::make('type')
                    ->label('Jenis Pajak')
                    ->options([
                        'masukan' => 'Pajak Masukan (Pembelian)',
                        'keluaran' => 'Pajak Keluaran (Penjualan)',
                    ])
                    ->required(),
                TextInput::make('nomor_faktur')
                    ->label('Nomor Seri Faktur Pajak')
                    ->required()
                    ->maxLength(50),
                DatePicker::make('tanggal_faktur')
                    ->label('Tanggal Faktur')
                    ->required(),
                TextInput::make('masa_pajak')
                    ->label('Masa Pajak (Bulan-Tahun)')
                    ->placeholder('MM-YYYY')
                    ->required()
                    ->maxLength(7),

                TextInput::make('npwp_lawan')
                    ->label('NPWP Lawan')
                    ->maxLength(50),
                TextInput::make('nama_lawan')
                    ->label('Nama PT / Lawan Transaksi')
                    ->maxLength(150),

                TextInput::make('dpp')
                    ->label('Dasar Pengenaan Pajak (DPP)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),
                TextInput::make('ppn')
                    ->label('Nilai PPN')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->label('Status Laporan')
                    ->options([
                        'draft' => 'Belum Dilaporkan (Draft)',
                        'reported' => 'Sudah Dilaporkan',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
