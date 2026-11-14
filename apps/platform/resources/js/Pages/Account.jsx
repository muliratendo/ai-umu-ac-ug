import { Head, Link, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Account() {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <Layout>
            <Head title="Account" />
            <section className="section-pad" aria-labelledby="account-heading">
                <div className="container">
                    <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Basic account</p>
                    <h1 id="account-heading">Your UMU AI Community account</h1>
                    <div className="split-layout">
                        <div className="panel">
                            <dl className="identity-list">
                                <dt>Signed in as</dt>
                                <dd>{user?.name}</dd>
                                <dt>Google account</dt>
                                <dd>{user?.email}</dd>
                            </dl>
                            <div className="account-actions">
                                <Link href="/logout" method="post" as="button" className="button button-secondary">
                                    Sign out
                                </Link>
                                <Link className="text-link" href="/community">
                                    View the community <span aria-hidden="true">→</span>
                                </Link>
                            </div>
                        </div>
                        <div>
                            <h2>What this access includes</h2>
                            <p>
                                You can reach this basic account page and the public pages. Signing in does not grant
                                membership, staff, editor, or administrator access.
                            </p>
                            <ul className="account-links">
                                <li><Link className="text-link" href="/">Home</Link></li>
                                <li><Link className="text-link" href="/community">Community</Link></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </Layout>
    );
}
