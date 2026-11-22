// Prints each guide's guide.html to its PDF, e.g. moderator-guide/moderator-guide.pdf.
// Usage: node docs/build-guides.mjs [moderator-guide admin-guide user-guide]
import { chromium } from 'playwright';

const guides = process.argv.length > 2 ? process.argv.slice(2) : ['moderator-guide', 'admin-guide', 'user-guide'];
const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH });
const page = await browser.newPage();
for (const guide of guides) {
    await page.goto(new URL(`./${guide}/guide.html`, import.meta.url).href, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await page.pdf({ path: new URL(`./${guide}/${guide}.pdf`, import.meta.url).pathname, preferCSSPageSize: true, printBackground: true });
    console.log(guide);
}
await browser.close();
