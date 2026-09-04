import { motion, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, FactBand, useT } from '../lib/shell.jsx';

/* ═══ MORSHEDA · the clinic ═══════════════════════════════════════════════
   Bright, rounded, nothing sharp anywhere — a room you are not frightened of.
   Laid out as a bento of soft tiles rather than rows, with Hokkaido petals
   falling past, because that is where the doctorate is.
   ══════════════════════════════════════════════════════════════════════════ */

function Petals() {
  const still = useReducedMotion();
  if (still) return null;
  const drops = [
    { left: '8%', d: 13, delay: 0 }, { left: '24%', d: 17, delay: -4 },
    { left: '47%', d: 15, delay: -9 }, { left: '68%', d: 19, delay: -2 },
    { left: '86%', d: 14, delay: -11 },
  ];
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {drops.map((p, i) => (
        <motion.span
          key={i}
          className="absolute block h-3 w-3 bg-secondary"
          style={{ left: p.left, top: '-6%', borderRadius: '60% 0 60% 0' }}
          animate={{ y: ['0vh', '112vh'], x: [0, 70], rotate: [0, 560], opacity: [0, 0.35, 0] }}
          transition={{ duration: p.d, delay: p.delay, repeat: Infinity, ease: 'linear' }}
        />
      ))}
    </div>
  );
}

export default function Morsheda({ person }) {
  const T = useT();

  return (
    <div className="min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Nav brand={T(person.name)} />
      <Petals />

      {/* soft wash behind the top of the page */}
      <div className="pointer-events-none fixed inset-x-0 top-0 z-0 h-[70vh] bg-gradient-to-b from-primary/12 to-transparent" aria-hidden />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[auto_1fr] lg:gap-16">
        <motion.div
          className="relative mx-auto w-[min(70vw,300px)]"
          initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
        >
          <Photo slug={person.slug} alt={T(person.name)} glyph="🦷"
            className="aspect-square rounded-[38%] border-4 border-base-100 shadow-2xl" />
          <motion.span
            className="absolute -right-1 -top-2 text-3xl"
            animate={{ scale: [1, 1.25, 1], rotate: [0, 18, 0], opacity: [0.6, 1, 0.6] }}
            transition={{ duration: 3.4, repeat: Infinity, ease: 'easeInOut' }}
            aria-hidden
          >✨</motion.span>
        </motion.div>

        <div>
          <Reveal>
            <span className="badge badge-primary badge-lg font-semibold">{T(person.role)}</span>
          </Reveal>
          <h1 className="mt-5 font-display text-5xl font-bold leading-[1.02] sm:text-6xl lg:text-7xl">
            <Words text={T(person.name)} />
          </h1>
          <Reveal delay={0.35}>
            <p className="mt-3 text-xl font-semibold text-primary">{T(person.profession)}</p>
            <p className="mt-4 max-w-lg leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>
        </div>
      </header>

      <FactBand items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Dentist', bn: 'দন্তচিকিৎসক' }) },
        { k: T({ en: 'Doctorate', bn: 'ডক্টরেট' }), v: 'Hokkaido University', sub: T({ en: 'Japan · PhD', bn: 'জাপান · পিএইচডি' }) },
        { k: T({ en: 'Trained at', bn: 'প্রশিক্ষণ' }), v: 'Pioneer Dental College', sub: T({ en: 'Dhaka', bn: 'ঢাকা' }) },
        { k: T({ en: 'Languages', bn: 'ভাষা' }), v: T({ en: 'Five', bn: 'পাঁচটি' }), sub: T({ en: 'including Japanese', bn: 'জাপানিসহ' }) },
      ]} />


      <Section kicker={T({ en: 'In her words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <div className="relative rounded-box border border-base-content/10 bg-base-200/60 p-8 shadow-lg sm:p-12">
            <span className="absolute -top-5 left-8 grid h-11 w-11 place-items-center rounded-full bg-primary font-display text-2xl leading-none text-primary-content" aria-hidden>&ldquo;</span>
            <p className="text-xl leading-relaxed sm:text-2xl">{T(person.speech)}</p>
            <p className="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-primary">— {T(person.name)}</p>
          </div>
        </Reveal>
      </Section>

      {/* bento: what she does, in tiles of different weight */}
      <Section kicker={T({ en: 'Practice', bn: 'পেশা' })} title={T({ en: 'What she does', bn: 'তিনি যা করেন' })}>
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.09} className={i === 0 ? 'sm:col-span-2 lg:col-span-1' : ''}>
              <motion.div
                whileHover={{ y: -7 }} transition={{ type: 'spring', stiffness: 320, damping: 22 }}
                className="card h-full border border-base-content/10 bg-base-200/60 shadow-md"
              >
                <div className="card-body">
                  <span className="text-4xl">{r.icon}</span>
                  <h3 className="card-title text-lg">{T(r.title)}</h3>
                  <p className="text-sm font-semibold opacity-55">{T(r.org)}</p>
                  {r.note && <p className="text-sm opacity-75">{T(r.note)}</p>}
                </div>
              </motion.div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* education as three stops, Dhaka to Sapporo */}
      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Dhaka to Sapporo', bn: 'ঢাকা থেকে সাপ্পোরো' })}>
        <div className="grid gap-5 sm:grid-cols-3">
          {person.education.map((e, i) => (
            <Reveal key={e.school + i} delay={i * 0.1}>
              <div className="relative h-full rounded-box border border-base-content/10 bg-base-200/50 p-7">
                <span className="absolute right-5 top-5 text-2xl opacity-25" aria-hidden>{['🏥', '🎓', '🌸'][i]}</span>
                <p className="font-mono2 text-xs text-primary">0{i + 1}</p>
                <h3 className="mt-2 font-display text-lg font-bold">{e.school}</h3>
                <p className="mt-1 text-sm opacity-60">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Five tongues', bn: 'পাঁচটি ভাষা' })}>
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

Morsheda.defaultTheme = 'light';
