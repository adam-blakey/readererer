// Takes the admin guide's screenshots from a running site seeded by docs/sample-data.php.
// Usage: node docs/admin-guide/screenshots.mjs [http://127.0.0.1:8000]
import { openGuideBrowser } from '../guide-browser.mjs';

const { browser, page, go, mark, shot, shotOf, login } = await openGuideBrowser(new URL('./screenshots/', import.meta.url).pathname);

// The area from a settings heading to the next one.
const section = (heading) => page.evaluate((heading) => {
    const all = [...document.querySelectorAll('form h2')];
    const i = all.findIndex((h) => h.textContent.trim() === heading);
    const y = all[i].getBoundingClientRect().top + scrollY - 16;
    const end = all[i + 1] ? all[i + 1].getBoundingClientRect().top + scrollY - 16 : all[i].closest('.card').getBoundingClientRect().bottom + scrollY;
    return { y, height: end - y };
}, heading);

await login('alex');

await go('/dashboard');
await mark([['a.nav-link[href$="/settings"]', '1', 'bottom'], ['a.nav-link[href$="/feature-flags"]', '2', 'bottom'], ['a[href$="/settings"] .card', '3', 'top']]);
await shot('dashboard', { y: 0, height: 620 });

// Settings
await go('/settings');
await mark([['.card-body.btn-list', '1', 'left']]);
await shot('settings-top', { y: 130, height: 640 });
await shot('settings-polls', { x: 60, width: 600, ...(await section('Attendance polls')) });
await shot('settings-reminders', { x: 60, width: 600, ...(await section('Reminders')) });
await page.locator('a[href$="/settings/import"]').scrollIntoViewIfNeeded();
await mark([['a.btn[href$="/settings/import"]', '1', 'top'], ['a.btn[href$="/settings/monitoring"]', '2', 'top']]);
const buttonsTop = await page.evaluate(() => document.querySelector('a.btn[href$="/settings/import"]').getBoundingClientRect().top + scrollY);
await shot('settings-bottom', { y: buttonsTop - 260, height: 340 });

// Ensembles
await go('/ensembles');
await mark([['a[href$="/ensembles/create"]', '1', 'right']]);
await shot('ensembles', { y: 130, height: 330 });
await go('/ensembles/1/edit');
await shot('ensemble-edit', { y: 130, height: 760 });
await go('/ensembles/1/seating-plan');
await shot('seating-plan', { y: 130, height: 720 });

// Setup groups
await go('/setup-groups/1/edit');
await shot('setup-group-edit', { y: 130, height: 700 });

// A date's emails
await go('/attendance/register');
const dateHref = await page.evaluate(() => [...document.querySelectorAll('a[href*="/term-dates/"]')].map((a) => a.getAttribute('href')).pop());
await go(dateHref);
await shotOf('send-emails', '.card:has(.card-title:text-is("Send emails"))');
await go('/notifications');
await mark([['form[action*="resend"] button, a[href*="resend"]', '1', 'left']]);
await shot('notifications', { y: 130, height: 520 });

// A member's page
await go('/users/3');
await mark([['a[href$="/edit"]', '1', 'bottom'], ['form[action*="password-reset-link"] button', '2', 'bottom'], ['a.btn[href$="/password"]', '3', 'bottom'], ['[data-bs-target="#modal-erase-user"]', '4', 'bottom']]);
await shot('user', { y: 130, height: 340 });

// Imports and monitoring
await go('/settings/import');
await shot('import', { y: 130, height: 420 });
await go('/settings/monitoring');
await shot('monitoring', { y: 130, height: 760 });

await browser.close();
