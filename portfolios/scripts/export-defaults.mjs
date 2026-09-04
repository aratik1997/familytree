import { writeFileSync } from 'node:fs';
import { PEOPLE } from '../src/data.js';

/**
 * Writes what each page says out of the box to src/defaults.json, for the
 * editor in the Laravel app to open its form with.
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
