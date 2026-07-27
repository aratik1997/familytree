import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { computeLayout, nameLines } from './layout';

const SPRITE_SIZE = 150;

/**
 * The showcase reads the same tokens the 2D view does, so a theme change or
 * a palette retune lands in both without either holding its own copy of the
 * colours.
 */
function readTokens(container) {
    const styles = getComputedStyle(container);
    const token = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;

    return {
        gold: token('--gold-500', '#C9A227'),
        maroon: token('--maroon-500', '#7B1E3B'),
        leaf: token('--leaf-600', '#2E7D5B'),
        male: token('--male-500', '#2F6FED'),
        female: token('--female-500', '#EC6BA5'),
        textHi: token('--text-hi', '#F4F1E8'),
        textMid: token('--text-mid', '#B9C7BF'),
        ink900: token('--ink-900', '#071612'),
        ink700: token('--ink-700', '#113429'),
    };
}

function genderColor(gender, tokens) {
    const key = gender?.toLowerCase?.();
    if (key === 'male') return tokens.male;
    if (key === 'female') return tokens.female;
    return tokens.gold;
}

const GENDER_GLYPH = { male: '♂', female: '♀' };

function lifespan(person) {
    if (person.is_deceased) {
        const born = person.birth_year ?? '—';
        const died = person.death_year ?? '—';
        return `${born} – ${died}`;
    }
    return person.birth_year ? `b. ${person.birth_year}` : '';
}

function roundedRect(ctx, x, y, w, h, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + w, y, x + w, y + h, radius);
    ctx.arcTo(x + w, y + h, x, y + h, radius);
    ctx.arcTo(x, y + h, x, y, radius);
    ctx.arcTo(x, y, x + w, y, radius);
    ctx.closePath();
}

/**
 * Only the top two corners rounded — for the photo, whose bottom edge sits
 * mid-card at the divider, not at a real card corner (rounding all four left
 * two little notches there showing the background through).
 */
function topRoundedRect(ctx, x, y, w, h, radius) {
    ctx.beginPath();
    ctx.moveTo(x, y + h);
    ctx.lineTo(x, y + radius);
    ctx.arcTo(x, y, x + radius, y, radius);
    ctx.lineTo(x + w - radius, y);
    ctx.arcTo(x + w, y, x + w, y + radius, radius);
    ctx.lineTo(x + w, y + h);
    ctx.closePath();
}

const CANVAS_SIZE = 256;
const CANVAS_SCALE = 2;

