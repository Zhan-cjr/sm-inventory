<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $data['organization_id'] = $user->organization_id;
            $data['promo_config'] = array_merge($data['promo_config'] ?? [], [
                'created_by_branch_id' => $user->branch_id,
                'created_by_user_id' => $user->id,
            ]);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $this->record->branches()->sync([$user->branch_id]);
        }
    }
}
