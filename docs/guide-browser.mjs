// Shared helpers for the guides' screenshot scripts.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

export async function openGuideBrowser(outDir) {
    const base = process.argv[2] ?? 'http://127.0.0.1:8000';
    const out = outDir.endsWith('/') ? outDir : outDir + '/';
    mkdirSync(out, { recursive: true });

    const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH });
    const context = await browser.newContext({ viewport: { width: 1280, height: 800 }, deviceScaleFactor: 2, locale: 'en-GB', timezoneId: 'Europe/London' });
    const page = await context.newPage();

    async function go(path) {
        await page.goto(path.startsWith('http') ? path : base + path);
        await page.waitForLoadState('networkidle');
    }

    // Numbered markers next to elements, so the text can refer to them.
    async function mark(markers) {
        const missing = await page.evaluate((markers) => {
            const missing = [];
            document.querySelectorAll('.guide-mark').forEach((el) => el.remove());
            for (const [selector, label, side = 'left'] of markers) {
                const target = typeof selector === 'string' ? document.querySelector(selector) : null;
                if (!target) {
                    missing.push(selector);
                    continue;
                }
                const r = target.getBoundingClientRect();
                const ring = document.createElement('div');
                ring.className = 'guide-mark';
                Object.assign(ring.style, { position: 'absolute', left: r.left + scrollX - 4 + 'px', top: r.top + scrollY - 4 + 'px', width: r.width + 8 + 'px', height: r.height + 8 + 'px', border: '3px solid #1d7a2c', borderRadius: '8px', zIndex: 9998, pointerEvents: 'none' });
                const dot = document.createElement('div');
                dot.className = 'guide-mark';
                dot.textContent = label;
                const x = side === 'right' ? r.right + scrollX + 8 : side === 'top' || side === 'bottom' ? r.left + scrollX + r.width / 2 - 14 : r.left + scrollX - 38;
                const y = side === 'top' ? r.top + scrollY - 40 : side === 'bottom' ? r.bottom + scrollY + 12 : r.top + scrollY + r.height / 2 - 14;
                Object.assign(dot.style, { position: 'absolute', left: x + 'px', top: y + 'px', width: '28px', height: '28px', borderRadius: '50%', background: '#1d7a2c', color: '#fff', font: '700 16px Roboto, Arial, sans-serif', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999, boxShadow: '0 1px 3px rgba(0,0,0,.4)' });
                document.body.append(ring, dot);
            }
            return missing;
        }, markers);
        if (missing.length) console.warn('Missing markers:', missing.join(', '));
    }

    async function shot(name, clip) {
        await page.mouse.move(0, 0);
        await page.evaluate(() => scrollTo(0, 0));
        const box = clip && { x: 0, width: page.viewportSize().width, ...clip };
        await page.screenshot({ path: out + name + '.png', fullPage: !!box || undefined, clip: box });
        await page.evaluate(() => document.querySelectorAll('.guide-mark').forEach((el) => el.remove()));
        console.log(name);
    }

    async function shotOf(name, selector, pad = 10) {
        await page.evaluate(() => scrollTo(0, 0));
        const box = await page.locator(selector).first().boundingBox();
        await page.screenshot({ path: out + name + '.png', fullPage: true, clip: { x: box.x - pad, y: box.y - pad, width: box.width + 2 * pad, height: box.height + 2 * pad } });
        console.log(name);
    }

    async function login(username) {
        await context.clearCookies();
        await go('/login');
        await page.fill('#username', username);
        await page.fill('#password', 'password');
        await page.click('button.btn-primary[type=submit]');
        await page.waitForLoadState('networkidle');
    }

    const top = (selector) => page.evaluate((s) => document.querySelector(s).getBoundingClientRect().top + scrollY, selector);

    return { browser, context, page, out, go, mark, shot, shotOf, login, top };
}
