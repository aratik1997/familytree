import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

/**
 * Reads the text the browser actually painted and looks for the wreckage of a
 * bad \u escape: a lone Greek letter from the U+1F00–U+1FFF block, which is
 * what \u1F950 and friends decay into. Nothing on these pages is in Greek, so
 * a single hit is a broken emoji.
 */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];
const b = await chromium.launch();
let bad = 0;

for (const p of PEOPLE) {
  const page = await b.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto(pathToFileURL(resolve(`dist/${p}/index.html`)).href);
  await page.waitForTimeout(1200);

  const found = await page.evaluate(() => {
    const text = document.body.innerText;
    const greek = [...new Set(text.match(/[\u1F00-\u1FFF]/g) || [])];
    const emoji = [...new Set(text.match(/\p{Extended_Pictographic}/gu) || [])];
    return { greek, emoji };
  });

  const ok = found.greek.length === 0;
  if (!ok) bad++;
  console.log(`${p.padEnd(9)} ${ok ? 'PASS' : 'FAIL'}  broken:${found.greek.join('') || 'none'}  emoji rendered: ${found.emoji.slice(0, 12).join(' ')}`);
  await page.close();
}

await b.close();
console.log(bad ? `\n${bad} page(s) still showing broken characters` : '\nno broken characters on any page');
process.exit(bad ? 1 : 0);
