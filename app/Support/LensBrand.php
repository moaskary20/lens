<?php

namespace App\Support;

use Filament\Panel;
use Filament\View\PanelsRenderHook;

class LensBrand
{
    public static function apply(Panel $panel): Panel
    {
        return $panel
            ->darkMode(true, true)
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('5.25rem')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.hooks.lens-theme-head')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.hooks.lens-theme-scripts')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn (): string => view('filament.hooks.lens-nav-kicker')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.hooks.lens-nav-kicker')->render(),
            );
    }
}
