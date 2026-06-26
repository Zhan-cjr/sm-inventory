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
                            TextInput::make('tax_rate')
                                ->label('Persentase PPN (%)')
                                ->numeric()
                                ->default(11)
                                ->required(),
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
                            \Filament\Forms\Components\Toggle::make('point_redemption_enabled')
                                ->label('Aktifkan Penukaran Poin')
                                ->default(true)
                                ->helperText('Jika dinonaktifkan, pelanggan tidak dapat menukarkan poin mereka sama sekali di POS Kasir maupun E-Commerce.')
                                ->columnSpanFull(),
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
                    ]),
                Section::make('Pengaturan Persetujuan (Approval Settings)')
                    ->description('Tentukan batas-batas transaksi yang membutuhkan persetujuan Manajer/Supervisor.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('po_approval_limit')
                                ->label('Limit Nominal Persetujuan PO (Rp)')
                                ->numeric()
                                ->nullable()
                                ->helperText('Jika total pesanan pembelian (PO) melebihi nominal ini, PO harus disetujui (Approval) terlebih dahulu. Kosongkan jika tidak ada batas.'),
                            \Filament\Forms\Components\Toggle::make('po_approval_max_qty_enabled')
                                ->label('Wajib Approval Jika Qty PO Melebihi Saran Sistem')
                                ->default(false)
                                ->helperText('Aktifkan jika PO memerlukan persetujuan saat Qty pesanan melebihi jumlah batas maksimal yang disarankan sistem (berdasarkan Min/Max).'),
                            TextInput::make('stock_adjustment_approval_amount_limit')
                                ->label('Batas Kewajaran Nominal Koreksi Stok (Rp)')
                                ->numeric()
                                ->nullable()
                                ->helperText('Jika total nilai nominal koreksi melebihi batas ini, koreksi stok wajib di-approve sebelum memotong/menambah stok riil. Kosongkan jika tidak ada batas.')
                                ->columnSpanFull(),
                        ])
                    ]),
                Section::make('Notifikasi Grup Telegram (Opsional)')
                    ->description('Masukkan Chat ID dari Grup Telegram untuk mengirim notifikasi persetujuan ke dalam grup secara spesifik. Kosongkan jika hanya ingin mengirim notifikasi ke personal chat supervisor.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('telegram_group_po_approval')
                                ->label('Chat ID Grup Approval PO')
                                ->placeholder('Contoh: -100123456789')
                                ->helperText('Notifikasi persetujuan Purchase Order akan dikirim ke grup ini.')
                                ->nullable(),
                            TextInput::make('telegram_group_stock_correction')
                                ->label('Chat ID Grup Approval Koreksi Stok')
                                ->placeholder('Contoh: -100123456789')
                                ->helperText('Notifikasi persetujuan Koreksi Stok akan dikirim ke grup ini.')
                                ->nullable(),
                            TextInput::make('telegram_group_warehouse_check')
                                ->label('Chat ID Grup Approval Pengecekan Gudang')
                                ->placeholder('Contoh: -100123456789')
                                ->helperText('Notifikasi persetujuan Pengecekan Gudang akan dikirim ke grup ini.')
                                ->nullable(),
                            TextInput::make('telegram_group_daily_report')
                                ->label('Chat ID Grup Laporan Harian')
                                ->placeholder('Contoh: -100123456789')
                                ->helperText('Notifikasi laporan harian akan dikirim ke grup ini.')
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                    ])
            ]);
    }
}
