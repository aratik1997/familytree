import { writeFileSync, readFileSync } from 'node:fs';
import { PEOPLE } from '../src/data.js';

/**
 * Writes what each page says out of the box to the Laravel app's
 * resources/portfolios/, for the owner's editor to open its form with.
 *
 * It lands there rather than here because the Laravel app is what reads it and
 * the portfolios source tree is not deployed alongside it — a file left here
 * would simply be missing on the live server.
 *
 * Exported rather than retyped in PHP: data.js is where the pages actually get
 * their words, and a second copy would be free to drift from it. Run as part
 * of the build so the two can never be out of step.
 */
const FIELDS = ['name', 'called', 'role', 'profession', 'tagline', 'speech'];

const out = {};
for (const [slug, p] of Object.entries(PEOPLE)) {
  const fields = {};
  for (const f of FIELDS) fields[f] = p[f] ?? { en: '', bn: '' };

  out[slug] = {
    fields,
    lists: {
      roles: (p.roles ?? []).map((r) => ({
        icon: r.icon ?? '•',
        title: r.title ?? { en: '', bn: '' },
        org: typeof r.org === 'string' ? { en: r.org, bn: r.org } : (r.org ?? { en: '', bn: '' }),
        note: r.note ?? { en: '', bn: '' },
      })),
      education: (p.education ?? []).map((e) => ({
        school: e.school ?? '',
        where: e.where ?? { en: '', bn: '' },
      })),
      languages: (p.languages ?? []).map((l) => ({
        name: l.name ?? { en: '', bn: '' },
        level: l.level ?? { en: '', bn: '' },
        v: l.v ?? 0,
      })),
      focus: (p.focus ?? []).map((f) => (typeof f === 'string' ? { en: f, bn: f } : f)),
    },
  };
}

writeFileSync('src/defaults.json', JSON.stringify(out, null, 2) + '\n');
console.log(`exported defaults for ${Object.keys(out).length} people`);

/**
 * The section manifest is written by hand, so check it still describes the
 * pages. A section added to a page and not listed here would be one the owner
 * cannot reorder or hide, and one they were never told about — the build
 * should stop rather than ship an editor that quietly omits it.
 */
const manifest = JSON.parse(readFileSync('../resources/portfolios/sections.json', 'utf8'));
const problems = [];

for (const slug of Object.keys(PEOPLE)) {
  const source = readFileSync(`src/people/${slug}.jsx`, 'utf8');
  const onPage = [...source.matchAll(/<Section sectionKey="([^"]+)"/g)].map((m) => m[1]);
  const listed = (manifest[slug] ?? []).map((s) => s.key);

  if (onPage.join('|') !== listed.join('|')) {
    problems.push(`  ${slug}
    page:     ${onPage.join(' → ') || '(none)'}
    manifest: ${listed.join(' → ') || '(none)'}`);
  }
}

if (problems.length) {
  console.error(['resources/portfolios/sections.json no longer matches the pages:', ...problems].join('\n'));
  process.exit(1);
}

console.log('section manifest matches all 8 pages');
