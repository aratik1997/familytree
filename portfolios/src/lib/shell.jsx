import { createContext, useContext, useEffect, useState, useRef } from 'react';
import { motion, useInView, useScroll, useSpring, useReducedMotion } from 'motion/react';

/* ═══════════════════════════════════════════════════════════════════════
   The parts every portfolio shares: which language, which theme, and the
   handful of motions they all use.

   Everything visual lives in the person's own page. What is here is only
   the plumbing — otherwise the eight would drift back towards looking like
   one template with the colours changed, which is the thing they must not
   look like.
   ═══════════════════════════════════════════════════════════════════════ */

const Ctx = createContext(null);
export const useShell = () => useContext(Ctx);

/** Reads a two-language value, or passes a plain string straight through. */
export function useT() {
  const { lang } = useShell();
  return (v) => (v && typeof v === 'object' && 'en' in v ? v[lang] : v);
}

export function Shell({ person: built, children }) {
  const [lang, setLang] = useState('en');
  const [theme, setTheme] = useState(built.defaultTheme || 'dark');
  const [person, setPerson] = useState(built);

  /**
   * Wording edited in the admin area, laid beside the page as data.json.
   *
   * Fetched relative to the page, never from the main site: each portfolio is
   * its own subdomain and a cross-origin request would simply be refused. If
   * the file is absent — nothing has been published yet — the page keeps the
   * words it was built with, so it is always complete either way.
   */
  useEffect(() => {
    // Opened straight from disk there is nothing to fetch from, and asking
    // anyway logs an error the catch cannot swallow — these pages are checked
    // by double-clicking them, so that noise would be the normal case.
    if (!/^https?:$/.test(location.protocol)) return;

    let live = true;
    fetch('data.json', { cache: 'no-cache' })
      .then((r) => (r.ok ? r.json() : null))
      .then((edits) => {
        if (!live || !edits || typeof edits !== 'object') return;
        const next = { ...built };
        for (const [field, value] of Object.entries(edits)) {
          if (!value || typeof value !== 'object') continue;
          // Only fields the page already has, and only languages actually
          // filled in — a blank box in the form must not erase a line.
          if (!(field in next)) continue;
          next[field] = {
            en: value.en?.trim() || next[field]?.en,
            bn: value.bn?.trim() || next[field]?.bn,
          };
        }
        setPerson(next);
      })
      .catch(() => { /* no file, or offline: the built words stand */ });
    return () => { live = false; };
  }, [built]);

  // Restored before first paint in index.html would be better still, but these
  // are single pages behind a fade-in, so a frame of the default is invisible.
  useEffect(() => {
    try {
      const l = localStorage.getItem('kl-lang');
      const t = localStorage.getItem('kl-theme');
      if (l === 'en' || l === 'bn') setLang(l);
      if (t === 'light' || t === 'dark') setTheme(t);
    } catch { /* private mode — defaults are fine */ }
  }, []);

  useEffect(() => {
    const r = document.documentElement;
    r.setAttribute('data-theme', theme);
    r.setAttribute('data-person', person.slug);
    r.setAttribute('lang', lang);
    try { localStorage.setItem('kl-theme', theme); localStorage.setItem('kl-lang', lang); } catch { /* ignore */ }
  }, [theme, lang, person.slug]);

  return (
    <Ctx.Provider value={{ lang, setLang, theme, setTheme, person }}>
      {children}
    </Ctx.Provider>
  );
}

/** The thin progress line at the very top of the window. */
export function ScrollBar() {
  const { scrollYProgress } = useScroll();
  const w = useSpring(scrollYProgress, { stiffness: 120, damping: 28, restDelta: 0.001 });
  return (
    <motion.div
      className="fixed inset-x-0 top-0 z-[70] h-[3px] origin-left bg-primary"
      style={{ scaleX: w }}
      aria-hidden
    />
  );
}

