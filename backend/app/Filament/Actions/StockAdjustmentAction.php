<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Stock;

class StockAdjustmentAction
{
    public static function make(): Action
    {
        return Action::make('adjust_stock')
            ->label('Penyesuaian Stok')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('warning')
            ->form([
                TextInput::make('current_qty')
                    ->label('Stok Saat Ini')
                    ->disabled()
                    ->default(fn (Stock $record) => $record->quantity_on_hand),
                TextInput::make('adjustment_qty')
                    ->label('Jumlah Penyesuaian (+ atau -)')
                    ->numeric()
                    ->required()
                    ->placeholder('Contoh: -5 atau 10'),
                Select::make('reason_code')
                    ->label('Alasan')
                    ->options([
                        'DAMAGE' => 'Barang Rusak',
                        'LOSS' => 'Kehilangan',
                        'CORRECTION' => 'Koreksi Data',
                        'EXPIRED' => 'Kadaluarsa',
                        'RETURN' => 'Retur Pelanggan',
                    ])
                    ->required(),
                TextInput::make('notes')
                    ->label('Catatan')
                    ->placeholder('Keterangan tambahan...'),
            ])
            ->action(function (Stock $record, array $data) {
                $adjustment = (int) $data['adjustment_qty'];
                $newQty = $record->quantity_on_hand + $adjustment;

                $record->reason_code = $data['reason_code'];
                $record->notes = $data['notes'];
                $record->log_type = 'ADJUSTMENT';

                $record->update([
                    'quantity_on_hand' => $newQty,
                ]);

                // The StockObserver will handle the InventoryLog creation, 
                // but we can pass more info via metadata or manually log here if needed.
                // For now, let's rely on the observer but maybe enhance it later.

                Notification::make()
                    ->title('Stok Diperbarui')
                    ->body("Stok berhasil disesuaikan menjadi {$newQty}.")
                    ->success()
                    ->send();
            });
    }
}
