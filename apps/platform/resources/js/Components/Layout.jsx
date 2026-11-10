import { Link } from '@inertiajs/react';

/** @param {{children: import('react').ReactNode}} props */
export default function Layout({ children }) {
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
                    </nav>
                </div>
            </header>
            <main id="main-content" tabIndex={-1}>{children}</main>
            <footer className="site-footer">
                <div className="container">
                    <p>AI learning, research, policy, and responsible innovation at Uganda Martyrs University.</p>
                </div>
            </footer>
        </div>
    );
}