/** Nav bar: name on the left, the two switches on the right. */
export function Nav({ brand, children }) {
  const { lang, setLang, theme, setTheme } = useShell();
  const [stuck, setStuck] = useState(false);

  useEffect(() => {
    const on = () => setStuck(window.scrollY > 24);
    on();
    addEventListener('scroll', on, { passive: true });
    return () => removeEventListener('scroll', on);
  }, []);

  return (
    <motion.nav
      initial={{ y: -70, opacity: 0 }}
      animate={{ y: 0, opacity: 1 }}
      transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
      className={`fixed inset-x-0 top-0 z-[65] flex items-center justify-between gap-4 px-4 sm:px-8 lg:px-14 transition-all duration-500 ${
        stuck ? 'py-3 bg-base-100/85 backdrop-blur-xl border-b border-base-content/10' : 'py-5 border-b border-transparent'
      }`}
    >
      <a href="#top" className="font-display text-lg sm:text-xl font-bold text-primary tracking-wide">
        {brand}
      </a>
      <div className="flex items-center gap-2">
        {children}
        <button
          type="button"
          onClick={() => setLang(lang === 'bn' ? 'en' : 'bn')}
          className="btn btn-sm btn-ghost border border-base-content/15 font-semibold"
          aria-label="Switch language"
        >
          {lang === 'bn' ? 'English' : 'বাংলা'}
        </button>
        <button
          type="button"
          onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
          className="btn btn-sm btn-ghost border border-base-content/15"
          aria-label="Switch theme"
        >
          <motion.span key={theme} initial={{ rotate: -90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} transition={{ duration: 0.4 }}>
            {theme === 'dark' ? '☾' : '☀'}
          </motion.span>
        </button>
      </div>
    </motion.nav>
  );
}

/** Fades and lifts its children once they are actually looked at. */
export function Reveal({ children, delay = 0, y = 28, className = '', as = 'div' }) {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true, margin: '-12% 0px' });
  const still = useReducedMotion();
  const M = motion[as] || motion.div;

  return (
    <M
      ref={ref}
      initial={still ? false : { opacity: 0, y }}
      animate={seen || still ? { opacity: 1, y: 0 } : {}}
      transition={{ duration: 0.75, delay, ease: [0.22, 1, 0.36, 1] }}
      className={className}
    >
      {children}
    </M>
  );
}

/** Splits a heading into words that rise into place one after another. */
export function Words({ text, className = '' }) {
  const still = useReducedMotion();
  const words = String(text).split(' ');
  return (
    <span className={className}>
      {words.map((w, i) => (
        <motion.span
          key={`${w}-${i}`}
          className="inline-block"
          initial={still ? false : { opacity: 0, y: '0.5em', filter: 'blur(6px)' }}
          animate={{ opacity: 1, y: 0, filter: 'blur(0px)' }}
          transition={{ duration: 0.7, delay: 0.15 + i * 0.07, ease: [0.22, 1, 0.36, 1] }}
        >
          {w}
          {i < words.length - 1 && ' '}
        </motion.span>
      ))}
    </span>
  );
}

/**
 * One bar per language — a single score, not separate marks for speaking and
 * writing. Fills only once scrolled to, so the movement is seen rather than
 * finished before anyone arrives.
 */
export function LevelBar({ name, level, value, delay = 0, mono = false }) {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true, margin: '-15% 0px' });
  const still = useReducedMotion();

  return (
    <div ref={ref}>
      <div className="mb-2 flex items-baseline justify-between gap-4">
        <span className="font-semibold">{name}</span>
        <span className={`text-xs uppercase tracking-wider text-primary ${mono ? 'font-mono2' : ''}`}>
          {level}
        </span>
      </div>
      <div className="h-2 w-full overflow-hidden rounded-full bg-base-content/15">
        <motion.div
          className="h-full rounded-full bg-gradient-to-r from-primary to-accent"
          initial={still ? false : { width: 0 }}
          animate={seen || still ? { width: `${value}%` } : {}}
          transition={{ duration: 1.3, delay, ease: [0.22, 1, 0.36, 1] }}
        />
      </div>
    </div>
  );
}

/**
 * The portrait. The placeholder is not a grey box that stays forever — it is
 * what shows until someone drops the real photograph in beside the page, and
 * it says exactly which filename to use.
 */
