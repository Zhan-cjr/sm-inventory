<?php

namespace App\Filament\Resources\Kontrabons;

use App\Filament\Resources\Kontrabons\Pages\CreateKontrabon;
use App\Filament\Resources\Kontrabons\Pages\ListKontrabons;
use App\Filament\Resources\Kontrabons\Schemas\KontrabonForm;
use App\Filament\Resources\Kontrabons\Tables\KontrabonsTable;
use App\Models\Kontrabon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;

class KontrabonResource extends Resource
{
    protected static ?string $model = Kontrabon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static \UnitEnum|string|null $navigationGroup = 'KEUANGAN';
    protected static ?string $modelLabel = 'Tukar Faktur (Kontrabon)';
    protected static ?string $pluralModelLabel = 'Tukar Faktur (Kontrabon)';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Info Kontrabon')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('kontrabon_number')
                            ->label('No. Kontrabon')
                            ->disabled(),
                        \Filament\Forms\Components\DatePicker::make('tanggal_kontrabon')
                            ->label('Tgl Kontrabon')
                            ->disabled(),
                        \Filament\Forms\Components\DatePicker::make('tanggal_jatuh_tempo')
                            ->label('Jatuh Tempo')
                            ->disabled(),
                        \Filament\Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Pemasok')
                            ->disabled(),
                         \Filament\Forms\Components\TextInput::make('total_amount')
                            ->label('Total Tagihan')
                            ->rupiah()
                            ->disabled(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->disabled(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Potongan Supplier (Promo/Klaim)')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('kontrabonDeductions')
                            ->relationship()
                            ->schema([
                                \Filament\Forms\Components\Select::make('supplier_deduction_id')
                                    ->relationship('supplierDeduction', 'notes')
                                    ->label('Keterangan / ID Promo')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('amount_applied')
                                    ->label('Nominal Terpotong')
                                    ->rupiah()
                                    ->disabled(),
                            ])
                            ->columns(2)
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement()
                            ->label('')
                    ])
                    ->visible(fn ($record) => $record && $record->kontrabonDeductions()->count() > 0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kontrabon_number')->label('No. Kontrabon')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_kontrabon')->label('Tgl Kontrabon')->date()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date()->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Pemasok')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total Tagihan')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->label('Sudah Dibayar')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->colors([
                        'danger' => fn ($state) => strtolower($state) === 'unpaid' || strtolower($state) === 'pending',
                        'warning' => fn ($state) => strtolower($state) === 'partial',
                        'success' => fn ($state) => strtolower($state) === 'paid',
                        'gray' => fn ($state) => strtolower($state) === 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match (strtolower($state)) {
                        'unpaid', 'pending' => 'Belum Lunas',
                        'partial' => 'Cicilan',
                        'paid' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('tanggal_kontrabon')->label('Tanggal Kontrabon'),
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
                        'type' => 'kontrabon',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ])
            ->actions([
                \Filament\Actions\Action::make('cetak_nota')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Kontrabon $record) => route('print.report', ['type' => 'kontrabon-nota', 'id' => $record->id]))
                    ->openUrlInNewTab(),
                \Filament\Actions\ViewAction::make()->label('Detail'),
                \Filament\Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->modalHeading('Batalkan Kontrabon')
                    ->action(function (Kontrabon $record) {
                        $record->status = 'CANCELLED';
                        $record->save();
                        // Optional: release GR billing status
                    })
                    ->hidden(fn (Kontrabon $record) => $record->status === 'CANCELLED' || $record->paid_amount > 0),
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
            'index' => ListKontrabons::route('/'),
            'create' => CreateKontrabon::route('/create'),
        ];
    }
}
