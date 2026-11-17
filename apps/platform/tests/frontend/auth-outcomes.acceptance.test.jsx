import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import RecoveryNotice from '../../resources/js/Components/RecoveryNotice';

afterEach(cleanup);
const errors = ['cancelled', 'expired_request', 'eligibility_denied', 'provider_unavailable', 'signin_unavailable', 'signin_required'];
const successes = ['signed_in', 'signed_out'];

describe('recovery acceptance contracts', () => {
    it.each([...errors, ...successes])('renders one meaningful announcement for %s', (code) => {
        render(<RecoveryNotice code={code} ssoAvailable />);
        const role = errors.includes(code) ? 'alert' : 'status';
        expect(screen.getAllByRole(role)).toHaveLength(1);
        expect(screen.getByRole(role).textContent.trim().length).toBeGreaterThan(25);
        expect(screen.queryByRole(role === 'alert' ? 'status' : 'alert')).not.toBeInTheDocument();
        expect(screen.queryByText(/acceptance-secret|access_token|client_secret|stack trace/i)).not.toBeInTheDocument();
    });
    it.each(errors)('never offers a provider link when unavailable: %s', (code) => {
        const { container } = render(<RecoveryNotice code={code} ssoAvailable={false} />);
        expect(container.querySelector('a[href="/auth/google"]')).toBeNull();
        expect(screen.getByRole('link', { name: 'Return home' })).toHaveAttribute('href', '/');
        expect(screen.getByRole('link', { name: 'View the community' })).toHaveAttribute('href', '/community');
    });
    it('does not render arbitrary outcome text supplied as a code', () => {
        const { container } = render(<RecoveryNotice code={'<script>unsafe</script>'} ssoAvailable />);
        expect(container).toBeEmptyDOMElement();
    });
});
