<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Filters\DateFilterHelper;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->searchable(),
                TextColumn::make('po_number')
                    ->searchable(),
                TextColumn::make('po_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_delivery_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('expired_date')
                    ->label('Tgl Kedaluwarsa')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'primary',
                    })
                    ->searchable(),
                TextColumn::make('computed_approver_name')
                    ->label('Diperiksa Oleh')
                    ->state(function ($record) {
                        $latestApproval = $record->approvals()->latest()->first();
                        return $latestApproval && $latestApproval->status !== 'pending' 
                            ? $latestApproval->user?->name 
                            : '-';
                    }),
                TextColumn::make('computed_approval_notes')
                    ->label('Catatan Approval')
                    ->state(function ($record) {
                        $latestApproval = $record->approvals()->latest()->first();
                        return $latestApproval && $latestApproval->status !== 'pending' 
                            ? $latestApproval->notes 
                            : '-';
                    })
                    ->limit(50),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateFilterHelper::make('po_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                Action::make('cetak_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\PurchaseOrder $record) => route('print.document', ['type' => 'po', 'ids' => [$record->id]]))
                    ->openUrlInNewTab()
                    ->visible(fn (\App\Models\PurchaseOrder $record) => strtolower($record->status) === 'approved' && (!$record->expired_date || $record->expired_date >= now()->toDateString())),
                Action::make('request_approval')
                    ->label('Request Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\PurchaseOrder $record) => $record->status === 'draft' || $record->status === 'rejected')
                    ->action(fn (\App\Models\PurchaseOrder $record) => $record->requestApproval()),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\PurchaseOrder $record) => $record->status === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_PO'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')->label('Catatan (Opsional)')
                    ])
                    ->action(fn (\App\Models\PurchaseOrder $record, array $data) => $record->approve(auth()->id(), $data['notes'] ?? null)),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\PurchaseOrder $record) => $record->status === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_PO'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')->label('Alasan Penolakan')->required()
                    ])
                    ->action(fn (\App\Models\PurchaseOrder $record, array $data) => $record->reject(auth()->id(), $data['notes'])),

                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('cetak_nota_massal')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $ids = $records->filter(fn ($record) => strtolower($record->status) === 'approved')->pluck('id')->toArray();
                            if (empty($ids)) {
                                \Filament\Notifications\Notification::make()->title('Tidak ada PO yang disetujui')->danger()->send();
                                return;
                            }
                            $url = route('print.document', ['type' => 'po']) . '?' . http_build_query(['ids' => $ids]);
                            // Redirect is tricky in bulk action closures for new tabs, so we send a notification with a button or dispatch event.
                            // Better: use url() to open direct
                        })
                        ->url(fn (Collection $records) => route('print.document', ['type' => 'po', 'ids' => $records->pluck('id')->toArray()]))
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
                        'type' => 'pesanan-pembelian',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ]);
    }
}
