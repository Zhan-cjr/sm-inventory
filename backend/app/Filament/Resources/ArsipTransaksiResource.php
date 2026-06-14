<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArsipTransaksiResource\Pages;
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

class ArsipTransaksiResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Arsip Transaksi';
    protected static ?string $pluralModelLabel = 'Arsip Transaksi';
    protected static ?string $modelLabel = 'Arsip Transaksi';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';

    protected static ?int $navigationSort = 8;

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
                            TextEntry::make('short_id')->label('No Transaksi')->badge()->color('gray')->copyable(),
                            TextEntry::make('local_transaction_id')->label('No Nota Lokal')->default('-'),
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
                            
                            TextEntry::make('change_amount')
                                ->label('Kembalian')
                                ->money('IDR', true)
                                ->visible(fn ($record) => $record && $record->change_amount > 0),
                            
                            // Payment Breakdown
                            TextEntry::make('tunai_paid')
                                ->label('Bayar Tunai')
                                ->state(function ($record) {
                                    if (!$record) return 0;
                                    if (strtoupper($record->payment_method) === 'CASH') return $record->received_amount;
                                    if (strtoupper($record->payment_method) === 'MULTI') {
                                        $details = $record->payment_details;
                                        if (is_string($details)) $details = json_decode($details, true);
                                        if (is_array($details)) {
                                            $cashAmount = collect($details)->where('method', 'CASH')->sum('amount');
                                            if ($cashAmount > 0) {
                                                return $cashAmount;
                                            }
                                        }
                                    }
                                    return 0;
                                })
                                ->money('IDR', true)
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return strtoupper($record->payment_method) === 'CASH' ||
                                        (strtoupper($record->payment_method) === 'MULTI' && 
                                         is_array($details) && 
                                         collect($details)->where('method', 'CASH')->sum('amount') > 0);
                                }),
                            TextEntry::make('card_paid')
                                ->label('Bayar Card')
                                ->state(function ($record) {
                                    if (!$record) return 0;
                                    if (strtoupper($record->payment_method) === 'CARD') return $record->final_amount;
                                    if (strtoupper($record->payment_method) === 'MULTI') {
                                        $details = $record->payment_details;
                                        if (is_string($details)) $details = json_decode($details, true);
                                        if (is_array($details)) {
                                            return collect($details)->where('method', 'CARD')->sum('amount');
                                        }
                                    }
                                    return 0;
                                })
                                ->money('IDR', true)
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return strtoupper($record->payment_method) === 'CARD' ||
                                        (strtoupper($record->payment_method) === 'MULTI' && 
                                         is_array($details) && 
                                         collect($details)->where('method', 'CARD')->sum('amount') > 0);
                                }),
                            TextEntry::make('point_paid')
                                ->label('Bayar Point')
                                ->state(function ($record) {
                                    if (!$record) return 0;
                                    if (strtoupper($record->payment_method) === 'POINT') return $record->final_amount;
                                    if (strtoupper($record->payment_method) === 'MULTI') {
                                        $details = $record->payment_details;
                                        if (is_string($details)) $details = json_decode($details, true);
                                        if (is_array($details)) {
                                            return collect($details)->where('method', 'POINT')->sum('amount');
                                        }
                                    }
                                    return 0;
                                })
                                ->money('IDR', true)
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return strtoupper($record->payment_method) === 'POINT' ||
                                        (strtoupper($record->payment_method) === 'MULTI' && 
                                         is_array($details) && 
                                         collect($details)->where('method', 'POINT')->sum('amount') > 0);
                                }),
                            TextEntry::make('voucher_paid')
                                ->label('Bayar Voucher')
                                ->state(function ($record) {
                                    if (!$record) return 0;
                                    if (strtoupper($record->payment_method) === 'VOUCHER') return $record->final_amount;
                                    if (strtoupper($record->payment_method) === 'MULTI') {
                                        $details = $record->payment_details;
                                        if (is_string($details)) $details = json_decode($details, true);
                                        if (is_array($details)) {
                                            return collect($details)->where('method', 'VOUCHER')->sum('amount');
                                        }
                                    }
                                    return 0;
                                })
                                ->money('IDR', true)
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return strtoupper($record->payment_method) === 'VOUCHER' ||
                                        (strtoupper($record->payment_method) === 'MULTI' && 
                                         is_array($details) && 
                                         collect($details)->where('method', 'VOUCHER')->sum('amount') > 0);
                                }),
                            TextEntry::make('transfer_paid')
                                ->label('Bayar Transfer')
                                ->state(function ($record) {
                                    if (!$record) return 0;
                                    if (strtoupper($record->payment_method) === 'TRANSFER') return $record->final_amount;
                                    if (strtoupper($record->payment_method) === 'MULTI') {
                                        $details = $record->payment_details;
                                        if (is_string($details)) $details = json_decode($details, true);
                                        if (is_array($details)) {
                                            return collect($details)->where('method', 'TRANSFER')->sum('amount');
                                        }
                                    }
                                    return 0;
                                })
                                ->money('IDR', true)
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return strtoupper($record->payment_method) === 'TRANSFER' ||
                                        (strtoupper($record->payment_method) === 'MULTI' && 
                                         is_array($details) && 
                                         collect($details)->where('method', 'TRANSFER')->sum('amount') > 0);
                                }),
                        ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('short_id')
                    ->label('No Transaksi')
                    ->searchable(query: function ($query, $search) {
                        $query->where('receipt_number', 'like', "%{$search}%")
                              ->orWhere('local_transaction_id', 'like', "%{$search}%");
                    })
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('receipt_number', $direction))
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('No transaksi disalin!'),
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
                    ->state(function (Transaction $record) {
                        if ($record->transaction_type === 'RETURN') {
                            $salesAmount = $record->items->where('quantity', '>', 0)->sum(function($i) { return $i->quantity * $i->unit_price; });
                            return $salesAmount;
                        }
                        return $record->total_amount;
                    })
                    ->summarize(Sum::make()->label('Total')->money('IDR')),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total Trans')
                    ->money('IDR', true)
                    ->summarize(Sum::make()->label('Total')->money('IDR')),
                Tables\Columns\TextColumn::make('tunai')
                    ->label('Tunai')
                    ->state(function (Transaction $record) {
                        if (strtoupper($record->payment_method) === 'CASH') return $record->final_amount;
                        if (strtoupper($record->payment_method) === 'MULTI') {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                $cashAmount = collect($details)->where('method', 'CASH')->sum('amount');
                                if ($cashAmount > 0) {
                                    return max(0, $cashAmount - $record->change_amount);
                                }
                            }
                        }
                        return 0;
                    })
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $cashOnly = (clone $query)->whereIn('payment_method', ['CASH', 'cash'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details', 'change_amount']);
                                $multiCash = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    if (!is_array($details)) return 0;
                                    $cashAmount = collect($details)->where('method', 'CASH')->sum('amount');
                                    return $cashAmount > 0 ? max(0, $cashAmount - $record->change_amount) : 0;
                                });
                                return $cashOnly + $multiCash;
                            })
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('card')
                    ->label('Card')
                    ->state(function (Transaction $record) {
                        if (strtoupper($record->payment_method) === 'CARD') return $record->final_amount;
                        if (strtoupper($record->payment_method) === 'MULTI') {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                return collect($details)->where('method', 'CARD')->sum('amount');
                            }
                        }
                        return 0;
                    })
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $cardOnly = (clone $query)->whereIn('payment_method', ['CARD', 'card'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details']);
                                $multiCard = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'CARD')->sum('amount') : 0;
                                });
                                return $cardOnly + $multiCard;
                            })
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('voucher')
                    ->label('Voucher')
                    ->state(function (Transaction $record) {
                        if (strtoupper($record->payment_method) === 'VOUCHER') return $record->final_amount;
                        if (strtoupper($record->payment_method) === 'MULTI') {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                return collect($details)->where('method', 'VOUCHER')->sum('amount');
                            }
                        }
                        return 0;
                    })
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $voucherOnly = (clone $query)->whereIn('payment_method', ['VOUCHER', 'voucher'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details']);
                                $multiVoucher = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'VOUCHER')->sum('amount') : 0;
                                });
                                return $voucherOnly + $multiVoucher;
                            })
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR', true)
                    ->state(fn (Transaction $record) => $record->transaction_type === 'RETURN' ? 0 : $record->discount_amount)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => $query->where('transaction_type', '!=', 'RETURN')->orWhereNull('transaction_type')->sum('discount_amount'))
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('retur')
                    ->label('Retur')
                    ->money('IDR', true)
                    ->state(function (Transaction $record) {
                        if ($record->transaction_type === 'RETURN') {
                            $returnAmount = $record->items->where('quantity', '<', 0)->sum(function($i) { return abs($i->quantity * $i->unit_price); });
                            return $returnAmount > 0 ? $returnAmount : abs($record->final_amount);
                        }
                        return 0;
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => abs($query->where('transaction_type', 'RETURN')->sum('final_amount')))
                            ->money('IDR')
                    ),
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
                    ->state(function (Transaction $record) {
                        if (strtoupper($record->payment_method) === 'POINT') return $record->final_amount;
                        if (strtoupper($record->payment_method) === 'MULTI') {
                            $details = $record->payment_details;
                            if (is_string($details)) $details = json_decode($details, true);
                            if (is_array($details)) {
                                return collect($details)->where('method', 'POINT')->sum('amount');
                            }
                        }
                        return 0;
                    })
                    ->money('IDR', true)
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $pointOnly = (clone $query)->whereIn('payment_method', ['POINT', 'point'])->sum('final_amount');
                                $multiRecords = (clone $query)->whereIn('payment_method', ['MULTI', 'multi'])->get(['payment_details']);
                                $multiPoint = $multiRecords->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'POINT')->sum('amount') : 0;
                                });
                                return $pointOnly + $multiPoint;
                            })
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('points_deducted')
                    ->label('Poin Ditukar')
                    ->state(function (Transaction $record) {
                        $details = $record->payment_details;
                        if (is_string($details)) $details = json_decode($details, true);
                        if (is_array($details)) {
                            return collect($details)->where('method', 'POINT')->sum('points_deducted');
                        }
                        return 0;
                    })
                    ->numeric()
                    ->summarize(
                        Summarizer::make()
                            ->label('Total')
                            ->using(function ($query) {
                                $records = $query->get(['payment_details']);
                                return $records->sum(function ($record) {
                                    $details = $record->payment_details;
                                    if (is_string($details)) $details = json_decode($details, true);
                                    return is_array($details) ? collect($details)->where('method', 'POINT')->sum('points_deducted') : 0;
                                });
                            })
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
                ExportAction::make()
                    ->exporter(TransactionExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
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
            'index' => Pages\ListArsipTransaksis::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Arsip is read-only
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('items');
    }
}


