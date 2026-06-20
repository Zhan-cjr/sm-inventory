<x-filament-panels::page.simple>
    @if (\Filament\Facades\Filament::hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ \Filament\Facades\Filament::getRegistrationAction() }}
        </x-slot>
    @endif

    <x-filament-panels::form>
        @if ($step === 1)
            <div class="space-y-4">
                {{-- Step 1: Input Email --}}
                <x-filament-forms::field-wrapper id="email" label="Alamat Email" required="true">
                    <x-filament::input.wrapper :valid="!$errors->has('email')">
                        <x-filament::input
                            type="email"
                            wire:model="email"
                            id="email"
                            required
                            autofocus
                            placeholder="nama@email.com"
                        />
                    </x-filament::input.wrapper>
                    @error('email')
                        <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                    @enderror
                </x-filament-forms::field-wrapper>
                
                <x-filament::button wire:click="requestOtp" type="button" class="w-full">
                    Kirim Kode OTP
                </x-filament::button>
            </div>
        @else
            <div class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    OTP telah dikirim ke <strong>{{ $email }}</strong>. Silakan periksa kotak masuk atau folder spam Anda.
                </p>

                {{-- Step 2: Input OTP & New Password --}}
                <x-filament-forms::field-wrapper id="otp" label="Kode OTP (6 digit)" required="true">
                    <x-filament::input.wrapper :valid="!$errors->has('otp')">
                        <x-filament::input
                            type="text"
                            wire:model="otp"
                            id="otp"
                            required
                            autofocus
                            maxlength="6"
                            placeholder="123456"
                            class="text-center tracking-widest font-mono text-lg"
                        />
                    </x-filament::input.wrapper>
                    @error('otp')
                        <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                    @enderror
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="password" label="Kata Sandi Baru" required="true">
                    <x-filament::input.wrapper :valid="!$errors->has('password')">
                        <x-filament::input
                            type="password"
                            wire:model="password"
                            id="password"
                            required
                        />
                    </x-filament::input.wrapper>
                    @error('password')
                        <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                    @enderror
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="passwordConfirmation" label="Konfirmasi Kata Sandi Baru" required="true">
                    <x-filament::input.wrapper :valid="!$errors->has('passwordConfirmation')">
                        <x-filament::input
                            type="password"
                            wire:model="passwordConfirmation"
                            id="passwordConfirmation"
                            required
                        />
                    </x-filament::input.wrapper>
                    @error('passwordConfirmation')
                        <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                    @enderror
                </x-filament-forms::field-wrapper>
                
                <x-filament::button wire:click="resetPassword" type="button" class="w-full">
                    Simpan Kata Sandi & Masuk
                </x-filament::button>

                <div class="text-center mt-2">
                    <button wire:click="$set('step', 1)" type="button" class="text-sm text-primary-600 hover:text-primary-500 font-medium">
                        Kembali ke masukkan email
                    </button>
                </div>
            </div>
        @endif
    </x-filament-panels::form>
    
    <div class="text-center mt-6">
        <a href="{{ \Filament\Facades\Filament::getCurrentPanel()->getLoginUrl() }}" class="text-sm text-primary-600 hover:text-primary-500 font-medium">
            &larr; Kembali ke halaman Login
        </a>
    </div>
</x-filament-panels::page.simple>
