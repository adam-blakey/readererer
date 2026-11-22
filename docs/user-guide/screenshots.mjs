// Takes the user guide's screenshots from a running site seeded by docs/sample-data.php.
// Usage: node docs/user-guide/screenshots.mjs [http://127.0.0.1:8000]
import { openGuideBrowser } from '../guide-browser.mjs';

const { browser, context, page, go, mark, shot, shotOf, login, top } = await openGuideBrowser(new URL('./screenshots/', import.meta.url).pathname);
const term = '/attendance/poll/rwo/autumn-' + new Date().getFullYear();

// Logging in
await context.clearCookies();
await go('/login');
await mark([['#username', '1'], ['#password', '2'], ['a[href$="/forgot-password"]', '3', 'right'], ['a[href$="/login/email"]', '4']]);
await shot('login', { y: 0, height: await page.evaluate(() => document.querySelector('.card').getBoundingClientRect().bottom + scrollY + 24) });

await go('/login/email');
await page.fill('#email', 'jamie@example.com');
await mark([['#email', '1'], ['button.btn-primary[type=submit]', '2']]);
await shot('login-link', { y: 0, height: await page.evaluate(() => document.querySelector('.card').getBoundingClientRect().bottom + scrollY + 24) });

// A shared ensemble login
await login('rwo');
await mark([['a[href$="/claim-account"]', '1', 'left']]);
await shot('ensemble-home', { y: 0, height: 560 });
await go('/claim-account');
await mark([['form[action*="/claim-account/"] button', '1', 'left']]);
await shot('claim-account', { y: 130, height: 560 });
await go(term);
await page.setViewportSize({ width: 1500, height: 800 });
await shot('ensemble-poll', { y: 140, height: 560 });
await page.setViewportSize({ width: 1280, height: 800 });

// A member
await login('jamie');
await go('/dashboard');
await mark([['.navbar .dropdown .nav-link', '1']]);
await shot('dashboard', { y: 0, height: 900 });
await page.click('.navbar .dropdown .nav-link');
await shot('user-menu', { y: 0, height: 260 });

await go(term);
await page.setViewportSize({ width: 1500, height: 800 });
await mark([['.form-switch', '1', 'top'], ['.btn-group[aria-label="Sort members by"]', '2', 'top']]);
await shot('poll', { y: 140, height: 600 });
await page.setViewportSize({ width: 1280, height: 800 });

await page.click('.form-switch input');
await page.waitForLoadState('networkidle');
const cell = 'tbody tr:nth-last-child(3) .three-state-checkbox:not([disabled])';
await mark([['.form-switch', '1', 'top'], [cell, '2', 'right']]);
await shot('poll-own', { y: 140, height: 640 });

await page.locator(cell).click();
await page.locator(cell).click();
await page.locator(cell).click();
await page.waitForTimeout(300);
await page.locator('tbody tr:nth-last-child(3) input[type=text]').fill('Robin Dep');
await mark([['tbody tr:nth-last-child(3) input[type=text]', '1', 'left'], ['tbody tr:last-child button[type=submit]', '2', 'left']]);
const ownTop = await top('tbody tr:nth-last-child(5)');
await shot('poll-dep', { x: 560, width: 720, y: ownTop, height: await page.evaluate(() => document.querySelector('tbody tr:last-child').getBoundingClientRect().bottom + scrollY) - ownTop + 16 });
await go(term);
await page.click('.form-switch input');
await page.waitForLoadState('networkidle');

// Profile and password
await page.click('.navbar .dropdown .nav-link');
await page.click('.dropdown-item:has-text("My profile")');
await page.waitForLoadState('networkidle');
await mark([['a.btn[href$="/edit"]', '1', 'left']]);
await shot('profile', { y: 130, height: 560 });
await go('/password');
await shotOf('change-password', 'form.card');

await browser.close();
