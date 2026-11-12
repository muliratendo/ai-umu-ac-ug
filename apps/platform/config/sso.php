<?php

return [
    'enabled' => (bool) env('SSO_ENABLED', false),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'hosted_domain' => env('UNIVERSITY_GOOGLE_HOSTED_DOMAIN'),
];