import { execSync } from 'node:child_process';
import { rmSync, existsSync } from 'node:fs';

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

let failed = 0;
for (const p of PEOPLE) {
  process.stdout.write(`building ${p.padEnd(9)} `);
  try {
    execSync('npx vite build --logLevel warn', {
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, VITE_PERSON: p },
    });
    console.log('ok');
  } catch (e) {
    failed++;
    console.log('FAILED');
    console.log(String(e.stdout || '') + String(e.stderr || ''));
  }
}

console.log(failed ? `\n${failed} build(s) failed` : `\nall ${PEOPLE.length} built into dist/`);
process.exit(failed ? 1 : 0);