/** The same square tile the 2D view draws: photo on top, caption beneath. */
function drawNodeCanvas(node, tokens) {
    const canvas = document.createElement('canvas');
    // Rendered at 2x and scaled back down via ctx.scale, so the sprite
    // texture stays sharp instead of blurring when the camera moves in.
    canvas.width = canvas.height = CANVAS_SIZE * CANVAS_SCALE;
    const ctx = canvas.getContext('2d');
    ctx.scale(CANVAS_SCALE, CANVAS_SCALE);

    // Portrait tile, matching the 2D card's proportions.
    const boxW = 158;
    const boxH = 244;
    const photoH = 180;
    const x = (CANVAS_SIZE - boxW) / 2;
    const y = (CANVAS_SIZE - boxH) / 2;
    const cx = CANVAS_SIZE / 2;
    const accent = genderColor(node.gender, tokens);

    // Card plate.
    ctx.save();
    ctx.shadowColor = 'rgba(0, 0, 0, 0.38)';
    ctx.shadowBlur = 16;
    ctx.shadowOffsetY = 6;
    roundedRect(ctx, x, y, boxW, boxH, 18);
    ctx.fillStyle = tokens.ink700;
    ctx.fill();
    ctx.restore();

    // Photo panel, top corners only.
    ctx.save();
    topRoundedRect(ctx, x, y, boxW, photoH, 18);
    ctx.clip();
    if (node.photoImage) {
        ctx.drawImage(node.photoImage, x, y, boxW, photoH);
    } else {
        ctx.fillStyle = accent;
        ctx.globalAlpha = 0.16;
        ctx.fillRect(x, y, boxW, photoH);
        ctx.globalAlpha = 1;
        ctx.fillStyle = accent;
        ctx.font = "600 40px 'Cormorant Garamond', serif";
        ctx.textAlign = 'center';
        const initials = (node.name ?? '?').split(' ').filter(Boolean).slice(0, 2)
            .map((w) => w[0]).join('').toUpperCase();
        ctx.fillText(initials, cx, y + photoH / 2 + 14);
    }
    ctx.restore();

    // Gold hairline around the card.
    roundedRect(ctx, x, y, boxW, boxH, 18);
    ctx.strokeStyle = tokens.gold;
    ctx.globalAlpha = 0.45;
    ctx.lineWidth = 1.5;
    ctx.stroke();
    ctx.globalAlpha = 1;

    // Dashed frame on the photo when the account is unclaimed.
    if (!node.is_claimed) {
        topRoundedRect(ctx, x + 3, y + 3, boxW - 6, photoH - 6, 15);
        ctx.setLineDash([8, 7]);
        ctx.strokeStyle = accent;
        ctx.lineWidth = 2.5;
        ctx.stroke();
        ctx.setLineDash([]);
    }

    // Gender rail down the leading edge.
    ctx.beginPath();
    ctx.moveTo(x + 2.5, y + 18);
    ctx.lineTo(x + 2.5, y + boxH - 18);
    ctx.strokeStyle = accent;
    ctx.lineWidth = 4;
    ctx.lineCap = 'round';
    ctx.stroke();

    // Divider under the photo.
    ctx.beginPath();
    ctx.moveTo(x + 10, y + photoH);
    ctx.lineTo(x + boxW - 10, y + photoH);
    ctx.strokeStyle = tokens.gold;
    ctx.globalAlpha = 0.5;
    ctx.lineWidth = 1.5;
    ctx.stroke();
    ctx.globalAlpha = 1;

    // Caption: name, then birth year.
    const lines = nameLines(node.name ?? '');
    ctx.textAlign = 'center';
    ctx.fillStyle = tokens.textHi;
    ctx.font = "600 17px 'Cormorant Garamond', serif";
    lines.forEach((line, i) => {
        ctx.fillText(line, cx, y + photoH + 22 + i * 18, boxW - 16);
    });

    const glyph = GENDER_GLYPH[node.gender?.toLowerCase?.()] ?? '·';
    const years = lifespan(node);
    ctx.fillStyle = tokens.textMid;
    ctx.font = "13px 'JetBrains Mono', monospace";
    ctx.fillText(`${glyph} ${years}`.trim(), cx, y + photoH + 24 + lines.length * 18, boxW - 16);

    // Deceased: maroon ribbon across the top-right corner.
    if (node.is_deceased) {
        ctx.beginPath();
        ctx.moveTo(x + boxW - 34, y);
        ctx.lineTo(x + boxW, y + 34);
        ctx.strokeStyle = tokens.maroon;
        ctx.lineWidth = 6;
        ctx.stroke();
    }

    return canvas;
}

