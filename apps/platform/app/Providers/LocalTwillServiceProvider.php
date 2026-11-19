<?php

namespace App\Providers;

use A17\Twill\TwillServiceProvider;
use Illuminate\Support\ServiceProvider;

final class LocalTwillServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && config('local-cms.enabled') === true) {
            $this->app->register(TwillServiceProvider::class);
        }
    }
}
