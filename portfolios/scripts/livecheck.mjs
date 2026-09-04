import { chromium } from 'playwright';

/**
 * Loads the eight live subdomains in a real browser and proves each works:
 * the page painted, the language switch responds, and nothing errored.
 * A 200 alone says only that Apache answered.
 */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const url = (p) => process.env.BASE ? `${process.env.BASE}/${p}/` : `https://${p}.khandanilegacy.com/`;

const b = await chromium.launch();
let bad = 0;

for (const p of PEOPLE) {
  const page = await b.newPage({ viewport: { width: 1280, height: 900 } });
  const errs = [];
  page.on('pageerror', (e) => errs.push(String(e)));
  page.on('console', (m) => {
    // A missing portrait and a missing data.json are both expected states:
    // one means no photograph yet, the other means nothing published yet.
    const u = m.location()?.url || '';
    if (m.type() === 'error' && !u.includes('/img/') && !u.includes('data.json')) errs.push(m.text());
  });

  let status = 0;
  try {
    const r = await page.goto(url(p), { waitUntil: 'domcontentloaded', timeout: 45000 });
    status = r?.status() ?? 0;
    await page.waitForTimeout(2200);
  } catch (e) { errs.push(String(e).slice(0, 90)); }

  const painted = await page.evaluate(() => document.querySelector('#root')?.children.length || 0).catch(() => 0);
  const h1 = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ').trim();

  let langOk = false, themeOk = false;
  try {
    const t0 = await page.getAttribute('html', 'data-theme');
    await page.getByLabel('Switch theme').click();
    await page.waitForTimeout(500);
    themeOk = (await page.getAttribute('html', 'data-theme')) !== t0;
    await page.getByLabel('Switch language').click();
    await page.waitForTimeout(700);
    langOk = (await page.getAttribute('html', 'lang')) === 'bn';
  } catch { /* reported via ok */ }

  const ok = status === 200 && painted > 0 && langOk && themeOk && errs.length === 0;
  if (!ok) bad++;
  console.log(`${p.padEnd(9)} ${ok ? 'PASS' : 'FAIL'}  http:${status}  painted:${painted}  theme:${themeOk ? 'y' : 'n'}  bangla:${langOk ? 'y' : 'n'}  err:${errs.length}  h1:"${h1.slice(0, 30)}"`);
  errs.slice(0, 2).forEach((e) => console.log('   ! ' + e.slice(0, 120)));
  await page.close();
}

await b.close();
console.log(bad ? `\n${bad} live page(s) with problems` : '\nall 8 subdomains live: rendering, theme and language all working');
process.exit(bad ? 1 : 0);
