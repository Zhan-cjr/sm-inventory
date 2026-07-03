<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\GoodsReceipt;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Perusahaan/Organisasi')
                    ->default(1)
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Cabang')
                    ->default(fn () => auth()->user()->branch_id ?? null),
                
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($set) { $set('goods_receipt_id', null); })
                    ->required(),
                    
                Select::make('goods_receipt_id')
                    ->label('Referensi Penerimaan Barang (GR)')
                    ->options(function ($get) {
                        $supplierId = $get('supplier_id');
                        if (!$supplierId) {
                            return [];
                        }
                        return GoodsReceipt::where('supplier_id', $supplierId)
                            ->where('status', '!=', 'CANCELLED')
                            ->orderBy('created_at', 'desc')
                            ->pluck('receipt_number', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if (!$state) {
                            $set('items', []);
                            $set('total_amount', 0);
                            return;
                        }

                        $gr = GoodsReceipt::with('items.product')->find($state);
                        if ($gr) {
                            $items = [];
                            foreach ($gr->items as $item) {
                                // Cek sisa stok di Batch FIFO untuk Penerimaan ini
                                $batch = \App\Models\StockBatch::where('reference_doc_type', 'GOODS_RECEIPT')
                                    ->where('reference_doc_id', $gr->id)
                                    ->where('product_id', $item->product_id)
                                    ->first();
                                
                                // Jika tidak ada batch atau sisa 0, berarti sudah terjual semua, tidak bisa diretur
                                $remainingQty = $batch ? (float) $batch->remaining_quantity : 0;

                                $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
                                $taxMultiplier = $gr->include_tax ? (1 + ($taxRate / 100)) : 1;
                                $netUnitPrice = $item->quantity_received > 0 ? ($item->subtotal / $item->quantity_received) : $item->unit_price;
                                $returnPrice = round($netUnitPrice * $taxMultiplier, 2);

                                if ($remainingQty > 0) {
                                    $items[] = [
                                        'product_id' => $item->product_id,
                                        'max_qty' => $remainingQty, // Batas maksimal adalah sisa yang belum terjual
                                        'quantity' => 0, // Default 0
                                        'unit_price' => $returnPrice,
                                        'subtotal' => 0,
                                        'reason' => '',
                                    ];
                                }
                            }
                            $set('items', $items);
                        }
                    }),

                TextInput::make('return_number')
                    ->label('Nomor Retur')
                    ->default(fn () => 'PRT-' . strtoupper(substr(uniqid(), -6)))
                    ->required()
                    ->readOnly(),

                DatePicker::make('return_date')
                    ->label('Tanggal Retur')
                    ->default(now())
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Diajukan',
                        'approved' => 'Disetujui',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->default('draft')
                    ->required(),

                TextInput::make('total_amount')
                    ->label('Total Nilai Retur')
                    ->numeric()
                    ->default(0.0)
                    ->readOnly(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),

                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                
                Repeater::make('items')
                    ->relationship()
                    ->label('Daftar Barang yang Diretur (Isi jumlah > 0 pada barang yang ingin diretur)')
                    ->columns(5)
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): \Illuminate\Database\Eloquent\Builder => 
                                \App\Models\Product::whereIn('id', \App\Models\Product::search($search)->take(50)->keys())
                            )
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('max_qty')
                            ->label('Sisa (Max Retur)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TextInput::make('quantity')
                            ->label('Jumlah Retur')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $max = (float) $get('max_qty');
                                $qty = (float) $state;
                                if ($qty > $max) {
                                    $set('quantity', $max);
                                    $qty = $max;
                                }
                                $price = (float) $get('unit_price');
                                $set('subtotal', $qty * $price);
                            })
                            ->columnSpan(1),

                        TextInput::make('unit_price')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        TextInput::make('reason')
                            ->label('Alasan Retur (Opsional)')
                            ->columnSpan(5),
                    ])
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($get, $set) {
                        $items = $get('items') ?? [];
                        $total = 0;
                        foreach ($items as $item) {
                            $total += (float) ($item['subtotal'] ?? 0);
                        }
                        $set('total_amount', $total);
                    })
                    ->disableItemCreation()
                    ->columnSpanFull(),
            ]);
    }
}
