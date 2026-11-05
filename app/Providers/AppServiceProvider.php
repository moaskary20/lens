<?php

namespace App\Providers;

use App\Filament\Auth\PanelLoginResponse;
use App\Notifications\Channels\FcmChannel;
use App\Support\BrevoMail;
use App\Support\SiteAuth;
use App\Support\SiteStats;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, PanelLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('fcm', fn ($app) => $app->make(FcmChannel::class));

        try {
            if (Schema::hasTable('settings')) {
                BrevoMail::apply();
            }
        } catch (\Throwable) {
            // Settings may be unavailable during migrate or first install.
        }

        View::composer('site.*', function ($view): void {
            if (! array_key_exists('authUser', $view->getData())) {
                $view->with('authUser', SiteAuth::user());
            }
            if (! array_key_exists('siteStats', $view->getData())) {
                $view->with('siteStats', SiteStats::marketplace());
            }
        });
    }
}
