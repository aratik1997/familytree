import { chromium } from 'playwright';
import { createServer } from 'node:http';
import { readFile, mkdir } from 'node:fs/promises';
import { existsSync, statSync } from 'node:fs';
import { extname, join, normalize } from 'node:path';

/**
 * Loads every built portfolio in a real browser and reports what it finds.
 *
 * A build that succeeds only proves the bundle was written; it says nothing
 * about whether React got as far as painting. This opens each page, watches
 * the console, exercises both switches, and takes a picture — which is the
 * only way to know the pages actually work.
 */

const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const TYPES = { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css',
                '.svg': 'image/svg+xml', '.jpg': 'image/jpeg', '.png': 'image/png' };

function serve(root, port) {
  const server = createServer(async (req, res) => {
    const url = decodeURIComponent(req.url.split('?')[0]);
    let file = normalize(join(root, url === '/' ? '/index.html' : url));
    if (!file.startsWith(normalize(root))) { res.writeHead(403).end(); return; }
    if (existsSync(file) && statSync(file).isDirectory()) file = join(file, 'index.html');
    if (!existsSync(file)) { res.writeHead(404).end('not found'); return; }
    res.writeHead(200, { 'content-type': TYPES[extname(file)] || 'application/octet-stream' });
    res.end(await readFile(file));
  });
  return new Promise((ok) => server.listen(port, () => ok(server)));
}

await mkdir('shots', { recursive: true });
const browser = await chromium.launch();
let problems = 0;

for (const p of PEOPLE) {
  const server = await serve(`dist/${p}`, 8123);
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

  // A missing portrait is not a fault: the 404 is what tells the page to draw
  // its placeholder instead. Everything else counts.
  const errors = [];
  // The console text for a failed request carries no URL, so the location is
  // what has to be checked — otherwise the deliberate portrait 404 is counted.
  const fromImg = (m) => (m.location()?.url || '').includes('/img/');
  page.on('console', (m) => {
    if (m.type() !== 'error') return;
    if (fromImg(m)) return;
    if (/Failed to load resource/.test(m.text())) return; // the response hook reports these, with a URL
    errors.push(m.text());
  });
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('response', (r) => {
    if (r.status() >= 400 && !r.url().includes('/img/')) errors.push(`${r.status()} ${r.url()}`);
  });

  await page.goto('http://127.0.0.1:8123/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1400);

  // Did React actually render anything?
  const painted = await page.evaluate(() => document.querySelector('#root')?.children.length > 0);
  const h1 = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ').trim();
  const bars = await page.locator('.h-2.w-full').count();

  // Exercise the two switches and confirm the document reacts.
  const beforeTheme = await page.getAttribute('html', 'data-theme');
  await page.getByLabel('Switch theme').click();
  await page.waitForTimeout(320);
  const afterTheme = await page.getAttribute('html', 'data-theme');

  await page.getByLabel('Switch language').click();
  await page.waitForTimeout(420);
  const lang = await page.getAttribute('html', 'lang');
  const h1bn = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ').trim();

  // Put both switches back before the picture, so what is captured is the
  // page as a visitor first meets it rather than as the test left it.
  await page.getByLabel('Switch language').click();
  await page.getByLabel('Switch theme').click();
  await page.waitForTimeout(700);
  await page.screenshot({ path: `shots/${p}.png`, fullPage: false });
  await page.screenshot({ path: `shots/${p}-full.png`, fullPage: true });

  const themeOk = beforeTheme !== afterTheme;
  const langOk = lang === 'bn' && h1bn !== h1;
  const ok = painted && themeOk && langOk && errors.length === 0;
  if (!ok) problems++;

  console.log(
    `${p.padEnd(9)} ${ok ? 'PASS' : 'FAIL'}  painted:${painted ? 'y' : 'n'}` +
    `  theme:${beforeTheme}->${afterTheme}` +
    `  lang:${langOk ? 'bn ok' : 'NO'}  bars:${bars}  errors:${errors.length}  h1:"${h1}"`
  );
  if (errors.length) errors.slice(0, 3).forEach((e) => console.log(`   ! ${e.slice(0, 160)}`));

  await page.close();
  server.close();
}

await browser.close();
console.log(problems ? `\n${problems} page(s) with problems` : '\nall 8 render, switch theme and switch language');
process.exit(problems ? 1 : 0);
