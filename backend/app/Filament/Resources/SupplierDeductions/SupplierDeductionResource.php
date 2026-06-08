<?php

namespace App\Filament\Resources\SupplierDeductions;

use App\Filament\Resources\SupplierDeductions\Pages\CreateSupplierDeduction;
use App\Filament\Resources\SupplierDeductions\Pages\EditSupplierDeduction;
use App\Filament\Resources\SupplierDeductions\Pages\ListSupplierDeductions;
use App\Filament\Resources\SupplierDeductions\Schemas\SupplierDeductionForm;
use App\Filament\Resources\SupplierDeductions\Tables\SupplierDeductionsTable;
use App\Models\SupplierDeduction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierDeductionResource extends Resource
{
    protected static ?string $model = SupplierDeduction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static \UnitEnum|string|null $navigationGroup = 'KEUANGAN';
    protected static ?string $navigationLabel = 'Klaim & Potongan';
    protected static ?string $pluralModelLabel = 'Klaim & Potongan Pemasok';
    protected static ?string $modelLabel = 'Potongan Pemasok';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SupplierDeductionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierDeductionsTable::configure($table)
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('created_at')->label('Tanggal Input'),
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
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
                        'type' => 'supplier-deductions',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('cetak_sellout')
                    ->label('Cetak Sellout')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (\App\Models\SupplierDeduction $record) => route('print.report', ['type' => 'promo-sellout', 'id' => $record->reference_id]))
                    ->openUrlInNewTab()
                    ->visible(fn (\App\Models\SupplierDeduction $record) => $record->deduction_type === 'PROMO_RAFAKSI'),
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
            'index' => ListSupplierDeductions::route('/'),
            'create' => CreateSupplierDeduction::route('/create'),
            'edit' => EditSupplierDeduction::route('/{record}/edit'),
        ];
    }
}
