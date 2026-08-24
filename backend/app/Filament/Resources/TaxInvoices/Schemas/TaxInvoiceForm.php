<?php

namespace App\Filament\Resources\TaxInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\EFakturService;
use App\Models\Supplier;
use App\Models\Organization;
use App\Filament\Resources\Suppliers\SupplierResource;

class TaxInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => \Illuminate\Support\Facades\Auth::user()->organization_id ?? Organization::first()?->id)
                    ->required(),

                Section::make('Scan e-Faktur QR Code (Otomatisasi DJP)')
                    ->description('Arahkan barcode/QR scanner ke kotak di bawah atau tempel (paste) URL validasi e-Faktur DJP.')
                    ->icon('heroicon-o-qr-code')
                    ->collapsible()
                    ->schema([
                        TextInput::make('scan_qr_url')
                            ->label('URL Validasi / Hasil Scan QR')
                            ->placeholder('Arahkan scanner fisik atau tempel hasil scan QR...')
                            ->helperText('Sistem akan langsung membaca data faktur dan mengisi seluruh formulir secara otomatis.')
                            ->prefixIcon('heroicon-o-camera')
                            ->autofocus()
                            ->extraInputAttributes([
                                'id' => 'scan_qr_input_field',
                                'autofocus' => 'autofocus',
                                'tabindex' => '1',
                            ])
                            ->suffixAction(
                                Action::make('connect_phone_gun')
                                    ->label('Hubungkan Scanner HP')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->color('primary')
                                    ->tooltip('Jadikan HP Anda sebagai Scanner Tembak Nirkabel')
                                    ->modalHeading('📱 Wireless Scanner Gun (Scan to PC)')
                                    ->modalWidth('lg')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Tutup')
                                    ->modalContent(fn () => view('filament.tax-invoices.pairing-modal'))
                            )
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (empty($state)) {
                                    return;
                                }

                                try {
                                    $service = app(EFakturService::class);
                                    $result = $service->fetchAndParse($state);
                                    $h = $result['header'];
                                    $items = $result['items'];

                                    // Auto-fill header
                                    $set('type', 'masukan');
                                    $set('nomor_faktur', $h['nomor_faktur']);
                                    $set('tanggal_faktur', $h['tanggal_faktur']);
                                    $set('masa_pajak', $h['masa_pajak']);
                                    $set('npwp_lawan', $h['npwp_penjual']);
                                    $set('nama_lawan', $h['nama_penjual']);
                                    $set('dpp', $h['dpp']);
                                    $set('ppn', $h['ppn']);

                                    // Auto-fill items repeater
                                    $set('items', $items);

                                    // Notification for approval
                                    $statusApproval = $h['status_approval'] ?? 'Faktur Valid';
                                    
                                    // Robust Supplier matching (15-digit vs 16-digit NPWP & name matching)
                                    $cleanNpwp = preg_replace('/[^0-9]/', '', $h['npwp_penjual']);
                                    $cleanNpwp15 = strlen($cleanNpwp) === 16 && str_starts_with($cleanNpwp, '0') ? substr($cleanNpwp, 1) : $cleanNpwp;
                                    $cleanNpwp16 = strlen($cleanNpwp) === 15 ? '0' . $cleanNpwp : $cleanNpwp;

                                    $supplier = Supplier::where(function ($q) use ($cleanNpwp, $cleanNpwp15, $cleanNpwp16) {
                                        $q->whereRaw("REGEXP_REPLACE(npwp, '[^0-9]', '') IN (?, ?, ?)", [$cleanNpwp, $cleanNpwp15, $cleanNpwp16])
                                          ->orWhere('npwp', 'like', "%{$cleanNpwp15}%");
                                    })->first();

                                    if (!$supplier && !empty($h['nama_penjual'])) {
                                        $supplier = Supplier::where('name', 'like', "%{$h['nama_penjual']}%")->first();
                                    }

                                    if ($supplier) {
                                        // Ensure supplier name and registered NPWP are set in the form
                                        if (empty($h['nama_penjual'])) {
                                            $set('nama_lawan', $supplier->name);
                                        }
                                        if (empty($h['npwp_penjual']) && !empty($supplier->npwp)) {
                                            $set('npwp_lawan', $supplier->npwp);
                                        }

                                        Notification::make()
                                            ->title('e-Faktur Berhasil Dimuat')
                                            ->body("Status: {$statusApproval}. Pemasok terhubung: {$supplier->name}")
                                            ->success()
                                            ->send();
                                    } else {
                                        $displayName = !empty($h['nama_penjual']) ? $h['nama_penjual'] : ('Pemasok NPWP ' . $h['npwp_penjual']);
                                        $createSupplierUrl = SupplierResource::getUrl('create') . '?' . http_build_query([
                                            'name' => $displayName,
                                            'npwp' => $h['npwp_penjual'],
                                            'address' => $h['alamat_penjual'] ?? '',
                                        ]);

                                        Notification::make()
                                            ->title('Pemasok Belum Terdaftar')
                                            ->body("Faktur valid dengan NPWP '{$h['npwp_penjual']}', namun pemasok belum ada di data master.")
                                            ->warning()
                                            ->persistent()
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('register_supplier')
                                                    ->label('Daftarkan Pemasok')
                                                    ->button()
                                                    ->color('primary')
                                                    ->url($createSupplierUrl)
                                                    ->openUrlInNewTab(),
                                            ])
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Gagal Membaca e-Faktur')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                Section::make('Informasi Faktur Pajak')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Pajak')
                            ->options([
                                'masukan' => 'Pajak Masukan (Pembelian)',
                                'keluaran' => 'Pajak Keluaran (Penjualan)',
                            ])
                            ->default('masukan')
                            ->required(),
                        TextInput::make('nomor_faktur')
                            ->label('Nomor Seri Faktur Pajak')
                            ->placeholder('040.000-26.34182924')
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
                            ->label('NPWP Lawan Transaksi / Penjual')
                            ->placeholder('Contoh: 013005533092000')
                            ->maxLength(50),
                        TextInput::make('nama_lawan')
                            ->label('Nama PT / Lawan Transaksi')
                            ->placeholder('PT INDOMARCO ADI PRIMA')
                            ->maxLength(150),
                        Select::make('status')
                            ->label('Status Laporan')
                            ->options([
                                'draft' => 'Belum Dilaporkan (Draft)',
                                'reported' => 'Sudah Dilaporkan',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Section::make('Dasar Pengenaan Pajak & Nilai PPN')
                    ->schema([
                        TextInput::make('dpp')
                            ->label('Dasar Pengenaan Pajak (DPP)')
                            ->rupiah()
                            ->default(0)
                            ->required(),
                        TextInput::make('ppn')
                            ->label('Nilai PPN')
                            ->rupiah()
                            ->default(0)
                            ->required(),
                    ])->columns(2),

                Section::make('Rincian Detail Barang / Jasa (Item Details)')
                    ->description('Daftar barang hasil ekstrak scan e-Faktur QR.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Daftar Barang')
                            ->itemLabel(function (array $state): ?string {
                                if (empty($state['name'])) {
                                    return 'Item Baru';
                                }
                                $qty = $state['jumlah_barang'] ?? 1;
                                $total = number_format((float) ($state['harga_total'] ?? 0), 0, ',', '.');
                                $dpp = number_format((float) ($state['dpp'] ?? 0), 0, ',', '.');
                                $ppn = number_format((float) ($state['ppn'] ?? 0), 0, ',', '.');
                                return "{$state['name']} (Qty: {$qty}) — Total: Rp {$total} | DPP: Rp {$dpp} | PPN: Rp {$ppn}";
                            })
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Barang / Jasa')
                                    ->placeholder('Nama produk / jasa kena pajak')
                                    ->required()
                                    ->columnSpan(7),
                                TextInput::make('jumlah_barang')
                                    ->label('Kuantitas (Qty)')
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(2),
                                TextInput::make('diskon')
                                    ->label('Diskon (Rp)')
                                    ->rupiah()
                                    ->default(0)
                                    ->columnSpan(3),
                                TextInput::make('harga_satuan')
                                    ->label('Harga Satuan')
                                    ->rupiah()
                                    ->columnSpan(3),
                                TextInput::make('harga_total')
                                    ->label('Harga Total')
                                    ->rupiah()
                                    ->columnSpan(3),
                                TextInput::make('dpp')
                                    ->label('DPP Barang')
                                    ->rupiah()
                                    ->columnSpan(3),
                                TextInput::make('ppn')
                                    ->label('PPN Barang')
                                    ->rupiah()
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Rincian Barang Manual'),
                    ]),
            ]);
    }
}
