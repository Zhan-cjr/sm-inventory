<?php

namespace App\Filament\Resources\StockQuarantineAudits;

use App\Filament\Resources\StockQuarantineAudits\Pages\ListStockQuarantineAudits;
use App\Filament\Resources\StockQuarantineAudits\Pages\CreateStockQuarantineAudit;
use App\Models\StockQuarantineAudit;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockQuarantineAuditResource extends Resource
{
    protected static ?string $model = StockQuarantineAudit::class;

    protected static \UnitEnum|string|null $navigationGroup = 'STOK OPNAME';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Audit Karantina BS';
    protected static ?string $modelLabel = 'Audit Karantina BS';
    protected static ?string $pluralModelLabel = 'Audit Karantina BS';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                StockQuarantineAudit::query()
                    ->when(Auth::user()->branch_id !== null, fn ($q) => $q->where('branch_id', Auth::user()->branch_id))
                    ->with(['branch', 'auditor', 'approver'])
                    ->latest('audit_date')
            )
            ->columns([
                TextColumn::make('audit_date')
                    ->label('Tgl Audit')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('audit_number')
                    ->label('No. Audit')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->hidden(fn () => Auth::user()->branch_id !== null)
                    ->sortable(),
                TextColumn::make('total_system_damaged')
                    ->label('Sistem BS')
                    ->numeric(decimalPlaces: 0)
                    ->alignRight()
                    ->color('gray'),
                TextColumn::make('total_actual_damaged')
                    ->label('Fisik BS')
                    ->numeric(decimalPlaces: 0)
                    ->alignRight()
                    ->weight('bold'),
                TextColumn::make('total_discrepancy_qty')
                    ->label('Selisih Fisik')
                    ->alignRight()
                    ->badge()
                    ->formatStateUsing(fn ($state) => ((float)$state > 0 ? '+' : '') . number_format($state, 0))
                    ->color(fn ($state) => (float)$state < 0 ? 'danger' : ((float)$state > 0 ? 'warning' : 'success')),
                TextColumn::make('total_discrepancy_cost')
                    ->label('Valuasi Selisih (HPP)')
                    ->money('IDR', true)
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'COMPLETED' => 'success',
                        'DRAFT' => 'warning',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('auditor.name')
                    ->label('Auditor')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
                SelectFilter::make('status')
                    ->options([
                        'COMPLETED' => 'Selesai',
                        'DRAFT' => 'Draft',
                    ]),
            ])
            ->actions([
                Action::make('print')
                    ->label('Cetak BAP')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (StockQuarantineAudit $record) => route('print.stock-quarantine-audit', ['auditId' => $record->id]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockQuarantineAudits::route('/'),
            'create' => CreateStockQuarantineAudit::route('/create'),
        ];
    }
}

