// Prints guide.html to moderator-guide.pdf.
// Usage: node docs/moderator-guide/build.mjs
import { chromium } from 'playwright';

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH });
const page = await browser.newPage();
await page.goto(new URL('./guide.html', import.meta.url).href, { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: new URL('./moderator-guide.pdf', import.meta.url).pathname, preferCSSPageSize: true, printBackground: true });
await browser.close();
