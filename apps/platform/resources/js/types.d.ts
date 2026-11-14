declare module '@inertiajs/core' {
    interface PageProps {
        auth: {
            user: { name: string; email: string } | null;
            sso_available: boolean;
        };
        flash: {
            auth_outcome: string | null;
        };
    }
}
