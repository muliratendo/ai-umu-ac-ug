import { Head, Link } from '@inertiajs/react';
import Layout from '../Components/Layout';

/**
 * Unit descriptions are provisional wording for the design refresh. The unit
 * names are the established content; the short descriptions were added so the
 * numbered cards carry meaning and are marked as awaiting confirmation.
 */
const UNITS = [
    {
        name: 'AI Club',
        description: 'The student-facing group where members learn and practise AI together.',
    },
    {
        name: 'AI Research Community',
        description: 'A space for research questions, methods, and sharing what members find.',
    },
    {
        name: 'AI Policy Committee',
        description: 'A forum for responsible-use and policy questions that affect the university.',
    },
    {
        name: 'AI Division under the university IT department',
        description: 'The university IT division associated with the community.',
    },
];

/**
 * Exploratory directions only. These are not approved projects, partnerships,
 * institutional departments, or commitments.
 */
const DIRECTIONS = [
    {
        key: 'agriculture',
        symbol: '✳',
        name: 'Agriculture',
        description: 'Questions around data, planning, and decision support in agricultural contexts.',
    },
    {
        key: 'law',
        symbol: '§',
        name: 'Law',
        description: 'Exploring responsible use, legal information, and the questions AI raises for practice.',
    },
    {
        key: 'business',
        symbol: '↗',
        name: 'Business',
        description: 'Looking at workflows, analysis, and how people can use AI tools with sound judgement.',
    },
    {
        key: 'science',
        symbol: '⌁',
        name: 'Science',
        description: 'Practising careful use of data, evidence, and computational methods across disciplines.',
    },
    {
        key: 'built',
        symbol: '⌂',
        name: 'Built environment',
        description: 'Considering how AI might support planning, design, and the places people use every day.',
    },
];

export default function Community() {
    return (
        <Layout>
            <Head title="About the community" />

            <section className="section-pad" aria-labelledby="community-heading">
                <div className="container">
                    <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />About the community</p>
                    <h1 id="community-heading">Four connected community units</h1>
                    <p className="hero-lede">
                        The UMU AI Community is an umbrella, not a single group. The student club is one part of it,
                        alongside research, policy, and university technology practice.
                    </p>
                    <p className="section-summary">
                        Short descriptions below are provisional while the community is being established.
                    </p>
                </div>
            </section>

            <section className="section-pad section-paper" aria-labelledby="units-heading">
                <div className="container">
                    <div className="section-intro-row">
                        <div>
                            <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />01 / The units</p>
                            <h2 id="units-heading">What the community <em>covers.</em></h2>
                        </div>
                    </div>
                    <div className="unit-grid">
                        {UNITS.map((unit, index) => (
                            <article className="unit-card" key={unit.name}>
                                <p className="card-number">{String(index + 1).padStart(2, '0')}</p>
                                <h3>{unit.name}</h3>
                                <p>{unit.description}</p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="section-pad" aria-labelledby="directions-heading">
                <div className="container">
                    <div className="collab-header">
                        <div>
                            <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />02 / Possible directions</p>
                            <h2 id="directions-heading">Where could we<br /><em>learn together?</em></h2>
                        </div>
                        <div className="explore-callout">
                            <span className="callout-mark" aria-hidden="true">!</span>
                            <p>
                                <strong>Exploratory only.</strong> These are areas to explore &mdash; not approved
                                projects, partnerships, or commitments.
                            </p>
                        </div>
                    </div>
                    <div className="area-grid">
                        {DIRECTIONS.map((direction, index) => (
                            <article className={`area-card area-${direction.key}`} key={direction.key}>
                                <span className="area-index">{String(index + 1).padStart(2, '0')}</span>
                                <span className="area-symbol" aria-hidden="true">{direction.symbol}</span>
                                <h3>{direction.name}</h3>
                                <p>{direction.description}</p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="section-pad prototype-note" aria-labelledby="community-note-heading">
                <div className="container prototype-note-inner">
                    <div>
                        <p className="eyebrow"><span className="eyebrow-line" aria-hidden="true" />Community status</p>
                        <h2 id="community-note-heading">Public pages, invite-only access.</h2>
                    </div>
                    <p>
                        Home and community pages are public. Account access is invite-only and managed by an operator.
                        No registration, newsletter, or membership form is active. Access questions should go to the
                        person who approved access. <Link className="text-link" href="/">Return to the home page</Link>.
                    </p>
                </div>
            </section>
        </Layout>
    );
}
