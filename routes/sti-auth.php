<?php

use Illuminate\Support\Facades\Route;
use Sti\StiAuth\Http\Controllers\LoginController;

Route::middleware(['web'])->group(function () {
    Route::get(config('sti-auth.route_login'), [LoginController::class, 'showLogin'])->name('login');
    Route::post(config('sti-auth.route_login'), [LoginController::class, 'login']);
    Route::post(config('sti-auth.route_logout'), [LoginController::class, 'logout'])->name('logout');
});
