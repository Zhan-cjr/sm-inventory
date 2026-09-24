<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\SuggestedStockTransferService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SuggestedStockTransfers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Saran Mutasi Stok AI';
    protected static ?string $title = 'Saran Mutasi Stok AI (Inter-Branch Rebalancing)';
    protected static string|\UnitEnum|null $navigationGroup = 'ANALISA AI';
    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.suggested-stock-transfers';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getSubheading(): ?string
    {
        return 'Analisa cerdas disparitas laju penjualan (ADS) & sisa hari stok (DOH) untuk meredistribusi stok mati antar-cabang secara aman (P2P Store-to-Store).';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('faq')
                ->label('Cara Membaca Saran AI')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Mutasi Stok Antar Cabang Cerdas')
                ->modalContent(view('filament.components.saran-mutasi-faq'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SuggestedStockTransferStats::class,
        ];
    }

    public function getFleetSummaryData(): array
    {
        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
        $toBranchId = data_get($this->tableFilters, 'to_branch_id.value') ?? (auth()->user()?->branch_id ?? null);

        $candidateStockIds = app(SuggestedStockTransferService::class)->getTransferCandidateStockIds($fromBranchId, $toBranchId);
        if (empty($candidateStockIds)) {
            return [
                'total_items' => 0,
                'total_units' => 0,
                'total_capital_freed' => 0,
                'total_routes' => 0,
                'routes' => [],
            ];
        }

        $records = Stock::whereIn('id', $candidateStockIds)->with(['product', 'branch'])->get();
        return app(SuggestedStockTransferService::class)->getFleetSummary($records, $fromBranchId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->where('is_active', true)
                    ->whereHas('product', fn($q) => $q->where('is_active', true))
                    ->with(['product', 'branch'])
            )
            ->groups([
                Group::make('branch.name')
                    ->label('Cabang Tujuan (Membutuhkan)')
                    ->collapsible(),
            ])
            ->defaultGroup('branch.name')
            ->columns([
                ImageColumn::make('product.image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold'),
                TextColumn::make('branch.name')
                    ->label('Cabang Tujuan (Membutuhkan)')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('destination_info')
                    ->label('Stok Tujuan (Laju/Hari)')
                    ->state(function ($record) {
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record);
                        if (!$calc) return '-';
                        $qoh = number_format($calc['destination_qoh'], 0, ',', '.');
                        return "{$qoh} {$calc['unit']} (Laku ~{$calc['destination_ads']}/hari)";
                    })
                    ->description(fn($record) => app(SuggestedStockTransferService::class)->calculateForStock($record)['destination_status'] ?? null),
                TextColumn::make('source_branch_name')
                    ->label('Cabang Pengirim (Donor)')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return $calc['source_branch_name'] ?? '-';
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('source_info')
                    ->label('Stok Pengirim (Laju/Hari)')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        if (!$calc) return '-';
                        $qoh = number_format($calc['source_qoh'], 0, ',', '.');
                        return "{$qoh} {$calc['unit']} (Laku ~{$calc['source_ads']}/hari)";
                    })
                    ->description(fn($record) => app(SuggestedStockTransferService::class)->calculateForStock($record, data_get($this->tableFilters, 'from_branch_id.value'))['source_status'] ?? null),
                TextColumn::make('doh_comparison')
                    ->label('Analisa DOH (Hari)')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        if (!$calc) return '-';
                        return "Asal: {$calc['source_doh']} hr ➔ Tujuan: {$calc['destination_doh']} hr";
                    })
                    ->color('warning'),
                TextColumn::make('suggested_qty')
                    ->label('Saran Mutasi')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return $calc ? number_format($calc['suggested_qty'], 0, ',', '.') . " {$calc['unit']}" : '-';
                    })
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('remaining_safe')
                    ->label('Sisa Pengirim (Aman)')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return $calc ? number_format($calc['remaining_source_qoh'], 0, ',', '.') . " {$calc['unit']} (Aman)" : '-';
                    })
                    ->color('success'),
                TextColumn::make('capital_freed')
                    ->label('Modal Diselamatkan')
                    ->state(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return $calc ? 'Rp ' . number_format($calc['capital_freed'], 0, ',', '.') : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('peluang_mutasi')
                    ->label('Hanya Rekomendasi Siap Kirim')
                    ->toggle()
                    ->default(true)
                    ->query(function (Builder $query) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $toBranchId = data_get($this->tableFilters, 'to_branch_id.value') ?? (auth()->user()?->branch_id ?? null);
                        $matchedIds = app(SuggestedStockTransferService::class)->getTransferCandidateStockIds($fromBranchId, $toBranchId);
                        return $query->whereIn('stocks.id', $matchedIds);
                    }),
                SelectFilter::make('to_branch_id')
                    ->label('Cabang Tujuan (Membutuhkan)')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $branchId = $data['value'] ?? null;
                        if (!$branchId) return $query;
                        return $query->where('stocks.branch_id', $branchId);
                    })
                    ->hidden(fn () => auth()->user()?->branch_id !== null),
                SelectFilter::make('from_branch_id')
                    ->label('Cabang Pengirim (Donor/Surplus)')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id')),
                SelectFilter::make('supplier_id')
                    ->label('Pemasok')
                    ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $supplierId = $data['value'] ?? null;
                        if (!$supplierId) return $query;
                        return $query->where(function ($q) use ($supplierId) {
                            $q->where('stocks.supplier_id', $supplierId)
                              ->orWhere(function ($sq) use ($supplierId) {
                                  $sq->whereNull('stocks.supplier_id')
                                     ->whereHas('product', fn ($pq) => $pq->where('supplier_id', $supplierId));
                              });
                        });
                    }),
            ])
            ->recordActions([
                Action::make('kirim_sekarang')
                    ->label('Kirim & Cetak Nota')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembuatan Surat Jalan Transfer')
                    ->modalDescription(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        if (!$calc) return 'Buat dokumen transfer untuk produk ini?';
                        return "Barang ini akan ditransfer dari {$calc['source_branch_name']} ke {$calc['destination_branch_name']} sebanyak {$calc['suggested_qty']} {$calc['unit']}. Surat jalan transfer otomatis terbit dan siap cetak.";
                    })
                    ->modalSubmitActionLabel('Kirim & Cetak Surat Jalan')
                    ->visible(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return !empty($calc) && $calc['suggested_qty'] > 0;
                    })
                    ->action(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        if (!$calc || $calc['suggested_qty'] <= 0) {
                            Notification::make()->title('Tidak ada kuantitas mutasi yang valid')->warning()->send();
                            return;
                        }

                        $service = app(SuggestedStockTransferService::class);
                        $transfer = $service->executeTransfer(
                            $calc['source_branch_id'],
                            $calc['destination_branch_id'],
                            [
                                [
                                    'product_id' => $calc['product_id'],
                                    'quantity' => $calc['suggested_qty'],
                                    'notes' => 'Rekomendasi Mutasi Cerdas On-Demand',
                                ]
                            ],
                            auth()->id() ?? 1,
                            "Mutasi On-Demand {$calc['product_name']} ({$calc['source_branch_name']} ➔ {$calc['destination_branch_name']})"
                        );

                        Notification::make()
                            ->title("Dokumen Transfer {$transfer->reference_number} Berhasil Dibuat!")
                            ->success()
                            ->send();

                        return redirect()->to(route('print.document', ['type' => 'transfer', 'ids' => [$transfer->id]]));
                    }),
                Action::make('buka_form')
                    ->label('Buka Form Transfer')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        if (!$calc) return null;
                        return route('filament.admin.resources.stock-transfers.create') . "?product_id={$calc['product_id']}&from_branch_id={$calc['source_branch_id']}&to_branch_id={$calc['destination_branch_id']}&quantity={$calc['suggested_qty']}";
                    })
                    ->openUrlInNewTab()
                    ->visible(function ($record) {
                        $fromBranchId = data_get($this->tableFilters, 'from_branch_id.value');
                        $calc = app(SuggestedStockTransferService::class)->calculateForStock($record, $fromBranchId);
                        return !empty($calc) && $calc['suggested_qty'] > 0;
                    }),
            ])
            ->bulkActions([
                BulkAction::make('kirim_terpilih_kolektif')
                    ->label('Kirim Barang Terpilih & Cetak Nota')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Mutasi Kolektif per Rute Armada')
                    ->modalDescription('Sistem akan mengelompokkan barang yang dicentang berdasarkan Rute (Cabang Asal ➔ Cabang Tujuan) dan menerbitkan 1 Surat Jalan Transfer resmi untuk setiap rute kendaraan.')
                    ->modalSubmitActionLabel('Terbitkan Surat Jalan & Cetak')
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) return;

                        $fromFilter = data_get($this->tableFilters, 'from_branch_id.value');
                        $service = app(SuggestedStockTransferService::class);
                        $groupedByRoute = [];

                        foreach ($records as $record) {
                            $calc = $service->calculateForStock($record, $fromFilter);
                            if ($calc && $calc['suggested_qty'] > 0) {
                                $rKey = "{$calc['source_branch_id']}_to_{$calc['destination_branch_id']}";
                                if (!isset($groupedByRoute[$rKey])) {
                                    $groupedByRoute[$rKey] = [
                                        'from_branch_id' => $calc['source_branch_id'],
                                        'to_branch_id' => $calc['destination_branch_id'],
                                        'from_name' => $calc['source_branch_name'],
                                        'to_name' => $calc['destination_branch_name'],
                                        'items' => [],
                                    ];
                                }

                                $groupedByRoute[$rKey]['items'][] = [
                                    'product_id' => $calc['product_id'],
                                    'quantity' => $calc['suggested_qty'],
                                    'notes' => 'Mutasi Kolektif Armada Keliling',
                                ];
                            }
                        }

                        if (empty($groupedByRoute)) {
                            Notification::make()->title('Tidak ada barang valid untuk dimutasi')->warning()->send();
                            return;
                        }

                        $createdTransfers = [];
                        $userId = auth()->id() ?? 1;

                        foreach ($groupedByRoute as $routeData) {
                            $transfer = $service->executeTransfer(
                                $routeData['from_branch_id'],
                                $routeData['to_branch_id'],
                                $routeData['items'],
                                $userId,
                                "Mutasi Kolektif Armada Rute {$routeData['from_name']} ➔ {$routeData['to_name']} (" . count($routeData['items']) . " Jenis Barang)"
                            );
                            $createdTransfers[] = $transfer->id;
                        }

                        $totalDocs = count($createdTransfers);
                        Notification::make()
                            ->title("{$totalDocs} Surat Jalan Transfer Berhasil Diterbitkan!")
                            ->success()
                            ->send();

                        return redirect()->to(route('print.document', ['type' => 'transfer', 'ids' => $createdTransfers]));
                    }),
            ]);
    }
}
