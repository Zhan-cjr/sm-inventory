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
                Action::make('view_items')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Pengecekan Gudang')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->mountUsing(function ($form, WarehouseCheck $record) {
                        // Empty because we render directly in content
                    })
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('items')
                            ->hiddenLabel()
                            ->content(function (WarehouseCheck $record) {
                                $record->load('items.product');
                                $data = [];
                                foreach ($record->items as $item) {
                                    $po = floatval($item->qty_po) == intval($item->qty_po) ? number_format($item->qty_po, 0) : number_format($item->qty_po, 2);
                                    $scanned = floatval($item->qty_scanned) == intval($item->qty_scanned) ? number_format($item->qty_scanned, 0) : number_format($item->qty_scanned, 2);
                                    $data[] = [
                                        'id' => $item->id,
                                        'barcode' => $item->product ? $item->product->barcode : '-',
                                        'name' => $item->product ? $item->product->name : (\Illuminate\Support\Facades\DB::table('products')->where('id', $item->product_id)->value('name') ?? 'Unknown'),
                                        'po' => $po,
                                        'scanned' => $scanned,
                                        'is_over' => $item->qty_scanned > $item->qty_po
                                    ];
                                }
                                $jsonItems = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
                                
                                $html = <<<HTML
<div x-data="{
    page: 1,
    perPage: 10,
    items: {$jsonItems},
    get totalPages() { return Math.ceil(this.items.length / this.perPage); },
    get paginatedItems() {
        let start = (this.page - 1) * this.perPage;
        return this.items.slice(start, start + this.perPage);
    }
}" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; font-family: inherit; font-size: 0.875rem;">
    <div style="overflow-x: auto; min-width: 600px;">
        <div style="display: grid; grid-template-columns: 20% 50% 15% 15%; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; color: #374151;">
            <div style="padding: 0.75rem 1rem; font-weight: 600;">Barcode</div>
            <div style="padding: 0.75rem 1rem; font-weight: 600;">Nama Barang</div>
            <div style="padding: 0.75rem 1rem; font-weight: 600; text-align: center;">Sisa PO</div>
            <div style="padding: 0.75rem 1rem; font-weight: 600; text-align: center;">Qty Fisik</div>
        </div>
        <div>
            <template x-for="item in paginatedItems" :key="item.id">
                <div style="display: grid; grid-template-columns: 20% 50% 15% 15%; border-bottom: 1px solid #f3f4f6; color: #4b5563; align-items: center;">
                    <div style="padding: 0.5rem 1rem;" x-text="item.barcode"></div>
                    <div style="padding: 0.5rem 1rem; font-weight: 500; color: #111827;" x-text="item.name"></div>
                    <div style="padding: 0.5rem 1rem; text-align: center;" x-text="item.po"></div>
                    <div style="padding: 0.5rem 1rem; text-align: center; font-weight: bold;" 
                        :style="item.is_over ? 'color: #dc2626;' : ''" x-text="item.scanned"></div>
                </div>
            </template>
        </div>
    </div>
    
    <div x-show="totalPages > 1" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background-color: #f9fafb; font-size: 0.875rem; border-top: 1px solid #e5e7eb; color: #374151;">
        <div>
            Halaman <span style="font-weight: 600;" x-text="page"></span> dari <span style="font-weight: 600;" x-text="totalPages"></span>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" @click="if(page > 1) page--" :disabled="page == 1" 
                style="padding: 0.25rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: white; cursor: pointer; color: #374151;"
                x-bind:style="page == 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''">
                Prev
            </button>
            <button type="button" @click="if(page < totalPages) page++" :disabled="page == totalPages" 
                style="padding: 0.25rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: white; cursor: pointer; color: #374151;"
                x-bind:style="page == totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''">
                Next
            </button>
        </div>
    </div>
</div>
HTML;
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ]),
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
                    ->visible(fn (WarehouseCheck $record) => in_array($record->status, ['approved', 'partially_processed']))
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

                        // Cek apakah sudah ada Goods Receipt berstatus DRAFT untuk pengecekan ini
                        $existingDraftGr = \App\Models\GoodsReceipt::where('warehouse_check_id', $record->id)
                            ->where('status', 'DRAFT')
                            ->first();

                        $isNewGr = false;
                        if ($existingDraftGr) {
                            $gr = $existingDraftGr;
                            // Hapus item draft lama untuk diisi ulang dengan data fisik terbaru
                            $gr->items()->delete();
                        } else {
                            $isNewGr = true;
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
                        }

                        $existingGrIds = \App\Models\GoodsReceipt::where('status', '!=', 'CANCELLED')
                            ->where('id', '!=', $gr->id)
                            ->where(function ($q) use ($record) {
                                $q->where('warehouse_check_id', $record->id);
                                if ($record->purchase_order_id) {
                                    $q->orWhere(function ($subQ) use ($record) {
                                        $subQ->where('purchase_order_id', $record->purchase_order_id)
                                             ->whereNull('warehouse_check_id');
                                    });
                                }
                            })
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

                            // If there are other finalized GRs, use remainingQty; otherwise full scanned qty
                            $qtyToInsert = ($existingGrIds->count() > 0) ? $remainingQty : $checkItem->qty_scanned;

                            if ($qtyToInsert > 0) {
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

                        // Jika tidak ada item tersisa dan tadi membuat GR baru, hapus GR kosong dan tandai processed
                        if ($gr->items()->count() === 0 && $isNewGr) {
                            $gr->delete();
                            $record->update(['status' => 'processed']);
                            \Filament\Notifications\Notification::make()
                                ->title('Sudah Diproses')
                                ->body('Semua barang pada pengecekan ini sudah pernah dibuatkan Goods Receipt sebelumnya.')
                                ->info()
                                ->send();
                            return null;
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

                Action::make('edit')
                    ->label('Edit Qty')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (WarehouseCheck $record) => $record->isEditable())
                    ->modalHeading('Edit / Revisi Qty Pengecekan Gudang')
                    ->modalDescription('Anda dapat mengedit Qty Fisik penerimaan jika terdapat salah ketik. Perhatian: Jika ada barang yang melebihi Qty Order / Sisa PO, dokumen akan otomatis membutuhkan Otorisasi ulang dari Supervisor.')
                    ->modalWidth('4xl')
                    ->mountUsing(function ($form, WarehouseCheck $record) {
                        $record->load('items.product');
                        $form->fill([
                            'items' => $record->items->map(fn($item) => [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'barcode' => $item->product ? ($item->product->barcode ?: '-') : '-',
                                'product_name' => $item->product ? $item->product->name : (\Illuminate\Support\Facades\DB::table('products')->where('id', $item->product_id)->value('name') ?? 'Unknown'),
                                'qty_po' => floatval($item->qty_po) == intval($item->qty_po) ? intval($item->qty_po) : floatval($item->qty_po),
                                'qty_scanned' => floatval($item->qty_scanned) == intval($item->qty_scanned) ? intval($item->qty_scanned) : floatval($item->qty_scanned),
                            ])->toArray()
                        ]);
                    })
                    ->form([
                        \Filament\Forms\Components\Repeater::make('items')
                            ->label('Daftar Barang Penerimaan')
                            ->schema([
                                \Filament\Forms\Components\Hidden::make('id'),
                                \Filament\Forms\Components\Hidden::make('product_id'),
                                \Filament\Forms\Components\TextInput::make('barcode')
                                    ->disabled()
                                    ->label('Barcode')
                                    ->columnSpan(3),
                                \Filament\Forms\Components\TextInput::make('product_name')
                                    ->disabled()
                                    ->label('Nama Barang')
                                    ->columnSpan(5),
                                \Filament\Forms\Components\TextInput::make('qty_po')
                                    ->disabled()
                                    ->label('Sisa PO')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('qty_scanned')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->label('Qty Fisik')
                                    ->helperText(fn ($get) => 'Maks PO: ' . $get('qty_po'))
                                    ->columnSpan(2),
                            ])
                            ->disableItemCreation()
                            ->columns(12)
                    ])
                    ->action(function (WarehouseCheck $record, array $data) {
                        $submittedIds = collect($data['items'])->pluck('id')->filter()->toArray();
                        $record->items()->whereNotIn('id', $submittedIds)->delete();
                
                        $hasOverQty = false;
                        foreach ($data['items'] as $itemData) {
                            $checkItem = $record->items()->where('id', $itemData['id'])->first();
                            if ($checkItem) {
                                $qty = floatval($itemData['qty_scanned'] ?? 0);
                                $checkItem->update([
                                    'qty_scanned' => $qty,
                                ]);
                                
                                if ($qty > floatval($checkItem->qty_po)) {
                                    $hasOverQty = true;
                                }
                            }
                        }

                        // Batalkan persetujuan pending lama jika ada
                        $record->cancelPendingApprovals();
                
                        if ($hasOverQty) {
                            $record->requestApproval('Revisi: Terdapat kuantitas penerimaan barang yang melebihi sisa PO.', 1);
                            \Filament\Notifications\Notification::make()
                                ->title('Pengecekan Disimpan')
                                ->body('Karena terdapat barang yang melebihi Qty Order / Sisa PO, dokumen wajib diotorisasi ulang oleh Supervisor.')
                                ->warning()
                                ->send();
                        } else {
                            $record->update([
                                'status' => 'approved',
                                'notes' => ($record->notes ? $record->notes . ' | ' : '') . 'Koreksi Qty oleh ' . auth()->user()->name . ' (Sesuai PO)',
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Pengecekan Disimpan')
                                ->body('Kuantitas berhasil diperbarui dan otomatis disetujui (Sesuai PO).')
                                ->success()
                                ->send();
                        }
                    }),

                Action::make('scan_edit')
                    ->label('Buka Scanner')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->visible(fn (WarehouseCheck $record) => $record->isEditable())
                    ->url(fn (WarehouseCheck $record) => route('warehouse.receive.scan', ['po_id' => $record->purchase_order_id, 'check_id' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('sync_status')
                    ->label('Sync Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (WarehouseCheck $record) => in_array($record->status, ['approved', 'partially_processed', 'processed']))
                    ->action(function (WarehouseCheck $record) {
                        $oldStatus = $record->status;
                        $record->syncStatus();
                        $record->refresh();

                        $statusLabels = [
                            'pending' => 'Pending',
                            'pending_approval' => 'Menunggu Otorisasi',
                            'approved' => 'Disetujui',
                            'partially_processed' => 'Dibuat GR Sebagian',
                            'rejected' => 'Ditolak',
                            'processed' => 'Sudah Dibuat GR',
                        ];
                        $label = $statusLabels[$record->status] ?? $record->status;

                        if ($oldStatus !== $record->status) {
                            \Filament\Notifications\Notification::make()
                                ->title('Status Diperbarui')
                                ->body("Status berubah dari '{$oldStatus}' menjadi: {$label}")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Status Sudah Sesuai')
                                ->body("Status dokumen sudah sesuai: {$label}")
                                ->info()
                                ->send();
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
