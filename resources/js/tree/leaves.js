/**
 * Falling leaves — the wind's second layer, on its own canvas above the SVG
 * so it never forces the tree itself to repaint.
 *
 * Deliberately modest: 8–14 leaves at a time, capped at 30fps, and fully
 * paused whenever the tab is hidden. Callers are expected to skip this
 * entirely under reduced motion, on phones, and on very large trees.
 */

const MIN_LEAVES = 8;
const MAX_LEAVES = 14;
const FRAME_MS = 1000 / 30;

export function startFallingLeaves(container, { leafColor, autumnColor } = {}) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    Object.assign(canvas.style, {
        position: 'absolute',
        inset: '0',
        width: '100%',
        height: '100%',
        pointerEvents: 'none',
        zIndex: '1',
    });
    container.appendChild(canvas);

    const context = canvas.getContext('2d');
    let width = 0;
    let height = 0;

    const resize = () => {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        width = container.clientWidth;
        height = container.clientHeight;
        canvas.width = Math.max(1, width * ratio);
        canvas.height = Math.max(1, height * ratio);
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
    };
    resize();

    const observer = new ResizeObserver(resize);
    observer.observe(container);

    const spawn = (fromTop = false) => ({
        x: Math.random() * width,
        y: fromTop ? -20 : Math.random() * height,
        vy: 0.25 + Math.random() * 0.55,
        drift: 0.35 + Math.random() * 0.75,
        phase: Math.random() * Math.PI * 2,
        spin: (Math.random() - 0.5) * 0.02,
        angle: Math.random() * Math.PI * 2,
        size: 5 + Math.random() * 5,
        autumn: Math.random() < 0.3,
        alpha: 0.35 + Math.random() * 0.4,
    });

    const leaves = Array.from(
        { length: MIN_LEAVES + Math.floor(Math.random() * (MAX_LEAVES - MIN_LEAVES)) },
        () => spawn()
    );

    let raf = null;
    let lastFrame = 0;

    const draw = (now) => {
        raf = requestAnimationFrame(draw);

        if (document.hidden) return;
        if (now - lastFrame < FRAME_MS) return;
        lastFrame = now;

        context.clearRect(0, 0, width, height);

        for (const leaf of leaves) {
            leaf.y += leaf.vy;
            leaf.phase += 0.02;
            leaf.x += Math.sin(leaf.phase) * leaf.drift;
            leaf.angle += leaf.spin;

            if (leaf.y > height + 30) Object.assign(leaf, spawn(true));

            context.save();
            context.translate(leaf.x, leaf.y);
            context.rotate(leaf.angle);
            context.globalAlpha = leaf.alpha;
            context.fillStyle = leaf.autumn ? autumnColor : leafColor;
            context.beginPath();
            context.moveTo(0, 0);
            context.quadraticCurveTo(leaf.size * 0.8, -leaf.size * 0.5, leaf.size * 0.6, -leaf.size * 1.6);
            context.quadraticCurveTo(-leaf.size * 0.2, -leaf.size * 0.9, 0, 0);
            context.fill();
            context.restore();
        }
    };

    raf = requestAnimationFrame(draw);

    /** A gust shakes a few extra leaves loose. */
    const onGust = () => {
        for (let i = 0; i < 3 + Math.floor(Math.random() * 3); i++) {
            if (leaves.length < MAX_LEAVES + 5) leaves.push(spawn(true));
        }
    };
    container.addEventListener('tree:gust', onGust);

    return function stop() {
        if (raf) cancelAnimationFrame(raf);
        observer.disconnect();
        container.removeEventListener('tree:gust', onGust);
        canvas.remove();
    };
}
