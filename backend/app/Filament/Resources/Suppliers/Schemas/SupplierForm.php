<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                TextInput::make('code')
                    ->default(function () {
                        $dateCode = now()->format('dmy'); // DDMMYY
                        $lastSupplier = \App\Models\Supplier::where('code', 'like', "SUP-{$dateCode}%")
                            ->orderBy('code', 'desc')
                            ->first();

                        if ($lastSupplier) {
                            $lastSequence = (int) substr($lastSupplier->code, -3);
                            $nextSequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
                        } else {
                            $nextSequence = '001';
                        }

                        return "SUP-{$dateCode}{$nextSequence}";
                    })
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Pemasok')
                    ->default(fn () => request()->query('name'))
                    ->required(),
                TextInput::make('npwp')
                    ->label('NPWP Pemasok')
                    ->placeholder('Contoh: 01.300.553.3-092.000')
                    ->default(fn () => request()->query('npwp'))
                    ->maxLength(50),
                TextInput::make('contact_person')
                    ->label('Contact Person (Pusat)'),
                TextInput::make('phone')
                    ->label('No. Telepon / HP')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address')
                    ->label('Alamat Kantor Pusat')
                    ->default(fn () => request()->query('address'))
                    ->columnSpanFull(),
                TextInput::make('default_due_days')
                    ->label('Jatuh Tempo Default (Hari)')
                    ->numeric()
                    ->default(0)
                    ->helperText('0 = Cash/COD. Di atas 0 = Tempo (Kredit)'),
                TextInput::make('default_po_expired_days')
                    ->label('Expired PO Default (Hari)')
                    ->numeric()
                    ->default(0)
                    ->helperText('0 = Tidak ada expired. Di atas 0 = Otomatis set expired PO berdasarkan Approval Date + jumlah hari ini.'),
                Select::make('payment_method')
                    ->label('Cara Pembayaran Default')
                    ->options([
                        'cash' => 'Tunai (Cash)',
                        'transfer' => 'Transfer Bank',
                        'giro' => 'Cek / Bilyet Giro',
                        'other' => 'Lainnya',
                    ])
                    ->default('transfer'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Toggle::make('is_consignment')
                    ->label('Supplier Konsinyasi / Titip Jual')
                    ->helperText('Jika aktif, seluruh barang dari supplier ini tidak diakui sebagai Hutang/Aset saat diterima.')
                    ->default(false),
                Toggle::make('gr_requires_po')
                    ->label('Penerimaan Wajib PO')
                    ->helperText('Jika aktif, penerimaan barang dari supplier ini wajib menyertakan Purchase Order (PO).')
                    ->default(true)
                    ->disabled(fn() => ! auth()->user()->hasCustomAuthorization('TOGGLE_SUPPLIER_GR_PO')),
            ]);
    }
}
