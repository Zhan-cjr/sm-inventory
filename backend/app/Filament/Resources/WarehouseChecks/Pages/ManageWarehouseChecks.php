<?php

namespace App\Filament\Resources\WarehouseChecks\Pages;

use App\Filament\Resources\WarehouseChecks\WarehouseCheckResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;
use App\Models\WarehouseCheck;

class ManageWarehouseChecks extends ManageRecords
{
    protected static string $resource = WarehouseCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resync_status')
                ->label('Resync Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Status Pengecekan Gudang')
                ->modalDescription('Perintah ini (warehouse-check:resync) akan memeriksa dan mencocokkan kembali status seluruh dokumen Pengecekan Gudang dengan faktur Goods Receipt (GR) yang telah dibuat/diterima.')
                ->modalSubmitActionLabel('Ya, Jalankan Resync')
                ->action(function () {
                    $checks = WarehouseCheck::whereIn('status', ['approved', 'processed', 'partially_processed'])->get();
                    $count = 0;

                    foreach ($checks as $check) {
                        $oldStatus = $check->status;
                        $check->syncStatus();
                        $check->refresh();
                        if ($oldStatus !== $check->status) {
                            $count++;
                        }
                    }

                    Notification::make()
                        ->title('Sinkronisasi Selesai')
                        ->body("Perintah warehouse-check:resync berhasil dijalankan. Sebanyak {$count} dokumen statusnya diperbarui.")
                        ->success()
                        ->send();
                }),

            Action::make('create')
                ->label('Buat Pengecekan Gudang')
                ->url(url('/warehouse/receive')),
        ];
    }
}
