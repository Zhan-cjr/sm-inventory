<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Supplier;
use App\Services\SupplierServiceLevelService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanServiceLevelSupplier extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Kinerja Supplier & Rekap PO';
    protected static ?string $title = 'Laporan Kinerja Supplier & Rekonsiliasi PO';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'service-level-supplier';

    protected string $view = 'filament.pages.laporan-service-level-supplier';

    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $selected_branch_id = 'ALL';
    public string $selected_supplier_id = 'ALL';
    public string $status_filter = 'ALL';
    public string $grade_filter = 'ALL';
    public string $active_tab = 'scorecard';
    public string $search = '';
    public ?string $selected_po_id = null;

    // Tab 1 Pagination Properties
    public int $scorecard_page = 1;
    public int $scorecard_per_page = 15;

    // Tab 2 Pagination Properties
    public int $recon_page = 1;
    public int $recon_per_page = 15;

    // Tab 3 Triage, Sort & Pagination Properties
    public string $discrepancy_type = 'ALL'; // ALL, PRICE_DIFF, REJECTED, SHORT_QTY
    public string $discrepancy_sort = 'impact_desc'; // impact_desc, qty_desc, date_desc
    public int $discrepancy_page = 1;
    public int $discrepancy_per_page = 15;

    protected $queryString = [
        'start_date' => ['except' => ''],
        'end_date' => ['except' => ''],
        'selected_branch_id' => ['except' => 'ALL'],
        'selected_supplier_id' => ['except' => 'ALL'],
        'active_tab' => ['except' => 'scorecard'],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->branch_id !== null) {
            $this->selected_branch_id = (string) $user->branch_id;
        }

        if (!$this->start_date) {
            $this->start_date = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        }
        if (!$this->end_date) {
            $this->end_date = Carbon::now()->format('Y-m-d');
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole(['superadmin', 'super_admin', 'super-admin', 'admin', 'owner', 'manager', 'purchasing', 'buyer', 'spv', 'supervisor']);
    }

    protected function getService(): SupplierServiceLevelService
    {
        return app(SupplierServiceLevelService::class);
    }

    protected function getFilterPayload(): array
    {
        return [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'branch_id' => $this->selected_branch_id,
            'supplier_id' => $this->selected_supplier_id,
            'status' => $this->status_filter,
            'grade' => $this->grade_filter,
            'search' => $this->search,
            'scorecard_page' => $this->scorecard_page,
            'scorecard_per_page' => $this->scorecard_per_page,
            'recon_page' => $this->recon_page,
            'recon_per_page' => $this->recon_per_page,
            'discrepancy_type' => $this->discrepancy_type,
            'discrepancy_sort' => $this->discrepancy_sort,
            'discrepancy_page' => $this->discrepancy_page,
            'discrepancy_per_page' => $this->discrepancy_per_page,
        ];
    }

    public function setTab(string $tab): void
    {
        $this->active_tab = $tab;
    }

    public function selectSupplier(string $supplierId): void
    {
        $this->selected_supplier_id = $supplierId;
        $this->recon_page = 1;
        $this->discrepancy_page = 1;
        $this->active_tab = 'reconciliation';
    }

    public function openPoDetail(string $poId): void
    {
        $this->selected_po_id = $poId;
    }

    public function closePoDetail(): void
    {
        $this->selected_po_id = null;
    }

    public function resetFilter(): void
    {
        $user = Auth::user();
        $this->selected_branch_id = ($user && $user->branch_id !== null) ? (string) $user->branch_id : 'ALL';
        $this->selected_supplier_id = 'ALL';
        $this->status_filter = 'ALL';
        $this->grade_filter = 'ALL';
        $this->search = '';
        $this->scorecard_page = 1;
        $this->recon_page = 1;
        $this->discrepancy_type = 'ALL';
        $this->discrepancy_sort = 'impact_desc';
        $this->discrepancy_page = 1;
        $this->start_date = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->format('Y-m-d');
    }

    // Tab 1 Pagination Actions
    public function setScorecardPage(int $page): void
    {
        $this->scorecard_page = max(1, $page);
    }

    public function nextScorecardPage(): void
    {
        $this->scorecard_page++;
    }

    public function prevScorecardPage(): void
    {
        if ($this->scorecard_page > 1) {
            $this->scorecard_page--;
        }
    }

    // Tab 2 Pagination Actions
    public function setReconPage(int $page): void
    {
        $this->recon_page = max(1, $page);
    }

    public function nextReconPage(): void
    {
        $this->recon_page++;
    }

    public function prevReconPage(): void
    {
        if ($this->recon_page > 1) {
            $this->recon_page--;
        }
    }

    // Tab 3 Triage & Pagination Actions
    public function setDiscrepancyType(string $type): void
    {
        $this->discrepancy_type = $type;
        $this->discrepancy_page = 1;
    }

    public function setDiscrepancySort(string $sort): void
    {
        $this->discrepancy_sort = $sort;
        $this->discrepancy_page = 1;
    }

    public function setDiscrepancyPage(int $page): void
    {
        $this->discrepancy_page = max(1, $page);
    }

    public function nextDiscrepancyPage(): void
    {
        $this->discrepancy_page++;
    }

    public function prevDiscrepancyPage(): void
    {
        if ($this->discrepancy_page > 1) {
            $this->discrepancy_page--;
        }
    }

    public function closePo(string $poId): void
    {
        $success = $this->getService()->closePo($poId);
        if ($success) {
            Notification::make()
                ->title('Sisa PO Berhasil Ditutup')
                ->body('Status PO telah diubah menjadi Closed dan sisa kuantitas tidak lagi dihitung sebagai tunggakan aktif.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Menutup PO')
                ->danger()
                ->send();
        }
    }

    public function closeAllExpiredPos(): void
    {
        $count = $this->getService()->closeAllExpiredPos();
        Notification::make()
            ->title("{$count} PO Expired Berhasil Ditutup")
            ->body('Semua PO yang melewati batas waktu expired telah di-close dan sisa tunggakan berhasil dihanguskan.')
            ->success()
            ->send();
    }

    public function getKpiSummaryProperty(): array
    {
        return $this->getService()->getKpiSummary($this->getFilterPayload());
    }

    public function getSupplierScorecardsProperty(): array
    {
        return $this->getService()->getSupplierScorecards($this->getFilterPayload());
    }

    public function getPoReconciliationsProperty(): array
    {
        return $this->getService()->getPoReconciliations($this->getFilterPayload());
    }

    public function getItemDiscrepanciesProperty(): array
    {
        return $this->getService()->getItemDiscrepancies($this->getFilterPayload());
    }

    public function getSelectedPoDetailProperty(): ?array
    {
        if (!$this->selected_po_id) {
            return null;
        }

        return $this->getService()->getPoDetail($this->selected_po_id);
    }

    public function exportCsv(): StreamedResponse
    {
        return $this->getService()->exportCsv($this->active_tab, $this->getFilterPayload());
    }
}
