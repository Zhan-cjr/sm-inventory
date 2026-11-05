<?php

namespace App\Filament\Resources\StockAudit\Pages;

use App\Filament\Resources\StockAudit\StockAuditInspectionResource;
use App\Models\StockAuditInspection;
use App\Services\StockAuditService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewStockAuditInspection extends ViewRecord
{
    protected static string $resource = StockAuditInspectionResource::class;

    protected string $view = 'filament.pages.view-stock-audit-inspection';

    protected function getHeaderActions(): array
    {
        /** @var StockAuditInspection $record */
        $record = $this->record;

        return [
            Action::make('cetak_bap')
                ->label('Cetak BAP (PDF / Nota)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(route('stock-audits.print-bap', ['record' => $record->id]))
                ->openUrlInNewTab(),

            Action::make('terapkan_koreksi')
                ->label('Terapkan Koreksi Stok')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => in_array($record->status, ['INVESTIGATION_PENDING', 'CLOSED']) && empty($record->adjustment_id) && $record->total_items_discrepancy > 0)
                ->requiresConfirmation()
                ->modalHeading('Terapkan Koreksi Stok Sekarang?')
                ->modalDescription("Stok fisik hasil sidak akan langsung menggantikan stok sistem untuk {$record->total_items_discrepancy} produk yang selisih. Dokumen penyesuaian stok resmi akan otomatis dibuat. Lanjutkan?")
                ->action(function (StockAuditService $service) use ($record) {
                    $service->applyStockAdjustment($record, Auth::user());
                    Notification::make()
                        ->title('Koreksi Stok Berhasil Diterapkan!')
                        ->body("Stok sistem telah disinkronkan dengan hasil audit {$record->audit_number}.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['status', 'adjustment_id']);
                }),

            Action::make('tutup_tanpa_koreksi')
                ->label('Tutup Sidak (Tanpa Koreksi)')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $record->status === 'INVESTIGATION_PENDING')
                ->requiresConfirmation()
                ->modalHeading('Tutup Temuan Sidak?')
                ->modalDescription('Gunakan opsi ini jika barang yang sempat dicurigai hilang sudah berhasil ditemukan di toko/gudang, sehingga stok sistem tidak perlu diubah.')
                ->action(function () use ($record) {
                    $record->update(['status' => 'CLOSED']);
                    app(StockAuditService::class)->syncToStockOpnameSession($record);
                    Notification::make()
                        ->title('Sesi Sidak Ditutup')
                        ->body('Status diubah menjadi DITUTUP tanpa melakukan perubahan stok.')
                        ->info()
                        ->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }

    /**
     * Revisi Qty Fisik / Klarifikasi Hasil Hitung Temuan Sidak
     */
    public function reviseItem(string $itemId, float $newPhysicalQty, ?string $notes = null): void
    {
        /** @var StockAuditInspection $inspection */
        $inspection = $this->record;

        if ($inspection->status === 'ADJUSTED') {
            Notification::make()->title('Gagal: Sidak ini sudah dikoreksi ke stok')->danger()->send();
            return;
        }

        $item = $inspection->items()->where('id', $itemId)->firstOrFail();
        $sysQty = (float) $item->system_quantity;
        $discQty = $newPhysicalQty - $sysQty;
        $unitCost = (float) $item->unit_cost;
        $discCost = $discQty * $unitCost;

        $status = 'MATCH';
        if ($discQty < 0) {
            $status = 'MINUS';
        } elseif ($discQty > 0) {
            $status = 'PLUS';
        }

        $item->update([
            'physical_quantity'    => $newPhysicalQty,
            'discrepancy_quantity' => $discQty,
            'discrepancy_cost'     => $discCost,
            'status'               => $status,
            'clarification_notes'  => $notes ?: $item->clarification_notes,
        ]);

        $inspection->recalculateSummary();
        app(StockAuditService::class)->syncToStockOpnameSession($inspection);

        Notification::make()->title('Hasil hitung fisik berhasil direvisi')->success()->send();
        $this->refreshFormData([
            'total_items_checked',
            'total_items_discrepancy',
            'total_loss_nominal',
            'total_gain_nominal',
            'accuracy_percentage'
        ]);
    }
}

