<?php

namespace App\Filament\Auth;

use App\Support\FilamentRedirect;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Support\Htmlable;

class VendorLogin extends BaseLogin
{
    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament.layouts.admin-login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            $panel = Filament::getCurrentPanel();
            if ($user instanceof FilamentUser && $panel && $user->canAccessPanel($panel)) {
                redirect()->to(FilamentRedirect::intended());

                return;
            }

            Filament::auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        $this->form->fill();
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Sign in';
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Creator desk for bookings, delivery, and payouts.';
    }
}
