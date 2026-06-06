<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
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

            // Mulai Pengecekan: COUNTING → CHECKING (setelah semua rak count1 selesai)
            Action::make('mulai_pengecekan')
                ->label('Mulai Pengecekan ke-2')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->visible(fn () => $record->status === 'COUNTING')
                ->requiresConfirmation()
                ->modalHeading('Mulai Pengecekan ke-2?')
                ->modalDescription('Pengecek ke-2 dapat mengakses rak yang sudah dihitung. Pastikan semua rak sudah dihitung oleh penghitung 1.')
                ->action(function () use ($record) {
                    $pendingRacks = $record->rackSessions()->where('count1_status', 'PENDING')->count();
                    if ($pendingRacks > 0) {
                        Notification::make()
                            ->title("Masih ada {$pendingRacks} rak yang belum dihitung penghitung 1!")
                            ->warning()->send();
                        return;
                    }
                    $record->update(['status' => 'CHECKING']);
                    Notification::make()->title('Sesi masuk tahap Pengecekan ke-2.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Final Check: CHECKING → FINAL_CHECK (jika ada item DISCREPANCY)
            Action::make('final_check')
                ->label('Final Check (SPV)')
                ->icon('heroicon-o-shield-check')
                ->color('danger')
                ->visible(fn () => $record->status === 'CHECKING')
                ->requiresConfirmation()
                ->modalHeading('Masuk ke tahap Final Check?')
                ->modalDescription('Item yang selisih antara penghitung 1 dan 2 akan ditampilkan untuk dicek SPV.')
                ->action(function () use ($record) {
                    $pendingRacks = $record->rackSessions()->where('count2_status', 'PENDING')->count();
                    if ($pendingRacks > 0) {
                        Notification::make()
                            ->title("Masih ada {$pendingRacks} rak yang belum dicek pengecek ke-2!")
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

            // Simpan & Selesaikan (dari FINAL_CHECK setelah SPV selesai)
            Action::make('selesaikan')
                ->label('Simpan & Selesaikan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $record->status === 'FINAL_CHECK')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stok Opname?')
                ->modalDescription('Stok akan diupdate sesuai hasil opname final. Aksi ini tidak dapat dibatalkan!')
                ->action(function () use ($record) {
                    $pendingFinal = $record->items()->where('status', 'DISCREPANCY')->count();
                    if ($pendingFinal > 0) {
                        Notification::make()
                            ->title("Masih ada {$pendingFinal} item selisih yang belum di-final-check!")
                            ->danger()->send();
                        return;
                    }
                    $this->finalize($record);
                    Notification::make()->title('Stok opname selesai! Stok telah diperbarui.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Tombol ke halaman final check
            Action::make('buka_final_check')
                ->label('Buka Final Check')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('danger')
                ->visible(fn () => $record->status === 'FINAL_CHECK')
                ->url(fn () => StockOpnameSessionResource::getUrl('final-check', ['record' => $record])),
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
                $totalFinal   = $summary['total_final'];
                $effectiveQty = ($totalFinal > 0) ? $totalFinal : $summary['total_count2'];
                if (!$effectiveQty) continue;

                $stock = Stock::where('branch_id', $session->branch_id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$stock) continue;

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
            
            // Catat jurnal akuntansi Stok Opname
            $accountingService = new \App\Services\AccountingService();
            $accountingService->recordStockOpnameJournal($session);
        });
    }
}
