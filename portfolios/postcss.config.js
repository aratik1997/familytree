/**
 * Deliberately empty.
 *
 * Vite walks up the tree looking for a PostCSS config, and the Laravel project
 * this folder sits inside has one — which pulls in that project's Tailwind v3
 * and collides with the v4 plugin used here. An empty config at this level
 * stops the search. Tailwind is handled by @tailwindcss/vite, not PostCSS.
 */
export default { plugins: {} };
