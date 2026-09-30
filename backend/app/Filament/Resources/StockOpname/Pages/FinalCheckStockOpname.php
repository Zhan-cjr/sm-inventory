<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinalCheckStockOpname extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StockOpnameSessionResource::class;
    protected string $view            = 'filament.pages.final-check-stock-opname';

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        $this->record->load([
            'items.product',
            'items.rackSession.rack',
        ]);

        if (!in_array($this->record->status, ['FINAL_CHECK', 'COMPLETED'])) {
            $this->redirect(StockOpnameSessionResource::getUrl('view', ['record' => $this->record]));
            return;
        }
    }

    public function getFinalPortalUrlProperty(): string
    {
        return route('opname.final', $this->record->session_token);
    }

    /**
     * Ringkasan status verifikasi final check
     */
    public function getSummaryProperty(): array
    {
        $items = $this->record->items()
            ->whereIn('status', ['DISCREPANCY', 'FINAL_DONE'])
            ->get();

        $total    = $items->count();
        $verified = $items->where('status', 'FINAL_DONE')->count();
        $pending  = $items->where('status', 'DISCREPANCY')->count();
        $percent  = $total > 0 ? round(($verified / $total) * 100) : 100;

        return [
            'total'       => $total,
            'verified'    => $verified,
            'pending'     => $pending,
            'percent'     => $percent,
            'is_complete' => $pending === 0 && $total > 0,
        ];
    }

    /**
     * Ambil data item discrepancy dikelompokkan per produk lintas rak
     */
    public function getDiscrepancyGrouped(): array
    {
        $items = $this->record->items()
            ->whereIn('status', ['DISCREPANCY', 'FINAL_DONE'])
            ->with(['product', 'rackSession.rack'])
            ->get();

        $grouped = [];
        foreach ($items as $item) {
            $pid = $item->product_id;
            if (!isset($grouped[$pid])) {
                $allItems = $this->record->items()
                    ->where('product_id', $pid)
                    ->get();

                $grouped[$pid] = [
                    'product_id'       => $pid,
                    'product_name'     => $item->product?->name ?? 'Produk #' . $pid,
                    'product_sku'      => $item->product?->sku ?? '-',
                    'product_barcode'  => $item->product?->barcode ?? '-',
                    'system_qty'       => (float) $item->system_quantity,
                    'total_count1'     => (float) $allItems->sum('count1_quantity'),
                    'total_count2'     => (float) $allItems->sum('count2_quantity'),
                    'racks'            => [],
                ];
            }

            $grouped[$pid]['racks'][] = [
                'item_id'         => $item->id,
                'rack_code'       => $item->rackSession?->rack?->rack_code ?? '-',
                'rack_name'       => $item->rackSession?->rack?->rack_name ?? '-',
                'count1_quantity' => (float) $item->count1_quantity,
                'count2_quantity' => (float) $item->count2_quantity,
                'discrepancy'     => (float) $item->discrepancy_1_2,
                'status'          => $item->status,
                'final_quantity'  => $item->final_quantity !== null ? (float) $item->final_quantity : null,
                'final_by_name'   => $item->final_by_name,
                'final_at'        => $item->final_at,
                'final_notes'     => $item->final_notes,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Selesaikan sesi stok opname setelah semua item selisih diverifikasi di portal fisik
     */
    public function finalizeSession(): void
    {
        $session = $this->record;

        $pending = $session->items()->where('status', 'DISCREPANCY')->count();
        if ($pending > 0) {
            Notification::make()
                ->title("Masih ada {$pending} item selisih yang belum diverifikasi di portal fisik!")
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($session) {
            $productSummary = $session->getProductSummary();

            foreach ($productSummary as $summary) {
                $productId = $summary['product_id'];
                if (!$productId) continue;

                $effectiveQty = isset($summary['effective_qty'])
                    ? (float) $summary['effective_qty']
                    : (($summary['total_final'] > 0) ? (float) $summary['total_final'] : (float) $summary['total_count2']);

                $stock = Stock::where('branch_id', $session->branch_id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$stock) {
                    $product = Product::find($productId);
                    if (!$product) continue;

                    $stock = Stock::create([
                        'branch_id'             => $session->branch_id,
                        'product_id'            => $productId,
                        'cost_price'            => $product->cost_price ?? 0,
                        'cost_price_tax'        => $product->cost_price_tax ?? 0,
                        'selling_price'         => $product->selling_price ?? 0,
                        'margin_gol_1'          => $product->margin_gol_1,
                        'harga_jual_1'          => $product->harga_jual_1,
                        'qty_min_gol_1'         => $product->qty_min_gol_1 ?? 1,
                        'margin_gol_2'          => $product->margin_gol_2,
                        'harga_jual_2'          => $product->harga_jual_2,
                        'qty_min_gol_2'         => $product->qty_min_gol_2,
                        'margin_gol_3'          => $product->margin_gol_3,
                        'harga_jual_3'          => $product->harga_jual_3,
                        'qty_min_gol_3'         => $product->qty_min_gol_3,
                        'quantity_on_hand'      => 0,
                        'is_active'             => true,
                        'min_qty'               => 3,
                        'max_qty'               => 15,
                        'version'               => 1,
                    ]);
                }

                $before = (float) $stock->quantity_on_hand;

                $stock->log_type           = $effectiveQty >= $before ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
                $stock->reason_code        = 'STOCK_OPNAME';
                $stock->reference_doc_type = 'STOCK_OPNAME';
                $stock->reference_doc_id   = $session->id;
                $stock->notes              = "Hasil Stok Opname {$session->session_number}";
                $stock->recorded_by        = Auth::id();

                $stock->update([
                    'quantity_on_hand' => $effectiveQty,
                    'last_count_date'  => $session->opname_date,
                ]);
            }

            $session->update([
                'status'       => 'COMPLETED',
                'approved_by'  => Auth::id(),
                'completed_at' => now(),
            ]);

            // Assign products to their scanned racks
            $scannedItems = $session->items()->with('rackSession.rack')->get();
            foreach ($scannedItems as $item) {
                if ($item->rackSession && $item->rackSession->rack) {
                    $rack = $item->rackSession->rack;
                    if ($rack->rack_code === 'ALL-RACK') continue;

                    $stock = Stock::where('branch_id', $session->branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();
                        
                    if ($stock) {
                        $stock->racks()->syncWithoutDetaching([$rack->id]);
                    }
                }
            }
            
            // Catat jurnal akuntansi Stok Opname
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordStockOpnameJournal($session);
        });

        Notification::make()
            ->title('Stok opname berhasil diselesaikan! Stok fisik telah diperbarui.')
            ->success()
            ->send();

        $this->redirect(StockOpnameSessionResource::getUrl('view', ['record' => $session]));
    }
}
