<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate_min_max')
                ->label('Hitung Min-Max Dinamis')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn () => (bool) auth()->user()?->hasCustomAuthorization('EDIT_SUPPLIER_CYCLE'))
                ->requiresConfirmation()
                ->modalHeading(fn () => "Kalkulasi Ulang Min-Max: {$this->record->name}")
                ->modalDescription('Sistem akan menganalisis histori penjualan 12 bulan terakhir dan memperbarui Min, Max, dan Safety Stock untuk seluruh barang milik supplier ini di semua cabang berdasarkan siklus kunjungan yang aktif.')
                ->modalSubmitActionLabel('Mulai Hitung Ulang')
                ->action(function () {
                    try {
                        $res = app(\App\Services\DynamicMinMaxService::class)->recalculateForSupplier($this->record->id);
                        Notification::make()
                            ->title('Min-Max Dinamis Berhasil Dihitung Ulang')
                            ->body("Berhasil memperbarui {$res['total_stocks_updated']} stok cabang untuk supplier {$this->record->name}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Menghitung Ulang')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->products()->exists()),
        ];
    }
}
