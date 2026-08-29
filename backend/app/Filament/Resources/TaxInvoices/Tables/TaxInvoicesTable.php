<?php

namespace App\Filament\Resources\TaxInvoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\TaxInvoice;

class TaxInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_faktur')
                    ->label('No. Faktur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Klik untuk melihat rincian barang')
                    ->action(
                        Action::make('modal_view_detail')
                            ->label('Detail Faktur')
                            ->modalHeading(fn (TaxInvoice $record) => "Rincian Faktur Pajak: {$record->nomor_faktur}")
                            ->modalWidth('5xl')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->extraModalFooterActions([
                                Action::make('cetak_dari_modal')
                                    ->label('Cetak Faktur')
                                    ->icon('heroicon-o-printer')
                                    ->color('success')
                                    ->url(fn (TaxInvoice $record) => url("/print/document/tax-invoice?id={$record->id}"))
                                    ->openUrlInNewTab(),
                            ])
                            ->modalContent(fn (TaxInvoice $record) => view('filament.tax-invoices.modal-detail', [
                                'record' => $record->load('items')
                            ]))
                    ),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'masukan' => 'success',
                        'keluaran' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('tanggal_faktur')
                    ->label('Tgl Faktur')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tgl Input')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('masa_pajak')
                    ->label('Masa')
                    ->badge()
                    ->searchable(),
                TextColumn::make('npwp_lawan')
                    ->label('NPWP Lawan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_lawan')
                    ->label('Lawan Transaksi')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('dpp')
                    ->label('DPP')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('ppn')
                    ->label('PPN')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'reported' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'reported' => 'Dilaporkan',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Pajak')
                    ->options([
                        'masukan' => 'Pajak Masukan (Pembelian)',
                        'keluaran' => 'Pajak Keluaran (Penjualan)',
                    ]),
                SelectFilter::make('status')
                    ->label('Status Laporan')
                    ->options([
                        'draft' => 'Draft',
                        'reported' => 'Dilaporkan',
                    ]),
                \Filament\Tables\Filters\Filter::make('filter_tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        \Filament\Forms\Components\Select::make('basis_tanggal')
                            ->label('Filter Berdasarkan')
                            ->options([
                                'created_at' => 'Tanggal Input Sistem (Waktu Admin Scan)',
                                'tanggal_faktur' => 'Tanggal Lembar Faktur Pajak',
                            ])
                            ->default('created_at')
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $basisLabel = ($data['basis_tanggal'] ?? 'created_at') === 'created_at' ? 'Tgl Input' : 'Tgl Faktur';
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = "{$basisLabel} dari " . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = "{$basisLabel} sampai " . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }
                        return $indicators;
                    })
                    ->query(function ($query, array $data) {
                        $col = ($data['basis_tanggal'] ?? 'created_at') === 'tanggal_faktur' ? 'tanggal_faktur' : 'created_at';
                        return $query
                            ->when($data['dari_tanggal'], fn ($q, $date) => $q->whereDate($col, '>=', $date))
                            ->when($data['sampai_tanggal'], fn ($q, $date) => $q->whereDate($col, '<=', $date));
                    }),
            ])
            ->defaultSort('tanggal_faktur', 'desc')
            ->headerActions([
                Action::make('export_excel_rekap')
                    ->label('Ekspor Rekap Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        $filename = 'Rekap_Faktur_Pajak_' . now()->format('Ymd_His') . '.csv';

                        return new StreamedResponse(function () use ($livewire) {
                            $handle = fopen('php://output', 'w');
                            // Add BOM for Excel UTF-8 recognition
                            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                            // Header as requested in Gambar 1
                            fputcsv($handle, [
                                'NOMOR_FAKTUR',
                                'MASA_PAJAK',
                                'TAHUN_PAJAK',
                                'TANGGAL_FAKTUR',
                                'NPWP',
                                'NAMA',
                                'JUMLAH_DPP',
                                'JUMLAH_PPN',
                            ]);

                            // Query respects active table filters and search
                            $exportQuery = clone $livewire->getFilteredTableQuery();
                            $exportQuery->orderBy('tanggal_faktur', 'desc')->chunk(200, function ($invoices) use ($handle) {
                                foreach ($invoices as $inv) {
                                    $parts = explode('-', $inv->masa_pajak ?? '');
                                    $masa = $parts[0] ?? (int) ($inv->tanggal_faktur?->format('m') ?? 1);
                                    $tahun = $parts[1] ?? $inv->tanggal_faktur?->format('Y') ?? now()->format('Y');

                                    fputcsv($handle, [
                                        $inv->nomor_faktur,
                                        (int) $masa,
                                        $tahun,
                                        $inv->tanggal_faktur ? $inv->tanggal_faktur->format('n/j/Y') : '',
                                        $inv->npwp_lawan,
                                        $inv->nama_lawan,
                                        (float) $inv->dpp,
                                        (float) $inv->ppn,
                                    ]);
                                }
                            });

                            fclose($handle);
                        }, 200, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('view_items')
                    ->label('Rincian')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn (TaxInvoice $record) => "Rincian Faktur Pajak: {$record->nomor_faktur}")
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Action::make('cetak_dari_modal_row')
                            ->label('Cetak Faktur')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->url(fn (TaxInvoice $record) => url("/print/document/tax-invoice?id={$record->id}"))
                            ->openUrlInNewTab(),
                    ])
                    ->modalContent(fn (TaxInvoice $record) => view('filament.tax-invoices.modal-detail', [
                        'record' => $record->load('items')
                    ])),
                EditAction::make(),
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (TaxInvoice $record): string => url('/print/document/tax-invoice?id=' . $record->id))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
