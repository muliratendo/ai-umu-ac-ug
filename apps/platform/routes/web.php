<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LogoutController;
use App\Http\Middleware\EnsureSsoAccess;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::get('/community', fn () => Inertia::render('Community'))->name('community');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/account', AccountController::class)->middleware(EnsureSsoAccess::class)->name('account');
Route::post('/logout', LogoutController::class)->name('logout');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

// Unknown routes are intentionally not handled by a catch-all fallback: doing so
// would shadow framework/vendor routes and turn method mismatches (405) into
// 404s.  A consistent branded 404 for unmatched routes and aborted 404s is
// rendered from the exception handler instead.
