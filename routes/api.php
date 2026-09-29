<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ClientInboxController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('app/bootstrap', [AppController::class, 'bootstrap']);
Route::get('app/vendors', [AppController::class, 'vendors']);
Route::get('app/vendors/{vendor}', [AppController::class, 'show']);

Route::post('app/auth/login', [AuthController::class, 'login']);
Route::post('app/auth/register', [AuthController::class, 'register']);
Route::get('app/auth/me', [AuthController::class, 'me']);
Route::get('app/bookings', [AuthController::class, 'bookings']);
Route::get('app/account/payment-methods', [AccountController::class, 'paymentMethods']);
Route::post('app/account/payment-methods', [AccountController::class, 'storePaymentMethod']);
Route::delete('app/account/payment-methods/{paymentMethod}', [AccountController::class, 'destroyPaymentMethod']);
Route::get('app/account/addresses', [AccountController::class, 'addresses']);
Route::post('app/account/addresses', [AccountController::class, 'storeAddress']);
Route::delete('app/account/addresses/{address}', [AccountController::class, 'destroyAddress']);
Route::get('app/account/settings', [AccountController::class, 'settings']);
Route::post('app/account/settings', [AccountController::class, 'updateSettings']);
Route::get('app/account/profile', [AccountController::class, 'profile']);
Route::post('app/account/profile', [AccountController::class, 'updateProfile']);
Route::post('app/issues', [IssueController::class, 'store']);
Route::get('app/account/projects', [AccountController::class, 'projects']);
Route::post('app/bookings/quote', [BookingController::class, 'quote']);
Route::post('app/bookings', [BookingController::class, 'store']);
Route::get('app/bookings/{booking}/deliverables', [BookingController::class, 'deliverables']);
Route::post('app/bookings/{booking}/request-edit', [BookingController::class, 'requestEdit']);
Route::post('app/bookings/{booking}/approve', [BookingController::class, 'approve']);
Route::post('app/bookings/{booking}/refuse', [BookingController::class, 'refuse']);
Route::get('app/disputes', [BookingController::class, 'disputes']);
Route::get('app/bookings/{booking}/dispute', [BookingController::class, 'showDispute']);
Route::post('app/bookings/{booking}/dispute', [BookingController::class, 'openDispute']);

Route::post('app/conversations', [ChatController::class, 'open']);
Route::get('app/conversations/{conversation}', [ChatController::class, 'show']);
Route::post('app/conversations/{conversation}/messages', [ChatController::class, 'store']);

Route::get('app/favorites', [ClientInboxController::class, 'favorites']);
Route::post('app/favorites/{vendor}', [ClientInboxController::class, 'save']);
Route::delete('app/favorites/{vendor}', [ClientInboxController::class, 'forget']);

Route::get('app/notifications', [ClientInboxController::class, 'notifications']);
Route::post('app/notifications/read-all', [ClientInboxController::class, 'readAll']);
Route::post('app/notifications/{notification}/read', [ClientInboxController::class, 'read']);

Route::prefix('search')->group(function (): void {
    Route::get('catalog', [SearchController::class, 'catalog']);
    Route::post('/', [SearchController::class, 'search']);
    Route::post('assistant/chat', [SearchController::class, 'chat']);
    Route::post('assistant', [SearchController::class, 'assistant']);
    Route::get('recommendations', [SearchController::class, 'recommendations']);
});

Route::post('bookings/{booking}/moodboard', [SearchController::class, 'moodboard']);
