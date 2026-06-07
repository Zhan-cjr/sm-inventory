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
    protected static \UnitEnum|string|null $navigationGroup = 'Keuangan';
    protected static ?string $modelLabel = 'Pembayaran Hutang';
    protected static ?string $pluralModelLabel = 'Pembayaran Hutang';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')->label('No. Pembayaran')->searchable(),
                Tables\Columns\TextColumn::make('payment_date')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Pemasok')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Bayar')->money('IDR'),
                Tables\Columns\TextColumn::make('status')->badge()->color('success'),
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
            'edit' => EditPurchasePayment::route('/{record}/edit'),
        ];
    }
}
