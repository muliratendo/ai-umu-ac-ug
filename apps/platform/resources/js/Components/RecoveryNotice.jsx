/**
 * Maps safe, server-controlled outcome codes to accessible recovery guidance.
 *
 * The server never sends provider errors, exception text, or eligibility
 * details; only these opaque codes cross the boundary.  Retry is offered as a
 * real full-page anchor to the Google entrypoint, and only when sign-in is
 * actually available.
 */

/** @typedef {{tone: 'error'|'success', heading: string, body: string, retry?: string}} Outcome */

/** @type {Record<string, Outcome>} */
const OUTCOMES = {
    cancelled: {
        tone: 'error',
        heading: 'Sign-in was cancelled',
        body: 'You cancelled Google sign-in or did not finish consent. Nothing was changed.',
        retry: 'Try signing in again',
    },
    expired_request: {
        tone: 'error',
        heading: 'That sign-in request expired',
        body: 'The sign-in request was invalid, expired, or already used. Start a new sign-in to continue.',
        retry: 'Start a new sign-in',
    },
    eligibility_denied: {
        tone: 'error',
        heading: 'Access is not available for this account',
        body: 'This Google account cannot be used to sign in. If you believe you should have access, contact the person who approved it. For privacy, we do not confirm whether a particular email is invited, revoked, or already registered. A fresh Google sign-in can let you choose a different account.',
        retry: 'Try a different Google account',
    },
    provider_unavailable: {
        tone: 'error',
        heading: 'Sign-in is temporarily unavailable',
        body: 'We could not reach Google or complete sign-in just now. Please try again in a moment.',
        retry: 'Try signing in again',
    },
    signin_unavailable: {
        tone: 'error',
        heading: 'Google sign-in is currently turned off',
        body: 'Google sign-in is temporarily unavailable on this site, so protected account pages cannot be reached. Public pages remain available.',
    },
    signin_required: {
        tone: 'error',
        heading: 'Please sign in to continue',
        body: 'That page needs a signed-in account with approved access. If you believe you should have access, contact the person who approved it.',
        retry: 'Sign in with Google',
    },
    signed_in: {
        tone: 'success',
        heading: 'You are signed in',
        body: 'You have basic account access. This does not grant membership, staff, editor, or administrator access.',
    },
    signed_out: {
        tone: 'success',
        heading: 'You are signed out',
        body: 'You have been signed out of this site. Your Google account is not affected.',
    },
};

/**
 * @param {{code: string | null, ssoAvailable?: boolean}} props
 */
export default function RecoveryNotice({ code, ssoAvailable = false }) {
    const outcome = code ? OUTCOMES[code] : undefined;

    if (!outcome) {
        return null;
    }

    const isError = outcome.tone === 'error';

    return (
        <div className={`container recovery-notice recovery-${outcome.tone}`}>
            <div className="recovery-body" role={isError ? 'alert' : 'status'}>
                <h2 className="recovery-heading">{outcome.heading}</h2>
                <p>{outcome.body}</p>
                {outcome.retry && !ssoAvailable ? (
                    <p className="recovery-hint">Sign-in is unavailable right now, so retrying is not offered.</p>
                ) : null}
            </div>
            <ul className="recovery-actions">
                {outcome.retry && ssoAvailable ? (
                    <li>
                        <a className="button" href="/auth/google">{outcome.retry}</a>
                    </li>
                ) : null}
                <li>
                    <a href="/">Return home</a>
                </li>
                <li>
                    <a href="/community">View the community</a>
                </li>
            </ul>
        </div>
    );
}
