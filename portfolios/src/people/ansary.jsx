import { motion, useScroll, useTransform, useReducedMotion } from 'motion/react';
import { useRef } from 'react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, TiltCard, FactBand, useT } from '../lib/shell.jsx';

/* ═══ ANSARY · the cafe ═══════════════════════════════════════════════════
   Warm, unhurried, serif. The hero is a cup: a round portrait inside a ring
   that turns, with steam rising behind the whole page. Positions run past on
   a slow marquee, the way a menu board does.
   ══════════════════════════════════════════════════════════════════════════ */

function Steam() {
  const still = useReducedMotion();
  if (still) return null;
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {[
        { left: '6%', d: 19, delay: 0 },
        { left: '38%', d: 25, delay: -7 },
        { left: '70%', d: 22, delay: -13 },
      ].map((s, i) => (
        <motion.div
          key={i}
          className="absolute bottom-[-10%] rounded-full blur-[60px]"
          style={{
            left: s.left,
            width: 'min(46vw,320px)',
            height: 'min(46vw,320px)',
            background: 'radial-gradient(circle, var(--color-primary), transparent 70%)',
          }}
          animate={{ y: ['0vh', '-125vh'], scale: [0.7, 1.5], opacity: [0, 0.18, 0] }}
          transition={{ duration: s.d, delay: s.delay, repeat: Infinity, ease: 'linear' }}
        />
      ))}
    </div>
  );
}

export default function Ansary({ person }) {
  const T = useT();
  const heroRef = useRef(null);
  const { scrollYProgress } = useScroll({ target: heroRef, offset: ['start start', 'end start'] });
  const y = useTransform(scrollYProgress, [0, 1], [0, 120]);
  const fade = useTransform(scrollYProgress, [0, 0.8], [1, 0]);

  return (
    <div className="grain min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Nav brand={T(person.name)} />
      <Steam />

      {/* ── hero ── */}
      <header id="top" ref={heroRef} className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[1fr_auto] lg:gap-16">
        <motion.div style={{ y, opacity: fade }}>
          <Reveal>
            <div className="mb-5 flex items-center gap-3">
              <span className="h-px w-9 bg-primary" />
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary">{T(person.role)}</p>
            </div>
          </Reveal>
          <h1 className="font-display text-6xl font-bold leading-[0.95] tracking-tight sm:text-7xl lg:text-8xl">
            <Words text={T(person.name)} className="text-primary" />
          </h1>
          <Reveal delay={0.35}>
            <p className="mt-7 max-w-xl text-lg leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>
          <Reveal delay={0.5} className="mt-9 flex flex-wrap gap-3">
            <a href="https://alwawah.com" target="_blank" rel="noopener noreferrer" className="btn btn-primary">
              {T({ en: 'Visit Al-Wawah', bn: 'আল-ওয়াওয়াহ দেখুন' })}
            </a>
            <a href="#work" className="btn btn-outline">{T({ en: 'His Work', bn: 'তাঁর কাজ' })}</a>
          </Reveal>
        </motion.div>

        <motion.div
          className="relative mx-auto aspect-square w-[min(74vw,320px)]"
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 1, delay: 0.2, ease: [0.22, 1, 0.36, 1] }}
        >
          <motion.div
            className="absolute -inset-4 rounded-full border border-dashed border-primary/45"
            animate={{ rotate: 360 }}
            transition={{ duration: 34, repeat: Infinity, ease: 'linear' }}
            aria-hidden
          />
          <Photo slug={person.slug} alt={T(person.name)} glyph="☕"
            className="absolute inset-0 rounded-full border-2 border-primary shadow-2xl" />
        </motion.div>
      </header>

      <FactBand items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Businessman', bn: 'ব্যবসায়ী' }) },
        { k: T({ en: 'Founded', bn: 'প্রতিষ্ঠা' }), v: 'Al-Wawah Cafe', sub: T({ en: 'Cafe · Bistro · Bakery', bn: 'ক্যাফে · বিস্ট্রো · বেকারি' }) },
        { k: T({ en: 'Boards', bn: 'পর্ষদ' }), v: T({ en: 'Two directorships', bn: 'দুটি পরিচালক পদ' }), sub: 'Provati Insurance · 8 Bit' },
        { k: T({ en: 'Languages', bn: 'ভাষা' }), v: T({ en: 'Five', bn: 'পাঁচটি' }), sub: T({ en: 'Bangla · English · Farsi · Hindi · Arabic', bn: 'বাংলা · ইংরেজি · ফারসি · হিন্দি · আরবি' }) },
      ]} />


      {/* ── speech ── */}
      <Section kicker={T({ en: 'In his words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <blockquote className="relative rounded-r-box border border-l-4 border-base-content/10 border-l-primary bg-base-200/60 p-8 sm:p-12">
            <span className="pointer-events-none absolute -top-4 left-5 font-display text-8xl leading-none text-primary/25" aria-hidden>&ldquo;</span>
            <p className="font-display text-2xl italic leading-relaxed sm:text-3xl">{T(person.speech)}</p>
            <footer className="mt-6 text-xs font-semibold uppercase tracking-[0.2em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      {/* ── a slow marquee of what he runs ── */}
      <div className="relative z-10 overflow-hidden border-y border-base-content/10 py-5">
        <motion.div
          className="flex w-max gap-10 whitespace-nowrap"
          animate={{ x: ['0%', '-50%'] }}
          transition={{ duration: 32, repeat: Infinity, ease: 'linear' }}
          aria-hidden
        >
          {[0, 1].map((dup) => (
            <div key={dup} className="flex gap-10">
              {['Al-Wawah Cafe', 'Provati Insurance', '8 Bit Private Ltd', 'Khandani Legacy', 'Uttara · Dhaka'].map((w) => (
                <span key={w} className="font-display text-2xl opacity-40">{w} <span className="text-primary">◆</span></span>
              ))}
            </div>
          ))}
        </motion.div>
      </div>

      {/* ── positions ── */}
      <Section id="work" kicker={T({ en: 'Positions', bn: 'দায়িত্ব' })} title={T({ en: 'Where he sits', bn: 'যেখানে তিনি আছেন' })}>
        <div className="grid gap-5 sm:grid-cols-2">
          {person.roles.map((r, i) => (
            <Reveal key={r.org} delay={i * 0.08}>
              <TiltCard>
                <div className="card h-full border border-base-content/10 bg-base-200/50 transition-colors hover:border-primary/50">
                  <div className="card-body">
                    <span className="text-3xl">{r.icon}</span>
                    <h3 className="card-title font-display text-primary">{T(r.title)}</h3>
                    <p className="text-sm opacity-60">{r.org}</p>
                    {r.note && <p className="mt-1 text-sm opacity-75">{T(r.note)}</p>}
                  </div>
                </div>
              </TiltCard>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ── education, as a timeline ── */}
      <Section kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Madrasa to boardroom', bn: 'মাদ্রাসা থেকে বোর্ডরুম' })}>
        <ol className="relative ml-3 border-l border-primary/35 pl-8">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.08} as="li" className="relative pb-9 last:pb-0">
              <span className="absolute -left-[41px] top-1.5 grid h-4 w-4 place-items-center rounded-full border-2 border-primary bg-base-100" aria-hidden />
              <h3 className="font-display text-xl">{e.school}</h3>
              <p className="text-sm opacity-55">{T(e.where)}</p>
            </Reveal>
          ))}
        </ol>
      </Section>

      {/* ── languages ── */}
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

Ansary.defaultTheme = 'dark';
