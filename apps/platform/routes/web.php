<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LogoutController;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::get('/community', fn () => Inertia::render('Community'))->name('community');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/account', AccountController::class)->name('account');
Route::post('/logout', LogoutController::class)->name('logout');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::fallback(fn (Request $request) => Inertia::render('NotFound')
    ->toResponse($request)
    ->setStatusCode(404))->name('not-found');