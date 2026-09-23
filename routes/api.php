<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientInboxController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('app/bootstrap', [AppController::class, 'bootstrap']);
Route::get('app/vendors', [AppController::class, 'vendors']);
Route::get('app/vendors/{vendor}', [AppController::class, 'show']);

Route::post('app/auth/login', [AuthController::class, 'login']);
Route::post('app/auth/register', [AuthController::class, 'register']);
Route::get('app/auth/me', [AuthController::class, 'me']);
Route::get('app/bookings', [AuthController::class, 'bookings']);

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
