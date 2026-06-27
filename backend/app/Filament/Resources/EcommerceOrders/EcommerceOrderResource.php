<?php

namespace App\Filament\Resources\EcommerceOrders;

use App\Filament\Resources\EcommerceOrders\Pages\ManageEcommerceOrders;
use App\Models\EcommerceOrder;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\BiteshipService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;

class EcommerceOrderResource extends Resource
{
    protected static ?string $model = EcommerceOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static \UnitEnum|string|null $navigationGroup = 'E-COMMERCE';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pesanan E-Commerce';

    protected static ?string $pluralModelLabel = 'Pesanan E-Commerce';

    protected static ?string $recordTitleAttribute = 'customer_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')
                    ->disabled()
                    ->required(),
                TextInput::make('customer_phone')
                    ->tel()
                    ->disabled()
                    ->required(),
                Select::make('delivery_method')
                    ->options([
                        'PICKUP' => 'Ambil di Cabang',
                        'DELIVERY' => 'Kirim ke Alamat',
                    ])
                    ->disabled()
                    ->required(),
                Textarea::make('delivery_address')
                    ->disabled()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'PENDING' => 'Menunggu Pembayaran / Konfirmasi',
                        'PROCESSING' => 'Sedang Diproses',
                        'COMPLETED' => 'Selesai',
                        'CANCELLED' => 'Dibatalkan',
                    ])
                    ->required(),
                TextInput::make('total_amount')
                    ->disabled()
                    ->required()
                    ->numeric(),
                Select::make('payment_method')
                    ->options([
                        'CASH' => 'Tunai (CASH)',
                        'MIDTRANS' => 'Payment Gateway (Midtrans)',
                        'QRIS' => 'QRIS',
                        'TRANSFER' => 'Transfer Bank',
                    ])
                    ->disabled(),
                Select::make('payment_status')
                    ->options([
                        'UNPAID' => 'Belum Dibayar',
                        'PAID' => 'Lunas',
                        'CHALLENGE' => 'Challenge / Review',
                        'PENDING' => 'Menunggu Pembayaran',
                        'FAILED' => 'Gagal',
                        'EXPIRED' => 'Kedaluwarsa',
                        'CANCELED' => 'Dibatalkan',
                    ])
                    ->required(),
                TextInput::make('points_redeemed')
                    ->label('Poin Ditukarkan')
                    ->disabled()
                    ->numeric(),
                TextInput::make('points_redeemed_discount')
                    ->label('Diskon Poin')
                    ->disabled()
                    ->numeric()
                    ->prefix('Rp'),
                Textarea::make('notes')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pelanggan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('customer_name')->label('Nama Pelanggan'),
                                TextEntry::make('customer_phone')->label('Nomor Telepon'),
                            ]),
                    ]),

                Section::make('Metode & Alamat Pengiriman')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('delivery_method')
                                    ->label('Metode')
                                    ->formatStateUsing(fn ($state) => $state === 'PICKUP' ? 'Ambil di Cabang' : 'Kirim ke Alamat'),
                                TextEntry::make('branch.name')
                                    ->label('Cabang Pengambilan')
                                    ->placeholder('-'),
                            ]),
                        TextEntry::make('delivery_address')
                            ->label('Alamat Lengkap')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Catatan Pelanggan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Detail Belanja')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Produk Dipesan')
                            ->schema([
                                TextEntry::make('product.name')->label('Nama Produk'),
                                TextEntry::make('quantity')->label('Jumlah'),
                                TextEntry::make('price')
                                    ->label('Harga Satuan')
                                    ->money('IDR'),
                                TextEntry::make('subtotal')
                                    ->label('Total')
                                    ->money('IDR'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Pembayaran')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Pesanan')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'PENDING' => 'warning',
                                        'PROCESSING' => 'info',
                                        'COMPLETED' => 'success',
                                        'CANCELLED' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('processedBy.name')
                                    ->label('Diproses Oleh')
                                    ->placeholder('-'),
                                TextEntry::make('payment_method')
                                    ->label('Metode Bayar')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('payment_status')
                                    ->label('Status Bayar')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'UNPAID', 'PENDING' => 'warning',
                                        'PAID' => 'success',
                                        'FAILED', 'EXPIRED', 'CANCELED' => 'danger',
                                        'CHALLENGE' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('points_redeemed')
                                    ->label('Poin Ditukarkan')
                                    ->numeric()
                                    ->placeholder('0'),
                                TextEntry::make('points_redeemed_discount')
                                    ->label('Potongan Poin')
                                    ->money('IDR')
                                    ->placeholder('Rp 0'),
                                TextEntry::make('total_amount')
                                    ->label('Total Akhir (Bayar)')
                                    ->money('IDR'),
                                TextEntry::make('created_at')
                                    ->label('Waktu Pemesanan')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer_name')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal Order')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Nama Pembeli')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('delivery_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn ($state) => $state === 'PICKUP' ? 'success' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'PICKUP' ? 'Ambil' : 'Kirim'),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'PROCESSING' => 'info',
                        'COMPLETED' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('processedBy.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->label('Tipe Bayar')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('payment_status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'UNPAID', 'PENDING' => 'warning',
                        'PAID' => 'success',
                        'FAILED', 'EXPIRED', 'CANCELED' => 'danger',
                        'CHALLENGE' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('points_redeemed_discount')
                    ->label('Potongan Poin')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('request_pickup')
                    ->label('Proses & Request Pickup')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (EcommerceOrder $record) => 
                        $record->delivery_method === 'DELIVERY' &&
                        empty($record->biteship_order_id) &&
                        !empty($record->courier_name)
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Request Pickup via Biteship')
                    ->modalDescription('Apakah Anda yakin ingin memanggil kurir untuk pesanan ini?')
                    ->action(function (EcommerceOrder $record) {
                        $biteship = new BiteshipService();
                        $branch = $record->branch ?? \App\Models\Branch::first();
                        $org = \App\Models\Organization::first();
                        
                        $items = $record->items->map(function($item) {
                            return [
                                'name' => $item->product->name ?? 'Produk',
                                'value' => (int) $item->price,
                                'quantity' => (int) $item->quantity,
                                'weight' => (int) ($item->product->weight_in_grams ?? 500)
                            ];
                        })->toArray();

                        $isInstant = in_array(strtolower($record->courier_name), ['gojek', 'grab']);
                        
                        $payload = [
                            'shipper_contact_name' => $branch->name ?? $org->name ?? 'Toserba',
                            'shipper_contact_phone' => $branch->phone ?? $org->phone ?? '081234567890',
                            'origin_contact_name' => $branch->name ?? $org->name ?? 'Toserba',
                            'origin_contact_phone' => $branch->phone ?? $org->phone ?? '081234567890',
                            'origin_address' => $branch->address ?? $org->address ?? 'Alamat Toko',
                            'origin_coordinate' => [
                                'latitude' => (float) ($branch->latitude ?? 0),
                                'longitude' => (float) ($branch->longitude ?? 0),
                            ],
                            'destination_contact_name' => $record->customer_name,
                            'destination_contact_phone' => $record->customer_phone,
                            'destination_address' => $record->delivery_address,
                            'destination_coordinate' => [
                                'latitude' => (float) ($record->destination_latitude ?? 0),
                                'longitude' => (float) ($record->destination_longitude ?? 0),
                            ],
                            'destination_postal_code' => $record->destination_postal_code ?? '12345',
                            'courier_company' => strtolower($record->courier_name),
                            'courier_type' => strtolower($record->courier_service),
                            'delivery_type' => $isInstant ? 'now' : 'later',
                            'items' => $items
                        ];

                        $result = $biteship->createOrder($payload);

                        if ($result['success']) {
                            $record->update([
                                'biteship_order_id' => $result['order']['id'] ?? null,
                                'awb_number' => $result['order']['courier']['waybill_id'] ?? null,
                                'status' => 'PROCESSING'
                            ]);

                            Notification::make()
                                ->title('Pickup Berhasil Direquest')
                                ->body('Kurir segera menjemput pesanan.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Request Pickup')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEcommerceOrders::route('/'),
        ];
    }
}
