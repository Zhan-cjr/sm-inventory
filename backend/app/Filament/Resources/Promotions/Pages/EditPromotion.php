<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('update', $this->getRecord()), 403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengubah promosi ini.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $data['organization_id'] = $user->organization_id;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $this->record->branches()->sync([$user->branch_id]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn ($record) => auth()->user()?->can('delete', $record)),
        ];
    }
}
