<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\MeController;
use Auth0\Laravel\Controllers\CallbackController;
use Auth0\Laravel\Controllers\LoginController;
use Auth0\Laravel\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

// Auth0 regular web application flow (session based). The controllers come from
// the SDK; the guard is `auth0-session` (the default guard, see config/auth.php).
Route::get('/login', LoginController::class)->name('login');
Route::get('/callback', CallbackController::class)->name('callback');
Route::get('/logout', LogoutController::class)->name('logout');

Route::get('/api/health', fn () => response()->json(['status' => 'ok']))->name('api.health');

// The middleware pipeline is a Chain of Responsibility: auth -> throttle -> controller.
Route::prefix('api')->middleware(['auth', 'throttle:60,1'])->group(function (): void {
    Route::get('/me', MeController::class)->name('api.me');
    Route::get('/countries', CountryController::class)->name('api.countries');
});
