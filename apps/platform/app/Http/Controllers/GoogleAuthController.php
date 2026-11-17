<?php

namespace App\Http\Controllers;

use App\Exceptions\SsoAccessDenied;
use App\Exceptions\SsoAuthenticationUnavailable;
use App\Services\GoogleAccountService;
use App\Support\AuthOutcome;
use App\Support\GoogleSso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): HttpResponse
    {
        abort_unless(GoogleSso::ready(), 404);

        $redirect = Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();

        if ($request->header('X-Inertia')) {
            return Inertia::location($redirect->getTargetUrl());
        }

        return $redirect;
    }

    public function callback(Request $request, GoogleAccountService $accounts): RedirectResponse
    {
        abort_unless(GoogleSso::ready(), 404);

        try {
            $provider = Socialite::driver('google');

            // Google redirects back with an `error` parameter when the user
            // cancels consent.  Validate state on this path too, then return
            // a cancellation message without contacting the provider.
            if ($request->has('error')) {
                $this->validateStateOrReject($request);

                return redirect('/')->with('auth_outcome', AuthOutcome::CANCELLED);
            }

            $user = $accounts->authenticate($provider->user());
            auth()->login($user);
            $request->session()->regenerate();

            return redirect('/account')->with('auth_outcome', AuthOutcome::SIGNED_IN);
        } catch (InvalidStateException) {
            return redirect('/')->with('auth_outcome', AuthOutcome::EXPIRED_REQUEST);
        } catch (SsoAccessDenied) {
            return redirect('/')->with('auth_outcome', AuthOutcome::ELIGIBILITY_DENIED);
        } catch (SsoAuthenticationUnavailable) {
            return redirect('/')->with('auth_outcome', AuthOutcome::PROVIDER_UNAVAILABLE);
        } catch (\Throwable $exception) {
            report($exception);
            return redirect('/')->with('auth_outcome', AuthOutcome::PROVIDER_UNAVAILABLE);
        }
    }

    /**
     * Mirror Socialite's state validation for the cancellation/no-code path
     * where the provider's user() method is never called.
     */
    private function validateStateOrReject(Request $request): void
    {
        $sessionState = $request->session()->pull('state');
        $requestState = $request->input('state');

        if (
            ! is_string($sessionState)
            || $sessionState === ''
            || ! is_string($requestState)
            || $requestState === ''
            || ! hash_equals($sessionState, $requestState)
        ) {
            throw new InvalidStateException;
        }
    }
}
