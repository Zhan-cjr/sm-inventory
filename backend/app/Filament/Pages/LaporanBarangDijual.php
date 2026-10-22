<?php

namespace App\Filament\Pages;

use App\Models\AllSalesItem;
use App\Models\Supplier;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\TransactionItemExporter;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Livewire\Attributes\Url;

class LaporanBarangDijual extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    #[Url(as: 'tab')]
    public string $activeTab = 'detail';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Laporan Barang Dijual';
    protected static ?string $title = 'Laporan Barang Dijual';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.report-page';

    public function getReportTabs(): array
    {
        return [
            'detail' => [
                'label' => 'Detail Per Transaksi',
                'icon' => 'heroicon-o-list-bullet',
            ],
            'rekap' => [
                'label' => 'Rekap Per Barang',
                'icon' => 'heroicon-o-squares-2x2',
            ],
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['id'] ?? $record['product_id'] ?? uniqid());
        }

        return (string) ($record->getKey() ?? $record->id ?? $record->product_id ?? uniqid());
    }

    public function table(Table $table): Table
    {
        $isRekap = $this->activeTab === 'rekap';

        return $table
            ->defaultKeySort(false)
            ->defaultSort($isRekap ? 'qty_terjual' : 'transaction_date', 'desc')
            ->query(
                AllSalesItem::query()
                    ->whereNotNull('all_sales_items.product_id')
                    ->when($isRekap, function ($q) {
                        $q->selectRaw("
                            all_sales_items.product_id as id,
                            all_sales_items.product_id,
                            SUM(CASE WHEN all_sales_items.quantity > 0 THEN all_sales_items.quantity ELSE 0 END) as qty_terjual,
                            SUM(CASE WHEN all_sales_items.quantity < 0 THEN ABS(all_sales_items.quantity) ELSE 0 END) as qty_retur,
                            SUM(all_sales_items.quantity) as qty_net,
                            SUM(all_sales_items.total_cogs) as total_cogs,
                            SUM(all_sales_items.subtotal) as subtotal
                        ")
                        ->groupBy('all_sales_items.product_id');
                    })
                    ->when(Auth::user()->branch_id !== null, function (Builder $query) {
                        $query->where('branch_id', Auth::user()->branch_id);
                    })
                    ->with(['branch', 'product', 'product.supplier'])
            )
            ->columns($isRekap ? [
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('qty_terjual')
                    ->label('Total Terjual')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('qty_retur')
                    ->label('Total Retur')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('qty_net')
                    ->label('Total Qty Bersih')
                    ->numeric()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->numeric())
                    ->sortable(),
                TextColumn::make('product.cost_price')
                    ->label('Harga Pokok (HPP)')
                    ->money('IDR', true)
                    ->state(fn ($record) => $record->product?->cost_price_tax > 0 ? $record->product?->cost_price_tax : ($record->product?->cost_price ?? 0)),
                TextColumn::make('product.selling_price')
                    ->label('Harga Jual')
                    ->money('IDR', true),
                TextColumn::make('subtotal')
                    ->label('Total Penjualan (Net)')
                    ->money('IDR', true)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', true))
                    ->sortable(),
            ] : [
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'POS' => 'success',
                        'ECOMMERCE' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('transaction_number')
                    ->label('No Transaksi')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('qty_terjual')
                    ->label('Qty Terjual')
                    ->state(fn (AllSalesItem $record) => $record->quantity > 0 ? $record->quantity : 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_retur')
                    ->label('Qty Retur')
                    ->state(fn (AllSalesItem $record) => $record->quantity < 0 ? abs($record->quantity) : 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->label('Harga Beli + PPN')
                    ->money('IDR', true)
                    ->state(function (AllSalesItem $record) {
                        $qty = abs($record->quantity);
                        if ($qty > 0 && $record->total_cogs > 0) {
                            return $record->total_cogs / $qty;
                        }

                        $branch_id = $record->branch_id;
                        $stock = \App\Models\Stock::where('product_id', $record->product_id)
                                ->where('branch_id', $branch_id)
                                ->first();
                                
                        if ($stock) {
                            return $stock->cost_price_tax > 0 ? $stock->cost_price_tax : ($stock->cost_price ?? 0);
                        }
                        
                        return $record->product?->cost_price_tax > 0 ? $record->product?->cost_price_tax : ($record->product?->cost_price ?? 0);
                    })
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Jual')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('discount_per_item')
                    ->label('Diskon/Item')
                    ->money('IDR', true)
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal (Net)')
                    ->money('IDR', true)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', true))
                    ->sortable(),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('transaction_date', 'transaction_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('product', fn ($q) => $q->where('supplier_id', $data['value']));
                        }
                    }),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'POS' => 'POS Offline',
                        'ECOMMERCE' => 'E-Commerce (Online)'
                    ])
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'laporan-barang-dijual',
                        'tab' => $this->activeTab,
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                    ]), true),
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter(\App\Filament\Exports\AllSalesItemExporter::class)
                        ->formats([\Filament\Actions\Exports\Enums\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \Filament\Actions\Action::make('export_xls')
                        ->label('Export Xlsx (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                            'type' => 'laporan-barang-dijual',
                            'tab' => $this->activeTab,
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
            ]);

    }
}
