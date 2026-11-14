<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __invoke(): Response
    {
        // Sign-in and continued eligibility are enforced by the
        // EnsureSsoAccess middleware on this route.
        return Inertia::render('Account');
    }
}
