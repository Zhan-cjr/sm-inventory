<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\ProductMonthlySalesHistory;
use App\Models\Stock;
use App\Services\DynamicMinMaxService;
use App\Services\SalesHistoryImportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;

class MigrasiHistoriPenjualan extends Page
{
    use HasPageShield, WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Migrasi Histori Penjualan';
    protected static ?string $title = 'Migrasi Histori Penjualan & Min-Max Dinamis';
    protected static \UnitEnum|string|null $navigationGroup = 'PERSEDIAAN';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'migrasi-histori-penjualan';

    protected string $view = 'filament.pages.migrasi-histori-penjualan';

    public string $selected_branch_id = '';
    public $files = [];
    public ?array $preview_data = null;
    public ?array $import_result = null;
    public ?array $recalculate_result = null;
    public bool $auto_recalculate = true;
    public int $eval_months = 12;

    public function mount(): void
    {
        $defaultBranch = auth()->user()->branch_id ?? Branch::first()?->id ?? '';
        $this->selected_branch_id = (string)$defaultBranch;
    }

    public function updatedSelectedBranchId(): void
    {
        $this->preview_data = null;
        $this->import_result = null;
        $this->recalculate_result = null;
    }

    public function updatedFiles(): void
    {
        $this->preview_data = null;
        $this->import_result = null;
    }

    public function runPreview(?SalesHistoryImportService $importService = null): void
    {
        $importService = $importService ?? app(SalesHistoryImportService::class);

        if (empty($this->files)) {
            Notification::make()
                ->title('Pilih File Terlebih Dahulu')
                ->body('Silakan pilih satu atau beberapa file Excel (.xls / .xlsx) laporan penjualan lama.')
                ->warning()
                ->send();
            return;
        }

        if (empty($this->selected_branch_id)) {
            Notification::make()
                ->title('Pilih Cabang')
                ->body('Tentukan cabang target untuk migrasi data.')
                ->warning()
                ->send();
            return;
        }

        try {
            $filePaths = [];
            foreach ($this->files as $file) {
                $filePaths[] = $file->getRealPath();
            }

            $this->preview_data = $importService->preview($filePaths, $this->selected_branch_id);

            Notification::make()
                ->title('Pratinjau Selesai')
                ->body("Terdeteksi {$this->preview_data['total_sheets']} sheet dan {$this->preview_data['matched_records_count']} baris produk cocok.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Membaca File')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runImport(?SalesHistoryImportService $importService = null, ?DynamicMinMaxService $dynamicService = null): void
    {
        $importService = $importService ?? app(SalesHistoryImportService::class);
        $dynamicService = $dynamicService ?? app(DynamicMinMaxService::class);

        if (empty($this->files)) {
            Notification::make()
                ->title('File Belum Dipilih')
                ->warning()
                ->send();
            return;
        }

        try {
            $filePaths = [];
            foreach ($this->files as $file) {
                $filePaths[] = $file->getRealPath();
            }

            $this->import_result = $importService->import($filePaths, $this->selected_branch_id);

            Notification::make()
                ->title('Import Data Berhasil!')
                ->body("Sebanyak " . ($this->import_result['saved_to_database'] ?? 0) . " data histori penjualan berhasil disimpan ke database analitik.")
                ->success()
                ->send();

            // Auto recalculate Min-Max if checked
            if ($this->auto_recalculate) {
                $this->runRecalculate($dynamicService);
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Melakukan Import')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runRecalculate(?DynamicMinMaxService $dynamicService = null): void
    {
        $dynamicService = $dynamicService ?? app(DynamicMinMaxService::class);

        if (empty($this->selected_branch_id)) {
            Notification::make()
                ->title('Pilih Cabang Terlebih Dahulu')
                ->warning()
                ->send();
            return;
        }

        try {
            $this->recalculate_result = $dynamicService->recalculateForBranch(
                $this->selected_branch_id,
                null,
                $this->eval_months
            );

            Notification::make()
                ->title('Kalkulasi Min-Max Dinamis Selesai')
                ->body("{$this->recalculate_result['total_stocks_updated']} stok produk di cabang ini telah diperbarui secara otomatis berbasis statistik.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Kalkulasi Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearFiles(): void
    {
        $this->files = [];
        $this->preview_data = null;
        $this->import_result = null;
        $this->recalculate_result = null;
    }

    public function getBranchStatsProperty(): array
    {
        if (empty($this->selected_branch_id)) {
            return [
                'total_migration_records' => 0,
                'unique_products_migrated' => 0,
                'earliest_live_pos' => '-',
                'total_active_stocks' => 0,
            ];
        }

        $totalRecs = ProductMonthlySalesHistory::where('branch_id', $this->selected_branch_id)->count();
        $uniqueProds = ProductMonthlySalesHistory::where('branch_id', $this->selected_branch_id)->distinct('product_id')->count('product_id');

        $earliestLive = DB::table('transactions')
            ->where('branch_id', $this->selected_branch_id)
            ->where(function ($q) {
                $q->where('is_voided', 0)->orWhereNull('is_voided');
            })
            ->min('created_at');

        $activeStocks = Stock::where('branch_id', $this->selected_branch_id)->where('is_active', true)->count();

        return [
            'total_migration_records' => $totalRecs,
            'unique_products_migrated' => $uniqueProds,
            'earliest_live_pos' => $earliestLive ? \Carbon\Carbon::parse($earliestLive)->format('F Y') : 'Belum Ada Transaksi Riil',
            'total_active_stocks' => $activeStocks,
        ];
    }
}
