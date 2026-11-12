<?php

namespace App\Http\Controllers;

use App\Services\GoogleAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __invoke(Request $request, GoogleAccountService $accounts): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/')->with('auth_error', 'Please sign in to view your account.');
        }

        if (! $accounts->canAccess($user)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/')->with('auth_error', 'Your account access is no longer available.');
        }

        return Inertia::render('Account');
    }
}