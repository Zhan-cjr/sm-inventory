<?php

namespace App\Filament\Resources\CPPartnerships\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CPPartnershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('business_name')
                    ->required(),
                TextInput::make('owner_name')
                    ->required(),
                FileUpload::make('image_url')
                    ->image()
                    ->disk('public')
                    ->directory('partnerships'),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('category')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'Pending' => 'Pending',
            'Reviewed' => 'Reviewed',
            'Accepted' => 'Accepted',
            'Rejected' => 'Rejected',
        ])
                    ->default('Pending')
                    ->required(),
            ]);
    }
}
