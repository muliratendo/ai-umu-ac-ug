declare module '@inertiajs/core' {
    interface PageProps {
        auth: {
            user: { name: string; email: string } | null;
            sso_available: boolean;
        };
        flash: {
            auth_error: string | null;
            auth_success: string | null;
        };
    }
}
