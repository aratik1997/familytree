import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwind from '@tailwindcss/vite';
import { viteSingleFile } from 'vite-plugin-singlefile';

/**
 * One config, built once per person.
 *
 * Each portfolio becomes its own subdomain, so each build has to be wholly
 * self-contained — a shared /assets directory at the top of dist would be
 * unreachable from ansary.khandanilegacy.com. So the person is chosen by an
 * env var and the output goes to its own folder, eight times over.
 *
 * Everything is then inlined into that one index.html. Not for the sake of
 * fewer requests, but because otherwise the page is blank when it is opened
 * from disk: a browser refuses to load an ES module or a stylesheet over
 * file://, so double-clicking the file gives a CORS error and an empty root.
 * These pages get checked by opening them and mailed around as files, so they
 * have to survive that. Inlined, each one is a single document that works from
 * a disk, a subdomain, or an attachment.
 */
export default defineConfig(() => {
  const person = process.env.VITE_PERSON || 'ansary';

  return {
    base: './',
    plugins: [react(), tailwind(), viteSingleFile()],
    define: { __PERSON__: JSON.stringify(person) },
    build: {
      outDir: `dist/${person}`,
      emptyOutDir: true,
      target: 'es2020',
      cssCodeSplit: false,
      assetsInlineLimit: 100_000_000,
      reportCompressedSize: false,
      rollupOptions: {
        output: { inlineDynamicImports: true },
      },
    },
  };
});
