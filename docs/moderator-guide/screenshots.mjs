// Takes the guide's screenshots from a running site seeded by sample-data.php.
// Usage: node docs/moderator-guide/screenshots.mjs [http://127.0.0.1:8000]
import { openGuideBrowser } from '../guide-browser.mjs';

const { browser, context, page, out, go, mark, shot, shotOf, login } = await openGuideBrowser(new URL('./screenshots/', import.meta.url).pathname);

const nextRehearsal = async () => page.evaluate(() => [...document.querySelectorAll('a[href*="/attendance/register/rwo/"]')].map((a) => a.getAttribute('href')).pop());

// Logging in
await context.clearCookies();
await go('/login');
await mark([['#username', '1'], ['#password', '2'], ['a[href$="/forgot-password"]', '3', 'right'], ['a[href$="/login/email"]', '4']]);
await shot('login', { y: 0, height: await page.evaluate(() => document.querySelector('.card').getBoundingClientRect().bottom + scrollY + 24) });

await login('morgan');

// Dashboard
await go('/dashboard');
await mark([['a.nav-link[href$="/attendance/register"]', '1', 'bottom'], ['a.nav-link[href$="/users"]', '2', 'bottom'], ['a.nav-link[href$="/notifications"]', '3', 'bottom'], ['.navbar .dropdown .nav-link', '4'], ['a[href$="/users"] .card', '5', 'top']]);
await shot('dashboard', { y: 0, height: 1000 });

await page.click('.navbar .dropdown .nav-link');
await shot('user-menu', { y: 0, height: 260 });

// Users
await go('/users');
await mark([['a[href$="/users/create"]', '1', 'right'], ['input[name=with_trashed]', '2']]);
await shot('users', { y: 160, height: 500 });

await go('/users/create');
await page.fill('input[name=first_name]', 'Sam');
await page.fill('input[name=last_name]', 'Fletcher');
await page.fill('input[name=username]', 'samfletcher');
await page.fill('input[name=email]', 'sam.fletcher@example.com');
await page.selectOption('select[name=setup_group]', { index: 2 });
await page.fill('input[name=phone_number]', '07700 900123');
await mark([['input[name=first_name]', '1'], ['input[name=username]', '2'], ['input[name=email]', '3'], ['select[name=role]', '4'], ['select[name=setup_group]', '5']]);
await shot('user-create', { y: 160, height: 560 });
await mark([['button.btn-primary[type=submit]', '6']]);
await page.locator('button.btn-primary[type=submit]').scrollIntoViewIfNeeded();
await shot('user-create-submit', { y: await page.evaluate(() => document.querySelector('button.btn-primary[type=submit]').getBoundingClientRect().top + scrollY - 140), height: 220 });
await page.click('button.btn-primary[type=submit]');
await page.waitForLoadState('networkidle');
const newUserUrl = page.url();
await mark([['a[href$="/edit"]', '1']]);
await shot('user-created', { y: 160, height: 520 });

await page.click('a[href$="/edit"]');
await page.waitForLoadState('networkidle');
await page.fill('input[name=phone_number]', '07700 900124');
await page.click('form[data-dirty-check] button[type=submit]');
await page.waitForLoadState('networkidle');
await mark([['#password', '1']]);
await shot('confirm-password', { y: 130, height: 360 });
await page.fill('#password', 'password');
await page.click('button.btn-primary[type=submit]');
await page.waitForLoadState('networkidle');
await go(newUserUrl + '/edit');

const addButton = '[data-bs-target="#modal-add-ensemble-user"]';
await page.locator(addButton).scrollIntoViewIfNeeded();
await mark([[addButton, '1']]);
const addTop = await page.evaluate((s) => document.querySelector(s).closest('.card').getBoundingClientRect().top + scrollY - 20, addButton);
await shot('user-edit-ensembles', { y: addTop, height: 340 });
await page.click(addButton);
await page.waitForSelector('#modal-add-ensemble-user.show');
await page.waitForTimeout(400);
const modal = '#modal-add-ensemble-user';
await page.selectOption(`${modal} select[name=ensemble_id]`, { label: 'Riverside Wind Orchestra' });
await page.selectOption(`${modal} select[name=instrument_family_id]`, { label: 'Flutes' });
const modalBox = await page.locator(`${modal} .modal-content`).boundingBox();
await page.screenshot({ path: out + 'add-ensemble-modal.png', clip: { x: modalBox.x - 10, y: modalBox.y - 10, width: modalBox.width + 20, height: modalBox.height + 20 } });
console.log('add-ensemble-modal');
await page.click(`${modal} button[type=submit]`);
await page.waitForLoadState('networkidle');

