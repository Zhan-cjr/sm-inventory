<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ViewPurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;

    protected string $view = 'filament.pages.edit-purchase-order';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    public $record;

    public function mount($record): void
    {
        $this->record = PurchaseOrderResource::getModel()::findOrFail($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Lihat Pesanan Pembelian';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Pesanan Pembelian')
                ->modalDescription(fn () => "Setujui PO {$this->record->po_number} senilai Rp " . number_format((float) $this->record->total_amount, 2) . '?')
                ->visible(fn () => strtolower((string) $this->record->status) === 'pending_approval' && (bool) auth()->user()?->hasCustomAuthorization('APPROVE_PO'))
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')->label('Catatan Approval (Opsional)'),
                ])
                ->action(function (array $data) {
                    $this->record->approve(auth()->id(), $data['notes'] ?? null);
                    \Filament\Notifications\Notification::make()
                        ->title("PO {$this->record->po_number} Berhasil Disetujui")
                        ->success()
                        ->send();

                    return redirect()->to(PurchaseOrderResource::getUrl('index'));
                }),

            Action::make('reject')
                ->label('Tolak PO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Pesanan Pembelian')
                ->visible(fn () => strtolower((string) $this->record->status) === 'pending_approval' && (bool) auth()->user()?->hasCustomAuthorization('APPROVE_PO'))
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')->label('Alasan Penolakan')->required(),
                ])
                ->action(function (array $data) {
                    $this->record->reject(auth()->id(), $data['notes']);
                    \Filament\Notifications\Notification::make()
                        ->title("PO {$this->record->po_number} Ditolak")
                        ->warning()
                        ->send();

                    return redirect()->to(PurchaseOrderResource::getUrl('index'));
                }),

            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => PurchaseOrderResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}

