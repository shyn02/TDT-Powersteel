<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
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
use Illuminate\Support\HtmlString;
use Filament\View\PanelsRenderHook;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Admin\Pages\Auth\Login::class)
            ->brandName(new HtmlString('<img src="' . asset('static/images/logo.png') . '" alt="TDT Powersteel" style="height:40px;width:auto;display:block;">'))
            ->renderHook(
                'body.start',
                fn () => '<link rel="stylesheet" href="' . asset('static/admin_custom.css') . '?v=' . filemtime(public_path('static/admin_custom.css')) . '">',
            )
            // NOTE: the hook name Filament's own layout actually checks
            // for is the PanelsRenderHook::BODY_START constant
            // ('panels::body.start'), not the plain string 'body.start'
            // used above — so that CSS `<link>` was never being output
            // anywhere. Kept as-is to avoid changing existing visual
            // behavior in this change; register new hooks with the
            // constant, like the one below, so they actually fire.
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn () => '<script src="' . asset('static/admin_live_badges.js') . '?v=' . filemtime(public_path('static/admin_live_badges.js')) . '"></script>',
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            // Sidebar groups shown top-to-bottom in this order, matching
            // the store/content/accounts/system layout of the old admin.
            ->navigationGroups([
                'Store Management',
                'Content Management',
                'User Access & Accounts',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
