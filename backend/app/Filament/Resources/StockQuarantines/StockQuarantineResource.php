<?php

namespace App\Filament\Resources\StockQuarantines;

use App\Filament\Resources\StockQuarantines\Pages\ListStockQuarantines;
use App\Filament\Resources\StockQuarantines\Pages\CreateStockQuarantine;
use App\Models\Stock;
use App\Models\StockQuarantineMovement;
use App\Models\Product;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockQuarantineResource extends Resource
{
    protected static ?string $model = StockQuarantineMovement::class;

    protected static \UnitEnum|string|null $navigationGroup = 'TRANSAKSI';

    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Karantina Barang BS';
    protected static ?string $modelLabel = 'Mutasi Karantina BS';
    protected static ?string $pluralModelLabel = 'Karantina Barang BS';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                StockQuarantineMovement::query()
                    ->when(Auth::user()->branch_id !== null, fn ($q) => $q->where('branch_id', Auth::user()->branch_id))
                    ->with(['branch', 'product', 'creator'])
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('reference_doc_id')
                    ->label('No. Dokumen')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null)
                    ->sortable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('movement_type')
                    ->label('Tipe Mutasi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'MOVE_TO_BS' => 'Masuk Karantina BS',
                        'RESTORE_TO_SALABLE' => 'Kembali ke Rak Jual',
                        'PURCHASE_RETURN' => 'Retur ke Supplier',
                        'WRITE_OFF_DISPOSAL' => 'Pemusnahan / Write-Off',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'MOVE_TO_BS' => 'danger',
                        'RESTORE_TO_SALABLE' => 'success',
                        'PURCHASE_RETURN' => 'info',
                        'WRITE_OFF_DISPOSAL' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->weight('bold'),
                TextColumn::make('reason')
                    ->label('Alasan Kerusakan')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->placeholder('-'),
                TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('movement_type')
                    ->label('Tipe Mutasi')
                    ->options([
                        'MOVE_TO_BS' => 'Masuk Karantina BS',
                        'RESTORE_TO_SALABLE' => 'Kembali ke Rak Jual',
                        'PURCHASE_RETURN' => 'Retur ke Supplier',
                        'WRITE_OFF_DISPOSAL' => 'Pemusnahan / Write-Off',
                    ]),
            ])
            ->actions([
                Action::make('restore')
                    ->label('Kembalikan ke Rak')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (StockQuarantineMovement $record) => $record->movement_type === 'MOVE_TO_BS')
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan Barang ke Stok Layak Jual')
                    ->modalDescription('Barang akan dikeluarkan dari Karantina BS dan dikembalikan ke stok layak jual di rak display.')
                    ->form([
                        TextInput::make('qty_to_restore')
                            ->label('Jumlah yang Dikembalikan')
                            ->numeric()
                            ->required()
                            ->default(fn (StockQuarantineMovement $record) => (float)$record->quantity)
                            ->minValue(0.01),
                        TextInput::make('restore_notes')
                            ->label('Catatan Pembatalan')
                            ->placeholder('Misal: Salah input / barang ternyata masih baik')
                            ->default('Pembatalan mutasi karantina'),
                    ])
                    ->action(function (StockQuarantineMovement $record, array $data) {
                        $qty = (float)$data['qty_to_restore'];
                        $stock = Stock::where('branch_id', $record->branch_id)
                            ->where('product_id', $record->product_id)
                            ->first();

                        if (!$stock) {
                            Notification::make()
                                ->title('Data stok cabang tidak ditemukan!')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $stock->restoreFromQuarantine($qty, $data['restore_notes'] ?? null);
                            Notification::make()
                                ->title("{$qty} pcs berhasil dikembalikan ke Stok Layak Jual.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal mengembalikan stok: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('print')
                    ->label('Cetak Bukti')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (StockQuarantineMovement $record) => route('print.stock-quarantine', [
                        'referenceNumber' => $record->reference_doc_id ?: $record->id,
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockQuarantines::route('/'),
            'create' => CreateStockQuarantine::route('/create'),
        ];
    }
}
