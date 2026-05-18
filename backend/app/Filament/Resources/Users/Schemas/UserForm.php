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
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
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
                \Filament\Forms\Components\CheckboxList::make('pos_authorizations')
                    ->label('Izin Otorisasi POS Kasir')
                    ->options([
                        'DISCOUNT' => 'Diskon (Manual & Total)',
                        'VOID' => 'Void / Hapus Item',
                        'RETURN' => 'Retur Transaksi',
                        'CLOSE_SHIFT' => 'Tutup Kasir',
                        'MENU' => 'Akses Menu (Pengaturan Kassa)',
                        'HOLD_RECALL' => 'Hold & Recall Transaksi',
                    ])
                    ->columns(2)
                    ->helperText('Centang menu apa saja yang bisa diotorisasi oleh user ini di POS Kasir (wajib mengisi password user saat otorisasi).'),
            ]);
    }
}
