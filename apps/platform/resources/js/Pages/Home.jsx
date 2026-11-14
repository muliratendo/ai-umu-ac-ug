import { Head, Link, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Home() {
    const { auth } = usePage().props;
    const ssoAvailable = Boolean(auth?.sso_available);

    return (
        <Layout>
            <Head title="Home" />
            <section className="hero" aria-labelledby="home-heading">
                <div className="container hero-grid">
                    <div className="hero-copy">
                        <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Uganda Martyrs University</p>
                        <h1 id="home-heading">A shared home for <em>responsible AI</em> at UMU.</h1>
                        <p className="hero-lede">
                            The UMU AI Community connects learning, research, policy, and university technology
                            practice across four connected units.
                        </p>
                        <div className="hero-actions">
                            <Link className="button button-primary" href="/community">
                                Explore the community <span aria-hidden="true">→</span>
                            </Link>
                            <a className="text-link" href="#foundation">
                                See what is available now <span aria-hidden="true">↓</span>
                            </a>
                        </div>
                    </div>
                    <aside className="hero-note" aria-label="Community principles">
                        <div className="note-topline"><span>UMU / AI</span><span>Field note 01</span></div>
                        <div className="orbit" aria-hidden="true">
                            <span className="orbit-ring" />
                            <span className="orbit-ring orbit-ring-two" />
                            <span className="orbit-dot orbit-dot-one" />
                            <span className="orbit-dot orbit-dot-two" />
                            <span className="orbit-core">AI</span>
                        </div>
                        <p className="note-kicker">A community space for</p>
                        <ul className="principle-list">
                            <li><span>01</span> Learning</li>
                            <li><span>02</span> Research</li>
                            <li><span>03</span> Policy</li>
                            <li><span>04</span> Responsible technology</li>
                        </ul>
                        <p className="note-foot">Start with curiosity. Build with care.</p>
                    </aside>
                </div>
                <div className="container hero-footer">
                    <span>Students, faculty, and university technology teams</span>
                    <Link href="/community">See the four units <span aria-hidden="true">↘</span></Link>
                </div>
            </section>

            <section className="section-pad section-ink" aria-labelledby="about-heading">
                <div className="container split-layout">
                    <div className="section-heading">
                        <p className="eyebrow eyebrow-light"><span className="eyebrow-line" aria-hidden="true" />01 / About</p>
                        <h2 id="about-heading">Four connected<br /><em>community units.</em></h2>
                    </div>
                    <div className="about-content">
                        <p className="intro-text">
                            The community is an umbrella that connects a student AI Club, a research community, a
                            policy committee, and an AI division under the university IT department.
                        </p>
                        <div className="about-rule" aria-hidden="true" />
                        <div className="about-details">
                            <div>
                                <p className="mini-label">Shared purpose</p>
                                <p>Learning, careful practice, and questions that matter in a university setting.</p>
                            </div>
                            <div>
                                <p className="mini-label">Our responsibility</p>
                                <p>To test ideas honestly, protect people&rsquo;s information, and keep human judgement in the loop.</p>
                            </div>
                        </div>
                        <p style={{ marginTop: '1.5rem' }}>
                            <Link className="text-link" href="/community">
                                Read about each unit <span aria-hidden="true">→</span>
                            </Link>
                        </p>
                    </div>
                </div>
            </section>

            <section className="section-pad" id="foundation" aria-labelledby="foundation-heading">
                <div className="container">
                    <div className="section-intro-row">
                        <div>
                            <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />02 / Foundation status</p>
                            <h2 id="foundation-heading">What is available <em>now.</em></h2>
                        </div>
                        <p className="section-summary">
                            This is an early foundation. Planned features are listed separately and are not available yet.
                        </p>
                    </div>
                    <div className="status-grid">
                        <div className="status-card">
                            <h3>Implemented</h3>
                            <ul className="status-list">
                                <li>Public home and community pages.</li>
                                <li>Invite-only Google sign-in with basic account access.</li>
                                <li>Accessible layout, a branded 404, and reduced-motion support.</li>
                            </ul>
                        </div>
                        <div className="status-card">
                            <h3>Planned, not available yet</h3>
                            <ul className="status-list">
                                <li>Membership and editorial administration.</li>
                                <li>News, events, and newsletters.</li>
                                <li>AI features, uploads, and administrative dashboards.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-pad section-ink" aria-labelledby="involved-heading">
                <div className="container get-involved-grid">
                    <div>
                        <p className="eyebrow eyebrow-light"><span className="eyebrow-line" aria-hidden="true" />03 / Getting involved</p>
                        <h2 id="involved-heading">Bring a question.<br /><em>Make room to learn.</em></h2>
                        <p className="get-involved-lede">
                            Account access is invite-only. An operator approves a Google account before it can sign in,
                            and an approval can be revoked at any time.
                        </p>
                        {ssoAvailable ? (
                            <p>
                                <a className="button button-primary" href="/auth/google">Sign in with Google</a>
                            </p>
                        ) : (
                            <p className="get-involved-lede">
                                Google sign-in is currently turned off on this site, so no sign-in option is shown.
                                Public pages remain available.
                            </p>
                        )}
                    </div>
                    <div className="involvement-panel">
                        <div className="audience-row">
                            <p className="audience-tag">Students</p>
                            <p>Come with an interest, a question, or a willingness to practise with others.</p>
                        </div>
                        <div className="audience-row">
                            <p className="audience-tag">Faculty &amp; collaborators</p>
                            <p>Help frame useful questions and offer context before any tool or prototype is suggested.</p>
                        </div>
                        <div className="access-note">
                            <p className="mini-label">Access guidance</p>
                            <p>
                                If you believe you should have access, contact the person who approved it. No public
                                approval or registration form is active.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-pad prototype-note" aria-labelledby="prototype-heading">
                <div className="container prototype-note-inner">
                    <div>
                        <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Prototype status</p>
                        <h2 id="prototype-heading">An early foundation for the community.</h2>
                    </div>
                    <p>
                        This site is an early foundation for the UMU AI Community. Wording, contacts, and any future
                        collaborations still require confirmation. It is not an official university publication.
                    </p>
                </div>
            </section>
        </Layout>
    );
}
