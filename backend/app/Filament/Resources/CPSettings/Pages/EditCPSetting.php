<?php

namespace App\Filament\Resources\CPSettings\Pages;

use App\Filament\Resources\CPSettings\CPSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCPSetting extends EditRecord
{
    protected static string $resource = CPSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['type'] === 'string') {
            $data['value_string'] = $data['value'];
        } elseif ($data['type'] === 'text') {
            $data['value_text'] = $data['value'];
        } elseif ($data['type'] === 'image') {
            $data['value_image'] = $data['value'];
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
