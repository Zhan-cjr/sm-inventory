<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\SimplePage;

class CustomPasswordReset extends SimplePage
{
    protected string $view = 'filament.pages.auth.custom-password-reset';

    public $email = '';
    public $otp = '';
    public $password = '';
    public $passwordConfirmation = '';
    
    public int $step = 1; // 1: Minta Email, 2: Input OTP & Password Baru

    public function mount(): void
    {
        if (\Filament\Facades\Filament::auth()->check()) {
            redirect()->to(\Filament\Facades\Filament::getCurrentPanel()->getUrl());
        }
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Lupa Kata Sandi';
    }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Lupa Kata Sandi';
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return $this->step === 1 
            ? 'Masukkan email yang terdaftar pada akun Anda.' 
            : 'Masukkan kode OTP yang telah dikirim ke email Anda.';
    }

    public function requestOtp(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'Email tidak terdaftar di sistem kami.');
            return;
        }

        // Generate 6 digit OTP
        $otp = (string) rand(100000, 999999);
        $key = 'admin_otp_pw_' . $this->email;
        
        \Illuminate\Support\Facades\Cache::put($key, $otp, 300); // 5 menit

        // Kirim OTP via Email
        try {
            \Illuminate\Support\Facades\Mail::raw("Halo {$user->name},\n\nKode OTP untuk mereset kata sandi backend admin SM Inventory Anda adalah: {$otp}.\n\nKode ini berlaku selama 5 menit. Mohon tidak membagikan kode ini kepada siapapun demi keamanan akun Anda.", function ($mailMsg) use ($user) {
                $mailMsg->to($user->email)
                    ->subject('Kode OTP Reset Kata Sandi Admin');
            });
            
            \Filament\Notifications\Notification::make()
                ->title('Kode OTP terkirim')
                ->body('Kode OTP telah dikirim ke email Anda.')
                ->success()
                ->send();
                
            $this->step = 2;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send admin email OTP: ' . $e->getMessage());
            $this->addError('email', 'Gagal mengirim email OTP. Pastikan konfigurasi email server benar.');
        }
    }

    public function resetPassword(): void
    {
        $this->validate([
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8',
            'passwordConfirmation' => 'required|string|same:password',
        ]);

        $key = 'admin_otp_pw_' . $this->email;
        $cachedOtp = \Illuminate\Support\Facades\Cache::get($key);

        if (!$cachedOtp || $cachedOtp !== $this->otp) {
            $this->addError('otp', 'Kode OTP salah atau telah kedaluwarsa.');
            return;
        }

        $user = \App\Models\User::where('email', $this->email)->first();

        if ($user) {
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($this->password),
            ]);

            \Illuminate\Support\Facades\Cache::forget($key);

            \Filament\Notifications\Notification::make()
                ->title('Berhasil')
                ->body('Kata sandi Anda berhasil diperbarui.')
                ->success()
                ->send();

            $this->redirect(\Filament\Facades\Filament::getCurrentPanel()->getLoginUrl());
        }
    }
}
