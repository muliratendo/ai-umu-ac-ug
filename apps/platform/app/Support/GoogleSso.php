<?php

namespace App\Support;

use Illuminate\Support\Str;

final class GoogleSso
{
    public static function enabled(): bool
    {
        return (bool) config('sso.enabled', false);
    }

    public static function ready(): bool
    {
        if (! self::enabled() || ! filled(config('sso.client_id')) || ! filled(config('sso.client_secret')) || ! filled(config('sso.redirect_uri'))) {
            return false;
        }

        return ! request()->routeIs('auth.google.callback') || self::callbackMatchesConfiguredUrl();
    }

    public static function normalizeEmail(string $email): ?string
    {
        $normalized = Str::lower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) === false ? null : $normalized;
    }

    private static function callbackMatchesConfiguredUrl(): bool
    {
        return hash_equals(
            rtrim((string) config('sso.redirect_uri'), '/'),
            rtrim(route('auth.google.callback'), '/'),
        );
    }
}