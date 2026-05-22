<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama & Operasional')
                    ->description('Pengaturan profil perusahaan, nama cabang utama, dan loyalitas poin.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Perusahaan/Toserba')
                                ->required(),
                            TextInput::make('code')
                                ->label('Kode Unik')
                                ->required(),
                            TextInput::make('timezone')
                                ->label('Zona Waktu')
                                ->required()
                                ->default('Asia/Jakarta'),
                            TextInput::make('currency_code')
                                ->label('Mata Uang')
                                ->required()
                                ->default('IDR'),
                            TextInput::make('point_conversion_rate')
                                ->label('Nilai Belanja per 1 Poin (Rp)')
                                ->required()
                                ->numeric()
                                ->default(1000)
                                ->helperText('Contoh: jika diisi 1000, maka setiap kelipatan Rp 1.000 belanja akan mendapatkan 1 poin.'),
                            TextInput::make('point_redemption_value')
                                ->label('Nilai Rupiah per 1 Poin ditukarkan (Rp)')
                                ->required()
                                ->numeric()
                                ->default(1.00)
                                ->helperText('Contoh: jika diisi 10, maka setiap 1 poin yang ditukarkan bernilai diskon Rp 10.'),
                            TextInput::make('minimum_points_to_redeem')
                                ->label('Batas Minimal Poin untuk Ditukarkan')
                                ->required()
                                ->numeric()
                                ->default(100)
                                ->helperText('Contoh: jika diisi 100, pelanggan harus menukarkan minimal 100 poin untuk mendapatkan potongan belanja.')
                                ->columnSpanFull(),
                        ])
                    ]),
                Section::make('Pengaturan WhatsApp Gateway')
                    ->description('Pilih layanan WhatsApp Gateway yang digunakan untuk mengirim notifikasi pesanan dan OTP reset kata sandi.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('wa_gateway_type')
                                ->label('Tipe WhatsApp Gateway')
                                ->options([
                                    'fonnte' => 'Fonnte (Layanan Pihak Ketiga)',
                                    'local' => 'Local WA Gateway (Custom Curl API)',
                                ])
                                ->default('fonnte')
                                ->reactive()
                                ->required(),
                            TextInput::make('wa_gateway_domain')
                                ->label('Domain / Endpoint URL WhatsApp Gateway')
                                ->placeholder('Contoh: http://localhost:8000/send-message')
                                ->helperText('Masukkan URL lengkap endpoint pengiriman gateway lokal Anda (misal: http://127.0.0.1:3000/api/send).')
                                ->required(fn ($get) => $get('wa_gateway_type') === 'local')
                                ->visible(fn ($get) => $get('wa_gateway_type') === 'local')
                                ->url()
                                ->columnSpanFull(),
                            TextInput::make('wa_gateway_sender')
                                ->label('Nomor Pengirim WhatsApp Gateway (Sender)')
                                ->placeholder('Contoh: 628123456789')
                                ->helperText('Nomor WhatsApp pengirim yang terdaftar/aktif di gateway lokal Anda (hanya digunakan untuk tipe Local WA Gateway).')
                                ->required(fn ($get) => $get('wa_gateway_type') === 'local')
                                ->visible(fn ($get) => $get('wa_gateway_type') === 'local')
                                ->columnSpanFull(),
                            TextInput::make('wa_gateway_token')
                                ->label('API Key / Token WhatsApp Gateway')
                                ->placeholder('Masukkan token Fonnte atau API key gateway lokal Anda')
                                ->password()
                                ->helperText('Token atau API Key otorisasi WhatsApp Gateway Anda.')
                                ->required(fn ($get) => $get('wa_gateway_type') !== null)
                                ->columnSpanFull(),
                        ])
                    ])
            ]);
    }
}
