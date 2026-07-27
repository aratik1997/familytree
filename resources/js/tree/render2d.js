import * as d3 from 'd3';
import { computeLayout, estimateTextWidth, nameLines, CARD } from './layout';
import { hashSeed, seededRange } from './seed';
import { startFallingLeaves } from './leaves';

/**
 * A rectangle with only its top two corners rounded — for the photo panel,
 * whose bottom edge sits mid-card at the divider rather than at a real card
 * corner. Rounding all four would leave two notches showing the card through.
 */
function topRoundedRectPath(x, y, w, h, r) {
    return `M ${x} ${y + r}
        A ${r} ${r} 0 0 1 ${x + r} ${y}
        H ${x + w - r}
        A ${r} ${r} 0 0 1 ${x + w} ${y + r}
        V ${y + h}
        H ${x}
        Z`;
}

/**
 * Every colour comes from the stylesheet's tokens rather than a literal in
 * here, so both themes and any future retune stay in one place. Read once per
 * render — cheap, and it picks up a theme switch on the next paint.
 */
function readTokens(container) {
    const styles = getComputedStyle(container);
    const token = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;

    return {
        gold: token('--gold-500', '#C9A227'),
        goldLight: token('--gold-300', '#E8CE7A'),
        goldText: token('--gold-text', '#E8CE7A'),
        maroon: token('--maroon-500', '#7B1E3B'),
        bark600: token('--bark-600', '#4A3524'),
        leaf600: token('--leaf-600', '#2E7D5B'),
        leaf400: token('--leaf-400', '#4FAE7F'),
        leafAutumn: token('--leaf-autumn', '#C08A3E'),
        male: token('--male-500', '#2F6FED'),
        female: token('--female-500', '#EC6BA5'),
        textHi: token('--text-hi', '#F4F1E8'),
        textMid: token('--text-mid', '#B9C7BF'),
        textLow: token('--text-low', '#7E9088'),
        ink900: token('--ink-900', '#071612'),
        ink700: token('--ink-700', '#113429'),
        ink600: token('--ink-600', '#17453A'),
    };
}

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Gender drives a colour *and* a glyph — colour is never the only signal. */
const GENDER_GLYPH = { male: '♂', female: '♀' };

function genderColor(gender, tokens) {
    const key = gender?.toLowerCase?.();
    if (key === 'male') return tokens.male;
    if (key === 'female') return tokens.female;
    return tokens.gold;
}

function genderLabel(gender) {
    const key = gender?.toLowerCase?.();
    if (key === 'male') return 'Male';
    if (key === 'female') return 'Female';
    return 'Gender not recorded';
}

/** "1958 – 2019", "b. 1994", or nothing at all. Always in the mono face. */
function lifespan(person) {
    const born = person.birth_year;
    if (person.is_deceased) {
        const died = person.death_year;
        if (born && died) return `${born} – ${died}`;
        if (born) return `${born} – —`;
        return died ? `— – ${died}` : '';
    }
    return born ? `b. ${born}` : '';
}

function isBirthdayToday(person) {
    if (!person.birth_date) return false;
    const today = new Date();
    const [, month, day] = person.birth_date.split('-').map(Number);
    return today.getMonth() + 1 === month && today.getDate() === day;
}

/**
 * A branch from parent to child: straight down, straight across, straight
 * down again, with square corners. Where several children share a parent (or
 * a couple's midpoint) their drop segments overlap into one stem, giving the
 * classic genealogy-chart look with children fanning off either side of it.
 */
function branchPath(sx, sy, tx, ty) {
    const midY = (sy + ty) / 2;

    return `M ${sx} ${sy} L ${sx} ${midY} L ${tx} ${midY} L ${tx} ${ty}`;
}

/**
 * Lay out and draw the family tree into `container` (a DOM element).
 *
 * Two distinct permissions:
 *  - `draggable` lets anyone shift a card around to read the chart more
 *    easily. For a non-admin that is purely a local view adjustment — it
 *    changes nothing for anybody else and is forgotten on reload.
 *  - `editable` (Super Admin only) is what actually changes records:
 *    dropping a card onto another links them as parent and child
 *    (`onReparent`), and a rearrangement is saved for everyone
 *    (`onReposition`). Clicking a line never changes anything either way —
 *    a branch just highlights, and a marriage line narrows the view.
 */
