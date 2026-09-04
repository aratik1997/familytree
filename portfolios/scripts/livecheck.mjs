import { chromium } from 'playwright';

/** Loads the eight live URLs in a real browser and proves each one works. */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const BASE = process.env.BASE || 'https://khandanilegacy.com/p';

const b = await chromium.launch();
let bad = 0;

for (const p of PEOPLE) {
  const page = await b.newPage({ viewport: { width: 1280, height: 900 } });
  const errs = [];
  page.on('pageerror', (e) => errs.push(String(e)));
  page.on('console', (m) => {
    if (m.type() === 'error' && !(m.location()?.url || '').includes('/img/')) errs.push(m.text());
  });

  let status = 0;
  try {
    const r = await page.goto(`${BASE}/${p}/`, { waitUntil: 'domcontentloaded', timeout: 45000 });
    status = r?.status() ?? 0;
    await page.waitForTimeout(2200);
  } catch (e) { errs.push(String(e).slice(0, 90)); }

  const painted = await page.evaluate(() => document.querySelector('#root')?.children.length || 0).catch(() => 0);
  const h1 = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ').trim();

  let langOk = false;
  try {
    await page.getByLabel('Switch language').click();
    await page.waitForTimeout(700);
    langOk = (await page.getAttribute('html', 'lang')) === 'bn';
  } catch { /* reported */ }

  const ok = status === 200 && painted > 0 && langOk && errs.length === 0;
  if (!ok) bad++;
  console.log(`${p.padEnd(9)} ${ok ? 'PASS' : 'FAIL'}  http:${status}  painted:${painted}  bangla:${langOk ? 'y' : 'n'}  errors:${errs.length}  h1:"${h1.slice(0, 32)}"`);
  errs.slice(0, 2).forEach((e) => console.log('   ! ' + e.slice(0, 120)));
  await page.close();
}

await b.close();
console.log(bad ? `\n${bad} live page(s) with problems` : '\nall 8 live pages render and switch language');
process.exit(bad ? 1 : 0);
