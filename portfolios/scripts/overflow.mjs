import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

/** Only elements that can actually widen the document — fixed ones cannot. */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const b = await chromium.launch();
for (const p of PEOPLE) {
  const page = await b.newPage({ viewport: { width: 375, height: 780 } });
  await page.goto(pathToFileURL(resolve(`dist/${p}/index.html`)).href);
  await page.waitForTimeout(1100);
  const out = await page.evaluate(() => {
    const w = document.documentElement.clientWidth;
    const over = document.documentElement.scrollWidth - w;
    const guilty = [...document.querySelectorAll('body *')]
      .filter((el) => getComputedStyle(el).position !== 'fixed')
      .map((el) => ({ el, r: el.getBoundingClientRect() }))
      .filter(({ r }) => r.width > 0 && (r.right > w + 1 || r.left < -1))
      .slice(0, 4)
      .map(({ el, r }) => `${el.tagName.toLowerCase()}.${(el.className||'').toString().split(' ').slice(0,3).join('.')} w:${Math.round(r.width)} right:${Math.round(r.right)}`);
    return { over, guilty };
  });
  console.log(`${p.padEnd(9)} overflow:${String(out.over).padStart(3)}px  ${out.guilty.join(' | ') || 'clean'}`);
  await page.close();
}
await b.close();
