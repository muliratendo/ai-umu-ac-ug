import { render, screen } from '@testing-library/react';
import axe from 'axe-core';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mockUsePage = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, as = 'a', method, href, ...props }) => {
        const Element = as === 'button' ? 'button' : 'a';
        return <Element {...props} href={as === 'button' ? undefined : href} data-inertia-link="" data-inertia-href={href} data-inertia-method={method}>{children}</Element>;
    },
    usePage: () => mockUsePage(),
}));

import Account from '../../resources/js/Pages/Account';
import Community from '../../resources/js/Pages/Community';
import Home from '../../resources/js/Pages/Home';
import Layout from '../../resources/js/Components/Layout';
import NotFound from '../../resources/js/Pages/NotFound';
import RecoveryNotice from '../../resources/js/Components/RecoveryNotice';

const noAuth = { auth: { user: null, sso_available: false }, flash: {} };
const ssoAvailable = { auth: { user: null, sso_available: true }, flash: {} };
const signedIn = {
    auth: { user: { name: 'Invited User', email: 'invited@example.test' }, sso_available: true },
    flash: {},
};

describe('public pages', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        mockUsePage.mockReturnValue({ props: noAuth });
    });

    it('renders all community units and named navigation links', () => {
        render(<Community />);

        expect(screen.getByRole('heading', { name: /four connected community units/i })).toBeVisible();
        expect(screen.getByText('AI Club')).toBeVisible();
        expect(screen.getByText('AI Research Community')).toBeVisible();
        expect(screen.getByText('AI Policy Committee')).toBeVisible();
        expect(screen.getByText(/AI Division under the university IT department/i)).toBeVisible();
        expect(screen.getByRole('link', { name: 'UMU AI Community home' })).toHaveAttribute('href', '/');
        expect(screen.getByRole('link', { name: 'Community' })).toHaveAttribute('href', '/community');
    });

    it('provides a skip-link target and hides unavailable sign-in UI', () => {
        render(<Home />);

        expect(screen.getByRole('link', { name: 'Skip to main content' })).toHaveAttribute('href', '#main-content');
        expect(document.querySelector('#main-content')).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: /sign in|join|chat/i })).not.toBeInTheDocument();
    });

    it('offers a useful next step from the not-found page', () => {
        render(<NotFound />);

        expect(screen.getByRole('heading', { name: /page could not be found/i })).toBeVisible();
        expect(screen.getByRole('link', { name: 'Go home' })).toHaveAttribute('href', '/');
    });

    it('passes an accessibility smoke check for the not-found page', async () => {
        const { container } = render(<NotFound />);
        const results = await axe.run(container);

        expect(results.violations).toEqual([]);
    });

    it('passes an accessibility smoke check for the community page', async () => {
        const { container } = render(<Community />);
        const results = await axe.run(container);

        expect(results.violations).toEqual([]);
    });
});

describe('authentication UI states', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('shows a sign-in link when SSO is available', () => {
        mockUsePage.mockReturnValue({ props: ssoAvailable, url: '/' });
        render(<Layout><span /></Layout>);

        const signIn = screen.getByRole('link', { name: /sign in with google/i });
        expect(signIn).toHaveAttribute('href', '/auth/google');
        expect(signIn).not.toHaveAttribute('data-inertia-link');
    });

    it('hides the sign-in link when SSO is unavailable', () => {
        mockUsePage.mockReturnValue({ props: noAuth, url: '/' });
        render(<Layout><span /></Layout>);

        expect(screen.queryByRole('link', { name: /sign in with google/i })).not.toBeInTheDocument();
    });

    it('shows account link and sign-out button when a user is signed in', () => {
        mockUsePage.mockReturnValue({ props: signedIn, url: '/' });
        render(<Layout><span /></Layout>);

        expect(screen.getByRole('link', { name: 'Account' })).toHaveAttribute('href', '/account');
        expect(screen.getByRole('link', { name: 'Account' })).toHaveAttribute('data-inertia-link');
        const signOut = screen.getByRole('button', { name: /sign out/i });
        expect(signOut).toHaveAttribute('data-inertia-method', 'post');
        expect(signOut).toHaveAttribute('data-inertia-href', '/logout');
    });

    it('maps safe flash outcomes to one alert or status announcement', () => {
        mockUsePage.mockReturnValue({
            url: '/',
            props: {
                auth: { user: null, sso_available: false },
                flash: { auth_outcome: 'expired_request' },
            },
        });
        render(<Layout><span /></Layout>);

        expect(screen.getByRole('alert')).toHaveTextContent('That sign-in request expired');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    it('does not disclose whether a specific email is in approvals', () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: null, sso_available: true },
                flash: { auth_outcome: 'eligibility_denied' },
            },
            url: '/',
        });
        render(<Layout><span /></Layout>);

        const alert = screen.getByRole('alert');
        expect(alert).toHaveTextContent('This Google account cannot be used to sign in');
        expect(alert).not.toHaveTextContent('invited@example.test');
        expect(alert).not.toHaveTextContent('@example.test');
        expect(alert).toHaveTextContent('contact the person who approved it');
        expect(screen.getByRole('link', { name: 'Try a different Google account' }))
            .not.toHaveAttribute('data-inertia-link');
        expect(screen.getByRole('link', { name: 'Return home' })).toHaveAttribute('href', '/');
        expect(screen.getByRole('link', { name: 'View the community' })).toHaveAttribute('href', '/community');
    });

    it('does not offer a retry while SSO is unavailable and keeps public recovery links', () => {
        render(<RecoveryNotice code="signin_unavailable" ssoAvailable={false} />);

        expect(screen.getByRole('alert')).toHaveTextContent('temporarily unavailable');
        expect(screen.queryByRole('link', { name: /try|sign in|retry/i })).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Return home' })).toHaveAttribute('href', '/');
        expect(screen.getByRole('link', { name: 'View the community' })).toHaveAttribute('href', '/community');
    });

    it('uses a status announcement for successful sign-in', () => {
        render(<RecoveryNotice code="signed_in" ssoAvailable />);

        expect(screen.getByRole('status')).toHaveTextContent('You are signed in');
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('moves focus to the main content after an internal Inertia navigation', () => {
        mockUsePage.mockReturnValue({ props: noAuth, url: '/' });
        const { rerender } = render(<Layout><span /></Layout>);

        mockUsePage.mockReturnValue({ props: noAuth, url: '/community' });
        rerender(<Layout><span /></Layout>);

        expect(document.activeElement).toBe(document.getElementById('main-content'));
    });
});

describe('account page', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('displays the signed-in identity without privileged badges', () => {
        mockUsePage.mockReturnValue({ props: signedIn });
        render(<Account />);

        expect(screen.getByRole('heading', { name: /your umu ai community account/i })).toBeVisible();
        expect(screen.getByText('Invited User')).toBeVisible();
        expect(screen.getByText('invited@example.test')).toBeVisible();
        expect(screen.queryByText(/membership|staff|admin|editor/i)).not.toBeNull();
        expect(screen.queryByText(/badge/i)).not.toBeInTheDocument();
    });

    it('passes an accessibility smoke check when signed in', async () => {
        mockUsePage.mockReturnValue({ props: signedIn });
        const { container } = render(<Account />);
        const results = await axe.run(container);

        expect(results.violations).toEqual([]);
    });
});
