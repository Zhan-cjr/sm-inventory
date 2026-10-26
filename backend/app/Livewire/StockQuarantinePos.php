<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Stock;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockQuarantinePos extends Component
{
    public $reference_number;
    public $movement_date;
    public $branch_id;
    public $notes = '';
    public $cetak_bukti = true;

    public $searchQuery = '';
    public $searchResults = [];
    public $cart = [];

    public $totalQty = 0;
    public $totalLines = 0;

    public $reasonOptions = [
        'Kemasan Bocor / Rusak' => 'Kemasan Bocor / Rusak',
        'Kadaluarsa (Expired)' => 'Kadaluarsa (Expired)',
        'Pecah di Rak Display' => 'Pecah di Rak Display',
        'Cacat Pabrik / Defect' => 'Cacat Pabrik / Defect',
        'Kualitas Menurun' => 'Kualitas Menurun',
        'Lainnya' => 'Lainnya',
    ];

    public function mount()
    {
        $this->reference_number = 'BS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $this->movement_date = date('Y-m-d');
        
        $userBranchId = Auth::user()?->branch_id;
        $this->branch_id = $userBranchId ?: Branch::where('is_active', true)->first()?->id;

        $this->calculateTotals();
    }

    public function updatedBranchId($value)
    {
        $this->cart = [];
        $this->searchResults = [];
        $this->calculateTotals();
    }

    public function updatedSearchQuery($value)
    {
        if (empty($this->branch_id)) {
            $this->searchResults = [];
            return;
        }

        $trimmed = trim((string)$value);
        if (strlen($trimmed) >= 1) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $trimmed);

            $products = Product::query()
                ->select(
                    'products.id',
                    'products.name',
                    'products.barcode',
                    'products.sku',
                    'products.unit_of_measure',
                    'stocks.quantity_on_hand',
                    'stocks.quantity_damaged'
                )
                ->leftJoin('stocks', function ($join) {
                    $join->on('stocks.product_id', '=', 'products.id')
                         ->where('stocks.branch_id', '=', $this->branch_id);
                })
                ->where('products.is_active', true)
                ->where(function ($q) use ($trimmed, $escaped) {
                    $q->where('products.barcode', $trimmed)
                      ->orWhere('products.sku', $trimmed)
                      ->orWhereJsonContains('products.metadata->additional_barcodes', $trimmed)
                      ->orWhere('products.barcode', 'like', "%{$escaped}%")
                      ->orWhere('products.sku', 'like', "%{$escaped}%")
                      ->orWhere('products.name', 'like', "%{$escaped}%");
                })
                ->orderByRaw("
                    CASE 
                        WHEN products.barcode = ? OR products.sku = ? THEN 1
                        WHEN products.barcode LIKE ? OR products.sku LIKE ? THEN 2
                        WHEN products.name LIKE ? THEN 3
                        ELSE 4
                    END
                ", [$trimmed, $trimmed, "{$escaped}%", "{$escaped}%", "{$escaped}%"])
                ->orderByRaw('COALESCE(stocks.quantity_on_hand, 0) DESC')
                ->take(20)
                ->get();

            $formatted = [];
            foreach ($products as $p) {
                $physical = (float)($p->quantity_on_hand ?? 0);
                $damaged = (float)($p->quantity_damaged ?? 0);
                $salable = max(0, $physical - $damaged);

                $formatted[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'barcode' => $p->barcode,
                    'sku' => $p->sku,
                    'unit_name' => $p->unit_of_measure ?? 'Pcs',
                    'salable_stock' => $salable,
                    'damaged_stock' => $damaged,
                    'physical_stock' => $physical,
                ];
            }

            $this->searchResults = $formatted;
        } else {
            $this->searchResults = [];
        }
    }

    public function handleScanEnter()
    {
        if (count($this->searchResults) === 1) {
            $this->selectProduct($this->searchResults[0]['id']);
        } elseif (count($this->searchResults) > 1) {
            $trimmed = trim((string)$this->searchQuery);
            foreach ($this->searchResults as $res) {
                if ($res['barcode'] === $trimmed || $res['sku'] === $trimmed) {
                    $this->selectProduct($res['id']);
                    return;
                }
            }
            // Pilih item pertama
            $this->selectProduct($this->searchResults[0]['id']);
        }
    }

    public function selectProduct($productId)
    {
        $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $productId)->first();
        $salable = $stock ? $stock->salable_quantity : 0;
        $damaged = $stock ? (float)($stock->quantity_damaged ?? 0) : 0;

        if ($salable <= 0) {
            Notification::make()
                ->title('Stok Layak Jual Kosong (0 pcs)')
                ->body('Tidak ada stok layak jual di rak display yang bisa dipindahkan ke Karantina BS.')
                ->warning()
                ->send();
            return;
        }

        $product = Product::find($productId);
        if (!$product) return;

        // Cek jika produk sudah ada di cart
        $existingIndex = null;
        foreach ($this->cart as $index => $item) {
            if ($item['product_id'] === $productId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQty = $this->cart[$existingIndex]['qty'] + 1;
            if ($newQty > $salable) {
                Notification::make()
                    ->title('Kuantitas melebihi stok layak jual!')
                    ->body("Maksimal stok yang tersedia di rak: {$salable} pcs.")
                    ->warning()
                    ->send();
                return;
            }
            $this->cart[$existingIndex]['qty'] = $newQty;
            $targetIndex = $existingIndex;
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'unit_name' => $product->unit_of_measure ?? 'Pcs',
                'salable_stock' => $salable,
                'damaged_stock' => $damaged,
                'qty' => 1,
                'reason' => 'Kemasan Bocor / Rusak',
                'notes' => '',
            ];
            $targetIndex = count($this->cart) - 1;
        }

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->calculateTotals();

        $this->dispatch('item-added', index: $targetIndex);
    }

    public function updateQty($index, $value)
    {
        if (!isset($this->cart[$index])) return;

        $qty = (float)$value;
        $salable = (float)$this->cart[$index]['salable_stock'];

        if ($qty > $salable) {
            $this->cart[$index]['qty'] = $salable;
            Notification::make()
                ->title('Kuantitas disesuaikan')
                ->body("Kuantitas dibatasi maksimal stok layak jual ({$salable} pcs).")
                ->warning()
                ->send();
        } else {
            $this->cart[$index]['qty'] = max(0.01, $qty);
        }

        $this->calculateTotals();
    }

    public function removeItem($index)
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
            $this->calculateTotals();
        }
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalLines = count($this->cart);
        $this->totalQty = array_sum(array_column($this->cart, 'qty'));
    }

    public function save()
    {
        if (empty($this->branch_id)) {
            Notification::make()->title('Pilih cabang terlebih dahulu!')->danger()->send();
            return;
        }

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang barang karantina masih kosong!')->danger()->send();
            return;
        }

        // Validasi kuantitas
        foreach ($this->cart as $index => $item) {
            $qty = (float)$item['qty'];
            if ($qty <= 0) {
                Notification::make()
                    ->title("Kuantitas {$item['name']} harus lebih dari 0!")
                    ->danger()
                    ->send();
                return;
            }

            $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $item['product_id'])->first();
            $salable = $stock ? $stock->salable_quantity : 0;
            if ($qty > $salable) {
                Notification::make()
                    ->title("Stok {$item['name']} tidak mencukupi!")
                    ->body("Hanya tersedia {$salable} pcs layak jual di rak display.")
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            foreach ($this->cart as $item) {
                $stock = Stock::where('branch_id', $this->branch_id)->where('product_id', $item['product_id'])->lockForUpdate()->first();
                if (!$stock) {
                    throw new \Exception("Stok produk {$item['name']} tidak ditemukan.");
                }

                $itemNotes = trim(($this->notes ? "{$this->notes} | " : '') . ($item['notes'] ?? ''));
                $stock->moveToQuarantine(
                    (float)$item['qty'],
                    $item['reason'],
                    $itemNotes ?: null,
                    $userId,
                    $this->reference_number
                );
            }

            DB::commit();

            Notification::make()
                ->title("Pemindahan {$this->totalLines} item ke Karantina BS Berhasil!")
                ->body("No Dokumen: {$this->reference_number}. Stok layak jual telah dikurangi otomatis.")
                ->success()
                ->send();

            if ($this->cetak_bukti) {
                $printUrl = route('print.stock-quarantine', ['referenceNumber' => $this->reference_number]);
                $this->dispatch('open-print-tab', url: $printUrl);
            }

            return redirect()->to('/admin/stock-quarantines');

        } catch (\Throwable $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal memproses pemindahan barang rusak!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('livewire.stock-quarantine-pos', [
            'branches' => $branches,
        ]);
    }
}
