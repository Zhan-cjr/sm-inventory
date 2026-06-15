<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\AccountingService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class PerbaikanNeraca extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Diagnosa & Perbaikan Neraca';
    protected static ?string $title = 'Diagnosa Neraca Saldo';
    protected static string|\UnitEnum|null $navigationGroup = 'AKUNTANSI';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.perbaikan-neraca';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                JournalEntry::query()
                    ->select('journal_entries.*')
                    ->join('journal_entry_lines', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->groupBy('journal_entries.id')
                    ->havingRaw('ABS(SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit)) > 0.01')
                    ->with('journalable')
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('selisih')
                    ->label('Status (Selisih)')
                    ->getStateUsing(function (JournalEntry $record) {
                        $debit = $record->lines->sum('debit');
                        $credit = $record->lines->sum('credit');
                        $diff = abs($debit - $credit);
                        return 'Selisih: Rp ' . number_format($diff, 2, ',', '.');
                    })
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('journalable_type')
                    ->label('Sumber')
                    ->getStateUsing(function (JournalEntry $record) {
                        if (!$record->journalable_type) return 'Manual / Unknown';
                        return class_basename($record->journalable_type);
                    }),
            ])
            ->actions([
                Action::make('perbaiki')
                    ->label('Proses Perbaikan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Perbaiki Jurnal Tidak Seimbang')
                    ->modalDescription('Tindakan ini akan menghapus jurnal yang rusak ini dan mencoba merancang ulang jurnal secara otomatis berdasarkan dokumen sumbernya. Lanjutkan?')
                    ->action(function (JournalEntry $record) {
                        $this->prosesPerbaikan($record);
                    })
                    ->visible(fn (JournalEntry $record) => $record->journalable_type !== null),
            ])
            ->emptyStateHeading('Neraca Saldo Seimbang')
            ->emptyStateDescription('Tidak ditemukan adanya selisih debit dan kredit pada seluruh jurnal.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public function prosesPerbaikan(JournalEntry $journal)
    {
        if (!$journal->journalable_type || !$journal->journalable) {
            Notification::make()
                ->title('Gagal Memperbaiki')
                ->body('Jurnal ini tidak memiliki dokumen sumber (Dibuat manual). Silakan perbaiki melalui menu Jurnal secara manual.')
                ->danger()
                ->send();
            return;
        }

        $type = $journal->journalable_type;
        $model = $journal->journalable;

        DB::beginTransaction();
        try {
            // 1. Hapus line dan jurnal lama
            $journal->lines()->delete();
            $journal->delete();

            // 2. Buat ulang jurnal
            $accountingService = new AccountingService();
            $success = false;

            if ($type === \App\Models\Transaction::class) {
                $success = $accountingService->recordTransactionJournal($model);
            } elseif ($type === \App\Models\GoodsReceipt::class) {
                $success = $accountingService->recordGoodsReceiptJournal($model);
            } elseif ($type === \App\Models\PurchaseReturn::class) {
                $success = $accountingService->recordPurchaseReturnJournal($model);
            } elseif ($type === \App\Models\EcommerceOrder::class) {
                $success = $accountingService->recordEcommerceOrderJournal($model);
            } elseif ($type === \App\Models\StockAdjustment::class) {
                $success = $accountingService->recordStockAdjustmentJournal($model);
            } elseif ($type === \App\Models\StockOpnameSession::class) {
                $success = $accountingService->recordStockOpnameJournal($model);
            } elseif ($type === \App\Models\StockTransfer::class) {
                $success = $accountingService->recordStockTransferJournal($model);
            } elseif ($type === \App\Models\Expense::class) {
                $success = $accountingService->recordExpenseJournal($model);
            }

            if ($success) {
                DB::commit();
                Notification::make()
                    ->title('Perbaikan Berhasil')
                    ->body('Jurnal telah berhasil dikalkulasi ulang dan diseimbangkan.')
                    ->success()
                    ->send();
            } else {
                DB::rollBack();
                Notification::make()
                    ->title('Perbaikan Gagal')
                    ->body('Gagal membuat ulang jurnal. Dokumen sumber mungkin tidak valid.')
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
