<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::get('/community', fn () => Inertia::render('Community'))->name('community');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::fallback(fn (Request $request) => Inertia::render('NotFound')
    ->toResponse($request)
    ->setStatusCode(404))->name('not-found');
