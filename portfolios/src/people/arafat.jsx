import { useRef } from 'react';
import { motion, useInView, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, FactBand, Chips, useT } from '../lib/shell.jsx';

/* ═══ ARAFAT · the smallholding ═══════════════════════════════════════════
   Nothing on this page is a straight line if it can help it. The portrait is
   a blob that keeps changing shape, a sprout draws itself out of the ground,
   and the year is laid out as four seasons rather than as a list of jobs.
   ══════════════════════════════════════════════════════════════════════════ */

function Leaves() {
  const still = useReducedMotion();
  if (still) return null;
  const fall = [
    { left: '9%', d: 16, delay: 0, g: '🌿' }, { left: '31%', d: 21, delay: -6, g: '🍃' },
    { left: '56%', d: 18, delay: -12, g: '🌿' }, { left: '79%', d: 23, delay: -3, g: '🍃' },
  ];
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {fall.map((l, i) => (
        <motion.span
          key={i} className="absolute text-base" style={{ left: l.left, top: '-6%' }}
          animate={{ y: ['0vh', '112vh'], x: [0, 90], rotate: [0, 420], opacity: [0, 0.45, 0] }}
          transition={{ duration: l.d, delay: l.delay, repeat: Infinity, ease: 'linear' }}
        >{l.g}</motion.span>
      ))}
    </div>
  );
}

function Sprout() {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true });
  const still = useReducedMotion();
  const paths = [
    { d: 'M30 78V26', delay: 0 },
    { d: 'M30 44c-14 0-20-9-20-16 9 0 20 5 20 16z', delay: 0.7 },
    { d: 'M30 34c12 0 18-8 18-14-8 0-18 4-18 14z', delay: 1 },
  ];
  return (
    <svg ref={ref} viewBox="0 0 60 80" className="mt-7 w-24 text-primary" fill="none" stroke="currentColor"
      strokeWidth="3.2" strokeLinecap="round" aria-hidden>
      {paths.map((p) => (
        <motion.path key={p.d} d={p.d}
          strokeDasharray={180} strokeDashoffset={still ? 0 : 180}
          animate={seen || still ? { strokeDashoffset: 0 } : {}}
          transition={{ duration: 1.4, delay: p.delay, ease: [0.4, 0, 0.2, 1] }}
        />
      ))}
    </svg>
  );
}

