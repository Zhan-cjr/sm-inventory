<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (?\Illuminate\Database\Eloquent\Model $record): bool => $record === null)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('telegram_chat_id')
                    ->label('Telegram Chat ID')
                    ->numeric()
                    ->helperText('ID unik Telegram pengguna. Gunakan fitur pesan pribadi untuk menerima notifikasi otorisasi.'),
                \Filament\Schemas\Components\Tabs::make('Authorizations')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Otorisasi POS Kasir')
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('pos_authorizations')
                                    ->label('Izin Otorisasi POS Kasir')
                                    ->options([
                                        'DISCOUNT' => 'Diskon (Manual & Total)',
                                        'OPEN_PRICE' => 'Ubah Harga (Open Price)',
                                        'MULTI_PAYMENT' => 'Multi Payment',
                                        'VOUCHER' => 'Bayar dgn Voucher',
                                        'VOID' => 'Void / Hapus Item',
                                        'RETURN' => 'Retur Transaksi',
                                        'CLOSE_SHIFT' => 'Tutup Kasir',
                                        'REPRINT_LAST' => 'Reprint Nota Terakhir',
                                        'REPRINT_OLD' => 'Reprint Nota Lama',
                                        'HOLD_RECALL' => 'Hold & Recall Transaksi',
                                    ])
                                    ->columns(2)
                                    ->helperText('Centang menu apa saja yang bisa diotorisasi oleh user ini di POS Kasir (wajib mengisi password user saat otorisasi).'),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Otorisasi Lainnya')
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('custom_authorizations')
                                    ->label('Izin Khusus Backend (Admin/Manajer)')
                                    ->options([
                                        'CANCEL_TRANSACTION' => 'Batalkan Transaksi (Arsip)',
                                        'UPDATE_SELLING_PRICE' => 'Update Harga Jual (Penerimaan Barang)',
                                        'APPROVE_PO' => 'Approval Purchase Order (PO)',
                                        'APPROVE_STOCK_ADJUSTMENT' => 'Approval Koreksi Stok (SO)',
                                    ])
                                    ->columns(2)
                                    ->helperText('Centang izin khusus untuk backend, berlaku spesifik untuk user ini (termasuk Admin/Superadmin wajib dicentang).'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
