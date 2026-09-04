import { useEffect, useState } from 'react';
import { motion, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, LevelBar, Photo, Section, Footer, FactBand, useShell, useT } from '../lib/shell.jsx';

/* ═══ ATIK · the terminal ═════════════════════════════════════════════════
   Monospace wherever it can be. The hero is a shell that answers `whoami` by
   typing, and the skills are read out as a build log rather than as prose.
   ══════════════════════════════════════════════════════════════════════════ */

const LINES = {
  en: ['Full-Stack Developer', 'Backend Engineer', 'DevOps · Docker · CI', 'System Designer'],
  bn: ['ফুল-স্ট্যাক ডেভেলপার', 'ব্যাকএন্ড ইঞ্জিনিয়ার', 'ডেভঅপস · ডকার · সিআই', 'সিস্টেম ডিজাইনার'],
};

/** Types a line, holds it, deletes it, moves to the next. */
function Typer() {
  const { lang } = useShell();
  const still = useReducedMotion();
  const [text, setText] = useState('');
  const [i, setI] = useState(0);
  const [del, setDel] = useState(false);

  useEffect(() => { setText(''); setI(0); setDel(false); }, [lang]);

  useEffect(() => {
    if (still) { setText(LINES[lang][0]); return; }
    const full = LINES[lang][i % LINES[lang].length];
    const done = !del && text === full;
    const empty = del && text === '';

    const wait = done ? 1500 : empty ? 220 : del ? 32 : 62;
    const id = setTimeout(() => {
      if (done) return setDel(true);
      if (empty) { setDel(false); return setI((n) => n + 1); }
      setText(del ? full.slice(0, text.length - 1) : full.slice(0, text.length + 1));
    }, wait);
    return () => clearTimeout(id);
  }, [text, del, i, lang, still]);

  return (
    <span>
      {text}
      <motion.span
        className="text-primary"
        animate={still ? {} : { opacity: [1, 1, 0, 0] }}
        transition={{ duration: 1.05, repeat: Infinity, times: [0, 0.5, 0.5, 1] }}
      >▋</motion.span>
    </span>
  );
}

function Rain() {
  const still = useReducedMotion();
  if (still) return null;
  // Kept to the outer margins. Run down the middle and the glyphs land under
  // the headline, where they read as a rendering fault rather than as weather.
  const cols = [
    { left: '2%', d: 11, delay: 0, t: '01001100\n10110010\n00111001' },
    { left: '9%', d: 15, delay: -5, t: 'SELECT *\nFROM tree\nWHERE ok' },
    { left: '89%', d: 13, delay: -8, t: 'git push\n--force-\nwith-lease' },
    { left: '96%', d: 17, delay: -3, t: 'docker\ncompose\nup -d' },
  ];
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {cols.map((c, i) => (
        <motion.pre
          key={i}
          className="absolute font-mono2 text-[11px] leading-none text-primary/25"
          style={{ left: c.left, top: '-12%' }}
          animate={{ y: ['0vh', '125vh'] }}
          transition={{ duration: c.d, delay: c.delay, repeat: Infinity, ease: 'linear' }}
        >{c.t}</motion.pre>
      ))}
    </div>
  );
}

