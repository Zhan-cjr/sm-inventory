<?php

namespace App\Filament\Resources\CPTestimonials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CPTestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')
                    ->required(),
                TextInput::make('role'),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('avatar_url')
                    ->image()
                    ->disk('public')
                    ->avatar()
                    ->directory('testimonials'),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(5),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
