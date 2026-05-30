<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use App\Models\Product;
use App\Models\GoodsReceipt;
use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class BarcodePrinter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static \UnitEnum|string|null $navigationGroup = 'UTILITY';
    protected static ?string $title = 'Cetak Barcode & Pricecard';
    protected static ?string $navigationLabel = 'Cetak Barcode';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.barcode-printer';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => auth()->user()->branch_id,
            'print_items' => []
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $userBranchId = auth()->user()->branch_id;

        return $schema
            ->schema([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::pluck('name', 'id'))
                    ->default($userBranchId)
                    ->hidden(fn () => $userBranchId !== null)
                    ->live()
                    ->required()
                    ->columnSpan(1),
                    
                Select::make('date_type')
                    ->label('Tipe Tanggal (Label Tempel)')
                    ->options([
                        'cetak' => 'Tanggal Cetak',
                        'expired' => 'Tanggal Expired',
                    ])
                    ->default('cetak')
                    ->live()
                    ->columnSpan(1),

                \Filament\Forms\Components\DatePicker::make('custom_date')
                    ->label('Pilih Tanggal Expired')
                    ->hidden(fn ($get) => $get('date_type') !== 'expired')
                    ->required(fn ($get) => $get('date_type') === 'expired')
                    ->columnSpan(1),

                Repeater::make('print_items')
                    ->label('')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, $get) use ($userBranchId) {
                                $branchId = $userBranchId ?? $get('../../branch_id');
                                return Product::where(function($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                                    })
                                    ->when($branchId, function($q) use ($branchId) {
                                        $q->whereHas('stocks', fn($sq) => $sq->where('branch_id', $branchId));
                                    })
                                    ->limit(50)->pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('sku', $product->sku);
                                        $set('barcode', $product->barcode);
                                        $set('price', $product->selling_price);
                                    }
                                }
                            })
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        TextInput::make('barcode')
                            ->label('Barcode')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        TextInput::make('price')
                            ->label('Harga Jual')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        TextInput::make('copies')
                            ->label('Jml Cetak')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Tambah Produk Manual')
                    ->columnSpan('full')
            ])
            ->columns(3)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('load_receipt')
                ->label('Load Data Penerimaan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Select::make('goods_receipt_id')
                        ->label('Pilih Transaksi Penerimaan')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            $branchId = auth()->user()->branch_id ?? $this->data['branch_id'] ?? null;
                            return GoodsReceipt::where('receipt_number', 'like', "%{$search}%")
                                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                ->limit(20)->pluck('receipt_number', 'id')->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => GoodsReceipt::find($value)?->receipt_number)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $receipt = GoodsReceipt::with('items.product')->find($data['goods_receipt_id']);
                    if ($receipt) {
                        $currentItems = $this->data['print_items'] ?? [];
                        foreach ($receipt->items as $item) {
                            if ($item->product) {
                                $currentItems[] = [
                                    'product_id' => $item->product_id,
                                    'sku' => $item->product->sku,
                                    'barcode' => $item->product->barcode,
                                    'price' => $item->product->selling_price,
                                    'copies' => $item->quantity_received > 0 ? $item->quantity_received : 1,
                                ];
                            }
                        }
                        $this->form->fill([
                            'branch_id' => $this->data['branch_id'] ?? auth()->user()->branch_id,
                            'print_items' => $currentItems
                        ]);
                    }
                }),
                
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $items = $this->data['print_items'] ?? [];
                    if (empty($items)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Tidak ada data untuk diexport')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $productIds = collect($items)->pluck('product_id')->filter()->unique();
                    $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                    $csvData = "SKU,Barcode,Nama Barang,Harga Jual\n";
                    foreach ($items as $item) {
                        $product = $products->get($item['product_id'] ?? null);
                        if (!$product) continue;

                        // Secure against commas in product names by wrapping in quotes
                        $name = str_replace('"', '""', $product->name);
                        $sku = $product->sku ?? '';
                        $barcode = $product->barcode ?? '';
                        $price = $product->selling_price ?? '';
                        $copies = (int) ($item['copies'] ?? 1);
                        
                        for ($i = 0; $i < $copies; $i++) {
                            $csvData .= "\"{$sku}\",\"{$barcode}\",\"{$name}\",\"{$price}\"\n";
                        }
                    }

                    return response()->streamDownload(function () use ($csvData) {
                        echo $csvData;
                    }, 'barcode_print_items.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('clear_all')
                ->label('Kosongkan Semua')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function () {
                    $this->form->fill([
                        'branch_id' => $this->data['branch_id'] ?? auth()->user()->branch_id,
                        'print_items' => []
                    ]);
                }),
        ];
    }
    
    public function printLabel()
    {
        return $this->processPrint('print.barcode.label');
    }

    public function printPricecard()
    {
        return $this->processPrint('print.barcode.pricecard');
    }

    public function clearAll()
    {
        $this->form->fill([
            'branch_id' => $this->data['branch_id'] ?? auth()->user()->branch_id,
            'print_items' => []
        ]);
        \Filament\Notifications\Notification::make()
            ->title('Semua data berhasil dikosongkan')
            ->success()
            ->send();
    }
    
    protected function processPrint($routeName)
    {
        $items = $this->data['print_items'] ?? [];
        if (empty($items)) {
            \Filament\Notifications\Notification::make()
                ->title('Tidak ada item untuk dicetak')
                ->danger()
                ->send();
            return;
        }

        // Generate unique session key for this print job
        $sessionKey = (string) Str::uuid();
        
        // Cache the queue for 1 hour
        Cache::put('print_queue_' . $sessionKey, $items, now()->addHours(1));

        $url = route($routeName, [
            'session_key' => $sessionKey,
            'branch_id' => $this->data['branch_id'] ?? auth()->user()->branch_id,
            'date_type' => $this->data['date_type'] ?? 'cetak',
            'custom_date' => $this->data['custom_date'] ?? null,
        ]);
        
        $this->dispatch('open-url-new-tab', url: $url);
    }
}
