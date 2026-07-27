/**
 * Photo editor — crop, zoom and rotate a picture before it is uploaded.
 *
 * The stage shows the whole picture dimmed, with a bright frame over the part
 * that will actually be kept. That frame matches the proportions of the card
 * on the family tree, so what you line up here is exactly what everyone sees
 * afterwards.
 *
 * The result is written back into the original <input type="file"> as a new
 * File, so every form keeps posting exactly the way it did before — nothing
 * on the server had to change.
 */

// The tree card's photo panel: 150 wide by 172 tall.
const TARGET_RATIO = 150 / 172;
const OUTPUT_WIDTH = 640;
const OUTPUT_HEIGHT = Math.round(OUTPUT_WIDTH / TARGET_RATIO);

const STAGE = 420;
const CROP_HEIGHT = 340;
const CROP_WIDTH = Math.round(CROP_HEIGHT * TARGET_RATIO);

const MAX_ZOOM = 6;

function el(tag, className, attrs = {}) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    for (const [key, value] of Object.entries(attrs)) {
        if (value != null) node.setAttribute(key, value);
    }
    return node;
}

function iconButton(label, svgPath) {
    const button = el('button', 'btn btn-secondary photo-editor__icon', {
        type: 'button',
        'aria-label': label,
        title: label,
    });
    button.innerHTML = `<svg viewBox="0 0 24 24" width="18" height="18" fill="none"
        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true">${svgPath}</svg>`;
    return button;
}

/**
 * Opens the editor for `file`. Resolves with a cropped File, or null if the
 * person backed out.
 */
function openEditor(file) {
    return new Promise((resolve) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(null);
        };

        image.onload = () => {
            const ui = buildEditor(image, file, (result) => {
                URL.revokeObjectURL(url);
                ui.remove();
                document.body.style.overflow = ui.previousOverflow;
                resolve(result);
            });
        };

        image.src = url;
    });
}

