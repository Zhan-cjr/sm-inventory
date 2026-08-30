<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated()
                    ->searchable()
                    ->preload(),
                Select::make('supplier_id')
                    ->relationship(
                        name: 'supplier', 
                        titleAttribute: 'name', 
                        modifyQueryUsing: fn ($query, $record) => $query->where(function ($q) use ($record) {
                            $q->where('is_active', true);
                            if ($record && $record->supplier_id) {
                                $q->orWhere('id', $record->supplier_id);
                            }
                        })
                    )
                    ->label('Pemasok Utama')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $set('supplier_division_id', null);
                        if ($state) {
                            $supplier = \App\Models\Supplier::find($state);
                            if ($supplier && $get('po_date') && $supplier->default_po_expired_days > 0) {
                                $poDate = \Carbon\Carbon::parse($get('po_date'));
                                $set('expired_date', $poDate->addDays($supplier->default_po_expired_days)->format('Y-m-d'));
                            }
                        }
                    }),
                Select::make('supplier_division_id')
                    ->label('Divisi / Sub-Supplier')
                    ->placeholder('Pilih divisi pemasok (Opsional)')
                    ->options(function (callable $get) {
                        $supplierId = $get('supplier_id');
                        if (!$supplierId) {
                            return [];
                        }
                        return \App\Models\SupplierDivision::where('supplier_id', $supplierId)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->live(),
                TextInput::make('po_number')
                    ->required(),
                DatePicker::make('po_date')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $supplierId = $get('supplier_id');
                        if ($supplierId && $state) {
                            $supplier = \App\Models\Supplier::find($supplierId);
                            if ($supplier && $supplier->default_po_expired_days > 0) {
                                $poDate = \Carbon\Carbon::parse($state);
                                $set('expired_date', $poDate->addDays($supplier->default_po_expired_days)->format('Y-m-d'));
                            }
                        }
                    }),
                DatePicker::make('expected_delivery_date'),
                DatePicker::make('expired_date')
                    ->label('Tgl Kadaluwarsa (Expired PO)')
                    ->disabledOn('edit'),
                TextInput::make('status')
                    ->required()
                    ->default('DRAFT'),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),

                Textarea::make('notes')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Checkbox::make('cetak_nota')
                    ->label('Cetak Nota setelah simpan')
                    ->dehydrated(false)
                    ->default(false),
            ]);
    }
}
