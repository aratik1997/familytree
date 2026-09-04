import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwind from '@tailwindcss/vite';

/**
 * One config, built once per person.
 *
 * Each portfolio becomes its own subdomain, so each build has to be wholly
 * self-contained — a shared /assets directory at the top of dist would be
 * unreachable from ansary.khandanilegacy.com. So the person is chosen by an
 * env var and the output goes to its own folder, eight times over.
 *
 * base is './' for the same reason: the files must not care what they are
 * served from.
 */
export default defineConfig(() => {
  const person = process.env.VITE_PERSON || 'ansary';

  return {
    base: './',
    plugins: [react(), tailwind()],
    define: { __PERSON__: JSON.stringify(person) },
    build: {
      outDir: `dist/${person}`,
      emptyOutDir: true,
      target: 'es2020',
      cssCodeSplit: false,
      rollupOptions: {
        output: {
          // Everything in one chunk: these are single pages, and a waterfall
          // of small requests costs more than it saves here.
          manualChunks: undefined,
        },
      },
    },
  };
});
