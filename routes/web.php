<?php

use App\Http\Controllers\Site\AccountController;
use App\Http\Controllers\Site\AiController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\BookingFlowController;
use App\Http\Controllers\Site\ChatController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InboxController;
use App\Http\Controllers\Site\MapsController;
use App\Http\Controllers\Site\VendorOnboardingController;
use App\Http\Middleware\RedirectIfComingSoon;
use Illuminate\Support\Facades\Route;

Route::middleware(RedirectIfComingSoon::class)->group(function (): void {
    Route::get('/', [HomeController::class, 'home'])->name('site.home');
    Route::get('/marketplace', [HomeController::class, 'marketplace'])->name('site.marketplace');
    Route::get('/search', [HomeController::class, 'search'])->name('site.search');
    Route::get('/explore', [HomeController::class, 'explore'])->name('site.explore');
    Route::get('/ai', [AiController::class, 'show'])->name('site.ai');
    Route::post('/ai/chat', [AiController::class, 'chat'])->name('site.ai.chat');
    Route::get('/creatives/{vendor}', [HomeController::class, 'vendor'])->name('site.vendor');
    Route::get('/help', [HomeController::class, 'help'])->name('site.help');
    Route::post('/help/report', [HomeController::class, 'helpReport'])->name('site.help.report');
    Route::get('/maps/search', [MapsController::class, 'search'])->name('site.maps.search');
    Route::get('/maps/reverse', [MapsController::class, 'reverse'])->name('site.maps.reverse');
    Route::get('/p/{slug}', [HomeController::class, 'page'])->name('site.page')
        ->whereIn('slug', ['about', 'contact', 'help', 'terms', 'privacy', 'guidelines', 'pricing', 'resources', 'faqs']);
    Route::post('/newsletter', [HomeController::class, 'newsletter'])->name('site.newsletter');

    Route::middleware('site.guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('site.login');
        Route::post('/login', [AuthController::class, 'login'])->name('site.login.submit');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('site.register');
        Route::post('/register', [AuthController::class, 'register'])->name('site.register.submit');
        Route::get('/join', [AuthController::class, 'showRoleChoice'])->name('site.join');
        Route::post('/auth/google', [AuthController::class, 'google'])->name('site.auth.google');
        Route::get('/vendors/join', [VendorOnboardingController::class, 'create'])->name('site.vendors.join');
        Route::post('/vendors/join', [VendorOnboardingController::class, 'store'])->name('site.vendors.join.submit');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('site.logout');

    // Legacy bookings login kept for redirects
    Route::get('/bookings', [HomeController::class, 'bookings'])->name('site.bookings');
    Route::post('/bookings/login', [HomeController::class, 'bookingsLogin'])->name('site.bookings.login');
    Route::post('/bookings/logout', [HomeController::class, 'bookingsLogout'])->name('site.bookings.logout');

    Route::middleware('site.auth')->group(function (): void {
        Route::get('/account', [AccountController::class, 'index'])->name('site.account');
        Route::get('/account/edit', [AccountController::class, 'edit'])->name('site.account.edit');
        Route::post('/account/edit', [AccountController::class, 'update'])->name('site.account.update');
        Route::get('/account/wallet', [AccountController::class, 'wallet'])->name('site.account.wallet');
        Route::get('/account/payment-methods', [AccountController::class, 'paymentMethods'])->name('site.account.payments');
        Route::post('/account/payment-methods', [AccountController::class, 'storePaymentMethod'])->name('site.account.payments.store');
        Route::delete('/account/payment-methods/{paymentMethod}', [AccountController::class, 'destroyPaymentMethod'])->name('site.account.payments.destroy');
        Route::get('/account/addresses', [AccountController::class, 'addresses'])->name('site.account.addresses');
        Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('site.account.addresses.store');
        Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('site.account.addresses.destroy');
        Route::get('/account/settings', [AccountController::class, 'settings'])->name('site.account.settings');
        Route::post('/account/settings', [AccountController::class, 'updateSettings'])->name('site.account.settings.update');
        Route::get('/account/report', [AccountController::class, 'report'])->name('site.account.report');
        Route::post('/account/report', [AccountController::class, 'storeReport'])->name('site.account.report.store');

        Route::get('/favorites', [InboxController::class, 'favorites'])->name('site.favorites');
        Route::post('/favorites/{vendor}', [InboxController::class, 'saveFavorite'])->name('site.favorites.save');
        Route::delete('/favorites/{vendor}', [InboxController::class, 'forgetFavorite'])->name('site.favorites.forget');
        Route::get('/notifications', [InboxController::class, 'notifications'])->name('site.notifications');
        Route::post('/notifications/read-all', [InboxController::class, 'readAll'])->name('site.notifications.readAll');
        Route::post('/notifications/clear-all', [InboxController::class, 'clearAll'])->name('site.notifications.clearAll');
        Route::post('/notifications/{notification}/read', [InboxController::class, 'readNotification'])->name('site.notifications.read');

        Route::get('/messages', [ChatController::class, 'index'])->name('site.messages');
        Route::post('/messages/open', [ChatController::class, 'open'])->name('site.messages.open');
        Route::get('/messages/vendor/{vendor}', [ChatController::class, 'openVendor'])->name('site.messages.vendor');
        Route::get('/messages/{conversation}', [ChatController::class, 'show'])->name('site.messages.show');
        Route::get('/messages/{conversation}/poll', [ChatController::class, 'poll'])->name('site.messages.poll');
        Route::post('/messages/{conversation}', [ChatController::class, 'store'])->name('site.messages.store');

        Route::get('/book/{vendor}', [BookingFlowController::class, 'start'])->name('site.book.start');
        Route::post('/book/quote', [BookingFlowController::class, 'quote'])->name('site.book.quote');
        Route::post('/book', [BookingFlowController::class, 'store'])->name('site.book.store');
        Route::get('/book/confirmed/{booking}', [BookingFlowController::class, 'confirmed'])->name('site.book.confirmed');
        Route::get('/bookings/{booking}/deliverables', [BookingFlowController::class, 'deliverables'])->name('site.bookings.deliverables');
        Route::post('/bookings/{booking}/deliverables', [BookingFlowController::class, 'uploadDelivery'])->name('site.bookings.deliver');
        Route::post('/bookings/{booking}/approve', [BookingFlowController::class, 'approve'])->name('site.bookings.approve');
        Route::post('/bookings/{booking}/refuse', [BookingFlowController::class, 'refuse'])->name('site.bookings.refuse');
        Route::post('/bookings/{booking}/cancel', [BookingFlowController::class, 'cancel'])->name('site.bookings.cancel');
        Route::post('/bookings/{booking}/dispute', [BookingFlowController::class, 'dispute'])->name('site.bookings.dispute');
        Route::post('/bookings/{booking}/request-edit', [BookingFlowController::class, 'requestEdit'])->name('site.bookings.requestEdit');

        Route::get('/vendors/profile', [VendorOnboardingController::class, 'edit'])->name('site.vendors.profile');
        Route::post('/vendors/profile', [VendorOnboardingController::class, 'update'])->name('site.vendors.profile.update');
    });
});
