import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const publicPages = [
  { path: '/', status: 200 },
  { path: '/community', status: 200 },
  { path: '/quality-check-not-found', status: 404 },
];

test.beforeEach(async ({ page }) => {
  page.on('pageerror', () => {
    // Do not put arbitrary browser error text into public evidence.
    throw new Error('BROWSER_UNHANDLED_PAGE_ERROR');
  });
});

for (const entry of publicPages) {
  test(`${entry.path}: response, landmarks and accessibility`, async ({ page }) => {
    const response = await page.goto(entry.path);
    expect(response, 'Expected an HTTP response').not.toBeNull();
    expect(response.status()).toBe(entry.status);

    expect(new URL(page.url()).origin).toBe(
      new URL(process.env.BROWSER_BASE_URL).origin,
    );

    await expect(page.getByRole('main')).toHaveCount(1);
    await expect(page.getByRole('main')).toBeVisible();
    await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);

    const result = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
      .analyze();

    // Keep failure output to rule IDs and counts, not page HTML.
    expect(
      result.violations.map(({ id, impact }) => ({ id, impact })),
    ).toEqual([]);
  });
}

test('community identifies the four units', async ({ page }) => {
  await page.goto('/community');

  for (const unit of [
    'AI Club',
    'AI Research Community',
    'AI Policy Committee',
    'AI Division',
  ]) {
    await expect(page.getByRole('main')).toContainText(unit);
  }
});

test('skip link moves focus to main content', async ({ page }) => {
  await page.goto('/');
  await page.keyboard.press('Tab');

  const skip = page.getByRole('link', { name: /skip.*content/i });
  await expect(skip).toBeFocused();
  await skip.press('Enter');

  await expect(page.getByRole('main')).toBeFocused();
});

test('narrow layout does not create page-wide horizontal overflow', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 800 });
  await page.goto('/community');

  const fits = await page.evaluate(
    () => document.documentElement.scrollWidth <= window.innerWidth + 1,
  );
  expect(fits).toBe(true);
});

test('accessibility detector catches a deliberately inaccessible fixture', async ({ page }) => {
  await page.setContent(
    '<!doctype html><html lang="en"><head><title>Detector test</title></head>' +
    '<body><main><h1>Detector test</h1><button></button></main></body></html>',
  );

  const result = await new AxeBuilder({ page }).analyze();
  expect(result.violations.some(v => v.id === 'button-name')).toBe(true);
});