function loadImage(url) {
    return new Promise((resolve) => {
        if (!url) return resolve(null);
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

/**
 * Mount the Three.js showcase view into `container`. Returns an `unmount()`
 * function that disposes the renderer/scene/geometries/textures and removes
 * all listeners — important since this view is toggled on/off repeatedly
 * without a page reload.
 */
export async function render3d(container, data, { onSelectNode } = {}) {
    const layout = computeLayout(data);
    const centerX = layout.width / 2;
    const centerY = layout.height / 2;

    // Pre-load every photo so canvases can be drawn synchronously afterwards.
    await Promise.all(
        layout.nodes.map(async (n) => {
            n.data.photoImage = await loadImage(n.data.photo_url);
        })
    );

    const scene = new THREE.Scene();
    const tokens = readTokens(container);
    scene.background = new THREE.Color(tokens.ink900);

    const camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 1, 5000);
    camera.position.set(0, -layout.height * 0.3, layout.height * 0.9);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.9));
    const dirLight = new THREE.DirectionalLight(0xffffff, 0.6);
    dirLight.position.set(0, 200, 400);
    scene.add(dirLight);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.target.set(0, 0, 0);

    const toScenePosition = (node) => new THREE.Vector3(
        node.x - centerX,
        -(node.y - centerY),
        0
    );

    const disposables = [];
    const spritesByNodeId = new Map();

    const maxAnisotropy = renderer.capabilities.getMaxAnisotropy();

    for (const node of layout.nodes) {
        const canvas = drawNodeCanvas(node.data, tokens);
        const texture = new THREE.CanvasTexture(canvas);
        texture.anisotropy = maxAnisotropy;
        const material = new THREE.SpriteMaterial({ map: texture });
        const sprite = new THREE.Sprite(material);
        sprite.position.copy(toScenePosition(node));
        sprite.scale.set(SPRITE_SIZE, SPRITE_SIZE, 1);
        sprite.userData.personId = node.data.id;
        scene.add(sprite);
        spritesByNodeId.set(node.data.id, sprite);
        disposables.push(texture, material, sprite.geometry);
    }

    const parentMaterial = new THREE.LineBasicMaterial({ color: new THREE.Color(tokens.leaf) });
    const stepMaterial = new THREE.LineDashedMaterial({ color: new THREE.Color(tokens.leaf), dashSize: 8, gapSize: 6 });
    const spouseMaterial = new THREE.LineBasicMaterial({ color: new THREE.Color(tokens.gold) });
    disposables.push(parentMaterial, stepMaterial, spouseMaterial);

    for (const link of layout.renderLinks) {
        // Square elbow — down, across, down — matching the 2D view rather
        // than cutting a diagonal between the two cards.
        const sourcePos = toScenePosition(link.source);
        const targetPos = toScenePosition(link.target);
        const midY = (sourcePos.y + targetPos.y) / 2;
        const geometry = new THREE.BufferGeometry().setFromPoints([
            sourcePos,
            new THREE.Vector3(sourcePos.x, midY, 0),
            new THREE.Vector3(targetPos.x, midY, 0),
            targetPos,
        ]);
        const material = link.dashed ? stepMaterial : parentMaterial;
        const line = new THREE.Line(geometry, material);
        if (material === stepMaterial) line.computeLineDistances();
        scene.add(line);
        disposables.push(geometry);
    }

    for (const { source, target } of layout.spouseLinks) {
        const geometry = new THREE.BufferGeometry().setFromPoints([
            toScenePosition(source),
            toScenePosition(target),
        ]);
        const line = new THREE.Line(geometry, spouseMaterial);
        scene.add(line);
        disposables.push(geometry);
    }

    const raycaster = new THREE.Raycaster();
    const pointer = new THREE.Vector2();

    function handleClick(event) {
        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        raycaster.setFromCamera(pointer, camera);
        const [hit] = raycaster.intersectObjects([...spritesByNodeId.values()]);
        if (hit) onSelectNode?.({ id: hit.object.userData.personId });
    }
    renderer.domElement.addEventListener('click', handleClick);

    let frameId;
    function animate() {
        frameId = requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    function handleResize() {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }
    window.addEventListener('resize', handleResize);

    return function unmount() {
        cancelAnimationFrame(frameId);
        window.removeEventListener('resize', handleResize);
        renderer.domElement.removeEventListener('click', handleClick);
        controls.dispose();
        for (const disposable of disposables) disposable.dispose?.();
        renderer.dispose();
        container.innerHTML = '';
    };
}
