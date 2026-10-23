<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\MeController;
use Auth0\Laravel\Controllers\CallbackController;
use Auth0\Laravel\Controllers\LoginController;
use Auth0\Laravel\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

// Auth0 session flow; controllers come from the SDK, the guard is `auth0-session` (config/auth.php).
Route::get('/login', LoginController::class)->name('login');
Route::get('/callback', CallbackController::class)->name('callback');
Route::get('/logout', LogoutController::class)->name('logout');

Route::get('/api/health', fn () => response()->json(['status' => 'ok']))->name('api.health');

// Chain of Responsibility: throttle -> auth -> controller. Throttling comes first so
// unauthenticated floods are limited too.
Route::prefix('api')->middleware(['throttle:60,1', 'auth'])->group(function (): void {
    Route::get('/me', MeController::class)->name('api.me');
    Route::get('/countries', CountryController::class)->name('api.countries');
});
