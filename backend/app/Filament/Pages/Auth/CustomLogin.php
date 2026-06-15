<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Auth\Pages\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    /**
     * Override the credentials from form data to use 'username' instead of 'email'
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    /**
     * Override the email form component to use username
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }
}
