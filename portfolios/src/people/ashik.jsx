import { motion, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, Counter, FactBand, Falling, Chips, useT } from '../lib/shell.jsx';

/* ═══ ASHIK · the chamber ═════════════════════════════════════════════════
   Symmetrical and squared off, the way a courtroom is. Everything is centred
   on an axis; the scales tip and settle above the name; the positions are set
   as a formal register rather than as cards.
   ══════════════════════════════════════════════════════════════════════════ */

function Scales() {
  const still = useReducedMotion();
  const beam = still ? {} : { rotate: [-6, 6, -6] };
  const panL = still ? {} : { y: [-9, 9, -9] };
  const panR = still ? {} : { y: [9, -9, 9] };
  const ease = { duration: 7, repeat: Infinity, ease: 'easeInOut' };

  return (
    <motion.svg
      viewBox="0 0 200 120" className="mx-auto mb-8 w-40 text-primary sm:w-52"
      fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" aria-hidden
      initial={{ opacity: 0, y: -14 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.9 }}
    >
      <path d="M100 18v88M74 106h52" />
      <circle cx="100" cy="14" r="4.5" fill="currentColor" stroke="none" />
      <motion.g style={{ originX: '100px', originY: '34px' }} animate={beam} transition={ease}>
        <path d="M40 34h120" />
        <motion.g animate={panL} transition={ease}><path d="M40 34v14M24 48h32l-16 20z" /></motion.g>
        <motion.g animate={panR} transition={ease}><path d="M160 34v14M144 48h32l-16 20z" /></motion.g>
      </motion.g>
    </motion.svg>
  );
}

