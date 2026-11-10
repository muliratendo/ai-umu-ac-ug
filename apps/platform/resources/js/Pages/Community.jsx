import { Head } from '@inertiajs/react';
import Layout from '../Components/Layout';

const units = ['AI Club', 'AI Research Community', 'AI Policy Committee', 'AI Division under the university IT department'];

export default function Community() {
    return (
        <Layout>
            <Head title="About the community" />
            <section className="container content-section" aria-labelledby="community-heading">
                <p className="eyebrow">About the community</p>
                <h1 id="community-heading">Four connected community units</h1>
                <ul className="unit-list">
                    {units.map((unit) => <li key={unit}>{unit}</li>)}
                </ul>
            </section>
        </Layout>
    );
}
