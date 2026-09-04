import { useRef } from 'react';
import { motion, useInView, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, FactBand, Falling, Chips, Steps, useT } from '../lib/shell.jsx';

/* ═══ MAIMUNA · the working paper ═════════════════════════════════════════
   Set like something printed: ruled paper, a serif face, and figures that are
   captioned and numbered. The portrait is Figure 1, the week is Figure 2, and
   the chart grows when you reach it.
   ══════════════════════════════════════════════════════════════════════════ */

function WeekChart({ bars, t }) {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true, margin: '-15% 0px' });
  const still = useReducedMotion();

  return (
    <figure ref={ref} className="mt-8 max-w-lg border border-base-content/12 bg-base-100 p-6 shadow-sm">
      <figcaption className="mb-5 text-[11px] font-semibold uppercase tracking-[0.16em] opacity-55">
        {t({ en: 'Fig. 2 — What the week holds', bn: 'চিত্র ২ — সপ্তাহে যা থাকে' })}
      </figcaption>
      <div className="flex h-32 items-end gap-3">
        {bars.map((b, i) => (
          <div key={b.label.en} className="flex flex-1 flex-col items-center gap-2">
            <motion.div
              className="w-full rounded-t bg-gradient-to-t from-primary to-accent"
              initial={still ? false : { height: 0 }}
              animate={seen || still ? { height: `${b.v}%` } : {}}
              transition={{ duration: 1.1, delay: i * 0.09, ease: [0.22, 1, 0.36, 1] }}
              style={{ minHeight: 4 }}
            />
            <span className="font-mono2 text-[10px] opacity-55">{t(b.label)}</span>
          </div>
        ))}
      </div>
    </figure>
  );
}

export default function Maimuna({ person }) {
  const T = useT();

  return (
    <div className="rule-paper min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Falling items={['0', '1', '\u03A3', '%', '\u00B5']} opacity={0.18} size="text-lg" />
      <Nav brand={<span className="font-display italic">{T(person.name)}</span>} />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[auto_1fr] lg:gap-16">
        <Reveal>
          <div className="mx-auto w-[min(62vw,248px)]">
            <Photo slug={person.slug} alt={T(person.name)} glyph="📊"
              className="aspect-[4/5] border border-base-content/25 shadow-md" />
            <p className="mt-2 text-center text-[11px] font-semibold uppercase tracking-[0.12em] opacity-55">
              {T({ en: 'Figure 1 · The researcher', bn: 'চিত্র ১ · গবেষক' })}
            </p>
          </div>
        </Reveal>

        <div>
          <Reveal>
            <p className="text-xs font-semibold uppercase tracking-[0.26em] text-primary">{T(person.role)}</p>
          </Reveal>
          <h1 className="font-display font-bold leading-[1.02] tracking-tight [font-size:clamp(1.9rem,7vw,3.6rem)] text-balance break-words">
            <Words text={T(person.name)} />
          </h1>
          <Reveal delay={0.35}>
            <p className="mt-3 font-display text-xl italic text-primary sm:text-2xl">{T(person.profession)}</p>
            <p className="mt-4 max-w-xl leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>
          <Reveal delay={0.45}><WeekChart bars={person.week} t={T} /></Reveal>
        </div>
      </header>

      <FactBand items={[
        { k: T({ en: 'Field', bn: 'ক্ষেত্র' }), v: T({ en: 'Economics', bn: 'অর্থনীতি' }), sub: T({ en: 'Research', bn: 'গবেষণা' }) },
        { k: T({ en: 'Degrees', bn: 'ডিগ্রি' }), v: T({ en: 'Three', bn: 'তিনটি' }), sub: T({ en: 'One bachelor, two masters', bn: 'এক স্নাতক, দুই স্নাতকোত্তর' }) },
        { k: T({ en: 'Co-founded', bn: 'সহ-প্রতিষ্ঠা' }), v: 'Bikku Bikku', sub: T({ en: 'Homemade cookies', bn: 'ঘরে বানানো কুকি' }) },
        { k: T({ en: 'University', bn: 'বিশ্ববিদ্যালয়' }), v: 'Independent University', sub: 'Bangladesh' },
      ]} />


      <Section kicker={T({ en: 'In her words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <blockquote className="border border-l-4 border-base-content/12 border-l-primary bg-base-100 p-8 shadow-sm sm:p-12">
            <p className="font-display text-xl italic leading-relaxed sm:text-2xl lg:text-3xl">{T(person.speech)}</p>
            <footer className="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      <Section kicker={T({ en: 'Work', bn: 'কাজ' })} title={T({ en: 'Research, and a bakery', bn: 'গবেষণা, আর এক বেকারি' })}>
        <div className="grid gap-5 sm:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.09}>
              <motion.div whileHover={{ y: -5 }} transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                className="h-full border border-base-content/12 bg-base-100 p-7 shadow-sm">
                <span className="font-mono2 text-xs text-secondary">{String(i + 1).padStart(2, '0')}</span>
                <span className="float-right text-2xl opacity-30">{r.icon}</span>
                <h3 className="mt-3 font-display text-lg font-semibold">{T(r.title)}</h3>
                <p className="mt-1 text-sm opacity-55">{T(r.org)}</p>
                {r.note && <p className="mt-3 text-sm leading-relaxed opacity-75">{T(r.note)}</p>}
              </motion.div>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.25}>
          <motion.a
            href="https://www.facebook.com/bikkubikkucookies/" target="_blank" rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-3 rounded-full border border-base-content/25 px-6 py-3 text-sm transition-colors hover:border-primary"
            whileHover={{ y: -3 }}
          >
            <motion.span className="text-2xl" animate={{ y: [0, -4, 0], rotate: [-6, 6, -6] }}
              transition={{ duration: 3.6, repeat: Infinity, ease: 'easeInOut' }} aria-hidden>🍪</motion.span>
            {T({ en: 'Bikku Bikku · 100% homemade', bn: 'বিক্কু বিক্কু · শতভাগ ঘরে বানানো' })}
          </motion.a>
        </Reveal>
      </Section>

      {/* three degrees from one university — a footnote-ish list */}
      {/* ── how a finding gets made ── */}
      <Section kicker={T({ en: 'Method', bn: 'পদ্ধতি' })} title={T({ en: 'How a finding gets made', bn: 'একটি সিদ্ধান্ত যেভাবে আসে' })}>
        <Steps items={person.method.map((m) => ({ n: m.n, title: T(m.title), note: T(m.note) }))} mono />
      </Section>

      {/* ── focus ── */}
      <Section kicker={T({ en: 'Focus', bn: 'কাজের ক্ষেত্র' })} title={T({ en: 'What she works in', bn: 'যেসব ক্ষেত্রে কাজ করেন' })}>
        <Chips items={person.focus.map(T)} />
      </Section>

      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'One university, three degrees', bn: 'এক বিশ্ববিদ্যালয়, তিন ডিগ্রি' })}>
        <ol className="max-w-3xl border-t border-base-content/15">
          {person.education.map((e, i) => (
            <Reveal key={i} delay={i * 0.08} as="li">
              <div className="flex items-baseline gap-5 border-b border-base-content/15 py-5">
                <span className="font-mono2 text-xs text-primary">[{i + 1}]</span>
                <div>
                  <p className="font-display text-lg font-semibold">{T(e.where)}</p>
                  <p className="text-sm opacity-55">{e.school}</p>
                </div>
              </div>
            </Reveal>
          ))}
        </ol>
      </Section>

      <Section kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Four tongues', bn: 'চারটি ভাষা' })}>
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

Maimuna.defaultTheme = 'light';
