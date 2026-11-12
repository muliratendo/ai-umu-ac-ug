import { Link, usePage } from '@inertiajs/react';

/** @param {{children: import('react').ReactNode}} props */
export default function Layout({ children }) {
    const { auth, flash } = usePage().props;
    const user = auth?.user;

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
                        {user ? <Link href="/account" className="nav-link">Account</Link> : auth?.sso_available ? <Link href="/auth/google" className="nav-link">Sign in with Google</Link> : null}
                        {user ? <Link href="/logout" method="post" as="button" className="nav-link nav-button">Sign out</Link> : null}
                    </nav>
                </div>
            </header>
            {flash?.auth_error ? <div className="container notice notice-error" role="alert">{flash.auth_error}</div> : null}
            {flash?.auth_success ? <div className="container notice notice-success" role="status">{flash.auth_success}</div> : null}
            <main id="main-content" tabIndex={-1}>{children}</main>
            <footer className="site-footer">
                <div className="container">
                    <p>AI learning, research, policy, and responsible innovation at Uganda Martyrs University.</p>
                </div>
            </footer>
        </div>
    );
}