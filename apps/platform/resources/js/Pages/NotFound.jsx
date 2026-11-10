import { Head, Link } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function NotFound() {
    return (
        <Layout>
            <Head title="Page not found" />
            <section className="container content-section" aria-labelledby="not-found-heading">
                <p className="eyebrow">404</p>
                <h1 id="not-found-heading">That page could not be found.</h1>
                <p>Return to the public home page to continue.</p>
                <Link className="button" href="/">Go home</Link>
            </section>
        </Layout>
    );
}
