<?php

namespace App\Http\Controllers;

use App\Exceptions\SsoAccessDenied;
use App\Services\GoogleAccountService;
use App\Support\GoogleSso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(GoogleSso::ready(), 404);

        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect();
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

                return redirect('/')->with('auth_error', 'Sign-in was cancelled or did not complete. Please try again if you intended to sign in.');
            }

            $user = $accounts->authenticate($provider->user());
            auth()->login($user);
            $request->session()->regenerate();

            return redirect('/account')->with('auth_success', 'You are signed in.');
        } catch (InvalidStateException) {
            return redirect('/')->with('auth_error', 'That sign-in request expired. Please try again.');
        } catch (SsoAccessDenied) {
            return redirect('/')->with('auth_error', 'This account is not approved for access.');
        } catch (\Exception $exception) {
            report($exception);
            return redirect('/')->with('auth_error', 'Sign-in is unavailable. Please try again later.');
        }
    }

    /**
     * Mirror Socialite's state validation for the cancellation/no-code path
     * where the provider's user() method is never called.
     */
    private function validateStateOrReject(Request $request): void
    {
        $sessionState = $request->session()->pull('state');

        if (empty($sessionState) || ! hash_equals($sessionState, (string) $request->input('state'))) {
            throw new InvalidStateException;
        }
    }
}
