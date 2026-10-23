<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));
});
