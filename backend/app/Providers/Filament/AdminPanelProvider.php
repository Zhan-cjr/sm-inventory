<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
            ])
            ->brandName('SM Inventory')
            ->font('Plus Jakarta Sans', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap')
            ->topNavigation()
            ->spa()
            ->databaseNotifications()
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="/css/admin-custom.css">' . view('filament.print-styles')->render(),
            )
            ->renderHook(
                'panels::brand.after',
                fn (): string => '
                    <div class="flex items-center justify-start mt-0.5 ml-1">
                        <a href="https://www.instagram.com/amn4ll?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" class="flex items-center gap-1.5 text-[10px] font-semibold text-gray-400 hover:text-indigo-500 dark:text-gray-500 dark:hover:text-sky-400 transition-colors">
                            <svg width="12" height="12" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="rounded">
                                <defs>
                                    <linearGradient id="zGradBrand" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#4f46e5" />
                                        <stop offset="100%" stop-color="#06b6d4" />
                                    </linearGradient>
                                </defs>
                                <rect width="100" height="100" rx="30" fill="url(#zGradBrand)" />
                                <path d="M30 30H70L30 70H70" stroke="white" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>Zhan_soft</span>
                        </a>
                    </div>
                '
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Custom widgets will be added here
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
