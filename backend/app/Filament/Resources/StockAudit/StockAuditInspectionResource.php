<?php

namespace App\Filament\Resources\StockAudit;

use App\Filament\Resources\StockAudit\Pages;
use App\Models\StockAuditInspection;
use App\Services\StockAuditService;
use App\Traits\HasBranchScope;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockAuditInspectionResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = StockAuditInspection::class;

    protected static \UnitEnum|string|null $navigationGroup = 'STOK OPNAME';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Sidak Audit Stok';
    protected static ?string $modelLabel = 'Sidak Audit Stok';
    protected static ?string $pluralModelLabel = 'Riwayat Sidak Audit Stok';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasCustomAuthorization('ACCESS_STOCK_AUDIT');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('audit_number')
                    ->label('No. Sidak')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('auditor.name')
                    ->label('Pemeriksa / Auditor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inspection_date')
                    ->label('Tgl Sidak')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('inspection_mode')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'FREE_SCAN' => 'Scan Bebas',
                        'RANDOM_LOTTERY' => 'Undian Acak',
                        'RISK_BASED' => 'Risiko Tinggi',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'FREE_SCAN' => 'info',
                        'RANDOM_LOTTERY' => 'warning',
                        'RISK_BASED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_items_checked')
                    ->label('Item Dicek')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_items_discrepancy')
                    ->label('Item Selisih')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('total_loss_nominal')
                    ->label('Kerugian (Minus)')
                    ->money('IDR', locale: 'id')
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'DRAFT' => 'Sedang Berjalan',
                        'INVESTIGATION_PENDING' => 'Menunggu Investigasi',
                        'ADJUSTED' => 'Stok Disesuaikan',
                        'CLOSED' => 'Ditutup',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'DRAFT' => 'warning',
                        'INVESTIGATION_PENDING' => 'info',
                        'ADJUSTED' => 'success',
                        'CLOSED' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->visible(fn () => Auth::user()?->branch_id === null),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Sedang Berjalan',
                        'INVESTIGATION_PENDING' => 'Menunggu Investigasi',
                        'ADJUSTED' => 'Stok Disesuaikan',
                        'CLOSED' => 'Ditutup',
                    ]),

                SelectFilter::make('inspection_mode')
                    ->label('Mode')
                    ->options([
                        'FREE_SCAN' => 'Scan Bebas',
                        'RANDOM_LOTTERY' => 'Undian Acak',
                        'RISK_BASED' => 'Risiko Tinggi',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('Lihat'),

                Action::make('cetak_bap')
                    ->label('Cetak BAP')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (StockAuditInspection $record) => route('stock-audits.print-bap', ['record' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('terapkan_koreksi')
                    ->label('Terapkan Koreksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (StockAuditInspection $record) => in_array($record->status, ['INVESTIGATION_PENDING', 'CLOSED']) && empty($record->adjustment_id) && $record->total_items_discrepancy > 0)
                    ->requiresConfirmation()
                    ->modalHeading('Terapkan Koreksi Stok Sekarang?')
                    ->modalDescription('Stok di sistem akan langsung disesuaikan dengan hasil fisik sidak audit ini. Lanjutkan?')
                    ->action(function (StockAuditInspection $record, StockAuditService $service) {
                        $service->applyStockAdjustment($record, Auth::user());
                        Notification::make()
                            ->title('Koreksi Stok Berhasil Diterapkan!')
                            ->body("Stok sistem telah disinkronkan dengan hasil audit {$record->audit_number}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAuditInspections::route('/'),
            'view'  => Pages\ViewStockAuditInspection::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['branch', 'auditor', 'adjustment']);
        $user  = Auth::user();
        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        return $query;
    }
}

