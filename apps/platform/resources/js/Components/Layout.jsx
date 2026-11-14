import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import RecoveryNotice from './RecoveryNotice';

/**
 * Inertia swaps the whole page component on each navigation, so this layout
 * unmounts and remounts between pages. Module scope therefore survives client
 * navigations for the life of the SPA session while resetting on a full document
 * load, which is exactly how we distinguish the initial page load from a
 * client-side transition.
 */
/** @type {string | null} */
let lastRenderedUrl = null;

/** Test seam: reset the module-scoped navigation tracking between cases. */
export function resetNavigationFocus() {
    lastRenderedUrl = null;
}

/** @param {{children: import('react').ReactNode}} props */
export default function Layout({ children }) {
    const { props, url } = usePage();
    const auth = props?.auth;
    const user = auth?.user;
    const ssoAvailable = Boolean(auth?.sso_available);
    const outcome = props?.flash?.auth_outcome ?? null;

    // Read before the effect below updates the module value.
    const isInitialSessionRender = lastRenderedUrl === null;

    useEffect(() => {
        const previousUrl = lastRenderedUrl;
        lastRenderedUrl = url;

        if (outcome) {
            // A live region inserted by a client-side transition announces
            // itself, but content already present at the initial document load
            // is not reliably announced, so move focus to the recovery message
            // only on the first render of the session. Doing it on every render
            // would double-announce the same message.
            if (isInitialSessionRender) {
                document.getElementById('recovery-notice')?.focus();
            }

            return;
        }

        if (previousUrl !== null && previousUrl !== url) {
            document.getElementById('main-content')?.focus();
        }
    }, [url, outcome, isInitialSessionRender]);

    return (
        <div className="site-shell">
            <a className="skip-link" href="#main-content">Skip to main content</a>
            <header className="site-header">
                <div className="container header-inner">
                    <Link href="/" className="brand" aria-label="UMU AI Community home">
                        <span className="brand-mark" aria-hidden="true"><span>U</span><span>A</span></span>
                        <span className="brand-name">UMU AI Community</span>
                    </Link>
                    <nav className="site-nav" aria-label="Primary">
                        <Link href="/" className="nav-link">Home</Link>
                        <Link href="/community" className="nav-link">Community</Link>
                        {user ? (
                            <Link href="/account" className="nav-link nav-cta">Account</Link>
                        ) : ssoAvailable ? (
                            <a href="/auth/google" className="nav-link nav-cta">Sign in with Google</a>
                        ) : null}
                        {user ? (
                            <Link href="/logout" method="post" as="button" className="nav-link nav-button">Sign out</Link>
                        ) : null}
                    </nav>
                </div>
            </header>
            {outcome ? <RecoveryNotice code={outcome} ssoAvailable={ssoAvailable} /> : null}
            <main id="main-content" tabIndex={-1}>{children}</main>
            <footer className="site-footer">
                <div className="container footer-inner">
                    <Link href="/" className="brand brand-footer">
                        <span className="brand-mark" aria-hidden="true"><span>U</span><span>A</span></span>
                        <span className="brand-name">UMU AI Community</span>
                    </Link>
                    <p>An early foundation for the UMU AI Community. Deferred features are marked as not yet available.</p>
                    <a className="back-to-top" href="#main-content">Back to top <span aria-hidden="true">↑</span></a>
                </div>
            </footer>
        </div>
    );
}
