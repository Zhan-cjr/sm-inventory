<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpnameSession;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewStockOpnameSession extends ViewRecord
{
    protected static string $resource = StockOpnameSessionResource::class;

    protected string $view = 'filament.pages.view-stock-opname-session';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            // Mulai Sesi: DRAFT → COUNTING
            Action::make('mulai_sesi')
                ->label('Mulai Sesi')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $record->status === 'DRAFT')
                ->requiresConfirmation()
                ->modalHeading('Mulai Sesi Opname?')
                ->modalDescription('Setelah dimulai, penghitung dapat mengakses rak via QR code. Lanjutkan?')
                ->action(function () use ($record) {
                    $record->update(['status' => 'COUNTING']);
                    Notification::make()->title('Sesi dimulai! QR code sudah aktif.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Final Check: COUNTING/CHECKING → FINAL_CHECK (jika ada item DISCREPANCY)
            Action::make('final_check')
                ->label('Final Check / Verifikasi Selisih')
                ->icon('heroicon-o-shield-check')
                ->color('danger')
                ->visible(fn () => in_array($record->status, ['COUNTING', 'CHECKING']))
                ->requiresConfirmation()
                ->modalHeading('Masuk ke tahap Final Check?')
                ->modalDescription('Item yang selisih antara penghitung 1 dan 2 akan diverifikasi pada tahap ini.')
                ->action(function () use ($record) {
                    $pendingCount1 = $record->rackSessions()->where('count1_status', 'PENDING')->count();
                    $pendingCount2 = $record->rackSessions()->where('count2_status', 'PENDING')->count();
                    if ($pendingCount1 > 0 || $pendingCount2 > 0) {
                        Notification::make()
                            ->title("Masih ada {$pendingCount1} rak belum dihitung (P1) atau {$pendingCount2} rak belum dicek (P2)!")
                            ->warning()->send();
                        return;
                    }

                    $discrepancies = $record->items()->where('status', 'DISCREPANCY')->count();
                    if ($discrepancies === 0) {
                        // Tidak ada selisih → langsung ke selesai
                        $this->finalize($record);
                        Notification::make()->title('Tidak ada selisih! Sesi langsung diselesaikan.')->success()->send();
                    } else {
                        $record->update(['status' => 'FINAL_CHECK']);
                        Notification::make()
                            ->title("Ditemukan {$discrepancies} item selisih. Silakan lakukan Final Check.")
                            ->warning()->send();
                    }
                    $this->refreshFormData(['status']);
                }),

            // Simpan & Selesaikan
            Action::make('selesaikan')
                ->label('Simpan & Selesaikan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($record->status, ['COUNTING', 'CHECKING', 'FINAL_CHECK']))
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stok Opname?')
                ->modalDescription('Stok akan diupdate sesuai hasil opname final. Aksi ini tidak dapat dibatalkan!')
                ->action(function () use ($record) {
                    $pendingCount1 = $record->rackSessions()->where('count1_status', 'PENDING')->count();
                    $pendingCount2 = $record->rackSessions()->where('count2_status', 'PENDING')->count();
                    if ($pendingCount1 > 0 || $pendingCount2 > 0) {
                        Notification::make()
                            ->title("Masih ada rak yang belum selesai dihitung!")
                            ->danger()->send();
                        return;
                    }

                    $pendingFinal = $record->items()->where('status', 'DISCREPANCY')->count();
                    if ($pendingFinal > 0) {
                        Notification::make()
                            ->title("Masih ada {$pendingFinal} item selisih yang belum diverifikasi final!")
                            ->danger()->send();
                        return;
                    }
                    $this->finalize($record);
                    Notification::make()->title('Stok opname selesai! Stok telah diperbarui.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Tombol modal scan QR Final Check
            Action::make('qr_final_check')
                ->label('Scan QR Final Check')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->visible(fn () => $record->status === 'FINAL_CHECK')
                ->modalHeading('QR Code Portal Pengecek Final')
                ->modalContent(fn () => view('filament.modals.qr-final-check', ['record' => $record]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            // Tombol ke halaman monitoring & QR final check
            Action::make('buka_final_check')
                ->label('Buka Final Check & Monitoring')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('danger')
                ->visible(fn () => $record->status === 'FINAL_CHECK')
                ->url(fn () => StockOpnameSessionResource::getUrl('final-check', ['record' => $record])),

            \Filament\Actions\DeleteAction::make()
                ->visible(fn () => $record->status !== 'COMPLETED'),
        ];
    }

    /**
     * Finalisasi sesi: update stok via StockObserver (single log entry)
     * Observer StockObserver sudah otomatis membuat InventoryLog saat quantity_on_hand berubah.
     * Cukup set properti kontekstual pada model Stock sebelum update() agar log tercatat benar.
     */
    protected function finalize(StockOpnameSession $session): void
    {
        DB::transaction(function () use ($session) {
            $productSummary = $session->getProductSummary();

            foreach ($productSummary as $summary) {
                $productId = $summary['product_id'];
                if (!$productId) continue;

                // Tentukan qty final yang dipakai
                $effectiveQty = isset($summary['effective_qty'])
                    ? (float) $summary['effective_qty']
                    : (($summary['total_final'] > 0) ? (float) $summary['total_final'] : (float) $summary['total_count2']);

                $stock = Stock::where('branch_id', $session->branch_id)
                    ->where('product_id', $productId)
                    ->first();

                // Jika barang terdaftar di master global tapi belum di stok cabang, otomatis daftarkan ke cabang
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

                // Set properti kontekstual — akan dibaca StockObserver untuk mengisi log yang benar.
                // Dengan cara ini hanya ada SATU entry di kartu stok (dari observer), bukan dua.
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
                    // Skip virtual rack if any
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
    }
}