// Upcoming rehearsals
await go('/attendance/register');
const registerHref = await nextRehearsal();
await mark([['tbody tr:last-child a[href*="/attendance/poll/"]', '1'], ['tbody tr:last-child a[href*="/attendance/register/"]', '2', 'right'], ['tbody tr:last-child td:first-child a', '3', 'top']]);
await page.evaluate(() => document.querySelector('tbody tr:last-child').scrollIntoView({ block: 'center' }));
const lastRowTop = await page.evaluate(() => document.querySelector('tbody tr:last-child').getBoundingClientRect().top + scrollY);
await shot('upcoming', { y: lastRowTop - 330, height: 420 });

// Poll
await go('/attendance/poll/rwo/autumn-' + new Date().getFullYear());
// Wide enough for the whole term, so the names and the latest dates show together.
await page.setViewportSize({ width: 1500, height: 800 });
await page.evaluate(() => {
    const cells = [...document.querySelectorAll('tbody tr:nth-child(2) .three-state-checkbox:not([disabled])')];
    cells[cells.length - 4].id = 'guide-cell';
});
await mark([['.btn-group[aria-label="Sort members by"]', '1', 'top'], ['.form-switch', '2', 'top'], ['button.dropdown-toggle', '3', 'top'], ['tbody tr:nth-child(2) a[href*="/history/"]', '4'], ['#guide-cell', '5', 'left']]);
await shot('poll', { y: 140, height: 640 });

await page.locator('#guide-cell').click();
await page.locator('button[type=submit]:has-text("Save")').last().scrollIntoViewIfNeeded();
await mark([['button[type=submit]:last-of-type', '1']]);
const saveTop = await page.evaluate(() => [...document.querySelectorAll('button.btn-primary[type=submit]')].pop().getBoundingClientRect().top + scrollY);
await shot('poll-save', { y: saveTop - 260, height: 330 });

await go('/attendance/poll/rwo/autumn-' + new Date().getFullYear());
await page.setViewportSize({ width: 1280, height: 800 });
await page.click('tbody tr:nth-child(2) a[href*="/history/"]');
await page.waitForLoadState('networkidle');
await shot('poll-history', { y: 130, height: 400 });

// Register
await go(registerHref);
await mark([['[data-register-fill]', '1', 'top'], ['[data-register-set="1"]', '2', 'top'], ['tbody tr:first-child .btn-group', '3', 'top'], ['tbody tr:first-child input[type=text], tbody tr:first-child textarea', '4', 'top']]);
await shot('register', { y: 140, height: 560 });
await page.click('[data-register-fill]');
const saveRegister = 'button[type=submit]:has-text("Save register")';
await page.locator(saveRegister).scrollIntoViewIfNeeded();
await mark([['.ms-auto.btn.btn-primary[type=submit]', '1']]);
const regSaveTop = await page.evaluate(() => document.querySelector('.ms-auto.btn.btn-primary[type=submit]').getBoundingClientRect().top + scrollY);
await shot('register-save', { y: regSaveTop - 220, height: 290 });

// A date's page
await go('/attendance/register');
const dateHref = await page.evaluate(() => [...document.querySelectorAll('a[href*="/term-dates/"]')].map((a) => a.getAttribute('href')).pop());
await go(dateHref);
await shot('term-date', { y: 130, height: 720 });

// Setup groups and notifications
await go('/setup-groups');
await shot('setup-groups', { y: 130, height: 520 });
await go('/notifications');
await shot('notifications', { y: 130, height: 620 });

await go('/terms');
await mark([['a[href$="/terms/create"]', '1', 'right'], ['tbody tr:last-child a[href$="/edit"]', '2']]);
await shot('terms', { y: 130, height: 300 });
await page.click('tbody tr:last-child a[href$="/edit"]');
await page.waitForLoadState('networkidle');
await page.click('#add-term-date');
await page.waitForTimeout(300);
const newRow = await page.evaluate(() => {
    const rows = [...document.querySelectorAll('.term-date-row:not([data-index="__INDEX__"])')];
    return rows.pop().getBoundingClientRect().top + scrollY;
});
await page.evaluate((y) => scrollTo(0, y - 300), newRow);
await mark([['button.btn-primary[type=submit]', '1', 'left']]);
await shot('term-edit-top', { y: 130, height: 240 });
await shot('term-add-date', { y: newRow - 16, height: await page.evaluate(() => [...document.querySelectorAll('.term-date-row:not([data-index="__INDEX__"])')].pop().getBoundingClientRect().height + 32) });

// An ensemble's shared login (admin)
await login('alex');
await go('/ensembles/1');
await mark([['#login_username', '1'], ['#login_password', '2'], ['form[action$="/login"] button[type=submit]', '3']]);
await shotOf('ensemble-shared-login', 'form[action$="/login"]', 60);

await browser.close();
console.log('Created user page:', newUserUrl);
