import {defineConfig} from '@playwright/test';
const base = new URL(process.env.AUTH_BASE_URL || 'http://localhost:8000');
if (base.protocol !== 'http:' || !['localhost', '127.0.0.1', '[::1]'].includes(base.hostname) || base.username || base.password) {
    throw new Error('Local loopback HTTP only. This runner must not target production.');
}
if (!['enabled', 'disabled'].includes(process.env.AUTH_EXPECT_SSO || '') || !process.env.AUTH_BUILD_IDENTITY) {
    throw new Error('Set AUTH_EXPECT_SSO and AUTH_BUILD_IDENTITY explicitly.');
}
export default defineConfig({
    testDir: './tests', workers: 1, retries: 0, reporter: [['list']],
    use: {baseURL: base.origin, browserName: 'chromium', serviceWorkers: 'block',
        screenshot: 'off', video: 'off', trace: 'off'},
    projects: [
        {name: 'desktop', use: {viewport: {width: 1440, height: 1000}}},
        {name: 'mobile', use: {viewport: {width: 390, height: 844}}},
    ],
});
