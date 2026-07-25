<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use App\Models\Product;
use App\Models\GoodsReceipt;
use App\Models\Branch;
use App\Models\Stock;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class BarcodePrinter extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

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
            'date_type' => 'cetak',
            'custom_date' => null,
            'add_product_id' => null,
            'print_items' => []
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $userBranchId = auth()->user()->branch_id;

        return $schema
            ->schema([
                Select::make('branch_id')
                    ->label('Cabang Gudang/Toko')
                    ->options(Branch::pluck('name', 'id'))
                    ->default($userBranchId)
                    ->hidden(fn () => $userBranchId !== null)
                    ->live()
                    ->required()
                    ->columnSpan(1),

                Select::make('date_type')
                    ->label('Tipe Tanggal (Label Tempel)')
                    ->options([
                        'cetak' => 'Tanggal Cetak Hari Ini',
                        'expired' => 'Tanggal Expired Produk',
                    ])
                    ->default('cetak')
                    ->live()
                    ->columnSpan(1),

                DatePicker::make('custom_date')
                    ->label('Pilih Tanggal Expired')
                    ->hidden(fn ($get) => $get('date_type') !== 'expired')
                    ->required(fn ($get) => $get('date_type') === 'expired')
                    ->columnSpan(1),

                Select::make('add_product_id')
                    ->label('🔎 Cari & Tambah Produk Ke Antrean (Ketik Nama / SKU / Barcode)')
                    ->searchable()
                    ->placeholder('Ketik nama barang, SKU, atau scan barcode...')
                    ->getSearchResultsUsing(function (string $search, $get) use ($userBranchId) {
                        $branchId = $userBranchId ?? $get('branch_id');
                        return Product::where(function($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('sku', 'like', "%{$search}%")
                                  ->orWhere('barcode', 'like', "%{$search}%");
                            })
                            ->when($branchId, function($q) use ($branchId) {
                                $q->whereHas('stocks', fn($sq) => $sq->where('branch_id', $branchId));
                            })
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} (SKU: {$p->sku} | Barcode: " . ($p->barcode ?? '-') . ")"])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $this->addProductToQueue($state);
                            $set('add_product_id', null);
                        }
                    })
                    ->columnSpan('full'),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function addProductToQueue($productId, $copies = 1): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $currentItems = $this->data['print_items'] ?? [];
        $branchId = $this->data['branch_id'] ?? auth()->user()->branch_id;

        // Check if product already in queue
        $existingIndex = null;
        foreach ($currentItems as $idx => $item) {
            if (($item['product_id'] ?? null) == $product->id) {
                $existingIndex = $idx;
                break;
            }
        }

        if ($existingIndex !== null) {
            $currentItems[$existingIndex]['copies'] += $copies;
        } else {
            $stock = $branchId ? Stock::where('product_id', $product->id)->where('branch_id', $branchId)->first() : null;
            $price = ($stock && $stock->selling_price > 0) ? $stock->selling_price : $product->selling_price;

            $currentItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? '-',
                'barcode' => $product->barcode ?? '-',
                'price' => (float) $price,
                'copies' => (int) $copies,
            ];
        }

        $this->data['print_items'] = $currentItems;

        \Filament\Notifications\Notification::make()
            ->title("{$product->name} ditambahkan ke antrean")
            ->success()
            ->send();
    }

    public function updateCopies($index, $copies): void
    {
        $copies = max(1, (int) $copies);
        if (isset($this->data['print_items'][$index])) {
            $this->data['print_items'][$index]['copies'] = $copies;
        }
    }

    public function changeCopiesStep($index, $delta): void
    {
        if (isset($this->data['print_items'][$index])) {
            $current = (int) ($this->data['print_items'][$index]['copies'] ?? 1);
            $next = max(1, $current + $delta);
            $this->data['print_items'][$index]['copies'] = $next;
        }
    }

    public function removeItem($index): void
    {
        if (isset($this->data['print_items'][$index])) {
            $name = $this->data['print_items'][$index]['name'] ?? 'Item';
            array_splice($this->data['print_items'], $index, 1);

            \Filament\Notifications\Notification::make()
                ->title("{$name} dihapus dari antrean")
                ->info()
                ->send();
        }
    }

    public function batchSetCopies(int $count = 1): void
    {
        $items = $this->data['print_items'] ?? [];
        if (empty($items)) {
            \Filament\Notifications\Notification::make()
                ->title('Tidak ada item di dalam antrean')
                ->warning()
                ->send();
            return;
        }

        foreach ($items as &$item) {
            $item['copies'] = $count;
        }
        
        $this->data['print_items'] = $items;

        \Filament\Notifications\Notification::make()
            ->title("Jumlah cetak semua produk diubah menjadi {$count}")
            ->success()
            ->send();
    }

    public function clearAll(): void
    {
        $this->data['print_items'] = [];
        \Filament\Notifications\Notification::make()
            ->title('Semua data antrean berhasil dikosongkan')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('load_receipt')
                ->label('Load Data Penerimaan (GR)')
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
                        $countAdded = 0;
                        foreach ($receipt->items as $item) {
                            if ($item->product) {
                                $copies = $item->quantity_received > 0 ? $item->quantity_received : 1;
                                $this->addProductToQueue($item->product_id, $copies);
                                $countAdded++;
                            }
                        }
                        \Filament\Notifications\Notification::make()
                            ->title("Berhasil memuat {$countAdded} barang dari GR {$receipt->receipt_number}")
                            ->success()
                            ->send();
                    }
                }),
                
            Action::make('export_excel')
                ->label('Export CSV / Excel')
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

                    $csvData = "SKU,Barcode,Nama Barang,Harga Jual,Kuantitas\n";
                    foreach ($items as $item) {
                        $product = $products->get($item['product_id'] ?? null);
                        if (!$product) continue;

                        $name = str_replace('"', '""', $product->name);
                        $sku = $product->sku ?? '';
                        $barcode = $product->barcode ?? '';
                        $price = $item['price'] ?? 0;
                        $copies = (int) ($item['copies'] ?? 1);
                        
                        $csvData .= "\"{$sku}\",\"{$barcode}\",\"{$name}\",\"{$price}\",\"{$copies}\"\n";
                    }

                    return response()->streamDownload(function () use ($csvData) {
                        echo $csvData;
                    }, 'antrean_cetak_barcode.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('clear_all')
                ->label('Kosongkan Semua')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(fn() => $this->clearAll()),
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

        $sessionKey = (string) Str::uuid();
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
