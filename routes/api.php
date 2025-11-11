<?php

use App\Http\Controllers\UsersDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/demo/users/offset', [UsersDemoController::class, 'offset']);
Route::get('/demo/users/cursor', [UsersDemoController::class, 'cursor']);
Route::get('/demo/users/hybrid', [UsersDemoController::class, 'hybrid']);
