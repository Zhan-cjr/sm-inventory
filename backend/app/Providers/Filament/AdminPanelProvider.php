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
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->colors([
                'primary' => Color::Indigo,
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
            ])
            ->brandName('SM Inventory')
            ->brandLogo(fn () => view('filament.brand'))
            ->favicon(asset('favicon.png'))
            ->font('Plus Jakarta Sans', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap')
            ->topNavigation()
            ->spa()
            ->databaseNotifications()
            ->navigationGroups([
                'FILE',
                'OPERATOR',
                'DATA MASTER',
                'PERSEDIAAN',
                'TRANSAKSI',
                'LAPORAN/ARSIP',
                'PENGATURAN',
                'E-COMMERCE',
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('LOGOUT')
                    ->label('LOGOUT')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->group('FILE')
                    ->sort(3)
                    ->url('/admin/logout-get'),
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="/css/admin-custom.css?v=' . filemtime(public_path('css/admin-custom.css')) . '">' . view('filament.print-styles')->render(),
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire("import-progress-bar")'),
            )
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
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
                FilamentShieldPlugin::make()
                    ->navigationGroup('OPERATOR'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
