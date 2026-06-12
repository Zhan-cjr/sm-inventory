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
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('contact_person'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address')
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
            ]);
    }
}
