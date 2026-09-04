import { useRef } from 'react';
import { motion, useInView, useReducedMotion } from 'motion/react';
import { Nav, ScrollBar, Reveal, Words, LevelBar, Photo, Section, Footer, FactBand, Falling, Chips, Steps, Ordered, useT } from '../lib/shell.jsx';

/* ═══ MARIA · the drafting table ══════════════════════════════════════════
   Not one rounded corner on the page — the radius tokens are set to zero, so
   even daisyUI's buttons come out square. Sections are numbered like drawing
   sheets, and the plan in the hero draws itself line by line.
   ══════════════════════════════════════════════════════════════════════════ */

const STROKES = [
  { d: 'M4 4h292v162H4z', len: 920 },
  { d: 'M4 108h130v58', len: 190 },
  { d: 'M190 4v62h106', len: 170 },
  { d: 'M134 66h162', len: 165 },
  { d: 'M40 40h60v40H40z', len: 200 },
];

function Plan() {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true, margin: '-10% 0px' });
  const still = useReducedMotion();

  return (
    <svg ref={ref} viewBox="0 0 300 170" className="mt-8 w-full max-w-sm text-primary" fill="none"
      stroke="currentColor" strokeWidth="1.6" aria-hidden>
      {STROKES.map((s, i) => (
        <motion.path
          key={s.d} d={s.d}
          strokeDasharray={s.len} strokeDashoffset={still ? 0 : s.len}
          animate={seen || still ? { strokeDashoffset: 0 } : {}}
          transition={{ duration: 1.6, delay: 0.25 + i * 0.18, ease: [0.6, 0.02, 0.2, 1] }}
        />
      ))}
      <motion.circle
        cx="196" cy="120" r="26" strokeDasharray="165" strokeDashoffset={still ? 0 : 165}
        animate={seen || still ? { strokeDashoffset: 0 } : {}}
        transition={{ duration: 1.6, delay: 1.15, ease: [0.6, 0.02, 0.2, 1] }}
      />
    </svg>
  );
}

