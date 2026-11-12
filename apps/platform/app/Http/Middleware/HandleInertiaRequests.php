<?php

namespace App\Http\Middleware;

use App\Support\GoogleSso;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'sso_available' => GoogleSso::ready(),
            ],
            'flash' => [
                'auth_error' => $request->session()->get('auth_error'),
                'auth_success' => $request->session()->get('auth_success'),
            ],
        ];
    }
}