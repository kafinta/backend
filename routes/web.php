<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SimulatedEmailController;

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

// Simulated Email Interface (Development Only)
Route::get('/simulated-emails', [SimulatedEmailController::class, 'index'])->name('simulated-emails.index');
Route::get('/simulated-emails/{filename}', [SimulatedEmailController::class, 'show'])->name('simulated-emails.show');
Route::delete('/simulated-emails/{filename}', [SimulatedEmailController::class, 'destroy'])->name('simulated-emails.destroy');
Route::delete('/simulated-emails', [SimulatedEmailController::class, 'clearAll'])->name('simulated-emails.clear-all');

Route::post('/user/auth/login', [App\Http\Controllers\UserController::class, 'login']);
Route::post('/user/auth/signup', [App\Http\Controllers\UserController::class, 'register']);