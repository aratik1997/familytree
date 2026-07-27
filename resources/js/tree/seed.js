/**
 * Deterministic pseudo-randomness, keyed off a person's id.
 *
 * The tree needs organic variation — branch curvature, leaf rotation, sway
 * timing — that stays *identical* between renders. Math.random() would
 * reshuffle the shape of the tree on every repaint, which reads as glitching
 * rather than as nature.
 */

/** FNV-1a over the string form of any key. Always a positive integer. */
export function hashSeed(key) {
    const text = String(key);
    let hash = 0x811c9dc5;

    for (let i = 0; i < text.length; i++) {
        hash ^= text.charCodeAt(i);
        hash = Math.imul(hash, 0x01000193);
    }

    return Math.abs(hash);
}

/** A stable value in [min, max) for a given seed. */
export function seededRange(seed, min, max) {
    // Mulberry32's mixing step — good enough spread for visual jitter.
    let t = (seed + 0x6d2b79f5) | 0;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    const unit = ((t ^ (t >>> 14)) >>> 0) / 4294967296;

    return min + unit * (max - min);
}
