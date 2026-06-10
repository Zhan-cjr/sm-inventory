<?php

namespace App\Filament\Pages;

use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ExportAction;
use App\Filament\Exports\ShiftExporter;
use Illuminate\Support\Facades\Auth;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ArsipLaporanEOD extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Arsip Laporan EOD';
    protected static ?string $title = 'Arsip Laporan End of Day (EOD)';
    protected static string|\UnitEnum|null $navigationGroup = 'LAPORAN/ARSIP';
    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.report-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shift::query()
                    ->where('status', 'CLOSED')
                    ->when(auth()->user()?->branch_id !== null, fn($q) => $q->where('branch_id', auth()->user()?->branch_id))
                    ->with(['branch', 'user', 'terminal'])
            )
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => auth()->user()?->branch_id !== null),
                TextColumn::make('terminal.name')
                    ->label('Kassa'),
                TextColumn::make('user.name')
                    ->label('Kasir'),
                TextColumn::make('shift_name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'OPEN' ? 'success' : 'gray'),
                TextColumn::make('starting_cash')
                    ->label('Kas Awal')
                    ->money('IDR', true),
                TextColumn::make('total_sales')
                    ->label('Pendapatan')
                    ->money('IDR', true)
                    ->state(fn (Shift $record): float => (float) (
                        $record->transactions()->where('is_voided', false)->sum('final_amount')
                        - $record->transactions()->where('is_voided', false)->get()->sum(function ($tx) {
                            $pointPayment = 0.0;
                            if (!empty($tx->payment_details)) {
                                $details = $tx->payment_details;
                                if (is_string($details)) $details = json_decode($details, true);
                                if (is_array($details)) {
                                    $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                                }
                            } elseif (strtoupper($tx->payment_method) === 'POINT') {
                                $pointPayment = (float) $tx->final_amount;
                            }
                            return $pointPayment;
                        })
                    )),
                TextColumn::make('expected_ending_cash')
                    ->label('Kas Harapan')
                    ->money('IDR', true)
                    ->state(fn (Shift $record): float => 
                        (float) ($record->starting_cash ?? 0) 
                        + (float) ($record->total_cash_sales ?? 0) 
                        - (float) ($record->total_cash_returns ?? 0) 
                        + (float) ($record->total_cash_in ?? 0) 
                        - (float) ($record->total_cash_out ?? 0)
                    ),
                TextColumn::make('actual_cash')
                    ->label('Kas Aktual')
                    ->money('IDR', true),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->money('IDR', true)
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
            ])
            ->filters([
                \App\Filament\Filters\DateFilterHelper::make('start_time', 'start_time'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => auth()->user()?->branch_id !== null),
                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                \Filament\Actions\Action::make('previewEod')
                    ->label('Lihat Struk')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->modalContent(fn (Shift $record) => view('print.eod-receipt-modal', [
                        'shift' => $this->prepareShiftData($record)
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraModalFooterActions([
                        \Filament\Actions\Action::make('print_thermal')
                            ->label('Cetak Struk')
                            ->color('success')
                            ->icon('heroicon-o-printer')
                            ->url(fn (Shift $record) => route('print.eod', ['shift' => $record->id]))
                            ->openUrlInNewTab()
                    ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ShiftExporter::class)
                    ->label('Export CSV')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
            ]);
    }

    public function prepareShiftData(Shift $shift): Shift
    {
        $shift->load(['user', 'terminal', 'cashMovements', 'branch.organization']);

        $expectedCash = $shift->starting_cash + $shift->total_cash_sales - $shift->total_cash_returns + $shift->total_cash_in - $shift->total_cash_out;
        $shift->expected_cash = $expectedCash;

        $cardSalesByBank = DB::table('transactions')
            ->join('banks', 'transactions.bank_id', '=', 'banks.id')
            ->where('transactions.shift_id', $shift->id)
            ->where('transactions.payment_method', 'CARD')
            ->where('transactions.is_voided', false)
            ->select('banks.name', DB::raw('SUM(transactions.final_amount) as total_amount'))
            ->groupBy('banks.id', 'banks.name')
            ->get();

        $returns = Transaction::with(['items.product'])
            ->where('shift_id', $shift->id)
            ->where('is_voided', false)
            ->where('final_amount', '<', 0)
            ->get();
        
        $returnItems = [];
        foreach ($returns as $tx) {
            foreach ($tx->items as $item) {
                if ($item->quantity < 0) {
                    $returnItems[] = [
                        'product_name' => $item->product ? $item->product->name : 'Unknown Item',
                        'quantity' => abs($item->quantity),
                        'total' => abs($item->quantity * $item->unit_price)
                    ];
                }
            }
        }

        $shiftTransactions = Transaction::where('shift_id', $shift->id)
            ->where('is_voided', false)
            ->get();
            
        $totalManual = $shiftTransactions->sum('manual_discount');
        $totalPromo = $shiftTransactions->sum('promo_discount');
        
        $totalPointDeduction = 0;
        foreach ($shiftTransactions as $tx) {
            $details = $tx->payment_details;
            if (is_string($details)) $details = json_decode($details, true);
            if (is_array($details)) {
                $totalPointDeduction += collect($details)->where('method', 'POINT')->sum('amount');
            } elseif (strtoupper($tx->payment_method) === 'POINT') {
                $totalPointDeduction += $tx->final_amount;
            }
        }

        $discountDetails = [
            'manual_discount' => $totalManual,
            'promo_discount' => $totalPromo,
            'point_deduction' => $totalPointDeduction
        ];

        $shift->card_sales_by_bank = $cardSalesByBank;
        $shift->returns_detail = $returnItems;
        $shift->discount_details = $discountDetails;

        return $shift;
    }
}






