<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\Auth;

class FinalCheckStockOpname extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StockOpnameSessionResource::class;
    protected string $view            = 'filament.pages.final-check-stock-opname';

    public array $finalQuantities = [];
    public array $finalNotes      = [];

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $this->record->load([
            'items.product',
            'items.rackSession.rack',
        ]);

        if ($this->record->status !== 'FINAL_CHECK') {
            $this->redirect(StockOpnameSessionResource::getUrl('view', ['record' => $this->record]));
            return;
        }

        // Pre-load discrepancy items
        $discrepancyItems = $this->record->items()->where('status', 'DISCREPANCY')->get();
        foreach ($discrepancyItems as $item) {
            $this->finalQuantities[$item->id] = $item->count2_quantity;
            $this->finalNotes[$item->id]      = '';
        }
    }

    public function saveFinalCheck(): void
    {
        $session         = $this->record;
        $discrepancyItems = $session->items()->where('status', 'DISCREPANCY')->get();
        $allFilled       = true;

        foreach ($discrepancyItems as $item) {
            $qty = $this->finalQuantities[$item->id] ?? null;
            if ($qty === null || $qty === '') {
                $allFilled = false;
                break;
            }

            $item->update([
                'final_quantity' => (float) $qty,
                'final_by'       => Auth::id(),
                'final_at'       => now(),
                'final_notes'    => $this->finalNotes[$item->id] ?? null,
                'status'         => 'FINAL_DONE',
            ]);
        }

        if (!$allFilled) {
            Notification::make()->title('Harap isi semua final quantity!')->danger()->send();
            return;
        }

        Notification::make()->title('Final check tersimpan. Silakan kembali dan klik "Simpan & Selesaikan".')->success()->send();
        $this->redirect(StockOpnameSessionResource::getUrl('view', ['record' => $session]));
    }

    /**
     * Ambil data item discrepancy dikelompokkan per produk lintas rak
     */
    public function getDiscrepancyGrouped(): array
    {
        $items = $this->record->items()
            ->where('status', 'DISCREPANCY')
            ->with(['product', 'rackSession.rack'])
            ->get();

        $grouped = [];
        foreach ($items as $item) {
            $pid = $item->product_id;
            if (!isset($grouped[$pid])) {
                // Hitung total lintas rak untuk produk ini
                $allItems = $this->record->items()
                    ->where('product_id', $pid)
                    ->get();

                $grouped[$pid] = [
                    'product_name' => $item->product?->name,
                    'product_sku'  => $item->product?->sku,
                    'system_qty'   => $item->system_quantity,
                    'total_count1' => $allItems->sum('count1_quantity'),
                    'total_count2' => $allItems->sum('count2_quantity'),
                    'racks'        => [],
                ];
            }

            $grouped[$pid]['racks'][] = [
                'item_id'         => $item->id,
                'rack_code'       => $item->rackSession?->rack?->rack_code,
                'rack_name'       => $item->rackSession?->rack?->rack_name,
                'count1_quantity' => $item->count1_quantity,
                'count2_quantity' => $item->count2_quantity,
                'discrepancy'     => $item->discrepancy_1_2,
            ];
        }

        return array_values($grouped);
    }
}
