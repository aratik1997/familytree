import { execSync } from 'node:child_process';
import { rmSync, existsSync, readdirSync, mkdirSync, copyFileSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Eight builds, one per person.
 *
 * Each portfolio becomes its own subdomain, so each needs a complete,
 * self-contained folder — index.html plus its own assets. A single build with
 * eight entry points would share one /assets directory, which none of the
 * subdomains could reach.
 */
const PEOPLE = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];

if (existsSync('dist')) rmSync('dist', { recursive: true, force: true });

/**
 * Vite copies the whole of public/ into every build, which would put each
 * person's photograph on all eight subdomains — Ashik's face served from
 * maria.khandanilegacy.com. Each build keeps only its own.
 */
function keepOwnPhotoOnly(person) {
  const dir = join('dist', person, 'img');
  if (!existsSync(dir)) return;
  for (const f of readdirSync(dir)) {
    if (f !== `${person}.jpg`) rmSync(join(dir, f), { force: true });
  }
}

let failed = 0;
for (const p of PEOPLE) {
  process.stdout.write(`building ${p.padEnd(9)} `);
  try {
    execSync('npx vite build --logLevel warn', {
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, VITE_PERSON: p },
    });
    keepOwnPhotoOnly(p);
    console.log('ok');
  } catch (e) {
    failed++;
    console.log('FAILED');
    console.log(String(e.stdout || '') + String(e.stderr || ''));
  }
}

console.log(failed ? `\n${failed} build(s) failed` : `\nall ${PEOPLE.length} built into dist/`);
process.exit(failed ? 1 : 0);