export default function Atik({ person }) {
  const T = useT();

  return (
    <div className="ink-grid min-h-screen bg-base-100 text-base-content [--grid:48px]">
      <ScrollBar />
      <Nav brand={<span className="font-mono2"><span className="text-secondary">$</span> atik</span>} />
      <Rain />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[1fr_auto] lg:gap-14">
        <div>
          <Reveal>
            <p className="font-mono2 text-sm text-secondary">// {T(person.role)}</p>
          </Reveal>
          <motion.h1
            className="mt-4 text-5xl font-extrabold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl"
            initial={{ opacity: 0, y: 22 }} animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.15, ease: [0.22, 1, 0.36, 1] }}
          >
            {T(person.name).split(' ')[0]}{' '}
            <span className="bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
              {T(person.name).split(' ').slice(1).join(' ')}
            </span>
          </motion.h1>
          <Reveal delay={0.35}>
            <p className="mt-5 max-w-lg leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>

          <Reveal delay={0.5}>
            <div className="mockup-code mt-8 max-w-xl border border-base-content/12 bg-base-200 text-base-content shadow-xl">
              <pre data-prefix="$" className="text-secondary"><code className="text-base-content">whoami</code></pre>
              <pre data-prefix=">" className="text-primary"><code><Typer /></code></pre>
              <pre data-prefix="→"><code className="opacity-70">php · mysql · js · react · flutter · docker</code></pre>
              <pre data-prefix="→"><code className="opacity-70">Uttara, Dhaka, Bangladesh</code></pre>
            </div>
          </Reveal>
        </div>

        <motion.div
          className="mx-auto w-[min(66vw,250px)]"
          initial={{ opacity: 0, x: 26 }} animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.9, delay: 0.25, ease: [0.22, 1, 0.36, 1] }}
        >
          <Photo slug={person.slug} alt={T(person.name)} glyph="⌨️"
            className="aspect-square rounded-box border border-primary/40 shadow-2xl" />
        </motion.div>
      </header>

      <FactBand mono items={[
        { k: T({ en: 'Role', bn: 'পদ' }), v: 'CEO · 8 Bit Private Ltd' },
        { k: T({ en: 'Focus', bn: 'কেন্দ্র' }), v: T({ en: 'Backend & DevOps', bn: 'ব্যাকএন্ড ও ডেভঅপস' }), sub: 'PHP · MySQL · Docker' },
        { k: T({ en: 'Studied', bn: 'পড়াশোনা' }), v: 'North South University', sub: T({ en: 'BSc Computer Science', bn: 'বিএসসি কম্পিউটার সায়েন্স' }) },
        { k: T({ en: 'Based', bn: 'অবস্থান' }), v: 'Uttara, Dhaka', sub: 'Bangladesh' },
      ]} />


      <Section kicker={T({ en: 'In his words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <div className="relative overflow-hidden rounded-box border border-base-content/12 bg-base-200/50 p-8 sm:p-12">
            <span className="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-primary to-secondary" aria-hidden />
            <p className="text-xl leading-relaxed sm:text-2xl">{T(person.speech)}</p>
            <p className="mt-5 font-mono2 text-sm text-primary">— {T(person.name)}</p>
          </div>
        </Reveal>
      </Section>

      <Section kicker={T({ en: 'Roles', bn: 'দায়িত্ব' })} title={T({ en: 'What he runs', bn: 'যা তিনি চালান' })}>
        <div className="grid gap-4 sm:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.08}>
              <motion.div whileHover={{ y: -5 }} transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                className="card h-full border border-base-content/12 bg-base-200/40">
                <div className="card-body">
                  <span className="text-3xl">{r.icon}</span>
                  <h3 className="card-title text-lg text-primary">{T(r.title)}</h3>
                  <p className="font-mono2 text-xs opacity-60">{T(r.org)}</p>
                  {r.note && <p className="text-sm opacity-75">{T(r.note)}</p>}
                </div>
              </motion.div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Stack', bn: 'প্রযুক্তি' })} title={T({ en: 'Tools of the trade', bn: 'কাজের হাতিয়ার' })}>
        <div className="grid max-w-3xl gap-5">
          {person.skills.map((s, i) => (
            <LevelBar key={s.label} name={s.label} level={String(s.v)} value={s.v} delay={i * 0.05} mono />
          ))}
        </div>
        <Reveal delay={0.2} className="mt-8 flex flex-wrap gap-2">
          {person.stack.map((s) => (
            <span key={s} className="badge badge-outline badge-lg font-mono2 text-xs">{s}</span>
          ))}
        </Reveal>
      </Section>

      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Madrasa to CSE', bn: 'মাদ্রাসা থেকে সিএসই' })}>
        <div className="grid gap-4 sm:grid-cols-3">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.09}>
              <div className="h-full rounded-box border border-base-content/12 bg-base-200/40 p-6">
                <p className="font-mono2 text-xs text-primary">{String(i + 1).padStart(2, '0')}</p>
                <h3 className="mt-2 font-semibold">{e.school}</h3>
                <p className="mt-1 font-mono2 text-xs opacity-60">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Three tongues', bn: 'তিনটি ভাষা' })}>
        <div className="grid max-w-2xl gap-6">
          {person.languages.map((l, i) => (
            <LevelBar key={l.name.en} name={T(l.name)} level={T(l.level)} value={l.v} delay={i * 0.06} mono />
          ))}
        </div>
      </Section>

      <Footer name={T(person.name)} role={T(person.role)} links={person.links} />
    </div>
  );
}

Atik.defaultTheme = 'dark';
