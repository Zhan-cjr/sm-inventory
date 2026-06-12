<?php

namespace App\Filament\Resources\CPSettings\Pages;

use App\Filament\Resources\CPSettings\CPSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCPSetting extends CreateRecord
{
    protected static string $resource = CPSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['type'] === 'string') {
            $data['value'] = $data['value_string'] ?? null;
        } elseif ($data['type'] === 'text') {
            $data['value'] = $data['value_text'] ?? null;
        } elseif ($data['type'] === 'image') {
            $data['value'] = $data['value_image'] ?? null;
        }
        
        unset($data['value_string'], $data['value_text'], $data['value_image']);
        return $data;
    }
}