function buildEditor(image, file, done) {
    const overlay = el('div', 'photo-editor');
    overlay.previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const panel = el('div', 'photo-editor__panel glass', {
        role: 'dialog',
        'aria-modal': 'true',
        'aria-label': 'Adjust photo',
    });

    const header = el('div', 'photo-editor__header');
    header.innerHTML = `
        <p class="eyebrow">Before uploading</p>
        <h2 class="photo-editor__title">Adjust the photo</h2>
        <p class="photo-editor__hint">Drag to move, scroll or use the slider to zoom.
            The bright frame is the part that will be shown.</p>
    `;

    const body = el('div', 'photo-editor__body');
    const canvas = el('canvas', 'photo-editor__stage', {
        width: STAGE,
        height: STAGE,
        'aria-label': 'Photo preview. Drag to reposition.',
        tabindex: '0',
    });
    const context = canvas.getContext('2d');

    // A live thumbnail of the finished card, beside the stage.
    const side = el('div', 'photo-editor__side');
    const previewLabel = el('p', 'eyebrow');
    previewLabel.textContent = 'Shown as';
    const preview = el('canvas', 'photo-editor__preview', {
        width: 150,
        height: 172,
        'aria-hidden': 'true',
    });
    const previewContext = preview.getContext('2d');
    side.append(previewLabel, preview);

    body.append(canvas, side);

    // --- Controls --------------------------------------------------------

    const controls = el('div', 'photo-editor__controls');

    const zoomOut = iconButton('Zoom out', '<circle cx="11" cy="11" r="7"/><path d="M8 11h6M20 20l-4.3-4.3"/>');
    const zoomIn = iconButton('Zoom in', '<circle cx="11" cy="11" r="7"/><path d="M11 8v6M8 11h6M20 20l-4.3-4.3"/>');
    const rotateLeft = iconButton('Rotate left', '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>');
    const rotateRight = iconButton('Rotate right', '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>');
    const reset = el('button', 'btn btn-secondary photo-editor__reset', { type: 'button' });
    reset.textContent = 'Reset';

    const slider = el('input', 'photo-editor__slider', {
        type: 'range',
        min: '1',
        max: '100',
        value: '0',
        'aria-label': 'Zoom',
    });

    controls.append(zoomOut, slider, zoomIn, rotateLeft, rotateRight, reset);

    const footer = el('div', 'photo-editor__footer');
    const cancel = el('button', 'btn btn-secondary', { type: 'button' });
    cancel.textContent = 'Cancel';
    const confirm = el('button', 'btn btn-primary', { type: 'button' });
    confirm.textContent = 'Use this photo';
    footer.append(cancel, confirm);

    panel.append(header, body, controls, footer);
    overlay.append(panel);
    document.body.append(overlay);

    // --- Transform state --------------------------------------------------

    const cropX = (STAGE - CROP_WIDTH) / 2;
    const cropY = (STAGE - CROP_HEIGHT) / 2;

    let quarterTurns = 0;
    let scale = 1;
    let offsetX = 0;
    let offsetY = 0;

    /** The picture's on-screen size, accounting for quarter turns. */
    const rotatedSize = () => (quarterTurns % 2 === 0
        ? { w: image.naturalWidth, h: image.naturalHeight }
        : { w: image.naturalHeight, h: image.naturalWidth });

    /** The smallest zoom that still covers the crop frame — no empty edges. */
    const minScale = () => {
        const { w, h } = rotatedSize();
        return Math.max(CROP_WIDTH / w, CROP_HEIGHT / h);
    };

    const clamp = () => {
        const floor = minScale();
        if (scale < floor) scale = floor;
        if (scale > floor * MAX_ZOOM) scale = floor * MAX_ZOOM;

        const { w, h } = rotatedSize();
        const limitX = Math.max(0, (w * scale - CROP_WIDTH) / 2);
        const limitY = Math.max(0, (h * scale - CROP_HEIGHT) / 2);
        offsetX = Math.min(limitX, Math.max(-limitX, offsetX));
        offsetY = Math.min(limitY, Math.max(-limitY, offsetY));
    };

    const syncSlider = () => {
        const floor = minScale();
        const ratio = (scale / floor - 1) / (MAX_ZOOM - 1);
        slider.value = String(Math.round(ratio * 99) + 1);
    };

    /** Paints the picture into `ctx`, centred on a box of `boxW` × `boxH`. */
    const paint = (ctx, boxW, boxH, factor) => {
        ctx.save();
        ctx.translate(boxW / 2 + offsetX * factor, boxH / 2 + offsetY * factor);
        ctx.rotate((quarterTurns * Math.PI) / 2);
        const drawW = image.naturalWidth * scale * factor;
        const drawH = image.naturalHeight * scale * factor;
        ctx.drawImage(image, -drawW / 2, -drawH / 2, drawW, drawH);
        ctx.restore();
    };

    const render = () => {
        clamp();

        context.clearRect(0, 0, STAGE, STAGE);

        // The whole picture, then dimmed, then the crop area painted back in
        // at full strength — so you can see what you are cutting away.
        paint(context, STAGE, STAGE, 1);
        context.fillStyle = 'rgba(0, 0, 0, 0.62)';
        context.fillRect(0, 0, STAGE, STAGE);

        context.save();
        context.beginPath();
        context.rect(cropX, cropY, CROP_WIDTH, CROP_HEIGHT);
        context.clip();
        paint(context, STAGE, STAGE, 1);
        context.restore();

        context.strokeStyle = getComputedStyle(document.documentElement)
            .getPropertyValue('--gold-300').trim() || '#E8CE7A';
        context.lineWidth = 2;
        context.strokeRect(cropX + 1, cropY + 1, CROP_WIDTH - 2, CROP_HEIGHT - 2);

        // Live card thumbnail — the same transform, just scaled down.
        previewContext.clearRect(0, 0, preview.width, preview.height);
        paint(previewContext, preview.width, preview.height, preview.width / CROP_WIDTH);

        syncSlider();
    };

    // --- Interaction ------------------------------------------------------

    let dragging = false;
    let lastX = 0;
    let lastY = 0;

    const startDrag = (x, y) => {
        dragging = true;
        lastX = x;
        lastY = y;
        canvas.style.cursor = 'grabbing';
    };

    const moveDrag = (x, y) => {
        if (!dragging) return;
        // On a narrow screen the canvas is displayed smaller than its
        // backing size, so a pointer movement covers more than one canvas
        // unit. Without this the picture lags behind the finger.
        const rect = canvas.getBoundingClientRect();
        const ratio = rect.width > 0 ? canvas.width / rect.width : 1;
        offsetX += (x - lastX) * ratio;
        offsetY += (y - lastY) * ratio;
        lastX = x;
        lastY = y;
        render();
    };

    const endDrag = () => {
        dragging = false;
        canvas.style.cursor = 'grab';
    };

    canvas.addEventListener('pointerdown', (event) => {
        canvas.setPointerCapture(event.pointerId);
        startDrag(event.clientX, event.clientY);
    });
    canvas.addEventListener('pointermove', (event) => moveDrag(event.clientX, event.clientY));
    canvas.addEventListener('pointerup', endDrag);
    canvas.addEventListener('pointercancel', endDrag);

    canvas.addEventListener('wheel', (event) => {
        event.preventDefault();
        scale *= event.deltaY < 0 ? 1.1 : 1 / 1.1;
        render();
    }, { passive: false });

    // Keyboard: arrows nudge, +/- zoom — the stage is focusable.
    canvas.addEventListener('keydown', (event) => {
        const step = event.shiftKey ? 20 : 6;
        const moves = {
            ArrowLeft: [step, 0], ArrowRight: [-step, 0],
            ArrowUp: [0, step], ArrowDown: [0, -step],
        };
        if (moves[event.key]) {
            event.preventDefault();
            offsetX += moves[event.key][0];
            offsetY += moves[event.key][1];
            render();
        } else if (event.key === '+' || event.key === '=') {
            event.preventDefault();
            scale *= 1.1;
            render();
        } else if (event.key === '-') {
            event.preventDefault();
            scale /= 1.1;
            render();
        }
    });

    slider.addEventListener('input', () => {
        const floor = minScale();
        const ratio = (Number(slider.value) - 1) / 99;
        scale = floor * (1 + ratio * (MAX_ZOOM - 1));
        render();
    });

    zoomIn.addEventListener('click', () => { scale *= 1.15; render(); });
    zoomOut.addEventListener('click', () => { scale /= 1.15; render(); });

    const turn = (delta) => {
        quarterTurns = (quarterTurns + delta + 4) % 4;
        // The picture's footprint changed, so re-fit rather than leaving a gap.
        scale = Math.max(scale, minScale());
        offsetX = 0;
        offsetY = 0;
        render();
    };
    rotateLeft.addEventListener('click', () => turn(-1));
    rotateRight.addEventListener('click', () => turn(1));

    const resetAll = () => {
        quarterTurns = 0;
        offsetX = 0;
        offsetY = 0;
        scale = minScale();
        render();
    };
    reset.addEventListener('click', resetAll);

    const close = (result) => done(result);

    cancel.addEventListener('click', () => close(null));
    overlay.addEventListener('mousedown', (event) => {
        if (event.target === overlay) close(null);
    });
    overlay.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close(null);
    });

    confirm.addEventListener('click', () => {
        const out = document.createElement('canvas');
        out.width = OUTPUT_WIDTH;
        out.height = OUTPUT_HEIGHT;
        const outContext = out.getContext('2d');
        outContext.imageSmoothingQuality = 'high';

        paint(outContext, OUTPUT_WIDTH, OUTPUT_HEIGHT, OUTPUT_WIDTH / CROP_WIDTH);

        out.toBlob((blob) => {
            if (!blob) {
                close(null);
                return;
            }
            const base = (file.name || 'photo').replace(/\.[^.]+$/, '');
            close(new File([blob], `${base}.jpg`, { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.92);
    });

    resetAll();
    canvas.style.cursor = 'grab';
    confirm.focus();

    return overlay;
}

/**
 * Wires one upload field: choosing a file opens the editor, and the cropped
 * result replaces what the form will post.
 */
export function initPhotoUpload(root) {
    const input = root.querySelector('input[type="file"]');
    const preview = root.querySelector('[data-photo-preview]');
    if (!input || input.dataset.editorReady) return;
    input.dataset.editorReady = 'true';

    // What was in the field before, so Cancel can put it back.
    let accepted = null;

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;

        const cropped = await openEditor(file);

        if (!cropped) {
            // Backed out: restore whatever was already accepted.
            const carry = new DataTransfer();
            if (accepted) carry.items.add(accepted);
            input.files = carry.files;
            return;
        }

        const carry = new DataTransfer();
        carry.items.add(cropped);
        input.files = carry.files;
        accepted = cropped;

        if (preview) {
            if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
            const url = URL.createObjectURL(cropped);
            preview.dataset.objectUrl = url;
            preview.src = url;
            preview.hidden = false;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    for (const root of document.querySelectorAll('[data-photo-upload]')) {
        initPhotoUpload(root);
    }
});
