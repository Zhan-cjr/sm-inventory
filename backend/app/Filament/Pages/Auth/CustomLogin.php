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

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.username' => 'Username atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        if (! \Filament\Facades\Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = \Filament\Facades\Filament::auth()->user();
        
        $panel = \Filament\Facades\Filament::getCurrentPanel() ?? app('filament')->getPanel('admin');
        if ($user && !$user->canAccessPanel($panel)) {
            \Filament\Facades\Filament::auth()->logout();
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.username' => __('Akun Anda tidak memiliki akses ke backend admin.'),
            ]);
        }

        session()->regenerate();

        return app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class);
    }
}
