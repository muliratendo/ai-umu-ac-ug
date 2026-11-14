<?php

namespace App\Support;

/**
 * Server-controlled, non-disclosing authentication outcome codes.
 *
 * Only these opaque codes cross the boundary to the browser; the frontend maps
 * them to headings, guidance and next steps.  Raw provider errors, exception
 * messages and eligibility details are never sent to the client.
 */
final class AuthOutcome
{
    public const CANCELLED = 'cancelled';
    public const EXPIRED_REQUEST = 'expired_request';
    public const ELIGIBILITY_DENIED = 'eligibility_denied';
    public const PROVIDER_UNAVAILABLE = 'provider_unavailable';
    public const SIGNIN_UNAVAILABLE = 'signin_unavailable';
    public const SIGNIN_REQUIRED = 'signin_required';
    public const SIGNED_IN = 'signed_in';
    public const SIGNED_OUT = 'signed_out';
}
