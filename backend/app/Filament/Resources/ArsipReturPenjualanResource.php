<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArsipReturPenjualanResource\Pages;
use App\Models\Transaction;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasBranchScope;
use Illuminate\Support\Facades\Auth;
use App\Filament\Exports\TransactionExporter;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class ArsipReturPenjualanResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Arsip Retur Penjualan';
    protected static ?string $pluralModelLabel = 'Arsip Retur Penjualan';
    protected static ?string $modelLabel = 'Arsip Retur Penjualan';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only schema if we want to show a view page
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('receipt_number')->label('No Nota'),
                            TextEntry::make('transaction_date')->label('Tanggal & Jam')->dateTime('d M Y H:i:s'),
                            TextEntry::make('customer.name')->label('Customer')->default('Tunai'),
                            TextEntry::make('cashier.name')->label('Kasir'),
                            TextEntry::make('payment_method')->label('Metode Pembayaran')->formatStateUsing(fn ($state) => strtoupper($state)),
                            TextEntry::make('is_voided')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'Void/Batal' : 'Berhasil')
                                ->color(fn ($state) => $state ? 'danger' : 'success'),
                        ])
                    ]),
                Section::make('Rincian Barang')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(5)->schema([
                                    TextEntry::make('product.name')->label('Barang'),
                                    TextEntry::make('quantity')->label('Qty'),
                                    TextEntry::make('unit_price')->label('Harga Satuan')->money('IDR', true),
                                    TextEntry::make('discount_per_item')->label('Diskon')->money('IDR', true),
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->state(fn ($record) => ($record->unit_price - $record->discount_per_item) * $record->quantity)
                                        ->money('IDR', true),
                                ])
                            ])
                            ->columns(1)
                    ]),
                Section::make('Ringkasan Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('total_amount')->label('Total Kotor')->money('IDR', true),
                            TextEntry::make('discount_amount')->label('Total Diskon')->money('IDR', true),
                            TextEntry::make('final_amount')->label('Total Bersih (Trans)')->money('IDR', true)->size(\Filament\Support\Enums\TextSize::Large)->weight(\Filament\Support\Enums\FontWeight::Bold),
                            TextEntry::make('received_amount')->label('Nominal Diterima')->money('IDR', true),
                        ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No Nota')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Jam')
                    ->dateTime('H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->state(fn (Transaction $record) => $record->customer ? $record->customer->name : 'Tunai'),
                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Penjualan')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->label('Total')->money('IDR')),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total Trans')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->label('Total')->money('IDR')),
                Tables\Columns\TextColumn::make('tunai')
                    ->label('Tunai')
                    ->state(fn (Transaction $record) => strtoupper($record->payment_method) === 'CASH' ? $record->final_amount : 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => $query->where('payment_method', 'CASH')->orWhere('payment_method', 'cash')->sum('final_amount'))
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('card')
                    ->label('Card')
                    ->state(fn (Transaction $record) => strtoupper($record->payment_method) === 'CARD' ? $record->final_amount : 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => $query->where('payment_method', 'CARD')->orWhere('payment_method', 'card')->sum('final_amount'))
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('voucher')
                    ->label('Voucher')
                    ->state(fn () => 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn () => 0)
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->label('Total')->money('IDR')),
                Tables\Columns\TextColumn::make('tax')
                    ->label('Tax')
                    ->state(fn () => 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn () => 0)
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('pembulatan')
                    ->label('Pembulatan')
                    ->state(fn () => 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn () => 0)
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('point')
                    ->label('Pembayaran Point')
                    ->state(fn () => 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn () => 0)
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('transfer')
                    ->label('Transfer')
                    ->state(fn (Transaction $record) => strtoupper($record->payment_method) === 'TRANSFER' ? $record->final_amount : 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => $query->where('payment_method', 'TRANSFER')->orWhere('payment_method', 'transfer')->sum('final_amount'))
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('compliment')
                    ->label('Compliment')
                    ->state(fn () => 0)
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn () => 0)
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('is_voided')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Void/Batal' : 'Berhasil')
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
            ])
            ->filters([
                TernaryFilter::make('is_voided')
                    ->label('Status Transaksi')
                    ->placeholder('Semua Transaksi')
                    ->trueLabel('Transaksi Batal (Void)')
                    ->falseLabel('Transaksi Berhasil'),
                \App\Filament\Filters\DateFilterHelper::make('transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->actions([
                ViewAction::make()
                    ->extraModalFooterActions([
                        Action::make('cetak_nota')
                            ->label('Cetak Nota')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->url(fn (Transaction $record) => route('print.transaction', $record), true)
                    ]),
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('void_reason')
                            ->label('Alasan Pembatalan')
                            ->required(),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        $record->update([
                            'is_voided' => true,
                            'void_reason' => $data['void_reason'],
                            'void_date' => now(),
                            'voided_by' => Auth::id(),
                        ]);
                        Notification::make()
                            ->title('Transaksi berhasil dibatalkan')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Transaction $record) => !$record->is_voided && Auth::user()->hasCustomAuthorization('CANCEL_TRANSACTION')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(TransactionExporter::class),
                    BulkAction::make('batalkan_bulk')
                        ->label('Batalkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('void_reason')
                                ->label('Alasan Pembatalan')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                if (!$record->is_voided) {
                                    $record->update([
                                        'is_voided' => true,
                                        'void_reason' => $data['void_reason'],
                                        'void_date' => now(),
                                        'voided_by' => Auth::id(),
                                    ]);
                                }
                            });
                            Notification::make()
                                ->title('Transaksi yang dipilih berhasil dibatalkan')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => Auth::user()->hasCustomAuthorization('CANCEL_TRANSACTION')),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'arsip-transaksi',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    ExportAction::make()
                        ->exporter(TransactionExporter::class)
                        ->label('Export CSV (Raw Data)')
                        ->color('success')
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'arsip-transaksi',
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button()
            ])
            ->defaultSort('transaction_date', 'desc')
            ->deferLoading()
            ->striped();
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArsipReturPenjualans::route('/'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('transaction_type', 'RETURN');
    }
    
    public static function canCreate(): bool
    {
        return false; // Arsip is read-only
    }
}


