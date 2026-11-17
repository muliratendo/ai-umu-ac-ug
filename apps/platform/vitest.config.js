import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['tests/frontend/**/*.{test,spec}.{js,jsx,ts,tsx,mjs,mts}'],
        environment: 'jsdom',
        setupFiles: './tests/frontend/setup.js',
    },
});
