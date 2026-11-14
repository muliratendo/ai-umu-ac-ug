<?php

namespace App\Http\Middleware;

use App\Services\GoogleAccountService;
use App\Support\AuthOutcome;
use App\Support\GoogleSso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A single reusable request-time boundary for protected SSO resources.
 *
 * When SSO is disabled or its configuration is incomplete, or when the current
 * account is no longer eligible, an existing session is terminated on its next
 * protected request.  This is request-time enforcement, not proactive deletion
 * of every stored session at the instant a configuration flag changes.
 */
class EnsureSsoAccess
{
    public function __construct(private readonly GoogleAccountService $accounts)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/')->with('auth_outcome', AuthOutcome::SIGNIN_REQUIRED);
        }

        $ready = GoogleSso::ready();

        if (! $ready || ! $this->accounts->canAccess($user)) {
            $outcome = $ready ? AuthOutcome::ELIGIBILITY_DENIED : AuthOutcome::SIGNIN_UNAVAILABLE;

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('auth_outcome', $outcome);
        }

        return $next($request);
    }
}