export default function Arafat({ person }) {
  const T = useT();
  const still = useReducedMotion();

  return (
    <div className="grain min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Nav brand={T(person.name)} />
      <Leaves />
      <div className="pointer-events-none fixed inset-0 z-0 opacity-60"
        style={{ background: 'radial-gradient(900px 400px at 10% 0%, color-mix(in oklab, var(--color-accent) 14%, transparent), transparent 60%), radial-gradient(700px 340px at 100% 100%, color-mix(in oklab, var(--color-primary) 16%, transparent), transparent 60%)' }}
        aria-hidden />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[1fr_auto] lg:gap-16">
        <div>
          <Reveal>
            <span className="badge badge-primary badge-lg font-semibold">{T(person.role)}</span>
          </Reveal>
          <h1 className="font-display font-bold leading-[1.02] tracking-tight [font-size:clamp(1.9rem,7vw,3.6rem)] text-balance break-words">
            <Words text={T(person.name)} />
          </h1>
          <Reveal delay={0.35}>
            <p className="mt-5 max-w-lg leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>
          <Sprout />
        </div>

        <motion.div
          className="mx-auto w-[min(70vw,286px)]"
          initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
        >
          <motion.div
            className="overflow-hidden border-4 border-base-100 shadow-2xl"
            animate={still ? {} : {
              borderRadius: [
                '58% 42% 55% 45% / 48% 52% 48% 52%',
                '44% 56% 42% 58% / 56% 44% 56% 44%',
                '58% 42% 55% 45% / 48% 52% 48% 52%',
              ],
            }}
            transition={{ duration: 15, repeat: Infinity, ease: 'easeInOut' }}
          >
            <Photo slug={person.slug} alt={T(person.name)} glyph="🌱" className="aspect-square" />
          </motion.div>
        </motion.div>
      </header>

      <FactBand items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Homesteader', bn: 'হোমস্টেডার' }) },
        { k: T({ en: 'Co-founded', bn: 'সহ-প্রতিষ্ঠা' }), v: 'Bikku Bikku', sub: T({ en: 'Baked from scratch', bn: 'একেবারে শুরু থেকে বানানো' }) },
        { k: T({ en: 'Schooled', bn: 'বিদ্যালয়' }), v: 'Mastermind School', sub: 'Yell International' },
        { k: T({ en: 'Languages', bn: 'ভাষা' }), v: T({ en: 'Four', bn: 'চারটি' }), sub: T({ en: 'including French', bn: 'ফরাসিসহ' }) },
      ]} />


      <Section kicker={T({ en: 'In his words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <blockquote className="relative rounded-box border border-base-content/10 bg-base-200/60 p-8 shadow-lg sm:p-12"
            style={{ borderBottomLeftRadius: '0.35rem' }}>
            <motion.span className="absolute -top-5 right-8 text-3xl"
              animate={still ? {} : { rotate: [-9, 9, -9] }}
              transition={{ duration: 5, repeat: Infinity, ease: 'easeInOut' }} aria-hidden>🌾</motion.span>
            <p className="text-xl leading-relaxed sm:text-2xl">{T(person.speech)}</p>
            <footer className="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      {/* the year, as four seasons rather than a list of tasks */}
      <Section kicker={T({ en: 'The year', bn: 'বছরটি' })} title={T({ en: 'How the work goes round', bn: 'কাজ যেভাবে ঘুরে চলে' })}>
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {person.seasons.map((s, i) => (
            <Reveal key={s.label.en} delay={i * 0.1}>
              <motion.div
                whileHover={{ y: -8, rotate: -1 }}
                transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                className="h-full rounded-box border border-base-content/10 bg-base-200/50 p-7 shadow-md"
              >
                <motion.span className="block text-4xl"
                  animate={still ? {} : { y: [0, -5, 0] }}
                  transition={{ duration: 3.5 + i * 0.4, repeat: Infinity, ease: 'easeInOut' }}
                >{s.icon}</motion.span>
                <h3 className="mt-4 font-display text-xl font-black">{T(s.label)}</h3>
                <p className="mt-1 text-sm opacity-70">{T(s.note)}</p>
              </motion.div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Work', bn: 'কাজ' })} title={T({ en: 'Land, and a small oven', bn: 'জমি, আর ছোট এক ওভেন' })}>
        <div className="grid gap-5 sm:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.09}>
              <div className="card h-full border border-base-content/10 bg-base-200/50 shadow-md">
                <div className="card-body">
                  <span className="text-3xl">{r.icon}</span>
                  <h3 className="card-title text-lg">{T(r.title)}</h3>
                  <p className="text-sm font-semibold opacity-55">{T(r.org)}</p>
                  {r.note && <p className="text-sm opacity-75">{T(r.note)}</p>}
                </div>
              </div>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.25}>
          <motion.a
            href="https://www.facebook.com/bikkubikkucookies/" target="_blank" rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-3 rounded-full border border-base-content/25 bg-base-200/60 px-6 py-3 font-semibold shadow-md"
            whileHover={{ y: -4, rotate: -2 }}
          >
            <motion.span className="text-2xl" animate={still ? {} : { rotate: [-9, 9, -9] }}
              transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }} aria-hidden>🍪</motion.span>
            {T({ en: 'Bikku Bikku · freshly baked', bn: 'বিক্কু বিক্কু · তাজা বেক করা' })}
          </motion.a>
        </Reveal>
      </Section>

      {/* ── focus ── */}
      <Section kicker={T({ en: 'Focus', bn: 'কাজের ক্ষেত্র' })} title={T({ en: 'What the work involves', bn: 'কাজে যা যা থাকে' })}>
        <Chips items={person.focus.map(T)} />
      </Section>

      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Schooling', bn: 'পড়াশোনা' })}>
        <div className="grid gap-5 sm:grid-cols-2">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.09}>
              <div className="h-full rounded-box border border-base-content/10 bg-base-200/50 p-7">
                <span className="text-2xl">{['📘', '🌍'][i]}</span>
                <h3 className="mt-3 font-display text-lg font-bold">{e.school}</h3>
                <p className="mt-1 text-sm opacity-60">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Four tongues', bn: 'চারটি ভাষা' })}>
        <div className="grid max-w-2xl gap-6">
          {person.languages.map((l, i) => (
            <LevelBar key={l.name.en} name={T(l.name)} level={T(l.level)} value={l.v} delay={i * 0.06} />
          ))}
        </div>
      </Section>

      <Footer name={T(person.name)} role={T(person.role)} links={person.links} />
    </div>
  );
}

Arafat.defaultTheme = 'light';
