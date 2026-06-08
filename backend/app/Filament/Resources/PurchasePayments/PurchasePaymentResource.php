<?php

namespace App\Filament\Resources\PurchasePayments;

use App\Filament\Resources\PurchasePayments\Pages\CreatePurchasePayment;
use App\Filament\Resources\PurchasePayments\Pages\EditPurchasePayment;
use App\Filament\Resources\PurchasePayments\Pages\ListPurchasePayments;
use App\Filament\Resources\PurchasePayments\Schemas\PurchasePaymentForm;
use App\Filament\Resources\PurchasePayments\Tables\PurchasePaymentsTable;
use App\Models\PurchasePayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;


class PurchasePaymentResource extends Resource
{
    protected static ?string $model = PurchasePayment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static \UnitEnum|string|null $navigationGroup = 'KEUANGAN';
    protected static ?string $modelLabel = 'Pembayaran Hutang';
    protected static ?string $pluralModelLabel = 'Pembayaran Hutang';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Detail Pembayaran')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('payment_number')
                            ->label('No Pembayaran')
                            ->disabled(),
                        \Filament\Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->disabled(),
                        \Filament\Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Pemasok')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('payment_method')
                            ->label('Metode Bayar')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_amount')
                            ->label('Total Bayar')
                            ->rupiah()
                            ->disabled(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->disabled(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')->label('No. Pembayaran')->searchable(),
                Tables\Columns\TextColumn::make('payment_date')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Pemasok')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Bayar')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color('success'),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('payment_date')->label('Tanggal Bayar'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => \Illuminate\Support\Facades\Auth::user()->branch_id !== null),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_daftar')
                    ->label('Cetak Daftar')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'pembayaran-hutang',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label('Detail'),
                \Filament\Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->modalHeading('Batalkan Pembayaran')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan pembayaran ini? Nominal pada faktur penerimaan (GR) akan dikembalikan ke status belum lunas.')
                    ->action(function (PurchasePayment $record) {
                        // Restore goods receipt paid amounts
                        foreach ($record->items as $item) {
                            $gr = $item->goodsReceipt;
                            if ($gr) {
                                $gr->paid_amount -= $item->amount_paid;
                                if ($gr->paid_amount <= 0) {
                                    $gr->payment_status = 'UNPAID';
                                    $gr->paid_amount = 0;
                                } else {
                                    $gr->payment_status = 'PARTIAL_PAID';
                                }
                                $gr->save();
                            }
                        }
                        $record->status = 'CANCELLED';
                        $record->save();
                    })
                    ->hidden(fn (PurchasePayment $record) => $record->status === 'CANCELLED'),
            ]);
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
            'index' => ListPurchasePayments::route('/'),
            'create' => CreatePurchasePayment::route('/create'),
        ];
    }
}
