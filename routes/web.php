<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Simulated Emails Interface (Development Only)
Route::get('/simulated-emails', function () {
    return view('simulated-emails.index');
})->name('simulated-emails.web');

// Verification Tokens Interface (Development Only)
Route::get('/verification-tokens', function () {
    return view('verification-tokens.index');
})->name('verification-tokens.web');

Route::post('/user/auth/login', [App\Http\Controllers\UserController::class, 'login']);
Route::post('/user/auth/signup', [App\Http\Controllers\UserController::class, 'register']);