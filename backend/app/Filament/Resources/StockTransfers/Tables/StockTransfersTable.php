<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use App\Services\StockTransferService;
use Filament\Tables\Table;
use App\Filament\Filters\DateFilterHelper;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('fromBranch.name')->label('Dari Cabang'),
                TextColumn::make('toBranch.name')->label('Ke Cabang'),
                TextColumn::make('transfer_date')->date(),
                TextColumn::make('total_amount')
                    ->label('Nominal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_transit',
                        'success' => 'received',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                DateFilterHelper::make('transfer_date'),
                Filter::make('branch')
                    ->form([
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->hidden(fn () => Auth::user()->branch_id !== null)
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['branch_id'],
                                fn (Builder $query, $branchId): Builder => $query->where(function ($q) use ($branchId) {
                                    $q->where('from_branch_id', $branchId)
                                      ->orWhere('to_branch_id', $branchId);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['branch_id']) {
                            return null;
                        }
                        return 'Cabang: ' . \App\Models\Branch::find($data['branch_id'])?->name;
                    }),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('cetak_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\StockTransfer $record) => route('print.document', ['type' => 'transfer', 'ids' => [$record->id]]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('Kirim')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $service = new StockTransferService();
                        $service->markAsInTransit($record, \Illuminate\Support\Facades\Auth::id());
                    }),
                Action::make('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'in_transit')
                    ->action(function ($record) {
                        $service = new StockTransferService();
                        $service->markAsReceived($record, \Illuminate\Support\Facades\Auth::id());
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('cetak_nota_massal')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            if (empty($ids)) return;
                        })
                        ->url(fn (Collection $records) => route('print.document', ['type' => 'transfer', 'ids' => $records->pluck('id')->toArray()]))
                        ->openUrlInNewTab()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_daftar')
                    ->label('Cetak Daftar')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'stock-transfer',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ]);
    }
}
