<?php

use Illuminate\Support\Facades\Route;
use Sti\StiAuth\Http\Controllers\DemoLoginController;

Route::get('/sti-auth/demo', [DemoLoginController::class, 'index']);
