<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameRackResource;
use App\Models\StockOpnameRack;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStockOpnameRack extends EditRecord
{
    protected static string $resource = StockOpnameRackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(fn (StockOpnameRack $record) => $record->rackSessions()->exists() ? 'Nonaktifkan Rak?' : 'Hapus Rak?')
                ->modalDescription(fn (StockOpnameRack $record) => $record->rackSessions()->exists()
                    ? "Rak '{$record->rack_name}' tidak dapat dihapus permanen karena sudah memiliki riwayat sesi opname. Melanjutkan tindakan ini akan mengubah status rak menjadi Nonaktif agar data histori opname masa lalu tetap aman."
                    : "Apakah Anda yakin ingin menghapus rak '{$record->rack_name}'?"
                )
                ->modalSubmitActionLabel(fn (StockOpnameRack $record) => $record->rackSessions()->exists() ? 'Ya, Nonaktifkan' : 'Hapus')
                ->action(function (DeleteAction $action, StockOpnameRack $record) {
                    if ($record->rackSessions()->exists()) {
                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->warning()
                            ->title('Rak Dinonaktifkan')
                            ->body("Rak '{$record->rack_name}' tidak dapat dihapus karena sudah memiliki riwayat sesi opname. Status rak telah diubah menjadi nonaktif.")
                            ->persistent()
                            ->send();

                        $action->redirect(StockOpnameRackResource::getUrl('index'));
                        return;
                    }

                    $record->delete();

                    Notification::make()
                        ->success()
                        ->title('Rak Berhasil Dihapus')
                        ->send();

                    $action->redirect(StockOpnameRackResource::getUrl('index'));
                }),
        ];
    }
}
