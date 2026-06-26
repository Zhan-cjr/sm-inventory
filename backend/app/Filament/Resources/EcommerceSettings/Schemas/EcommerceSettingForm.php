<?php

namespace App\Filament\Resources\EcommerceSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class EcommerceSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kustomisasi & Kontak E-Commerce')
                    ->description('Sesuaikan tampilan halaman depan website e-commerce dan kontak layanan pelanggan.')
                    ->schema([
                        Grid::make(2)->schema([
                            FileUpload::make('logo_path')
                                ->label('Logo E-Commerce / Perusahaan')
                                ->image()
                                ->disk('public')
                                ->directory('logos')
                                ->columnSpanFull(),
                            TextInput::make('phone')
                                ->label('No Kontak Layanan')
                                ->placeholder('Contoh: +62 812-3456-7890'),
                            TextInput::make('email')
                                ->label('Email Layanan')
                                ->email()
                                ->placeholder('Contoh: cs@toserbaselamat.com'),
                            Textarea::make('address')
                                ->label('Alamat Kantor Pusat')
                                ->placeholder('Contoh: Jl. Perintis Kemerdekaan No. 123 Sukabumi')
                                ->columnSpanFull(),
                            
                            // E-Commerce Categories
                            TagsInput::make('ecommerce_categories')
                                ->label('Daftar Kategori E-Commerce')
                                ->placeholder('Tambah kategori e-commerce baru...')
                                ->helperText('Kategori khusus untuk tampilan e-commerce (misalnya: Sembako, Makanan & Minuman, Perlengkapan Ibadah, Kesehatan & Herbal, dll). Ketik nama kategori lalu tekan Enter.')
                                ->columnSpanFull(),

                            // E-Commerce Banner & Custom Content
                            TextInput::make('ecommerce_banner_title')
                                ->label('Judul Banner Utama (Hero Title)')
                                ->placeholder('Contoh: Belanja Untung, Murah, Manfaat')
                                ->default('Belanja Untung, Murah, Manfaat')
                                ->columnSpanFull(),
                            Textarea::make('ecommerce_banner_subtitle')
                                ->label('Subjudul Banner Utama')
                                ->placeholder('Contoh: Dan InsyaAllah Berkah. Temukan berbagai kebutuhan keluarga muslim...')
                                ->default('Dan InsyaAllah Berkah. Temukan berbagai kebutuhan keluarga muslim dengan harga terbaik dari cabang Toserba Selamat terdekat Anda.')
                                ->columnSpanFull(),
                            FileUpload::make('ecommerce_banner_images')
                                ->label('Gambar Banner Promo Carousel (Multiple)')
                                ->image()
                                ->multiple()
                                ->disk('public')
                                ->directory('ecommerce_banners')
                                ->helperText('Unggah satu atau banyak gambar (rekomendasi: 1200x400px) untuk ditampilkan sebagai Promo Carousel yang bergeser. Fitur banner tunggal lama sudah diabaikan.')
                                ->columnSpanFull(),
                            TextInput::make('ecommerce_banner_cta_text')
                                ->label('Teks Tombol CTA')
                                ->placeholder('Contoh: Mulai Belanja')
                                ->default('Mulai Belanja'),
                            TextInput::make('ecommerce_announcement')
                                ->label('Pengumuman Berjalan (Running Announcement)')
                                ->placeholder('Contoh: Nikmati diskon promo khusus member baru dan kumpulkan poin belanja!')
                                ->default('Selamat datang di toko online resmi kami! Nikmati promo menarik dan poin di setiap transaksi.')
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
                Section::make('Pengaturan Logistik & Pengiriman (Biteship)')
                    ->description('Integrasi API Cek Ongkir dan Request Pickup otomatis menggunakan layanan Biteship.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('biteship_api_key')
                                ->label('API Key Biteship')
                                ->placeholder('biteship_live_xxxxxxxxxxxx')
                                ->password()
                                ->helperText('Dapatkan API Key ini dari menu Settings > API Keys di dashboard Biteship Anda.')
                                ->columnSpanFull(),
                            Select::make('logistics_markup_type')
                                ->label('Tipe Markup Ongkos Kirim')
                                ->options([
                                    'NONE' => 'Tidak Ada Markup (Harga Asli)',
                                    'FIXED' => 'Nominal Tetap (Rp)',
                                    'PERCENTAGE' => 'Persentase (%)',
                                ])
                                ->default('NONE')
                                ->helperText('Markup ini ditambahkan secara diam-diam ke ongkir yang dilihat oleh pelanggan.'),
                            TextInput::make('logistics_markup_value')
                                ->label('Nilai Markup')
                                ->numeric()
                                ->default(0)
                                ->placeholder('Contoh: 2000 (untuk Rp) atau 10 (untuk %)')
                                ->helperText('Masukkan nilai markup tanpa simbol (misal: 2000 untuk Rp 2.000 atau 10 untuk 10%).'),
                        ])
                    ])
            ]);
    }
}
