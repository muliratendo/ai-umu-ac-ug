import { render, screen } from '@testing-library/react';
import axe from 'axe-core';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

import Community from '../../resources/js/Pages/Community';
import Home from '../../resources/js/Pages/Home';
import NotFound from '../../resources/js/Pages/NotFound';

describe('public pages', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
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

    it('provides a skip-link target and does not advertise deferred capabilities', () => {
        render(<Home />);

        expect(screen.getByRole('link', { name: 'Skip to main content' })).toHaveAttribute('href', '#main-content');
        expect(document.querySelector('#main-content')).toBeInTheDocument();
        expect(screen.getByText(/planned and not available yet/i)).toBeVisible();
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
