<?php

namespace App\Filament\Resources\PosDevices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Forms\Get;

class PosDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('device_uuid')
                    ->label('Device UUID')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->placeholder('Dihasilkan otomatis oleh perangkat client'),
                
                TextInput::make('name')
                    ->label('Nama Perangkat')
                    ->placeholder('Masukkan nama perangkat, misal: PC Kasir 1')
                    ->required(),

                Select::make('branch_id')
                    ->label('Cabang (Branch)')
                    ->relationship('branch', 'name')
                    ->placeholder('Pilih Cabang Terkunci')
                    ->nullable()
                    ->live(),

                Select::make('terminal_id')
                    ->label('Terminal POS')
                    ->placeholder('Pilih Terminal POS Terkunci')
                    ->options(function ($get) {
                        $branchId = $get('branch_id');
                        if (!$branchId) {
                            return [];
                        }
                        return \App\Models\Terminal::where('branch_id', $branchId)->pluck('name', 'id');
                    })
                    ->nullable(),

                Select::make('status')
                    ->label('Status Otorisasi')
                    ->options([
                        'PENDING' => 'Menunggu Persetujuan',
                        'APPROVED' => 'Disetujui (Approved)',
                        'BLOCKED' => 'Diblokir (Blocked)',
                    ])
                    ->required()
                    ->default('PENDING')
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state === 'APPROVED') {
                            $set('approved_at', now()->toDateTimeString());
                            $set('blocked_at', null);
                        } elseif ($state === 'BLOCKED') {
                            $set('blocked_at', now()->toDateTimeString());
                            $set('approved_at', null);
                        } else {
                            $set('approved_at', null);
                            $set('blocked_at', null);
                        }
                    }),

                DateTimePicker::make('approved_at')
                    ->label('Disetujui Pada')
                    ->disabled()
                    ->dehydrated(),

                DateTimePicker::make('blocked_at')
                    ->label('Diblokir Pada')
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('user_agent')
                    ->label('Browser/Sistem Info (User Agent)')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull(),
            ]);
    }
}
