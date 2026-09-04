import { motion, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, Counter, FactBand, Falling, Chips, Steps, Ordered, useT } from '../lib/shell.jsx';

/* ═══ ANAS · the flight deck ══════════════════════════════════════════════
   Condensed capitals, like the placards in a cockpit. An attitude indicator
   rolls beside the portrait, a flight strip runs the aircraft between two
   airports, and the language bars are painted like runway markings.
   ══════════════════════════════════════════════════════════════════════════ */

function Clouds() {
  const still = useReducedMotion();
  if (still) return null;
  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {[
        { top: '14%', d: 42, s: 1 }, { top: '32%', d: 58, s: 1.5, delay: -18 },
        { top: '58%', d: 50, s: 0.8, delay: -34 },
      ].map((c, i) => (
        <motion.div
          key={i}
          className="absolute h-14 w-44 rounded-full bg-base-100 opacity-40 blur-xl"
          style={{ top: c.top, scale: c.s }}
          animate={{ x: ['-30vw', '130vw'] }}
          transition={{ duration: c.d, delay: c.delay || 0, repeat: Infinity, ease: 'linear' }}
        />
      ))}
    </div>
  );
}

/** Artificial horizon — the instrument everyone recognises from a cockpit. */
function Attitude() {
  const still = useReducedMotion();
  return (
    <div className="relative aspect-square w-[min(52vw,224px)] flex-none overflow-hidden rounded-full border-[6px] border-base-300 shadow-2xl" aria-hidden>
      <motion.div
        className="absolute -inset-[30%]"
        style={{ background: 'linear-gradient(180deg, var(--color-secondary) 0 50%, #8A5A32 50% 100%)' }}
        animate={still ? {} : { rotate: [-9, 9, -9], y: [0, 9, 0] }}
        transition={{ duration: 8, repeat: Infinity, ease: 'easeInOut' }}
      />
      <div className="absolute inset-0 rounded-full shadow-[inset_0_0_0_2px_rgba(255,255,255,0.22)]" />
      <div className="absolute inset-0 grid place-items-center text-2xl text-primary drop-shadow">✛</div>
    </div>
  );
}