export function render2d(container, data, { onSelectNode, onSelectCouple, editable = false, draggable = true, onReparent, onReposition, showLeaves = true } = {}) {
    const tokens = readTokens(container);
    const layout = computeLayout(data);
    const { width, height, nodes, spouseLinks, renderLinks, generationRows, rootAnchor, maxGeneration } = layout;

    container.innerHTML = '';
    container.style.position = 'relative';

    // Performance guards scale the ornament down as the tree grows.
    const reduced = prefersReducedMotion();
    const nodeCount = nodes.length;
    const windEnabled = !reduced && nodeCount <= 800;
    const turbulenceEnabled = windEnabled && nodeCount <= 400;
    const maxSwayDepth = nodeCount > 400 ? 3 : Infinity;
    const leavesEnabled = showLeaves && !reduced && nodeCount <= 800
        && window.matchMedia('(min-width: 640px)').matches;

    // One SVG unit per screen pixel. The zoom transform alone decides how the
    // tree is framed — a viewBox sized to the *layout* would scale the whole
    // drawing a second time on top of it, shrinking wide trees to a smudge.
    const viewportWidth = Math.max(1, container.clientWidth);
    const viewportHeight = Math.max(1, container.clientHeight);

    const svg = d3.select(container)
        .append('svg')
        .attr('width', '100%')
        .attr('height', '100%')
        .attr('viewBox', [0, 0, viewportWidth, viewportHeight])
        .attr('role', 'img')
        .attr('aria-label', 'Family tree diagram');

    const defs = svg.append('defs');

    // --- Materials -------------------------------------------------------

    // Turbulence for the leaf layer only — never applied to cards or text,
    // where it would wreck both readability and framerate.
    if (turbulenceEnabled) {
        const breeze = defs.append('filter')
            .attr('id', 'breeze')
            .attr('x', '-20%').attr('y', '-20%')
            .attr('width', '140%').attr('height', '140%');
        const turbulence = breeze.append('feTurbulence')
            .attr('type', 'fractalNoise')
            .attr('baseFrequency', '0.008 0.014')
            .attr('numOctaves', 2)
            .attr('seed', 7)
            .attr('result', 'noise');
        turbulence.append('animate')
            .attr('attributeName', 'baseFrequency')
            .attr('dur', '14s')
            .attr('values', '0.008 0.014; 0.011 0.010; 0.008 0.014')
            .attr('repeatCount', 'indefinite');
        breeze.append('feDisplacementMap')
            .attr('in', 'SourceGraphic')
            .attr('in2', 'noise')
            .attr('scale', 3)
            .attr('xChannelSelector', 'R')
            .attr('yChannelSelector', 'G');
    }

    const root = svg.append('g');

    const zoom = d3.zoom()
        .scaleExtent([0.25, 2.5])
        .on('zoom', (event) => root.attr('transform', event.transform));
    svg.call(zoom);

    // Frame the tree with its trunk centred and its head near the top of the
    // viewport. A wide tree is allowed to overflow rather than shrink past
    // legibility — panning is cheaper than unreadable cards.
    const MIN_LEGIBLE_SCALE = 0.5;
    const fit = Math.min((viewportWidth - 40) / width, (viewportHeight - 40) / height);
    const scale = Math.max(MIN_LEGIBLE_SCALE, Math.min(1, fit));
    const initialX = viewportWidth / 2 - rootAnchor.x * scale;
    const initialY = 16;
    svg.call(zoom.transform, d3.zoomIdentity.translate(initialX, initialY).scale(scale));

    // --- Generation levels -----------------------------------------------
    // Each generation is ruled off with its own level line. Cards slide along
    // their line but never leave it, so the chart reads like a ledger.

    const ordinal = (n) => {
        const suffixes = ['th', 'st', 'nd', 'rd'];
        const mod100 = n % 100;
        return n + (suffixes[(mod100 - 20) % 10] ?? suffixes[mod100] ?? suffixes[0]);
    };

    const levelGroup = root.append('g').attr('class', 'generation-levels');
    const levelY = (row) => row.y - CARD.height / 2 - 22;

    levelGroup.selectAll('line.generation-rule')
        .data(generationRows)
        .join('line')
        .attr('class', 'generation-rule')
        .attr('x1', 0)
        .attr('x2', width)
        .attr('y1', levelY)
        .attr('y2', levelY)
        .attr('stroke', tokens.gold)
        .attr('stroke-opacity', 0.22)
        .attr('stroke-width', 1)
        .attr('pointer-events', 'none');

    levelGroup.selectAll('text.generation-label')
        .data(generationRows)
        .join('text')
        .attr('class', 'generation-label')
        .attr('x', 10)
        .attr('y', (d) => levelY(d) - 7)
        .attr('font-size', 11)
        .attr('font-weight', 600)
        .attr('letter-spacing', '0.12em')
        .attr('fill', tokens.textLow)
        .attr('pointer-events', 'none')
        .style('text-transform', 'uppercase')
        .text((d) => `${ordinal(d.generation)} Generation`);

    // --- Branches --------------------------------------------------------

    const nodeById = new Map(nodes.map((n) => [n.data.id, n]));
    const tempPos = new Map();
    const livePos = (id) => tempPos.get(id) ?? nodeById.get(id);

    // Connectors taper with depth and shift from bark to leaf as they climb.
    const barkToLeaf = d3.interpolateRgb(tokens.bark600, tokens.leaf600);
    const branchWidth = (generation) => Math.max(1.5, 10 - (generation - 1) * 1.6);
    const branchColor = (generation) =>
        barkToLeaf(maxGeneration <= 1 ? 0 : (generation - 1) / (maxGeneration - 1));

    const linkPathFor = (link) => {
        let sx;
        let sy;
        if (link.parentIds.length === 2) {
            const p1 = livePos(link.parentIds[0]);
            const p2 = livePos(link.parentIds[1]);
            sx = (p1.x + p2.x) / 2;
            sy = (p1.y + p2.y) / 2;
        } else {
            const p = livePos(link.parentIds[0]);
            sx = p.x;
            sy = p.y;
        }
        const t = livePos(link.target.data.id);
        return branchPath(sx, sy, t.x, t.y);
    };

    const linkGroup = root.append('g').attr('class', 'branches');

    const visibleLinks = linkGroup.selectAll('path.visible-link')
        .data(renderLinks)
        .join('path')
        .attr('class', 'visible-link')
        .attr('fill', 'none')
        .attr('stroke', (d) => branchColor(d.target.generation))
        .attr('stroke-width', (d) => branchWidth(d.target.generation))
        .attr('stroke-linecap', 'round')
        .attr('pointer-events', 'none')
        .attr('stroke-dasharray', (d) => (d.dashed ? '7,5' : null))
        .attr('d', linkPathFor);

    // A dashed stem gets a small leaf glyph at the join, marking a step or
    // adoptive tie without needing a legend to decode it.
    linkGroup.selectAll('path.graft-mark')
        .data(renderLinks.filter((d) => d.dashed))
        .join('path')
        .attr('class', 'graft-mark')
        .attr('fill', tokens.leaf400)
        .attr('pointer-events', 'none')
        .attr('transform', (d) => {
            const t = livePos(d.target.data.id);
            // Just above the child card, where the stem lands on it.
            return `translate(${t.x}, ${t.y - CARD.height / 2 - 4})`;
        })
        .attr('d', 'M0 0 C 5 -3, 8 -8, 6 -13 C 1 -12, -2 -7, 0 0 Z');

    const linkHitAreas = linkGroup.selectAll('path.hit-area')
        .data(renderLinks)
        .join('path')
        .attr('class', 'hit-area')
        .attr('fill', 'none')
        .attr('stroke', 'transparent')
        .attr('stroke-width', 16)
        .attr('cursor', 'pointer')
        .attr('d', linkPathFor)
        // Clicking a branch only picks it out — it highlights, and changes
        // nothing about anybody's record.
        .on('click', (event, d) => {
            event.stopPropagation();
            clearEmphasis();
            d3.select(visibleLinks.nodes()[renderLinks.indexOf(d)])
                .attr('stroke-width', branchWidth(d.target.generation) + 2.5)
                .attr('stroke', tokens.goldLight);
        });

    const clearEmphasis = () => {
        visibleLinks
            .attr('stroke-width', (d) => branchWidth(d.target.generation))
            .attr('stroke', (d) => branchColor(d.target.generation));
        visibleSpouseLines.attr('stroke-width', 2);
    };

    // --- Marriage lines --------------------------------------------------

    const spouseGroup = root.append('g').attr('class', 'marriages');

    const marriageGeometry = (link) => {
        const s = livePos(link.source.data.id);
        const t = livePos(link.target.data.id);
        const [left, right] = s.x <= t.x ? [s, t] : [t, s];
        return { left, right, midX: (left.x + right.x) / 2, midY: (left.y + right.y) / 2 };
    };

    const marriageStatus = (link) => link.status ?? 'married';

    const visibleSpouseLines = spouseGroup.selectAll('path.visible-link')
        .data(spouseLinks)
        .join('path')
        .attr('class', 'visible-link')
        .attr('fill', 'none')
        .attr('stroke-width', 2)
        .attr('pointer-events', 'none')
        .each(function (d) { applyMarriageLine.call(this, d); });

    // A widowed union renders half its line in maroon; a divorced one is
    // dashed with a clear break at the centre.
    function applyMarriageLine(d) {
        const { left, right, midX, midY } = marriageGeometry(d);
        const status = marriageStatus(d);
        const sel = d3.select(this);

        if (status === 'divorced' || status === 'separated') {
            sel.attr('d', `M ${left.x} ${left.y} L ${midX - 9} ${midY} M ${midX + 9} ${midY} L ${right.x} ${right.y}`)
                .attr('stroke', tokens.gold)
                .attr('stroke-dasharray', '5,4');
            return;
        }

        if (status === 'widowed') {
            sel.attr('d', `M ${left.x} ${left.y} L ${right.x} ${right.y}`)
                .attr('stroke', tokens.maroon)
                .attr('stroke-dasharray', null);
            return;
        }

        sel.attr('d', `M ${left.x} ${left.y} L ${right.x} ${right.y}`)
            .attr('stroke', tokens.gold)
            .attr('stroke-dasharray', null);
    }

    // The 10px gold diamond that marks the exact midpoint of a union.
    const marriageDiamonds = spouseGroup.selectAll('rect.union-mark')
        .data(spouseLinks)
        .join('rect')
        .attr('class', 'union-mark')
        .attr('width', 10)
        .attr('height', 10)
        .attr('fill', (d) => (marriageStatus(d) === 'widowed' ? tokens.maroon : tokens.gold))
        .attr('pointer-events', 'none')
        .each(function (d) {
            const { midX, midY } = marriageGeometry(d);
            d3.select(this).attr('transform', `translate(${midX - 5}, ${midY - 5}) rotate(45, 5, 5)`);
        });

    const spouseHitAreas = spouseGroup.selectAll('line.hit-area')
        .data(spouseLinks)
        .join('line')
        .attr('class', 'hit-area')
        .attr('stroke', 'transparent')
        .attr('stroke-width', 16)
        .attr('cursor', 'pointer')
        .each(function (d) {
            const { left, right } = marriageGeometry(d);
            d3.select(this).attr('x1', left.x).attr('y1', left.y).attr('x2', right.x).attr('y2', right.y);
        })
        .on('click', (event, d) => {
            event.stopPropagation();
            clearEmphasis();
            d3.select(visibleSpouseLines.nodes()[spouseLinks.indexOf(d)]).attr('stroke-width', 4);
            onSelectCouple?.(d.source.data, d.target.data);
        });

    const refreshAllLines = () => {
        visibleLinks.attr('d', linkPathFor);
        linkHitAreas.attr('d', linkPathFor);
        visibleSpouseLines.each(function (d) { applyMarriageLine.call(this, d); });
        marriageDiamonds.each(function (d) {
            const { midX, midY } = marriageGeometry(d);
            d3.select(this).attr('transform', `translate(${midX - 5}, ${midY - 5}) rotate(45, 5, 5)`);
        });
        spouseHitAreas.each(function (d) {
            const { left, right } = marriageGeometry(d);
            d3.select(this).attr('x1', left.x).attr('y1', left.y).attr('x2', right.x).attr('y2', right.y);
        });
    };

    // --- Foliage ---------------------------------------------------------
    // Leaf clusters scattered along each branch, denser toward the outer
    // generations. A deceased person's branch turns autumn.

    const leafLayer = root.append('g').attr('class', 'foliage').attr('pointer-events', 'none');
    if (turbulenceEnabled) leafLayer.attr('filter', 'url(#breeze)');

    const LEAF_PATH = 'M0 0 C 6 -4, 10 -10, 7 -17 C 1 -15, -3 -8, 0 0 Z';

    for (const link of renderLinks) {
        const child = link.target;
        const seed = hashSeed(child.data.id);
        const depthRatio = maxGeneration <= 1 ? 1 : (child.generation - 1) / (maxGeneration - 1);
        const count = Math.round(2 + depthRatio * 4);
        const deceased = child.data.is_deceased;

        const pathNode = visibleLinks.nodes()[renderLinks.indexOf(link)];
        if (!pathNode) continue;
        const totalLength = pathNode.getTotalLength();

        for (let i = 0; i < count; i++) {
            const at = 0.25 + (i / Math.max(1, count)) * 0.6;
            const point = pathNode.getPointAtLength(totalLength * at);
            const rotation = seededRange(seed + i * 37, 0, 360);
            const size = seededRange(seed + i * 53, 0.5, 0.85);

            let fill;
            if (deceased) {
                fill = i % 2 === 0 ? tokens.leafAutumn : tokens.maroon;
            } else {
                fill = i % 2 === 0 ? tokens.leaf600 : tokens.leaf400;
            }

            leafLayer.append('path')
                .attr('d', LEAF_PATH)
                .attr('fill', fill)
                .attr('fill-opacity', 0.85)
                .attr('transform', `translate(${point.x}, ${point.y}) rotate(${rotation}) scale(${size})`);
        }
    }

    // --- Nodes -----------------------------------------------------------

    const spouseByNodeId = new Map();
    for (const { source, target } of spouseLinks) {
        spouseByNodeId.set(source.data.id, target);
        spouseByNodeId.set(target.data.id, source);
    }

    const CLICK_SLOP = 4;
    let dragOrigin = { x: 0, y: 0 };

    const halfW = CARD.width / 2;
    const halfH = CARD.height / 2;

    const nodeGroup = root.append('g').attr('class', 'people')
        .selectAll('g')
        .data(nodes)
        .join('g')
        .attr('transform', (d) => `translate(${d.x}, ${d.y})`)
        .attr('data-person-id', (d) => d.data.id)
        .attr('data-generation', (d) => d.generation)
        .attr('cursor', draggable ? 'grab' : 'pointer')
        .attr('tabindex', 0)
        .attr('role', 'button')
        .attr('aria-label', (d) => {
            const parts = [d.data.name, genderLabel(d.data.gender)];
            const years = lifespan(d.data);
            if (years) parts.push(years);
            if (d.data.is_deceased) parts.push('Deceased');
            return parts.join(', ');
        })
        .on('click', (_event, d) => onSelectNode?.(d.data))
        .on('keydown', (event, d) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                onSelectNode?.(d.data);
            }
        });

    // Card plate — a portrait tile: photo across the top, name and birth year
    // beneath it. The gender colour runs right around the border; dashed
    // means the account has not been claimed yet. Colour is never the only
    // signal, so the ♂/♀ glyph in the caption repeats it.
    nodeGroup.append('rect')
        .attr('class', (d) => `card-plate${isBirthdayToday(d.data) ? ' birthday-ring' : ''}`)
        .attr('x', -halfW)
        .attr('y', -halfH)
        .attr('width', CARD.width)
        .attr('height', CARD.height)
        .attr('rx', CARD.radius)
        .attr('fill', tokens.ink700)
        .attr('fill-opacity', 0.95)
        .attr('stroke', (d) => genderColor(d.data.gender, tokens))
        .attr('stroke-width', 2.5)
        .attr('stroke-dasharray', (d) => (d.data.is_claimed ? null : '7,5'));


    // Photo panel, clipped so only its top corners follow the card's radius.
    const photoTop = -halfH;
    const photoBottom = photoTop + CARD.photoHeight;
    const clipPrefix = 'tree-photo-clip-';

    nodeGroup.append('clipPath')
        .attr('id', (d) => clipPrefix + d.data.id)
        .append('path')
        .attr('d', topRoundedRectPath(-halfW, photoTop, CARD.width, CARD.photoHeight, CARD.radius));

    nodeGroup.filter((d) => !!d.data.photo_url)
        .append('image')
        .attr('href', (d) => d.data.photo_url)
        .attr('x', -halfW)
        .attr('y', photoTop)
        .attr('width', CARD.width)
        .attr('height', CARD.photoHeight)
        .attr('clip-path', (d) => `url(#${clipPrefix + d.data.id})`)
        .attr('preserveAspectRatio', 'xMidYMid slice')
        // Someone who has died is shown desaturated, not hidden.
        .style('filter', (d) => (d.data.is_deceased ? 'saturate(0.35)' : null));

    // Initials on a gender-tinted panel when there is no photo.
    const withoutPhoto = nodeGroup.filter((d) => !d.data.photo_url);
    withoutPhoto.append('path')
        .attr('d', topRoundedRectPath(-halfW, photoTop, CARD.width, CARD.photoHeight, CARD.radius))
        .attr('fill', (d) => genderColor(d.data.gender, tokens))
        .attr('fill-opacity', 0.16);
    withoutPhoto.append('text')
        .attr('x', 0)
        .attr('y', photoTop + CARD.photoHeight / 2 + 10)
        .attr('text-anchor', 'middle')
        .attr('font-size', 28)
        .attr('font-weight', 600)
        .attr('fill', (d) => genderColor(d.data.gender, tokens))
        .attr('fill-opacity', 0.85)
        .text((d) => (d.data.name ?? '?').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase());

    // Gold divider between the photo and the caption.
    nodeGroup.append('line')
        .attr('x1', -halfW + 8)
        .attr('x2', halfW - 8)
        .attr('y1', photoBottom)
        .attr('y2', photoBottom)
        .attr('stroke', tokens.gold)
        .attr('stroke-width', 1)
        .attr('stroke-opacity', 0.5);

    // Caption: name, then birth year in the mono face.
    const captionWidth = CARD.width - 16;

    nodeGroup.each(function (d) {
        const group = d3.select(this);
        const lines = nameLines(d.data.name ?? '').map((line) => {
            if (estimateTextWidth(line) <= captionWidth) return line;
            const maxChars = Math.max(4, Math.floor(captionWidth / 5.9));
            return `${line.slice(0, maxChars - 1)}…`;
        });

        // Centre the caption block in whatever room is left below the photo.
        const lineHeight = 13;
        const blockHeight = lines.length * lineHeight + 14;
        const captionTop = photoBottom + ((halfH - photoBottom) - blockHeight) / 2 + 12;

        group.append('text')
            .attr('text-anchor', 'middle')
            .attr('font-size', 12)
            .attr('font-weight', 600)
            .attr('fill', tokens.textHi)
            .style('font-family', 'var(--font-display)')
            .selectAll('tspan')
            .data(lines)
            .join('tspan')
            .attr('x', 0)
            .attr('y', (_line, i) => captionTop + i * lineHeight)
            .text((line) => line);

        const glyph = GENDER_GLYPH[d.data.gender?.toLowerCase?.()] ?? '·';
        const years = lifespan(d.data);
        const deceasedMark = d.data.is_deceased ? ' ✦' : '';

        group.append('text')
            .attr('x', 0)
            .attr('y', captionTop + lines.length * lineHeight + 2)
            .attr('text-anchor', 'middle')
            .attr('font-size', 10.5)
            .attr('fill', tokens.textMid)
            .style('font-family', 'var(--font-mono)')
            .style('font-variant-numeric', 'tabular-nums')
            .text(`${glyph} ${years}${deceasedMark}`.trim());

        // The full name, for when it had to be shortened to fit.
        group.append('title').text(d.data.name ?? '');

        if (isBirthdayToday(d.data)) {
            group.append('text')
                .attr('x', halfW - 8)
                .attr('y', halfH - 8)
                .attr('text-anchor', 'end')
                .attr('font-size', 11)
                .attr('fill', tokens.goldText)
                .text('♕');
        }
    });

    // Deceased: a thin maroon ribbon across the top-right corner.
    nodeGroup.filter((d) => d.data.is_deceased)
        .append('path')
        .attr('d', `M ${halfW - 30} ${-halfH} L ${halfW} ${-halfH + 30}`)
        .attr('stroke', tokens.maroon)
        .attr('stroke-width', 5)
        .attr('stroke-linecap', 'round');

    nodeGroup
        .on('mouseenter', function (_event, d) {
            d3.select(this).select('.card-plate')
                .attr('stroke', tokens.goldLight)
                .attr('stroke-opacity', 1)
                .style('filter', `drop-shadow(0 0 10px ${genderColor(d.data.gender, tokens)}2E)`);
        })
        .on('mouseleave', function () {
            d3.select(this).select('.card-plate')
                .attr('stroke', tokens.gold)
                .attr('stroke-opacity', 0.45)
                .style('filter', null);
        });

    // --- Dragging --------------------------------------------------------

    if (draggable) {
        nodeGroup.call(
            d3.drag()
                .on('start', function (event, d) {
                    d3.select(this).raise().attr('cursor', 'grabbing');
                    dragOrigin = { x: d.x, y: d.y };
                })
                .on('drag', function (event, d) {
                    // Each generation is a level line, so a card slides
                    // sideways along its own row and never leaves it.
                    d3.select(this).attr('transform', `translate(${event.x}, ${d.y})`);
                    tempPos.set(d.data.id, { x: event.x, y: d.y });

                    const spouse = spouseByNodeId.get(d.data.id);
                    if (spouse) {
                        const spouseX = spouse.x + (event.x - d.x);
                        tempPos.set(spouse.data.id, { x: spouseX, y: spouse.y });
                        nodeGroup.filter((n) => n === spouse).attr('transform', `translate(${spouseX}, ${spouse.y})`);
                    }

                    refreshAllLines();
                })
                .on('end', function (event, d) {
                    d3.select(this).attr('cursor', 'grab');

                    const restore = () => {
                        tempPos.clear();
                        d3.select(this).attr('transform', `translate(${d.x}, ${d.y})`);
                        const spouse = spouseByNodeId.get(d.data.id);
                        if (spouse) {
                            nodeGroup.filter((n) => n === spouse)
                                .attr('transform', `translate(${spouse.x}, ${spouse.y})`);
                        }
                        refreshAllLines();
                    };

                    // A plain click still runs a full drag cycle, so without
                    // this every click on a person would silently pin them to
                    // their current spot as a manual override.
                    if (Math.abs(event.x - dragOrigin.x) < CLICK_SLOP) {
                        restore();
                        return;
                    }

                    // Dropping one card onto another links them as parent and
                    // child — a change to the records, so Super Admin only.
                    if (editable) {
                        const point = event.sourceEvent.changedTouches
                            ? event.sourceEvent.changedTouches[0]
                            : event.sourceEvent;
                        const stack = document.elementsFromPoint(point.clientX, point.clientY);
                        const targetGroup = stack
                            .map((el) => el.closest('[data-person-id]'))
                            .find((group) => group && group !== this);
                        const targetId = targetGroup ? Number(targetGroup.dataset.personId) : null;

                        if (targetId) {
                            // Snap back first: a successful link re-renders
                            // the whole tree from scratch anyway.
                            restore();
                            onReparent?.(d.data.id, targetId);
                            return;
                        }
                    }

                    const spouse = spouseByNodeId.get(d.data.id);

                    d.x = event.x;
                    if (spouse) {
                        const moved = tempPos.get(spouse.data.id);
                        if (moved) spouse.x = moved.x;
                    }

                    // Only a Super Admin's rearrangement is saved for
                    // everyone; anyone else has simply nudged their own view.
                    if (editable) {
                        onReposition?.(d.data.id, d.x, d.y);
                        if (spouse) onReposition?.(spouse.data.id, spouse.x, spouse.y);
                    }

                    tempPos.clear();
                    refreshAllLines();
                })
        );
    }

    // --- Wind ------------------------------------------------------------
    // Layer 1: structural sway, one group per generation. Amplitude rises
    // with depth — trunk barely moves, outer leaves move most.

    let gustTimer = null;
    if (windEnabled) {
        const byGeneration = d3.group(
            [...visibleLinks.nodes()].map((el, i) => ({ el, link: renderLinks[i] })),
            (entry) => entry.link.target.generation
        );

        for (const [generation, entries] of byGeneration) {
            if (generation > maxSwayDepth) continue;

            const seed = hashSeed(`gen-${generation}`);
            const amp = Math.min(1.1, 0.12 * (generation + 1));
            const dur = 5500 + (seed % 2500);
            const delay = -(seed % 4000);

            for (const { el } of entries) {
                const parent = el.parentNode;
                const wrapper = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                wrapper.setAttribute('class', 'branch-group');
                wrapper.style.setProperty('--amp', amp);
                wrapper.style.setProperty('--dur', `${dur}ms`);
                wrapper.style.setProperty('--delay', `${delay}ms`);
                parent.insertBefore(wrapper, el);
                wrapper.appendChild(el);
            }
        }

        // Gusts: every 18–35s the whole tree leans harder, then eases back.
        const scheduleGust = () => {
            const wait = 18000 + Math.random() * 17000;
            gustTimer = window.setTimeout(() => {
                if (!document.hidden) {
                    // Tells the leaf canvas to shake a few extra leaves loose.
                    container.dispatchEvent(new CustomEvent('tree:gust'));
                    const groups = container.querySelectorAll('.branch-group');
                    groups.forEach((group, index) => {
                        const base = parseFloat(group.style.getPropertyValue('--amp')) || 0.3;
                        // Propagate left to right at 40ms per 100px.
                        const offsetX = group.getBoundingClientRect().left;
                        const lag = (offsetX / 100) * 40;
                        window.setTimeout(() => {
                            group.style.setProperty('--amp', base * 2.2);
                            window.setTimeout(() => group.style.setProperty('--amp', base), 2500);
                        }, lag);
                    });
                }
                scheduleGust();
            }, wait);
        };
        scheduleGust();
    }

    // Layer 2 lives on its own canvas above the SVG.
    const stopLeaves = leavesEnabled
        ? startFallingLeaves(container, { leafColor: tokens.leaf400, autumnColor: tokens.leafAutumn })
        : null;

    // --- Recentre ---------------------------------------------------------
    // Any element marked `data-recentre` snaps the view back to the eldest
    // generation, for when panning has wandered off the tree.

    const recentre = () => {
        const targetScale = Math.max(MIN_LEGIBLE_SCALE, Math.min(1, fit));
        const x = viewportWidth / 2 - rootAnchor.x * targetScale;
        svg.transition().duration(620).ease(d3.easeCubicOut)
            .call(zoom.transform, d3.zoomIdentity.translate(x, 16).scale(targetScale));
    };

    for (const control of document.querySelectorAll('[data-recentre]')) {
        control.onclick = recentre;
    }

    // --- Accessible mirror ------------------------------------------------
    // Screen readers get real hierarchy rather than a wall of SVG shapes.

    const childrenByParent = new Map();
    for (const link of layout.links) {
        const list = childrenByParent.get(link.source.data.id) ?? [];
        list.push(link.target);
        childrenByParent.set(link.source.data.id, list);
    }

    const roots = nodes.filter((n) => n.generation === 1);
    const seen = new Set();
    const buildList = (people) => {
        const items = people.map((person) => {
            if (seen.has(person.data.id)) return `<li>${escapeHtml(person.data.name)}</li>`;
            seen.add(person.data.id);
            const kids = childrenByParent.get(person.data.id) ?? [];
            const years = lifespan(person.data);
            const label = `${escapeHtml(person.data.name)}${years ? `, ${years}` : ''}`;
            return `<li>${label}${kids.length ? buildList(kids) : ''}</li>`;
        });
        return `<ul>${items.join('')}</ul>`;
    };

    const mirror = document.createElement('div');
    mirror.className = 'sr-only-tree';
    mirror.innerHTML = `<h2>Family tree hierarchy</h2>${buildList(roots)}`;
    container.appendChild(mirror);

    return {
        width,
        height,
        destroy() {
            if (gustTimer) window.clearTimeout(gustTimer);
            stopLeaves?.();
        },
    };
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));
}
