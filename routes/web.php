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
    // Create directory if it doesn't exist
    $emailsDir = storage_path('simulated-emails');
    if (!\Illuminate\Support\Facades\File::exists($emailsDir)) {
        \Illuminate\Support\Facades\File::makeDirectory($emailsDir, 0755, true);
    }

    // Get all email files
    $files = \Illuminate\Support\Facades\File::files($emailsDir);

    $emails = [];
    foreach ($files as $file) {
        $emails[] = [
            'filename' => $file->getFilename(),
            'size' => $file->getSize(),
            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            'url' => route('simulated-emails.show.web', ['filename' => $file->getFilename()]),
        ];
    }

    // Sort by creation time (newest first)
    usort($emails, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    return view('simulated-emails.index', [
        'initialEmails' => json_encode([
            'success' => true,
            'data' => [
                'emails' => $emails
            ]
        ])
    ]);
})->name('simulated-emails.web');

// Show a specific simulated email (web version)
Route::get('/simulated-emails/{filename}', function ($filename) {
    $filepath = storage_path('simulated-emails/' . $filename);

    if (!\Illuminate\Support\Facades\File::exists($filepath)) {
        abort(404, 'Email not found');
    }

    $content = \Illuminate\Support\Facades\File::get($filepath);

    return response($content)->header('Content-Type', 'text/html');
})->name('simulated-emails.show.web');

// Verification Tokens Interface (Development Only)
Route::get('/verification-tokens', function () {
    $tokens = \App\Models\EmailVerificationToken::with('user')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($token) {
            return [
                'id' => $token->id,
                'user_id' => $token->user_id,
                'username' => $token->user->username,
                'email' => $token->email,
                'token' => $token->token,
                'verification_code' => $token->verification_code,
                'verification_url' => "http://localhost:3000/auth/verify?token=" . $token->token,
                'created_at' => $token->created_at->format('Y-m-d H:i:s'),
                'expires_at' => $token->expires_at->format('Y-m-d H:i:s'),
                'is_expired' => $token->isExpired(),
            ];
        });

    return view('verification-tokens.index', [
        'initialTokens' => json_encode([
            'success' => true,
            'data' => [
                'tokens' => $tokens
            ]
        ])
    ]);
})->name('verification-tokens.web');

// Password Reset Tokens Interface (Development Only)
Route::get('/password-reset-tokens', function () {
    $tokens = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($token) {
            return [
                'email' => $token->email,
                'reset_code' => $token->reset_code,
                'reset_url' => "http://localhost:3000/auth/reset-password?token=" . $token->token,
                'created_at' => $token->created_at,
                'expires_at' => \Carbon\Carbon::parse($token->created_at)->addMinutes(60)->format('Y-m-d H:i:s'),
                'is_expired' => \Carbon\Carbon::parse($token->created_at)->addMinutes(60)->isPast(),
            ];
        });

    return view('password-reset-tokens.index', [
        'initialTokens' => json_encode([
            'success' => true,
            'data' => [
                'tokens' => $tokens
            ]
        ])
    ]);
})->name('password-reset-tokens.web');

// OAuth Users Interface (Development Only)
Route::get('/oauth-users', function () {
    $users = \App\Models\User::whereNotNull('provider')
        ->with('roles')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'provider' => $user->provider,
                'provider_display' => $user->getProviderDisplayName(),
                'provider_id' => $user->provider_id,
                'has_password' => $user->hasPassword(),
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'roles' => $user->roles->pluck('name')->join(', '),
                'profile_picture' => $user->profile_picture,
            ];
        });

    return view('oauth-users.index', [
        'initialUsers' => json_encode([
            'success' => true,
            'data' => [
                'users' => $users
            ]
        ])
    ]);
})->name('oauth-users.web');

Route::post('/user/auth/login', [App\Http\Controllers\UserController::class, 'login']);
Route::post('/user/auth/signup', [App\Http\Controllers\UserController::class, 'register']);