export function Photo({ slug, alt, glyph, className = '', imgClass = '' }) {
  const [failed, setFailed] = useState(false);
  const T = useT();

  return (
    // No position class of its own: a caller that positions this absolutely
    // would otherwise be fighting a hardcoded `relative`, and which of the two
    // wins is decided by Tailwind's output order rather than by the caller.
    <div className={`overflow-hidden bg-base-200 ${className}`}>
      {!failed ? (
        <img
          src={`img/${slug}.jpg`}
          alt={alt}
          onError={() => setFailed(true)}
          className={`h-full w-full object-cover ${imgClass}`}
        />
      ) : (
        <div className="grid h-full w-full place-items-center p-6 text-center">
          <div>
            <div className="mb-2 text-4xl">{glyph}</div>
            <p className="text-xs uppercase tracking-widest opacity-50">
              {T({ en: 'Add photo', bn: 'ছবি দিন' })}
            </p>
            <p className="mt-1 font-mono2 text-xs opacity-40">img/{slug}.jpg</p>
          </div>
        </div>
      )}
    </div>
  );
}

/** Section wrapper: an eyebrow, a heading, and whatever follows. */
export function Section({ id, kicker, title, children, className = '', kickerClass = '', titleClass = '' }) {
  return (
    <section id={id} className={`relative z-10 mx-auto w-[92vw] max-w-6xl py-16 sm:py-24 ${className}`}>
      {(kicker || title) && (
        <Reveal className="mb-10">
          {kicker && (
            <p className={`mb-3 text-xs font-semibold uppercase tracking-[0.28em] text-primary ${kickerClass}`}>
              {kicker}
            </p>
          )}
          {title && (
            <h2 className={`font-display text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl ${titleClass}`}>
              {title}
            </h2>
          )}
        </Reveal>
      )}
      {children}
    </section>
  );
}

/** A card that tilts very slightly towards the cursor. */
export function TiltCard({ children, className = '', max = 7 }) {
  const ref = useRef(null);
  const still = useReducedMotion();

  const onMove = (e) => {
    if (still || !ref.current) return;
    const r = ref.current.getBoundingClientRect();
    const px = (e.clientX - r.left) / r.width - 0.5;
    const py = (e.clientY - r.top) / r.height - 0.5;
    ref.current.style.transform =
      `perspective(900px) rotateY(${px * max}deg) rotateX(${-py * max}deg) translateY(-4px)`;
  };
  const reset = () => {
    if (ref.current) ref.current.style.transform = '';
  };

  return (
    <div
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={reset}
      className={`transition-transform duration-300 will-change-transform ${className}`}
    >
      {children}
    </div>
  );
}

/** Counts up to a number when it comes into view. */
export function Counter({ to, suffix = '', className = '' }) {
  const ref = useRef(null);
  const seen = useInView(ref, { once: true });
  const [n, setN] = useState(0);
  const still = useReducedMotion();

  useEffect(() => {
    if (!seen) return;
    if (still) { setN(to); return; }
    let raf, start;
    const step = (ts) => {
      start ??= ts;
      const p = Math.min((ts - start) / 1100, 1);
      setN(Math.round(to * (1 - Math.pow(1 - p, 3))));
      if (p < 1) raf = requestAnimationFrame(step);
    };
    raf = requestAnimationFrame(step);
    return () => cancelAnimationFrame(raf);
  }, [seen, to, still]);

  return <span ref={ref} className={className}>{n}{suffix}</span>;
}


/**
 * A dense strip of facts directly under the hero.
 *
 * The heroes were reading thin — a name, a line, and a lot of air. This puts
 * something substantial immediately under the fold on every page, and because
 * each one composes its own facts it adds weight without adding sameness.
 */