export default function Anas({ person }) {
  const T = useT();
  const still = useReducedMotion();

  return (
    <div className="min-h-screen bg-base-100 text-base-content">
      <ScrollBar />
      <Nav brand={<span className="uppercase tracking-[0.2em]">{T(person.name)}</span>} />
      <Clouds />
      <Falling items={['✈️', '☁️', '✦']} opacity={0.2} />
      <div className="pointer-events-none fixed inset-x-0 top-0 z-0 h-[60vh] bg-gradient-to-b from-secondary/20 to-transparent" aria-hidden />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-center gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[1fr_auto] lg:gap-14">
        <div>
          <Reveal>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary">{T(person.role)}</p>
          </Reveal>
          <h1 className="font-display font-bold leading-[1.02] tracking-tight [font-size:clamp(1.9rem,7vw,3.6rem)] text-balance break-words uppercase">
            <Words text={T(person.name)} />
          </h1>
          <Reveal delay={0.35}>
            <p className="mt-3 font-display text-lg font-semibold text-primary sm:text-xl">{T(person.profession)}</p>
            <p className="mt-5 max-w-lg leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>

          {/* flight strip */}
          <Reveal delay={0.5}>
            <div className="mt-8 max-w-xl border border-base-content/15 bg-base-200/60 p-4 shadow-lg">
              <div className="flex justify-between font-mono2 text-[11px] uppercase tracking-[0.12em] opacity-55">
                <span>DAC</span><span>{T({ en: 'Flight plan', bn: 'ফ্লাইট প্ল্যান' })}</span><span>KUL</span>
              </div>
              <div className="relative my-3 h-8">
                <div className="absolute inset-x-0 top-1/2 border-t border-dashed border-base-content/35" />
                <motion.span
                  className="absolute top-1/2 text-2xl"
                  style={{ translateY: '-50%' }}
                  animate={still ? { left: '50%' } : { left: ['2%', '96%', '2%'] }}
                  transition={{ duration: 11, repeat: Infinity, ease: 'easeInOut' }}
                  aria-hidden
                >✈️</motion.span>
              </div>
              <div className="flex justify-between font-mono2 text-[11px] uppercase tracking-[0.12em] opacity-55">
                <span>{T({ en: 'Dhaka', bn: 'ঢাকা' })}</span><span>HDG 118°</span><span>{T({ en: 'Malaysia', bn: 'মালয়েশিয়া' })}</span>
              </div>
            </div>
          </Reveal>
        </div>

        <div className="mx-auto grid justify-items-center gap-5">
          <Reveal delay={0.2}>
            <Photo slug={person.slug} alt={T(person.name)} glyph="✈️"
              className="aspect-square w-[min(56vw,232px)] rounded-full border-4 border-base-100 shadow-2xl" />
          </Reveal>
          <Reveal delay={0.35}><Attitude /></Reveal>
        </div>
      </header>

      <FactBand mono items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Pilot', bn: 'পাইলট' }) },
        { k: T({ en: 'Trained at', bn: 'প্রশিক্ষণ' }), v: 'Malaysian Flying Academy', sub: 'Sdn Bhd' },
        { k: T({ en: 'Also', bn: 'পাশাপাশি' }), v: T({ en: 'Marketing Officer', bn: 'মার্কেটিং অফিসার' }), sub: 'Al-Wawah' },
        { k: T({ en: 'Schooled', bn: 'বিদ্যালয়' }), v: 'Mastermind School', sub: T({ en: 'Dhaka', bn: 'ঢাকা' }) },
      ]} />


      <Ordered person={person}>
      <Section sectionKey="speech" kicker={T({ en: 'In his words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <blockquote className="border border-base-content/12 border-t-4 border-t-primary bg-base-200/50 p-8 shadow-lg sm:p-12">
            <p className="text-xl leading-relaxed sm:text-2xl">{T(person.speech)}</p>
            <footer className="mt-6 text-xs font-semibold uppercase tracking-[0.2em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      {/* an instrument row */}
      <Section sectionKey="roles" className="!py-10">
        <div className="grid gap-px border border-base-content/15 bg-base-content/15 sm:grid-cols-3">
          {[
            { n: 118, s: '°', l: 'HEADING' },
            { n: 4, s: '', l: T({ en: 'LANGUAGES', bn: 'ভাষা' }) },
            { n: 2, s: '', l: T({ en: 'ROLES', bn: 'দায়িত্ব' }) },
          ].map((s, i) => (
            <Reveal key={s.l} delay={i * 0.09}>
              <div className="bg-base-100 p-6 text-center">
                <p className="font-mono2 text-3xl font-bold text-primary sm:text-4xl"><Counter to={s.n} suffix={s.s} /></p>
                <p className="mt-1 text-[11px] uppercase tracking-[0.18em] opacity-55">{s.l}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section sectionKey="instruments" kicker={T({ en: 'Roles', bn: 'দায়িত্ব' })} title={T({ en: 'On duty', bn: 'দায়িত্বে' })}>
        <div className="grid gap-4 sm:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.08}>
              <motion.div whileHover={{ y: -6 }} transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                className="h-full border border-base-content/12 bg-base-200/50 p-6 shadow-md">
                <span className="font-mono2 text-xs uppercase tracking-[0.14em] text-primary">{String(i + 1).padStart(2, '0')}</span>
                <span className="float-right text-2xl">{r.icon}</span>
                <h3 className="mt-3 font-display text-xl font-bold uppercase">{T(r.title)}</h3>
                <p className="mt-1 text-sm opacity-55">{T(r.org)}</p>
                {r.note && <p className="mt-2 text-sm opacity-75">{T(r.note)}</p>}
              </motion.div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ── the checklist, which is the whole job ── */}
      <Section sectionKey="checklist" kicker={T({ en: 'Checklist', bn: 'চেকলিস্ট' })} title={T({ en: 'Every flight, in order', bn: 'প্রতিটি ফ্লাইট, ক্রম অনুসারে' })}>
        <Steps items={person.checklist.map((c) => ({ n: c.n, title: T(c.title), note: T(c.note) }))} mono />
      </Section>

      {/* ── focus ── */}
      <Section sectionKey="focus" kicker={T({ en: 'Focus', bn: 'কাজের ক্ষেত্র' })} title={T({ en: 'What he works in', bn: 'যেসব ক্ষেত্রে কাজ করেন' })}>
        <Chips items={person.focus.map(T)} mono />
      </Section>

      <Section sectionKey="education" kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Training', bn: 'প্রশিক্ষণ' })}>
        <div className="grid gap-4 sm:grid-cols-2">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.09}>
              <div className="h-full border border-base-content/12 bg-base-200/40 p-6">
                <span className="font-mono2 text-xs text-primary">{String.fromCharCode(65 + i)}</span>
                <h3 className="mt-2 font-display text-lg font-bold uppercase">{e.school}</h3>
                <p className="mt-1 text-sm opacity-60">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section sectionKey="languages" kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Four tongues', bn: 'চারটি ভাষা' })}>
        <div className="grid max-w-2xl gap-6">
          {person.languages.map((l, i) => (
            <LevelBar key={l.name.en} name={<span className="font-display uppercase tracking-wide">{T(l.name)}</span>}
              level={T(l.level)} value={l.v} delay={i * 0.06} mono />
          ))}
        </div>
      </Section>
      </Ordered>

      <Footer name={T(person.name)} role={T(person.role)} links={person.links} />
    </div>
  );
}

Anas.defaultTheme = 'light';