export default function Maria({ person }) {
  const T = useT();

  return (
    <div className="ink-grid min-h-screen bg-base-100 text-base-content [--grid:20px]">
      <ScrollBar />
      <Falling items={['△', '□', '○', '✕']} opacity={0.2} />
      <Nav brand={<span className="font-mono2 uppercase tracking-[0.2em]">{T(person.name)}</span>} />

      <header id="top" className="relative z-10 mx-auto grid w-[92vw] max-w-6xl items-end gap-10 pb-10 pt-28 sm:pt-32 lg:grid-cols-[1fr_auto] lg:gap-16">
        <div>
          <Reveal>
            <p className="font-mono2 text-xs uppercase tracking-[0.24em] text-primary">{T(person.role)}</p>
          </Reveal>
          <h1 className="font-display font-bold leading-[1.02] tracking-tight [font-size:clamp(1.9rem,7vw,3.6rem)] text-balance break-words">
            <Words text={T(person.name)} />
          </h1>
          <Reveal delay={0.35}>
            <div className="my-6 flex items-center gap-3 font-mono2 text-xs uppercase tracking-[0.14em] opacity-55">
              <span className="h-px flex-1 bg-base-content/35" />
              <span>Architect · B.Arch · NSU</span>
              <span className="h-px flex-1 bg-base-content/35" />
            </div>
            <p className="mt-3 font-display text-lg font-semibold text-primary sm:text-xl">{T(person.profession)}</p>
            <p className="max-w-xl leading-relaxed opacity-75">{T(person.tagline)}</p>
          </Reveal>
          <Plan />
        </div>

        <Reveal delay={0.3}>
          <div className="mx-auto w-[min(64vw,252px)]">
            <Photo slug={person.slug} alt={T(person.name)} glyph="📐"
              className="aspect-[3/4] border border-base-content/35" />
            <div className="mt-2 flex justify-between font-mono2 text-[11px] uppercase tracking-[0.14em] opacity-50">
              <span>Fig. 01</span><span>Portrait</span>
            </div>
          </div>
        </Reveal>
      </header>

      <FactBand mono items={[
        { k: T({ en: 'Profession', bn: 'পেশা' }), v: T({ en: 'Architect', bn: 'স্থপতি' }), sub: 'B.Arch · NSU' },
        { k: T({ en: 'Founded', bn: 'প্রতিষ্ঠা' }), v: 'Bikku Bikku', sub: T({ en: 'Homemade cookies', bn: 'ঘরে বানানো কুকি' }) },
        { k: T({ en: 'Schooled', bn: 'বিদ্যালয়' }), v: 'Uttara High School', sub: T({ en: 'Dhaka', bn: 'ঢাকা' }) },
        { k: T({ en: 'Languages', bn: 'ভাষা' }), v: T({ en: 'Four', bn: 'চারটি' }), sub: T({ en: 'including French', bn: 'ফরাসিসহ' }) },
      ]} />


      <Ordered person={person}>
      <Section sectionKey="speech" kicker={T({ en: 'In her words', bn: 'তাঁর ভাষায়' })}>
        <Reveal>
          <blockquote className="relative border border-base-content/30 bg-base-200/40 p-8 sm:p-12">
            <span className="absolute bottom-3 right-3 h-6 w-6 border-b border-r border-primary" aria-hidden />
            <p className="text-xl leading-relaxed sm:text-2xl">{T(person.speech)}</p>
            <footer className="mt-6 font-mono2 text-xs uppercase tracking-[0.16em] text-primary">— {T(person.name)}</footer>
          </blockquote>
        </Reveal>
      </Section>

      {/* work, set out as drawing sheets */}
      <Section sectionKey="work" kicker={T({ en: 'Work', bn: 'কাজ' })} title={T({ en: 'Two practices', bn: 'দুই ধরনের কাজ' })}>
        <div className="grid gap-px border border-base-content/25 bg-base-content/25 sm:grid-cols-3">
          {person.roles.map((r, i) => (
            <Reveal key={r.title.en} delay={i * 0.09}>
              <div className="group h-full bg-base-100 p-7 transition-colors hover:bg-base-200">
                <div className="flex items-start justify-between">
                  <span className="font-mono2 text-xs text-primary">{String(i + 1).padStart(2, '0')}</span>
                  <span className="text-2xl opacity-40 transition-opacity group-hover:opacity-100">{r.icon}</span>
                </div>
                <h3 className="mt-4 font-display text-lg font-bold">{T(r.title)}</h3>
                <p className="mt-1 font-mono2 text-xs opacity-55">{T(r.org)}</p>
                {r.note && <p className="mt-3 text-sm leading-relaxed opacity-75">{T(r.note)}</p>}
              </div>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.25}>
          <motion.a
            href="https://www.facebook.com/bikkubikkucookies/" target="_blank" rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-3 border border-dashed border-base-content/35 px-5 py-3 font-mono2 text-sm transition-colors hover:border-primary"
            whileHover={{ rotate: -1.5, scale: 1.02 }}
          >
            <motion.span className="text-2xl" animate={{ rotate: [-8, 8, -8] }}
              transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }} aria-hidden>🍪</motion.span>
            {T({ en: 'Bikku Bikku · baked fresh', bn: 'বিক্কু বিক্কু · তাজা বেকিং' })}
          </motion.a>
        </Reveal>
      </Section>

      {/* ── how a drawing gets made ── */}
      <Section sectionKey="method" kicker={T({ en: 'Method', bn: 'পদ্ধতি' })} title={T({ en: 'How a drawing gets made', bn: 'একটি নকশা যেভাবে তৈরি হয়' })}>
        <Steps items={person.method.map((m) => ({ n: m.n, title: T(m.title), note: T(m.note) }))} mono />
      </Section>

      {/* ── focus ── */}
      <Section sectionKey="focus" kicker={T({ en: 'Focus', bn: 'কাজের ক্ষেত্র' })} title={T({ en: 'What she works in', bn: 'যেসব ক্ষেত্রে কাজ করেন' })}>
        <Chips items={person.focus.map(T)} mono />
      </Section>

      <Section sectionKey="education" kicker={T({ en: 'Education', bn: 'শিক্ষা' })} title={T({ en: 'Schooling', bn: 'পড়াশোনা' })}>
        <div className="grid gap-px border border-base-content/25 bg-base-content/25 sm:grid-cols-2">
          {person.education.map((e, i) => (
            <Reveal key={e.school} delay={i * 0.09}>
              <div className="h-full bg-base-100 p-7">
                <span className="font-mono2 text-xs text-primary">{String.fromCharCode(65 + i)}</span>
                <h3 className="mt-3 font-display text-lg font-bold">{e.school}</h3>
                <p className="mt-1 text-sm opacity-60">{T(e.where)}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section sectionKey="languages" kicker={T({ en: 'Languages', bn: 'ভাষা' })} title={T({ en: 'Four tongues', bn: 'চারটি ভাষা' })}>
        <div className="grid max-w-2xl gap-6">
          {person.languages.map((l, i) => (
            <LevelBar key={l.name.en} name={T(l.name)} level={T(l.level)} value={l.v} delay={i * 0.06} mono />
          ))}
        </div>
      </Section>
      </Ordered>

      <Footer name={T(person.name)} role={T(person.role)} links={person.links} />
    </div>
  );
}

Maria.defaultTheme = 'light';