export default function Ashik({ person }) {
  const T = useT();

  return (
    <div className="grain min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Nav brand={T(person.short)} />

      {/* a brass rule down the centre of the page, behind everything */}
      <div className="pointer-events-none fixed inset-y-0 left-1/2 z-0 w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-primary/20 to-transparent" aria-hidden />

      <header id="top" className="relative z-10 mx-auto w-[92vw] max-w-4xl pb-10 pt-28 text-center sm:pt-32">
        <Falling items={['⚖️', '◆', '📜']} opacity={0.16} />
      <Scales />
        <Reveal>
          <p className="mb-5 text-xs font-semibold uppercase tracking-[0.34em] text-primary">{T(person.role)}</p>
        </Reveal>
        <h1 className="font-display font-bold leading-[1.02] tracking-tight [font-size:clamp(1.9rem,7vw,3.6rem)] text-balance break-words">
          <Words text={T(person.name)} />
        </h1>
        <Reveal delay={0.4}>
          <p className="mt-3 font-display text-xl italic text-primary/80">{T({ en: 'known as', bn: 'পরিচিত' })} {T(person.called)}</p>
          <div className="mx-auto my-7 h-0.5 w-16 bg-primary" />
          <p className="mt-3 font-display text-lg font-semibold text-primary sm:text-xl">{T(person.profession)}</p>
          <p className="mx-auto max-w-2xl text-base leading-relaxed opacity-75 sm:text-lg">{T(person.tagline)}</p>
        </Reveal>

        <Reveal delay={0.55}>
          <div className="relative mx-auto mt-12 w-[min(64vw,250px)]">
            <div className="absolute -bottom-2.5 -right-2.5 left-2.5 top-2.5 border border-primary/25" aria-hidden />
            <Photo
              slug={person.slug} alt={T(person.name)} glyph="⚖️"
              className="relative aspect-[4/5] border border-primary/45"
              imgClass="grayscale-[0.15]"
            />
          </div>
        </Reveal>
      </header>

      <FactBand items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Advocate', bn: 'আইনজীবী' }), sub: T({ en: 'LLB · Juvenile law', bn: 'এলএলবি · কিশোর আইন' }) },
        { k: T({ en: 'Founded', bn: 'প্রতিষ্ঠা' }), v: 'Ayesha Foundation', sub: T({ en: 'Non-profit', bn: 'অলাভজনক' }) },
        { k: T({ en: 'Home', bn: 'নিবাস' }), v: 'Laksam, Cumilla' },
        { k: T({ en: 'Read at', bn: 'পড়েছেন' }), v: 'IIUM Malaysia', sub: T({ en: 'Business Administration', bn: 'ব্যবসায় প্রশাসন' }) },
      ]} />


      <Section kicker={T({ en: 'In his words', bn: 'তাঁর ভাষায়' })} className="max-w-4xl text-center"
        kickerClass="text-center">
        <Reveal>
          <blockquote className="border border-base-content/10 border-t-4 border-t-primary bg-base-200/50 p-8 sm:p-12">
            <p className="font-display text-xl italic leading-relaxed sm:text-2xl lg:text-3xl">{T(person.speech)}</p>
            <footer className="mt-6 text-xs font-semibold uppercase tracking-[0.24em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      {/* the register of directorships — numbered, ruled, formal */}
      <Section kicker={T({ en: 'Positions', bn: 'দায়িত্ব' })} title={T({ en: 'Directorships', bn: 'পরিচালনার দায়িত্ব' })}
        className="max-w-4xl text-center" kickerClass="text-center">
        <div className="mt-2 border-t border-base-content/15 text-left">
          {person.roles.map((r, i) => (
            <Reveal key={r.org} delay={i * 0.07}>
              <div className="group grid grid-cols-[auto_1fr] items-baseline gap-x-5 border-b border-base-content/15 py-5 transition-colors hover:bg-base-200/60 sm:grid-cols-[auto_1fr_auto] sm:gap-x-8 sm:px-4">
                <span className="font-mono2 text-xs text-primary/70">{String(i + 1).padStart(2, '0')}</span>
                <div>
                  <h3 className="font-display text-lg font-bold sm:text-xl">{T(r.title)}</h3>
                  <p className="text-sm opacity-60">{r.org}</p>
                  {r.note && <p className="mt-1 max-w-xl text-sm opacity-70">{T(r.note)}</p>}
                </div>
                <span className="hidden text-2xl opacity-0 transition-opacity group-hover:opacity-100 sm:block">{r.icon}</span>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* three stats, because a practice is easier to grasp as figures */}
      <Section className="max-w-4xl">
        <div className="grid gap-px border border-base-content/15 bg-base-content/15 sm:grid-cols-3">
          {[
            { n: 4, s: '+', l: T({ en: 'Companies led', bn: 'যেসব প্রতিষ্ঠান পরিচালনা করেন' }) },
            { n: 1, s: '', l: T({ en: 'Foundation founded', bn: 'প্রতিষ্ঠিত ফাউন্ডেশন' }) },
            { n: 4, s: '', l: T({ en: 'Institutions studied at', bn: 'যেসব প্রতিষ্ঠানে পড়েছেন' }) },
          ].map((s, i) => (
            <Reveal key={s.l} delay={i * 0.1}>
              <div className="bg-base-100 p-7 text-center">
                <p className="font-display text-4xl font-black text-primary sm:text-5xl">
                  <Counter to={s.n} suffix={s.s} />
                </p>
                <p className="mt-2 text-xs uppercase tracking-[0.16em] opacity-60">{s.l}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Practice', bn: 'পেশা' })} title={T({ en: 'Law, and what it is for', bn: 'আইন, এবং তার উদ্দেশ্য' })}
        className="max-w-5xl text-center" kickerClass="text-center">
        <div className="grid gap-5 text-left sm:grid-cols-3">
          {person.practice.map((p, i) => (
            <Reveal key={p.title.en} delay={i * 0.09}>
              <div className="h-full border border-base-content/12 bg-base-200/40 p-7 transition-colors hover:border-primary/50">
                <h3 className="font-display text-lg font-bold text-primary">{T(p.title)}</h3>
                <p className="mt-2 text-sm leading-relaxed opacity-75">{T(p.note)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ── the wider register of companies ── */}
      <Section kicker={T({ en: 'Companies', bn: 'প্রতিষ্ঠান' })} title={T({ en: 'The wider register', bn: 'বৃহত্তর তালিকা' })}
        className="max-w-5xl text-center" kickerClass="text-center">
        <div className="grid gap-px border border-base-content/15 bg-base-content/15 text-left sm:grid-cols-2">
          {person.companies.map((c, i) => (
            <Reveal key={c.org} delay={i * 0.05}>
              <div className="h-full bg-base-100 p-5 transition-colors hover:bg-base-200">
                <p className="text-xs uppercase tracking-[0.14em] text-primary">{T(c.role)}</p>
                <p className="mt-1.5 font-display text-base font-bold">{c.org}</p>
                <p className="mt-0.5 text-xs opacity-55">{T(c.sector)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ── memberships ── */}
      <Section kicker={T({ en: 'Memberships', bn: 'সদস্যপদ' })} title={T({ en: 'Where he serves', bn: 'যেখানে যুক্ত' })}
        className="max-w-4xl text-center" kickerClass="text-center">
        <div className="grid gap-4 text-left sm:grid-cols-3">
          {person.memberships.map((m, i) => (
            <Reveal key={m.name} delay={i * 0.08}>
              <div className="h-full border border-base-content/12 bg-base-200/40 p-6">
                <span className="text-2xl">{m.icon}</span>
                <h3 className="mt-3 font-display text-base font-bold">{m.name}</h3>
                <p className="mt-1 text-xs opacity-55">{T(m.note)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ── core expertise ── */}
      <Section kicker={T({ en: 'Expertise', bn: 'দক্ষতা' })} title={T({ en: 'Core expertise', bn: 'মূল দক্ষতা' })}
        className="max-w-4xl text-center" kickerClass="text-center">
        <Chips items={person.focus.map(T)} className="justify-center" />
      </Section>

      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Where he read', bn: 'যেখানে পড়েছেন' })}
        className="max-w-4xl text-center" kickerClass="text-center">
        <div className="flex flex-wrap justify-center gap-3">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.07}>
              <div className="border border-base-content/15 px-5 py-3 text-left transition-colors hover:border-primary">
                <p className="text-sm font-semibold">{e.school}</p>
                <p className="text-xs opacity-55">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Four tongues', bn: 'চারটি ভাষা' })}
        className="max-w-3xl text-center" kickerClass="text-center">
        <div className="grid gap-6 text-left">
          {person.languages.map((l, i) => (
            <LevelBar key={l.name.en} name={T(l.name)} level={T(l.level)} value={l.v} delay={i * 0.06} />
          ))}
        </div>
      </Section>

      <Footer name={T(person.name)} role={T(person.role)} links={person.links} />
    </div>
  );
}

Ashik.defaultTheme = 'dark';
