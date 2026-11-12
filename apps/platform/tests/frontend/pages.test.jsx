import { render, screen } from '@testing-library/react';
import axe from 'axe-core';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mockUsePage = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    usePage: () => mockUsePage(),
}));

import Account from '../../resources/js/Pages/Account';
import Community from '../../resources/js/Pages/Community';
import Home from '../../resources/js/Pages/Home';
import Layout from '../../resources/js/Components/Layout';
import NotFound from '../../resources/js/Pages/NotFound';

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
        mockUsePage.mockReturnValue({ props: ssoAvailable });
        render(<Layout><span /></Layout>);

        expect(screen.getByRole('link', { name: /sign in with google/i })).toHaveAttribute('href', '/auth/google');
    });

    it('hides the sign-in link when SSO is unavailable', () => {
        mockUsePage.mockReturnValue({ props: noAuth });
        render(<Layout><span /></Layout>);

        expect(screen.queryByRole('link', { name: /sign in with google/i })).not.toBeInTheDocument();
    });

    it('shows account link and sign-out button when a user is signed in', () => {
        mockUsePage.mockReturnValue({ props: signedIn });
        render(<Layout><span /></Layout>);

        expect(screen.getByRole('link', { name: 'Account' })).toHaveAttribute('href', '/account');
        expect(screen.getByRole('link', { name: /sign out/i })).toHaveAttribute('href', '/logout');
    });

    it('displays flash error and success notices with correct roles', () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: null, sso_available: false },
                flash: { auth_error: 'That sign-in request expired.', auth_success: 'You are signed out.' },
            },
        });
        render(<Layout><span /></Layout>);

        expect(screen.getByRole('alert')).toHaveTextContent('That sign-in request expired.');
        expect(screen.getByRole('status')).toHaveTextContent('You are signed out.');
    });

    it('does not disclose whether a specific email is in approvals', () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: null, sso_available: true },
                flash: { auth_error: 'This account is not approved for access.', auth_success: null },
            },
        });
        render(<Layout><span /></Layout>);

        const alert = screen.getByRole('alert');
        expect(alert).toHaveTextContent('not approved for access');
        expect(alert).not.toHaveTextContent('invited@example.test');
        expect(alert).not.toHaveTextContent('@example.test');
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
