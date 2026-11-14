import { Head, Link } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function NotFound() {
    return (
        <Layout>
            <Head title="Page not found" />
            <section className="section-pad" aria-labelledby="not-found-heading">
                <div className="container">
                    <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />404</p>
                    <h1 id="not-found-heading">That page could not be found.</h1>
                    <p className="hero-lede">
                        The page may have moved, or the address may be incorrect. The public pages are still available.
                    </p>
                    <div className="hero-actions">
                        <Link className="button button-primary" href="/">Go home</Link>
                        <Link className="text-link" href="/community">
                            View the community <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                </div>
            </section>
        </Layout>
    );
}
