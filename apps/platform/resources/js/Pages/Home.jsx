import { Head } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Home() {
    return (
        <Layout>
            <Head title="Home" />
            <section className="hero container" aria-labelledby="home-heading">
                <p className="eyebrow">Uganda Martyrs University</p>
                <h1 id="home-heading">A shared home for responsible AI at UMU.</h1>
                <p className="lede">The UMU AI Community will connect learning, research, policy, and university technology teams.</p>
                <a className="button" href="/community">Explore the community</a>
            </section>
            <section className="container content-section" aria-labelledby="status-heading">
                <h2 id="status-heading">Foundation status</h2>
                <p>This initial foundation provides public information pages and invite-only basic account access. Membership, editorial administration, and AI capabilities are planned and not available yet.</p>
            </section>
        </Layout>
    );
}