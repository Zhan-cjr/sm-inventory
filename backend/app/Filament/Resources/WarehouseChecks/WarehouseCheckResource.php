<?php

namespace App\Filament\Resources\WarehouseChecks;

use App\Filament\Resources\WarehouseChecks\Pages\ManageWarehouseChecks;
use App\Models\WarehouseCheck;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\ViewField;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\ActionSize;
use App\Traits\HasBranchScope;

class WarehouseCheckResource extends Resource
{
    use HasBranchScope;
    protected static ?string $model = WarehouseCheck::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Pengecekan Gudang';
    protected static ?string $modelLabel = 'Pengecekan Gudang';
    protected static ?string $pluralModelLabel = 'Pengecekan Gudang';
    protected static \UnitEnum|string|null $navigationGroup = 'TRANSAKSI';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Readonly form
                \Filament\Forms\Components\TextInput::make('purchaseOrder.po_number')
                    ->label('No. PO'),
                \Filament\Forms\Components\TextInput::make('checker.name')
                    ->label('Pengecek'),
                \Filament\Forms\Components\TextInput::make('status')
                    ->label('Status'),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Catatan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Nama Pemasok')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('checker.name')
                    ->label('Diperiksa Oleh')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Cek')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'partially_processed' => 'warning',
                        'rejected' => 'danger',
                        'processed' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'pending_approval' => 'Menunggu Otorisasi',
                        'approved' => 'Disetujui',
                        'partially_processed' => 'Dibuat GR Sebagian',
                        'rejected' => 'Ditolak',
                        'processed' => 'Sudah Dibuat GR',
                        default => $state,
                    }),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('created_at', 'filter_tanggal'),
                \Filament\Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Cabang')
                    ->visible(fn () => !auth()->user()->branch_id),
            ])
            ->recordActions([
                Action::make('approve_overqty')
                    ->label('Otorisasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (WarehouseCheck $record) => $record->status === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_GR_OVERQUANTITY'))
                    ->requiresConfirmation()
                    ->action(function (WarehouseCheck $record, array $data) {
                        $record->approve(auth()->id(), $data['notes'] ?? null);
                    })
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan (Opsional)'),
                    ]),

                Action::make('reject_overqty')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WarehouseCheck $record) => $record->status === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_GR_OVERQUANTITY'))
                    ->requiresConfirmation()
                    ->action(function (WarehouseCheck $record, array $data) {
                        $record->reject(auth()->id(), $data['notes'] ?? null);
                    })
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ]),

                Action::make('create_gr')
                    ->label(fn (WarehouseCheck $record) => $record->status === 'approved' ? 'Proses Jadi GR' : 'Input GR / Faktur Baru')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn (WarehouseCheck $record) => in_array($record->status, ['approved', 'partially_processed', 'processed']))
                    ->requiresConfirmation()
                    ->modalHeading('Buat Goods Receipt / Input Faktur Supplier')
                    ->modalDescription('Tindakan ini akan membuat Draft Goods Receipt berdasarkan hasil pengecekan gudang yang sudah disahkan ini. Anda dapat memasukkan Nomor Faktur Supplier dan menyesuaikan item pada form setelahnya.')
                    ->action(function (WarehouseCheck $record) {
                        // Create Draft GR
                        $po = $record->purchaseOrder;
                        
                        $due_days = 0;
                        if ($po->supplier_id) {
                            $supplier = \App\Models\Supplier::find($po->supplier_id);
                            if ($supplier) {
                                $due_days = $supplier->default_due_days ?? 0;
                            }
                        }
                        
                        $gr = \App\Models\GoodsReceipt::create([
                            'warehouse_check_id' => $record->id,
                            'purchase_order_id' => $po->id,
                            'supplier_id' => $po->supplier_id,
                            'branch_id' => $record->branch_id,
                            'receipt_number' => 'GR-' . date('YmdHis'),
                            'receipt_date' => now(),
                            'due_date' => now()->addDays($due_days),
                            'received_by' => $record->checker->name,
                            'status' => 'DRAFT',
                            'total_amount' => 0,
                            'include_tax' => $po->include_tax,
                        ]);

                        $existingGrIds = \App\Models\GoodsReceipt::where('warehouse_check_id', $record->id)
                            ->where('id', '!=', $gr->id)
                            ->where('status', '!=', 'CANCELLED')
                            ->pluck('id');

                        $total = 0;
                        $totalScanned = 0;
                        $totalReceivedSoFar = 0;

                        foreach ($record->items as $checkItem) {
                            $totalScanned += $checkItem->qty_scanned;

                            $alreadyReceived = \App\Models\GoodsReceiptItem::whereIn('goods_receipt_id', $existingGrIds)
                                ->where('product_id', $checkItem->product_id)
                                ->sum('quantity_received');

                            $remainingQty = max(0, $checkItem->qty_scanned - $alreadyReceived);
                            $totalReceivedSoFar += ($alreadyReceived + $remainingQty);

                            // If there are remaining items or it's the first GR
                            $qtyToInsert = ($existingGrIds->count() > 0) ? $remainingQty : $checkItem->qty_scanned;

                            if ($checkItem->qty_scanned > 0) {
                                $poItem = $po->items()->where('product_id', $checkItem->product_id)->first();
                                $price = $poItem ? ($poItem->unit_cost ?? 0) : 0;
                                $subtotal = $price * $qtyToInsert;
                                
                                $gr->items()->create([
                                    'product_id' => $checkItem->product_id,
                                    'quantity_ordered' => $checkItem->qty_po,
                                    'quantity_received' => $qtyToInsert,
                                    'unit_price' => $price,
                                    'subtotal' => $subtotal,
                                ]);
                                $total += $subtotal;
                            }
                        }

                        $taxAmount = 0;
                        if ($gr->include_tax) {
                            $taxRate = \App\Models\Organization::first()->tax_rate ?? 11;
                            $taxAmount = $total * ($taxRate / 100);
                        }
                        
                        $gr->update([
                            'total_amount' => $total + $taxAmount,
                            'tax_amount' => $taxAmount
                        ]);

                        // Determine status: if total received across all GRs >= total scanned, mark processed
                        $newStatus = ($totalScanned > 0 && $totalReceivedSoFar >= $totalScanned) ? 'processed' : 'partially_processed';
                        $record->update(['status' => $newStatus]);

                        return redirect()->to(\App\Filament\Resources\GoodsReceipts\GoodsReceiptResource::getUrl('edit', ['record' => $gr]));
                    }),

                Action::make('edit_rejected')
                    ->label('Revisi / Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (WarehouseCheck $record) => $record->status === 'rejected')
                    ->modalHeading('Revisi Pengecekan Gudang')
                    ->modalDescription('Anda dapat mengedit Qty Fisik atau menghapus barang. Jika total qty masih melebihi PO, akan kembali meminta otorisasi.')
                    ->mountUsing(function ($form, WarehouseCheck $record) {
                        $record->load('items.product');
                        $form->fill([
                            'items' => $record->items->map(fn($item) => [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'product_name' => $item->product ? $item->product->name : (\Illuminate\Support\Facades\DB::table('products')->where('id', $item->product_id)->value('name') ?? 'Unknown'),
                                'qty_po' => $item->qty_po,
                                'qty_scanned' => $item->qty_scanned,
                            ])->toArray()
                        ]);
                    })
                    ->form([
                        \Filament\Forms\Components\Repeater::make('items')
                            ->label('Daftar Barang')
                            ->schema([
                                \Filament\Forms\Components\Hidden::make('id'),
                                \Filament\Forms\Components\Hidden::make('product_id'),
                                \Filament\Forms\Components\TextInput::make('product_name')
                                    ->disabled()
                                    ->label('Barang'),
                                \Filament\Forms\Components\TextInput::make('qty_po')
                                    ->disabled()
                                    ->label('Sisa PO'),
                                \Filament\Forms\Components\TextInput::make('qty_scanned')
                                    ->numeric()
                                    ->required()
                                    ->label('Qty Fisik'),
                            ])
                            ->disableItemCreation()
                            ->columns(3)
                    ])
                    ->action(function (WarehouseCheck $record, array $data) {
                        $submittedIds = collect($data['items'])->pluck('id')->filter()->toArray();
                        // Hapus item yang dibuang dari repeater
                        $record->items()->whereNotIn('id', $submittedIds)->delete();
                
                        $hasOverQty = false;
                        foreach ($data['items'] as $itemData) {
                            $checkItem = $record->items()->where('id', $itemData['id'])->first();
                            if ($checkItem) {
                                $checkItem->update([
                                    'qty_scanned' => $itemData['qty_scanned']
                                ]);
                                
                                if ($itemData['qty_scanned'] > $checkItem->qty_po) {
                                    $hasOverQty = true;
                                }
                            }
                        }
                
                        if ($hasOverQty) {
                            $record->requestApproval('Revisi: Terdapat kuantitas barang yang melebihi sisa PO.', 1);
                            \Filament\Notifications\Notification::make()->title('Disimpan: Menunggu Otorisasi lagi karena Qty > PO')->warning()->send();
                        } else {
                            $record->update(['status' => 'approved', 'notes' => 'Direvisi oleh Gudang (Qty sesuai PO)']);
                            \Filament\Notifications\Notification::make()->title('Disimpan: Otomatis Disetujui (Sesuai PO)')->success()->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouseChecks::route('/'),
        ];
    }
}
