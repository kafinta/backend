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

// Temporary test route for queue system (remove in production)
Route::get('/test-queue', function () {
    $emailService = app(\App\Services\EmailService::class);

    // Create a test user
    $testUser = new \App\Models\User([
        'id' => 999,
        'name' => 'Queue Test User',
        'email' => 'chelseajames529@gmail.com',
        'username' => 'queuetest'
    ]);

    // Test queued welcome email
    $result = $emailService->sendWelcomeEmail($testUser);

    return response()->json([
        'success' => $result,
        'message' => $result ? 'Welcome email queued successfully!' : 'Failed to queue welcome email',
        'queue_info' => 'Check queue with: php artisan queue:work or php artisan email:process-queue'
    ]);
});