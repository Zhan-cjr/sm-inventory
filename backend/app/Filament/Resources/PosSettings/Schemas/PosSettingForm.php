<?php

namespace App\Filament\Resources\PosSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Checkbox;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Models\Branch;

class PosSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Pengaturan POS')
                    ->tabs([
                        Tabs\Tab::make('General Setting')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make('Pengaturan Umum POS')
                                    ->description('Kelola parameter operasional umum untuk aplikasi kasir.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('name')
                                                ->label('Nama Organisasi')
                                                ->disabled(),
                                            TextInput::make('code')
                                                ->label('Kode Organisasi')
                                                ->disabled(),
                                            Toggle::make('allow_minus_stock')
                                                ->label('Izinkan Stok Minus')
                                                ->helperText('Jika diaktifkan, kasir dapat menjual produk meskipun stok fisik tercatat kosong.')
                                                ->required(),
                                        ])
                                    ])
                            ]),

                        Tabs\Tab::make('Pengaturan Tombol')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Section::make('Pintasan Keyboard & Tombol Kasir')
                                    ->description('Sesuaikan tombol pintasan keyboard untuk mempercepat transaksi kasir.')
                                    ->schema([
                                        Repeater::make('posSettings')
                                            ->relationship('posSettings')
                                            ->label('Tombol POS')
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columns(4)
                                            ->schema([
                                                TextInput::make('key_name')
                                                    ->label('Fungsi POS')
                                                    ->disabled()
                                                    ->dehydrated(),
                                                TextInput::make('display_name')
                                                    ->label('Label Tombol')
                                                    ->required(),
                                                TextInput::make('shortcut_key')
                                                    ->label('Shortcut (Klik & Tekan)')
                                                    ->extraInputAttributes([
                                                        'x-on:keydown.prevent' => "
                                                            let k = \$event.key;
                                                            if (['Shift', 'Control', 'Alt', 'Meta', 'CapsLock', 'Tab', 'ContextMenu'].includes(k)) return;
                                                            \$el.value = k === ' ' ? 'Space' : k === 'Backspace' ? '' : k;
                                                            \$el.dispatchEvent(new Event('input', { bubbles: true }));
                                                            \$el.blur();
                                                        ",
                                                        'readonly' => true,
                                                        'style' => 'cursor: pointer; caret-color: transparent;',
                                                        'title' => 'Klik pada kotak ini lalu tekan tombol pada keyboard. Tekan Backspace untuk menghapus.'
                                                    ]),
                                                Toggle::make('is_active')
                                                    ->label('Aktif')
                                                    ->default(true),
                                            ])
                                    ])
                            ]),

                        Tabs\Tab::make('Pengaturan Struk')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Layout Cetak Struk')
                                    ->description('Konfigurasi teks struk belanja, logo, dan PPN untuk printer kasir thermal.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('selected_branch_id')
                                                ->label('Lokasi TOKO')
                                                ->options(fn ($record) => $record ? $record->branches()->pluck('name', 'id') : [])
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if (!$state) return;
                                                    $branch = Branch::find($state);
                                                    if ($branch) {
                                                        $set('receipt_footer_layout', $branch->receipt_footer_layout);
                                                        $set('receipt_show_logo', $branch->receipt_show_logo);
                                                        $set('receipt_show_tax', $branch->receipt_show_tax);
                                                        $set('receipt_tax_message', $branch->receipt_tax_message);
                                                        $set('receipt_tax_rate', $branch->receipt_tax_rate);
                                                        $set('receipt_tax_rate_message', $branch->receipt_tax_rate_message);
                                                        $set('receipt_dpp_rate', $branch->receipt_dpp_rate);
                                                        $set('receipt_dpp_message', $branch->receipt_dpp_message);
                                                        $set('receipt_total_tax_message', $branch->receipt_total_tax_message);
                                                        
                                                        $set('receipt_header_line1', $branch->receipt_header_line1);
                                                        $set('receipt_header_line1_bold', $branch->receipt_header_line1_bold);
                                                        $set('receipt_header_line2', $branch->receipt_header_line2);
                                                        $set('receipt_header_line2_bold', $branch->receipt_header_line2_bold);
                                                        $set('receipt_header_line3', $branch->receipt_header_line3);
                                                        $set('receipt_header_line3_bold', $branch->receipt_header_line3_bold);
                                                        $set('receipt_header_line4', $branch->receipt_header_line4);
                                                        $set('receipt_header_line4_bold', $branch->receipt_header_line4_bold);
                                                        
                                                        $set('receipt_footer_line1', $branch->receipt_footer_line1);
                                                        $set('receipt_footer_line1_bold', $branch->receipt_footer_line1_bold);
                                                        $set('receipt_footer_line2', $branch->receipt_footer_line2);
                                                        $set('receipt_footer_line2_bold', $branch->receipt_footer_line2_bold);
                                                        $set('receipt_footer_line3', $branch->receipt_footer_line3);
                                                        $set('receipt_footer_line3_bold', $branch->receipt_footer_line3_bold);
                                                        $set('receipt_footer_line4', $branch->receipt_footer_line4);
                                                        $set('receipt_footer_line4_bold', $branch->receipt_footer_line4_bold);
                                                        $set('receipt_footer_line5', $branch->receipt_footer_line5);
                                                        $set('receipt_footer_line5_bold', $branch->receipt_footer_line5_bold);
                                                        $set('receipt_footer_line6', $branch->receipt_footer_line6);
                                                        $set('receipt_footer_line6_bold', $branch->receipt_footer_line6_bold);
                                                    }
                                                })
                                                ->afterStateHydrated(function ($component, $state, callable $set, $record) {
                                                    if ($record) {
                                                        $firstBranch = $record->branches()->first();
                                                        if ($firstBranch) {
                                                            $component->state($firstBranch->id);
                                                            $set('receipt_footer_layout', $firstBranch->receipt_footer_layout);
                                                            $set('receipt_show_logo', $firstBranch->receipt_show_logo);
                                                            $set('receipt_show_tax', $firstBranch->receipt_show_tax);
                                                            $set('receipt_tax_message', $firstBranch->receipt_tax_message);
                                                            $set('receipt_tax_rate', $firstBranch->receipt_tax_rate);
                                                            $set('receipt_tax_rate_message', $firstBranch->receipt_tax_rate_message);
                                                            $set('receipt_dpp_rate', $firstBranch->receipt_dpp_rate);
                                                            $set('receipt_dpp_message', $firstBranch->receipt_dpp_message);
                                                            $set('receipt_total_tax_message', $firstBranch->receipt_total_tax_message);
                                                            
                                                            $set('receipt_header_line1', $firstBranch->receipt_header_line1);
                                                            $set('receipt_header_line1_bold', $firstBranch->receipt_header_line1_bold);
                                                            $set('receipt_header_line2', $firstBranch->receipt_header_line2);
                                                            $set('receipt_header_line2_bold', $firstBranch->receipt_header_line2_bold);
                                                            $set('receipt_header_line3', $firstBranch->receipt_header_line3);
                                                            $set('receipt_header_line3_bold', $firstBranch->receipt_header_line3_bold);
                                                            $set('receipt_header_line4', $firstBranch->receipt_header_line4);
                                                            $set('receipt_header_line4_bold', $firstBranch->receipt_header_line4_bold);
                                                            
                                                            $set('receipt_footer_line1', $firstBranch->receipt_footer_line1);
                                                            $set('receipt_footer_line1_bold', $firstBranch->receipt_footer_line1_bold);
                                                            $set('receipt_footer_line2', $firstBranch->receipt_footer_line2);
                                                            $set('receipt_footer_line2_bold', $firstBranch->receipt_footer_line2_bold);
                                                            $set('receipt_footer_line3', $firstBranch->receipt_footer_line3);
                                                            $set('receipt_footer_line3_bold', $firstBranch->receipt_footer_line3_bold);
                                                            $set('receipt_footer_line4', $firstBranch->receipt_footer_line4);
                                                            $set('receipt_footer_line4_bold', $firstBranch->receipt_footer_line4_bold);
                                                            $set('receipt_footer_line5', $firstBranch->receipt_footer_line5);
                                                            $set('receipt_footer_line5_bold', $firstBranch->receipt_footer_line5_bold);
                                                            $set('receipt_footer_line6', $firstBranch->receipt_footer_line6);
                                                            $set('receipt_footer_line6_bold', $firstBranch->receipt_footer_line6_bold);
                                                        }
                                                    }
                                                })
                                                ->required(),
                                                
                                            Radio::make('receipt_footer_layout')
                                                ->label('Footer Line')
                                                ->options([
                                                    2 => '2 Line',
                                                    4 => '4 Line',
                                                    6 => '6 Line',
                                                ])
                                                ->default(4)
                                                ->inline()
                                                ->reactive()
                                                ->required(),

                                            Toggle::make('receipt_show_logo')
                                                ->label('Tampilkan logo perusahaan')
                                                ->default(false),
                                        ]),

                                        // Informasi PPN Section
                                        Section::make('Informasi PPN')
                                            ->collapsible()
                                            ->schema([
                                                Checkbox::make('receipt_show_tax')
                                                    ->label('Cetak Informasi PPN')
                                                    ->reactive()
                                                    ->default(false),
                                                Grid::make(2)->schema([
                                                    TextInput::make('receipt_tax_message')
                                                        ->label('Message')
                                                        ->default('Harga di atas sudah termasuk PPN')
                                                        ->visible(fn ($get) => $get('receipt_show_tax')),
                                                    Grid::make(2)->schema([
                                                        TextInput::make('receipt_tax_rate')
                                                            ->label('PPn (%)')
                                                            ->numeric()
                                                            ->default(11.00),
                                                        TextInput::make('receipt_tax_rate_message')
                                                            ->label('Message')
                                                            ->default('Tarif PPn'),
                                                    ])->visible(fn ($get) => $get('receipt_show_tax')),
                                                    Grid::make(2)->schema([
                                                        TextInput::make('receipt_dpp_rate')
                                                            ->label('DPP')
                                                            ->numeric()
                                                            ->default(1.11),
                                                        TextInput::make('receipt_dpp_message')
                                                            ->label('Message')
                                                            ->default('SblmPPn'),
                                                    ])->visible(fn ($get) => $get('receipt_show_tax')),
                                                    TextInput::make('receipt_total_tax_message')
                                                        ->label('Message Total PPN')
                                                        ->default('NilPPn')
                                                        ->visible(fn ($get) => $get('receipt_show_tax')),
                                                ]),
                                            ]),

                                        // Header Lines Section
                                        Section::make('Header Struk')
                                            ->description('Tentukan baris pembuka di bagian atas struk belanja.')
                                            ->collapsible()
                                            ->schema(
                                                array_merge(
                                                    [
                                                        Placeholder::make('header_helper')
                                                            ->content('Tip: Anda dapat mengetik manual atau gunakan tag di bawah untuk menyesuaikan data otomatis. Tag: {org_name} (Nama Organisasi), {branch_name} (Nama Cabang), {branch_address} (Alamat Cabang), {branch_phone} (No Telepon Cabang).')
                                                    ],
                                                    array_map(function ($i) {
                                                        return Grid::make(3)->schema([
                                                            TextInput::make("receipt_header_line{$i}")
                                                                ->label("Line {$i}")
                                                                ->columnSpan(2)
                                                                ->suffixAction(
                                                                    Action::make("tagHeader{$i}")
                                                                        ->icon('heroicon-m-plus')
                                                                        ->label('Tag')
                                                                        ->form([
                                                                            Select::make('tag')
                                                                                ->label('Pilih Tag Otomatis')
                                                                                ->options([
                                                                                    '{org_name}' => 'Nama Organisasi',
                                                                                    '{branch_name}' => 'Nama Cabang',
                                                                                    '{branch_address}' => 'Alamat Cabang',
                                                                                    '{branch_phone}' => 'Telepon Cabang',
                                                                                ])
                                                                                ->required()
                                                                        ])
                                                                        ->action(function ($data, $state, callable $set) use ($i) {
                                                                            $set("receipt_header_line{$i}", $data['tag']);
                                                                        })
                                                                ),
                                                            Checkbox::make("receipt_header_line{$i}_bold")
                                                                ->label('Tebal')
                                                                ->default(false),
                                                        ]);
                                                    }, range(1, 4))
                                                )
                                            ),

                                        // Footer Lines Section
                                        Section::make('Footer Struk')
                                            ->description('Tentukan baris penutup di bagian bawah struk belanja.')
                                            ->collapsible()
                                            ->schema(
                                                array_map(function ($i) {
                                                    return Grid::make(3)->schema([
                                                        TextInput::make("receipt_footer_line{$i}")
                                                            ->label("Line {$i}")
                                                            ->columnSpan(2),
                                                        Checkbox::make("receipt_footer_line{$i}_bold")
                                                            ->label('Tebal')
                                                            ->default(false),
                                                    ])->visible(fn ($get) => in_array($i, [1, 2]) || ($get('receipt_footer_layout') >= $i));
                                                }, range(1, 6))
                                            ),
                                    ])
                            ]),

                        Tabs\Tab::make('Pengaturan Timbangan')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Section::make('Barcode Timbangan Digital')
                                    ->description('Konfigurasi untuk membaca barcode yang dihasilkan oleh timbangan digital (misal format 20XXXXXWWWWWC).')
                                    ->schema([
                                        Toggle::make('scale_barcode_enabled')
                                            ->label('Aktifkan Barcode Timbangan')
                                            ->helperText('Otomatis mendeteksi dan mengekstrak kuantitas (berat) dari barcode timbangan saat di-scan di kasir.')
                                            ->default(false)
                                            ->reactive(),
                                        Grid::make(2)->schema([
                                            TextInput::make('scale_barcode_prefix')
                                                ->label('Prefix Barcode')
                                                ->helperText('Angka awalan dari barcode timbangan (contoh: 20)')
                                                ->default('20')
                                                ->required()
                                                ->visible(fn ($get) => $get('scale_barcode_enabled')),
                                            TextInput::make('scale_barcode_item_code_length')
                                                ->label('Panjang Kode Barang (SKU)')
                                                ->numeric()
                                                ->default(5)
                                                ->required()
                                                ->visible(fn ($get) => $get('scale_barcode_enabled')),
                                            TextInput::make('scale_barcode_weight_length')
                                                ->label('Panjang Angka Berat/Qty')
                                                ->numeric()
                                                ->default(5)
                                                ->required()
                                                ->visible(fn ($get) => $get('scale_barcode_enabled')),
                                            TextInput::make('scale_barcode_weight_decimal_places')
                                                ->label('Jumlah Angka Desimal Berat')
                                                ->helperText('Berapa digit terakhir yang dianggap sebagai desimal (contoh: 3 berarti 00125 = 0.125)')
                                                ->numeric()
                                                ->default(3)
                                                ->required()
                                                ->visible(fn ($get) => $get('scale_barcode_enabled')),
                                        ])
                                    ])
                            ])
                    ])
            ]);
    }
}