export function FactBand({ items, className = '', mono = false }) {
  return (
    <Reveal delay={0.15}>
      <div className={`relative z-10 mx-auto grid w-[92vw] max-w-6xl gap-px border-y border-base-content/12 bg-base-content/12 sm:grid-cols-2 lg:grid-cols-4 ${className}`}>
        {items.map((it, i) => (
          <motion.div
            key={it.k}
            className="bg-base-100 px-5 py-6"
            initial={{ opacity: 0, y: 14 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: i * 0.07, ease: [0.22, 1, 0.36, 1] }}
          >
            <p className={`text-[11px] uppercase tracking-[0.2em] opacity-50 ${mono ? 'font-mono2' : ''}`}>{it.k}</p>
            <p className="mt-1.5 font-display text-lg font-bold leading-snug">{it.v}</p>
            {it.sub && <p className="mt-0.5 text-xs opacity-55">{it.sub}</p>}
          </motion.div>
        ))}
      </div>
    </Reveal>
  );
}


/**
 * A field of things drifting down the page.
 *
 * Every portfolio has one, and what falls is what that person's day is made
 * of — coffee beans, teeth, blueprint marks, leaves. Kept to the margins by
 * default so nothing lands on top of a headline and reads as a fault.
 */
export function Falling({ items, columns, opacity = 0.35, size = 'text-base' }) {
  const still = useReducedMotion();
  if (still) return null;

  const cols = columns || ['4%', '17%', '31%', '68%', '83%', '95%'];

  return (
    <div className="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden>
      {cols.map((left, i) => (
        <motion.span
          key={i}
          className={`absolute ${size}`}
          style={{ left, top: '-8%', opacity }}
          animate={{ y: ['0vh', '115vh'], x: [0, i % 2 ? 60 : -60], rotate: [0, i % 2 ? 400 : -400] }}
          transition={{
            duration: 14 + (i % 4) * 3,
            delay: -(i * 3.5),
            repeat: Infinity,
            ease: 'linear',
          }}
        >
          {items[i % items.length]}
        </motion.span>
      ))}
    </div>
  );
}

/** Row of small labelled pills — what somebody is actually good at. */
export function Chips({ items, className = '', mono = false }) {
  return (
    <div className={`flex flex-wrap gap-2 ${className}`}>
      {items.map((c, i) => (
        <motion.span
          key={i}
          className={`badge badge-outline badge-lg h-auto whitespace-normal py-2 text-xs ${mono ? 'font-mono2' : ''}`}
          initial={{ opacity: 0, scale: 0.9 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.4, delay: i * 0.05 }}
          whileHover={{ y: -3 }}
        >
          {c}
        </motion.span>
      ))}
    </div>
  );
}

/**
 * Numbered steps. Used where a person's work has an order to it — a method, a
 * checklist, a journey — which is most of them, and it fills the space a bare
 * list of nouns would leave.
 */
export function Steps({ items, mono = false }) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {items.map((it, i) => (
        <Reveal key={it.n || i} delay={i * 0.08}>
          <motion.div
            whileHover={{ y: -6 }}
            transition={{ type: 'spring', stiffness: 300, damping: 20 }}
            className="h-full rounded-box border border-base-content/12 bg-base-200/50 p-6"
          >
            <span className={`text-xs text-primary ${mono ? 'font-mono2' : 'font-semibold'}`}>{it.n}</span>
            <h3 className="mt-3 font-display text-lg font-bold">{it.title}</h3>
            <p className="mt-1.5 text-sm leading-relaxed opacity-70">{it.note}</p>
          </motion.div>
        </Reveal>
      ))}
    </div>
  );
}

/** Footer, identical in structure everywhere, themed by the tokens. */
export function Footer({ name, role, links = [] }) {
  return (
    <footer className="relative z-10 border-t border-base-content/10 py-12 text-center">
      <p className="font-display text-lg">{name}</p>
      <p className="mt-1 text-sm opacity-60">{role}</p>
      <div className="mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-sm">
        {links.map((l) => (
          <a key={l.href} href={l.href} target="_blank" rel="noopener noreferrer" className="link link-primary no-underline hover:underline">
            {l.label}
          </a>
        ))}
        <a href="https://khandanilegacy.com" className="link link-primary no-underline hover:underline">
          khandanilegacy.com
        </a>
      </div>
    </footer>
  );
}
