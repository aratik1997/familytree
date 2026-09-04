import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

/**
 * Opens every built page the way a person actually does — by double-clicking
 * it. This is the check that was missing: the HTTP verification passed while
 * the same files were blank from disk, because a browser will not load an ES
 * module over file://.
 */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const browser = await chromium.launch();
let bad = 0;

for (const p of PEOPLE) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const errs = [];
  page.on('console', (m) => { if (m.type() === 'error' && !(m.location()?.url || '').includes('/img/')) errs.push(m.text()); });
  page.on('pageerror', (e) => errs.push(String(e)));

  await page.goto(pathToFileURL(resolve(`dist/${p}/index.html`)).href);
  await page.waitForTimeout(1500);

  const kids = await page.evaluate(() => document.querySelector('#root')?.children.length || 0);
  const h1 = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ').trim();

  // The switches must work from disk too — localStorage throws on some
  // file:// origins, and the handlers have to survive that.
  let switched = false;
  try {
    const before = await page.getAttribute('html', 'data-theme');
    await page.getByLabel('Switch theme').click();
    await page.waitForTimeout(300);
    switched = (await page.getAttribute('html', 'data-theme')) !== before;
  } catch { /* reported below */ }

  const ok = kids > 0 && switched && errs.length === 0;
  if (!ok) bad++;
  console.log(`${p.padEnd(9)} ${ok ? 'PASS' : 'FAIL'}  root:${kids}  theme-switch:${switched ? 'y' : 'n'}  errors:${errs.length}  h1:"${h1}"`);
  errs.slice(0, 2).forEach((e) => console.log(`   ! ${e.slice(0, 150)}`));
  await page.close();
}

await browser.close();
console.log(bad ? `\n${bad} blank or broken from disk` : '\nall 8 work when opened straight from disk');
process.exit(bad ? 1 : 0);
