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
                //
            ])
            ->recordActions([
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
