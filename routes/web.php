<?php

use App\Http\Controllers\Site\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('site.home');
Route::get('/search', [HomeController::class, 'search'])->name('site.search');
Route::get('/bookings', [HomeController::class, 'bookings'])->name('site.bookings');
Route::get('/explore', [HomeController::class, 'explore'])->name('site.explore');
Route::get('/creatives/{vendor}', [HomeController::class, 'vendor'])->name('site.vendor');
Route::get('/p/{slug}', [HomeController::class, 'page'])->name('site.page')
    ->whereIn('slug', ['about', 'contact', 'help', 'terms', 'privacy', 'guidelines', 'pricing', 'resources', 'faqs']);
Route::post('/newsletter', [HomeController::class, 'newsletter'])->name('site.newsletter');
