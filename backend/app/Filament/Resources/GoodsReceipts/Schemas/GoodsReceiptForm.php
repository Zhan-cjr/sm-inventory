<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purchase_order_id')
                    ->relationship(
                        name: 'purchaseOrder', 
                        titleAttribute: 'po_number', 
                        modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, ?\Illuminate\Database\Eloquent\Model $record) {
                            $query->where(function ($sub) {
                                $sub->where('status', 'approved')
                                    ->where(function ($q) {
                                        $q->whereNull('expired_date')
                                          ->orWhere('expired_date', '>=', now()->toDateString());
                                    })
                                    ->whereHas('warehouseChecks', function ($qc) {
                                        $qc->whereIn('status', ['approved', 'partially_processed', 'processed']);
                                    });
                            });

                            if ($record && $record->purchase_order_id) {
                                $query->orWhere('id', $record->purchase_order_id);
                            }
                        }
                    )
                    ->disabledOn('edit')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $po = \App\Models\PurchaseOrder::find($state);
                            if ($po) {
                                $set('supplier_id', $po->supplier_id);
                                $set('supplier_division_id', $po->supplier_division_id);
                            }
                        }
                    })
                    ->required(function (\Filament\Forms\Get $get) {
                        $supplierId = $get('supplier_id');
                        if ($supplierId) {
                            $supplier = \App\Models\Supplier::find($supplierId);
                            return $supplier && $supplier->gr_requires_po;
                        }
                        return false;
                    }),
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
                            if ($supplier && $get('receipt_date')) {
                                $receiptDate = \Carbon\Carbon::parse($get('receipt_date'));
                                $set('due_date', $receiptDate->addDays($supplier->default_due_days)->format('Y-m-d'));
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
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id)
                    ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                    ->dehydrated()
                    ->searchable()
                    ->preload(),
                TextInput::make('receipt_number')
                    ->required(),
                DateTimePicker::make('receipt_date')
                    ->required()
                    ->disabledOn('edit')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $supplierId = $get('supplier_id');
                        if ($supplierId && $state) {
                            $supplier = \App\Models\Supplier::find($supplierId);
                            if ($supplier) {
                                $receiptDate = \Carbon\Carbon::parse($state);
                                $set('due_date', $receiptDate->addDays($supplier->default_due_days)->format('Y-m-d'));
                            }
                        }
                    }),
                \Filament\Forms\Components\DatePicker::make('due_date')
                    ->label('Jatuh Tempo (Tempo Pembayaran)'),
                TextInput::make('received_by')
                    ->required(),
                TextInput::make('faktur_supplier'),
                \Filament\Forms\Components\FileUpload::make('faktur_image')
                    ->label('Bukti Faktur (Max 1MB)')
                    ->maxSize(1024)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->directory('faktur_receipts')
                    ->columnSpanFull(),
                TextInput::make('total_amount')
                    ->required()
                    ->rupiah(),
                Toggle::make('include_tax')
                    ->label('Include PPN')
                    ->default(true)
                    ->required(),
                TextInput::make('tax_amount')
                    ->label('Nominal PPN')
                    ->rupiah()
                    ->default(0),
                Select::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Lunas (Hutang)',
                        'partial' => 'Sebagian (Cicilan)',
                        'paid' => 'Lunas',
                    ])
                    ->default('unpaid')
                    ->required(),
                TextInput::make('paid_amount')
                    ->label('Jumlah Sudah Dibayar')
                    ->rupiah()
                    ->default(0)
                    ->disabled() // Because payments should be recorded via payment system
                    ->dehydrated(),
                TextInput::make('status')
                    ->required()
                    ->default('RECEIVED'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Checkbox::make('cetak_nota')
                    ->label('Cetak Nota setelah simpan')
                    ->dehydrated(false)
                    ->default(false),
            ]);
    }
}
