import { Head, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Account() {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <Layout>
            <Head title="Account" />
            <section className="container content-section" aria-labelledby="account-heading">
                <p className="eyebrow">Basic account</p>
                <h1 id="account-heading">Your UMU AI Community account</h1>
                <p>
                    You are signed in as <strong>{user?.name}</strong>
                    {' '}(<span>{user?.email}</span>).
                </p>
                <p>Authentication does not grant membership, staff, editor, or administrator access.</p>
            </section>
        </Layout>
    );
}
