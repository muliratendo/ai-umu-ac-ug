import {test, expect} from '@playwright/test';
import fs from 'node:fs';
import crypto from 'node:crypto';
const identity = JSON.parse(fs.readFileSync(process.env.AUTH_BUILD_IDENTITY, 'utf8'));
const enabled = process.env.AUTH_EXPECT_SSO === 'enabled';
const digest = buffer => crypto.createHash('sha256').update(buffer).digest('hex');

test('served HTML and every manifest output match the exact tested build', async ({page, request, baseURL}) => {
    const errors = [];
    page.on('pageerror', () => errors.push('pageerror'));
    await page.route('**/*', async route => {
        if (new URL(route.request().url()).origin !== new URL(baseURL).origin) return route.abort();
        return route.continue();
    });
    for (const [file, expected] of Object.entries(identity.files)) {
        const response = await request.get('/build/' + file);
        expect(response.status(), 'Missing release output: ' + file).toBe(200);
        expect(digest(await response.body()), 'Wrong release bytes: ' + file).toBe(expected);
        if (file.endsWith('.js')) expect(response.headers()['content-type']).toMatch(/javascript|ecmascript/);
        if (file.endsWith('.css')) expect(response.headers()['content-type']).toContain('text/css');
    }
    const response = await page.goto('/');
    expect(response.status()).toBe(200);
    const scripts = await page.locator('script[src]').evaluateAll(nodes => nodes.map(n => new URL(n.src).pathname));
    expect(scripts).toContain('/build/' + identity.entry);
    expect(scripts.some(src => src.includes('@vite/client'))).toBe(false);
    await expect(page.locator('main')).toBeVisible();
    await expect(page.locator('h1')).toHaveCount(1);
    expect(errors).toEqual([]);
});

test('actual Google control makes document navigation; no provider request leaves browser', async ({page, baseURL}) => {
    let blockedGoogle = false;
    const errors = [];
    page.on('pageerror', () => errors.push('pageerror'));
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin === new URL(baseURL).origin) return route.continue();
        if (url.hostname === 'accounts.google.com') {
            blockedGoogle = true;
            return route.fulfill({status: 200, contentType: 'text/html', body: '<!doctype html><title>Blocked OAuth smoke target</title><h1>OAuth navigation intercepted locally</h1>'});
        }
        return route.abort();
    });
    await page.goto('/');
    const links = page.locator('a[href="/auth/google"]');
    if (!enabled) {await expect(links).toHaveCount(0); return;}
    expect(await links.count()).toBeGreaterThan(0);
    const start = page.waitForRequest(req => new URL(req.url()).pathname === '/auth/google');
    await links.first().click({noWaitAfter: true});
    const req = await start;
    expect(req.isNavigationRequest()).toBe(true);
    expect(req.resourceType()).toBe('document');
    expect(req.headers()['x-inertia']).toBeUndefined();
    await expect.poll(() => blockedGoogle).toBe(true);
    expect(errors).toEqual([]);
});

test('stale Inertia version requests a local reload', async ({ request, baseURL }) => {
    const response = await request.get('/auth/google', {
        headers: {
            'X-Inertia': 'true',
            'X-Inertia-Version': 'release-smoke-intentionally-stale',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html, application/xhtml+xml',
        },
        maxRedirects: 0,
    });

    if (!enabled) {
        expect(response.status()).toBe(404);
        return;
    }

    expect(response.status()).toBe(409);
    expect(response.headers()['location']).toBeUndefined();

    const version = response.headers()['x-inertia-version'];
    expect(typeof version).toBe('string');
    expect(version.length).toBeGreaterThan(0);

    const location = response.headers()['x-inertia-location'];
    expect(typeof location).toBe('string');

    const target = new URL(location, baseURL);
    expect(target.origin).toBe(new URL(baseURL).origin);
    expect(target.pathname).toBe('/auth/google');

    // Do not log cookies, state, or a complete OAuth destination.
});

test('matching Inertia version receives the Google external-location fallback', async ({
    request,
}) => {
    if (!enabled) {
        const response = await request.get('/auth/google', {
            headers: {
                'X-Inertia': 'true',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html, application/xhtml+xml',
            },
            maxRedirects: 0,
        });

        expect(response.status()).toBe(404);
        return;
    }

    // Obtain the server's actual version through the stale-client protocol.
    // Do not calculate or hard-code the adapter's hashing algorithm.
    const probe = await request.get('/auth/google', {
        headers: {
            'X-Inertia': 'true',
            'X-Inertia-Version': 'release-smoke-intentionally-stale',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html, application/xhtml+xml',
        },
        maxRedirects: 0,
    });

    expect(probe.status()).toBe(409);

    const version = probe.headers()['x-inertia-version'];
    expect(typeof version).toBe('string');
    expect(version.length).toBeGreaterThan(0);

    // Now simulate a client running the server's current asset version.
    const response = await request.get('/auth/google', {
        headers: {
            'X-Inertia': 'true',
            'X-Inertia-Version': version,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html, application/xhtml+xml',
        },
        maxRedirects: 0,
    });

    expect(response.status()).toBe(409);
    expect(response.headers()['location']).toBeUndefined();

    const location = response.headers()['x-inertia-location'];
    expect(typeof location).toBe('string');

    const target = new URL(location);
    expect(target.protocol).toBe('https:');
    expect(target.hostname).toBe('accounts.google.com');
    expect(target.searchParams.get('response_type')).toBe('code');
    expect(target.searchParams.has('state')).toBe(true);

    // maxRedirects: 0 prevents following an unexpected ordinary redirect.
    // Never log target.href, OAuth state, or session cookies.
});
