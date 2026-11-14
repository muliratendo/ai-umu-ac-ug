import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import RecoveryNotice from './RecoveryNotice';

/** @param {{children: import('react').ReactNode}} props */
export default function Layout({ children }) {
    const { props, url } = usePage();
    const auth = props?.auth;
    const user = auth?.user;
    const outcome = props?.flash?.auth_outcome ?? null;
    const previousUrl = useRef(url);

    useEffect(() => {
        if (previousUrl.current === url) {
            return;
        }

        previousUrl.current = url;

        // The recovery notice announces itself through its live region, so skip
        // the main-content focus reset to avoid a duplicate announcement.
        if (outcome) {
            return;
        }

        document.getElementById('main-content')?.focus();
    }, [url, outcome]);

    return (
        <div className="site-shell">
            <a className="skip-link" href="#main-content">Skip to main content</a>
            <header className="site-header">
                <div className="container header-inner">
                    <Link href="/" className="brand" aria-label="UMU AI Community home">
                        UMU AI Community
                    </Link>
                    <nav aria-label="Primary navigation">
                        <Link href="/" className="nav-link">Home</Link>
                        <Link href="/community" className="nav-link">Community</Link>
                        {user ? <Link href="/account" className="nav-link">Account</Link> : auth?.sso_available ? <a href="/auth/google" className="nav-link">Sign in with Google</a> : null}
                        {user ? <Link href="/logout" method="post" as="button" className="nav-link nav-button">Sign out</Link> : null}
                    </nav>
                </div>
            </header>
            {outcome ? <RecoveryNotice code={outcome} ssoAvailable={Boolean(auth?.sso_available)} /> : null}
            <main id="main-content" tabIndex={-1}>{children}</main>
            <footer className="site-footer">
                <div className="container">
                    <p>AI learning, research, policy, and responsible innovation at Uganda Martyrs University.</p>
                </div>
            </footer>
        </div>
    );
}
