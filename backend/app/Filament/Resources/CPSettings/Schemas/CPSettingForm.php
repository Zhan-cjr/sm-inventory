<?php

namespace App\Filament\Resources\CPSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CPSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required(),
                TextInput::make('label'),
                \Filament\Forms\Components\Select::make('type')
                    ->options([
                        'string' => 'Teks Pendek',
                        'text' => 'Teks Panjang / Paragraf',
                        'image' => 'Gambar / Logo',
                    ])
                    ->default('string')
                    ->live()
                    ->required(),
                TextInput::make('value_string')
                    ->label('Nilai Setting')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('type') === 'string')
                    ->required(fn (\Filament\Forms\Get $get) => $get('type') === 'string'),
                Textarea::make('value_text')
                    ->label('Nilai Setting')
                    ->columnSpanFull()
                    ->visible(fn (\Filament\Forms\Get $get) => $get('type') === 'text')
                    ->required(fn (\Filament\Forms\Get $get) => $get('type') === 'text'),
                \Filament\Forms\Components\FileUpload::make('value_image')
                    ->label('Upload Gambar')
                    ->image()
                    ->disk('public')
                    ->directory('settings')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('type') === 'image')
                    ->required(fn (\Filament\Forms\Get $get) => $get('type') === 'image'),
            ]);
    }
}
