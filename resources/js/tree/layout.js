import { graphStratify, sugiyama } from 'd3-dag';

/**
 * Node card metrics. Every card is the same square plate: the photo fills the
 * top, and the name over the birth year sits beneath it. Exported so both
 * renderers draw to exactly the box the layout reserved for it.
 */
export const CARD = {
    width: 150,
    height: 232,
    // Portrait, so a head-and-shoulders photo sits the way a portrait wants
    // to rather than being cropped into a letterbox.
    photoHeight: 172,
    radius: 14,
    railWidth: 2,
};

// The row pitch is generous on purpose, so the connectors between one
// generation and the next have room to read as a chart rather than a thicket.
const NODE_SIZE = [CARD.width, CARD.height + 130];
const GAP = [34, 34];
const MARRIED_Y_OFFSET = 0;
const SPOUSE_MARGIN = 46;
const GENERATION_LABEL_MARGIN = 130;
const SIBLING_GAP = 34;

const CHAR_WIDTH = 5.9;

/**
 * Splits a long name across two lines (roughly balanced by word count), so a
 * name too wide for the fixed card width still fits inside it. Shared by both
 * renderers so they wrap identically.
 */
export function nameLines(name) {
    if (!name) return [''];
    if (name.length <= 16) return [name];

    const words = name.split(' ');
    if (words.length < 2) return [name];

    let bestSplit = 1;
    let bestDiff = Infinity;
    for (let i = 1; i < words.length; i++) {
        const left = words.slice(0, i).join(' ').length;
        const right = words.slice(i).join(' ').length;
        const diff = Math.abs(left - right);
        if (diff < bestDiff) {
            bestDiff = diff;
            bestSplit = i;
        }
    }

    return [words.slice(0, bestSplit).join(' '), words.slice(bestSplit).join(' ')];
}

/**
 * Every card is the same square, so this is a constant — kept as a function
 * because the layout and both renderers call it per node, and a card that
 * ever needed to vary would only have to change here.
 */
export function estimateBoxWidth() {
    return CARD.width;
}

/** Roughly how wide a line of card text renders, for ellipsising. */
export function estimateTextWidth(text) {
    return Math.ceil((text?.length ?? 0) * CHAR_WIDTH);
}

/**
 * Runs the sugiyama DAG layout on the parent/child edges only (see the note
 * in render2d.js on why spouse edges are excluded), and returns plain data
 * both the 2D (SVG) and 3D (Three.js) renderers can draw from without each
 * re-implementing the layout math.
 */
export function computeLayout({ nodes, edges }) {
    const parentEdges = edges.filter((e) => e.type === 'parent');
    const spouseEdges = edges.filter((e) => e.type === 'spouse');

    const parentIdsByChild = new Map();
    for (const edge of parentEdges) {
        const list = parentIdsByChild.get(edge.target) ?? [];
        list.push(edge.source);
        parentIdsByChild.set(edge.target, list);
    }

    const stratifyInput = nodes.map((node) => ({
        ...node,
        id: String(node.id),
        parentIds: (parentIdsByChild.get(node.id) ?? []).map(String),
    }));

    const relationshipType = new Map(
        parentEdges.map((e) => [`${e.source}-${e.target}`, e.relationship_type])
    );

    const dag = graphStratify()(stratifyInput);
    const layout = sugiyama().nodeSize(() => NODE_SIZE).gap(GAP);
    let { width, height } = layout(dag);

    const laidOutNodes = [...dag.nodes()];
    const laidOutLinks = [...dag.links()];
    const nodeById = new Map(laidOutNodes.map((n) => [n.data.id, n]));

    reorderSiblingsByBirthDate(laidOutNodes, laidOutLinks);

    const spouseLinks = spouseEdges
        .filter((e) => nodeById.has(String(e.source)) && nodeById.has(String(e.target)))
        .map((e) => ({
            source: nodeById.get(String(e.source)),
            target: nodeById.get(String(e.target)),
            // Carried through so the renderer can draw a divorced union
            // dashed and a widowed one part-maroon.
            status: e.status ?? 'married',
        }));

    positionSpouses(laidOutNodes, laidOutLinks, spouseLinks);

    const generationById = computeGenerationById(laidOutNodes, laidOutLinks, spouseLinks);

    centerChildrenUnderParents(laidOutNodes, laidOutLinks, spouseLinks, generationById);

    // The offsets positionSpouses() applies can push nodes past the bounds
    // sugiyama originally computed, so pad the canvas out to fit them.
    const maxX = Math.max(width, ...laidOutNodes.map((n) => n.x + estimateBoxWidth(n.data.name)));
    const maxY = Math.max(height, ...laidOutNodes.map((n) => n.y + CARD.height));
    width = maxX;
    height = maxY;

    // Each node carries its own generation from here on: the renderers use it
    // to taper connectors, scale the wind, and draw the generation labels.
    for (const node of laidOutNodes) {
        node.generation = generationById.get(node.data.id) ?? 1;
    }

    // The chart hangs downward: the eldest generation sits along the top and
    // each generation below is younger. A little breathing room above the
    // first row keeps it off the edge of the canvas.
    const TOP_MARGIN = 40;
    for (const node of laidOutNodes) {
        node.y += TOP_MARGIN;
    }
    height += TOP_MARGIN;

    const generationRows = computeGenerationRows(laidOutNodes, generationById);

    // Reserve a strip on the left for the generation labels — otherwise
    // they'd have nowhere to sit without overlapping the leftmost boxes.
    for (const node of laidOutNodes) {
        node.x += GENERATION_LABEL_MARGIN;
    }
    width += GENERATION_LABEL_MARGIN;

    // Where the auto-layout left everyone, kept before any override lands on
    // top of it. The cascade below moves a person's children by how far that
    // person actually travelled, and it can only know that by comparing
    // against this.
    const autoX = new Map(laidOutNodes.map((n) => [n.data.id, n.x]));

    // A manually dragged position (saved from a previous session) overrides
    // whatever the layout just computed for that person — the last step, so
    // nothing above accidentally clobbers it.
    for (const node of laidOutNodes) {
        if (node.data.pos_x != null && node.data.pos_y != null) {
            node.x = node.data.pos_x;
            node.y = node.data.pos_y;
        }
    }

    // An override can separate a couple if only one side was ever dragged
    // (e.g. the marriage was recorded after the drag) — pull the untouched
    // spouse back beside the one with an explicit saved position. If both
    // sides have their own override, trust both and leave them as placed.
    const movedIds = new Set(
        laidOutNodes.filter((n) => n.data.pos_x != null && n.data.pos_y != null).map((n) => n.data.id)
    );
    for (const { source, target } of spouseLinks) {
        const sourceOverridden = movedIds.has(source.data.id);
        const targetOverridden = movedIds.has(target.data.id);
        if (sourceOverridden === targetOverridden) continue;
        const [anchor, spouse] = sourceOverridden ? [source, target] : [target, source];
        placeBeside(laidOutNodes, anchor, spouse);
        movedIds.add(spouse.data.id);
    }

    // A moved couple's children should follow underneath them rather than
    // being left behind at their original spot — shifted by however far the
    // parents travelled. Deliberately a shift and not a re-centering: every
    // sibling moves by the same amount, so the spacing that
    // centerChildrenUnderParents() laid out between them survives. Pinning
    // them to the parents' midpoint instead would stack a whole sibling set
    // on one point, and the de-overlap sweep further down would then fan them
    // back out in arbitrary order, shoving the rest of the row aside.
    // Skipped for any child with their own explicit override — an explicit
    // placement always wins.
    const parentNodeIdsByChild = new Map();
    for (const link of laidOutLinks) {
        const childId = link.target.data.id;
        const list = parentNodeIdsByChild.get(childId) ?? [];
        list.push(link.source.data.id);
        parentNodeIdsByChild.set(childId, list);
    }

    // Ascending generation — parents sit above their children, so walking
    // downward is what lets a move cascade on through the generations. Sorted
    // by generation rather than by y: an overridden node is currently carrying
    // its stored y, which can be a row position from an older shape of the
    // tree, and sorting on that could visit a child before its own parent.
    for (const child of [...laidOutNodes].sort((a, b) => a.generation - b.generation)) {
        if (child.data.pos_x != null && child.data.pos_y != null) continue;
        const parentIds = parentNodeIdsByChild.get(child.data.id) ?? [];
        if (parentIds.length === 0 || !parentIds.some((id) => movedIds.has(id))) continue;

        const parentNodes = parentIds.map((id) => nodeById.get(id)).filter(Boolean);
        if (parentNodes.length === 0) continue;

        // How far the parents drifted from where the layout had put them,
        // averaged so a couple that moved together shifts their children by
        // exactly that much.
        const shift = parentNodes.reduce(
            (total, parent) => total + (parent.x - (autoX.get(parent.data.id) ?? parent.x)),
            0
        ) / parentNodes.length;

        child.x += shift;
        movedIds.add(child.data.id);
    }

    // Every generation is a level line. A saved position can move someone
    // along their row but never off it, so this snaps the vertical back to
    // the generation's own line — whatever the stored y happened to be.
    const rowYByGeneration = new Map(generationRows.map((row) => [row.generation, row.y]));
    for (const node of laidOutNodes) {
        const rowY = rowYByGeneration.get(node.generation);
        if (rowY != null) node.y = rowY;
    }

    // With nobody able to step out of their row, two cards can only get out
    // of each other's way sideways. A saved position doesn't reserve space in
    // the auto-layout, so sweep each row left to right and push apart anyone
    // who ended up overlapping — order is preserved, only the spacing gives.
    const rowMembers = new Map();
    for (const node of laidOutNodes) {
        if (!rowMembers.has(node.y)) rowMembers.set(node.y, []);
        rowMembers.get(node.y).push(node);
    }

    const minPitch = CARD.width + 12;
    for (const members of rowMembers.values()) {
        members.sort((a, b) => a.x - b.x);
        for (let i = 1; i < members.length; i++) {
            const gap = members[i].x - members[i - 1].x;
            if (gap < minPitch) members[i].x = members[i - 1].x + minPitch;
        }
    }

    width = Math.max(width, ...laidOutNodes.map((n) => n.x + estimateBoxWidth(n.data.name)));
    height = Math.max(height, ...laidOutNodes.map((n) => n.y + CARD.height));

    // Where the view centres itself. Averaging *all* the roots would aim at
    // empty space whenever there are several unconnected founders, so this
    // picks the root with the most children — and their couple's midpoint if
    // they are married, which is where the chart actually fans out from.
    const childCount = new Map();
    for (const link of laidOutLinks) {
        const parentId = link.source.data.id;
        childCount.set(parentId, (childCount.get(parentId) ?? 0) + 1);
    }

    const rootNodes = laidOutNodes.filter((n) => n.generation === 1);
    const candidates = rootNodes.length > 0 ? rootNodes : laidOutNodes;
    const principal = candidates.reduce(
        (best, node) => ((childCount.get(node.data.id) ?? 0) > (childCount.get(best.data.id) ?? 0) ? node : best),
        candidates[0]
    );

    const principalSpouse = spouseLinks
        .map(({ source, target }) => (source === principal ? target : target === principal ? source : null))
        .find((node) => node && Math.abs(node.y - principal.y) < CARD.height);

    const anchorNodes = principalSpouse ? [principal, principalSpouse] : [principal];

    const rootAnchor = {
        x: anchorNodes.reduce((sum, n) => sum + n.x, 0) / anchorNodes.length,
        y: Math.min(...anchorNodes.map((n) => n.y)),
    };

    return {
        width,
        height,
        nodes: laidOutNodes,
        links: laidOutLinks,
        relationshipType,
        spouseLinks,
        renderLinks: buildRenderLinks(laidOutLinks, spouseLinks, relationshipType),
        generationRows,
        rootAnchor,
        maxGeneration: Math.max(1, ...laidOutNodes.map((n) => n.generation)),
    };
}

/**
 * Which generation each person belongs to. Generation = 1 for anyone with no
 * parents in the tree; otherwise one more than the deepest parent's
 * generation. A married-in spouse with no parents of their own is pulled up
 * to match their partner's generation, since visually they sit in the same
 * row. Purely graph-derived — no coordinates involved — so it can be computed
 * before the layout positions are finalised.
 */
function computeGenerationById(nodes, links, spouseLinks) {
    const generationById = new Map(nodes.map((node) => [node.data.id, 1]));

    // Two rules have to hold at once: a child sits strictly below every parent,
    // and a married couple shares a row. They pull against each other — pulling
    // a married-in spouse down to their partner's row means everyone descended
    // from that spouse has to come down too, which can deepen another couple
    // further along, and so on. So neither rule can be applied once and left:
    // both are relaxed together until a pass changes nothing.
    //
    // Doing this in one pass each (the parents settled first, then the spouses)
    // is what put people in the wrong row — a spouse pulled down took none of
    // their own descendants with them, leaving a child stranded in the same row
    // as the parent they descend from.
    //
    // Generations only ever increase here, so this always settles. The cap is a
    // guard against a contradiction in the records — someone recorded as both
    // an ancestor and a spouse of the same person — not the expected exit.
    const cap = nodes.length + 2;
    for (let pass = 0; pass < cap; pass++) {
        let changed = false;

        for (const link of links) {
            const parent = generationById.get(link.source.data.id) ?? 1;
            if ((generationById.get(link.target.data.id) ?? 1) < parent + 1) {
                generationById.set(link.target.data.id, parent + 1);
                changed = true;
            }
        }

        for (const { source, target } of spouseLinks) {
            const a = generationById.get(source.data.id) ?? 1;
            const b = generationById.get(target.data.id) ?? 1;
            const deepest = Math.max(a, b);
            if (a !== deepest) { generationById.set(source.data.id, deepest); changed = true; }
            if (b !== deepest) { generationById.set(target.data.id, deepest); changed = true; }
        }

        if (!changed) break;
    }

    return generationById;
}

/**
 * One row entry per generation actually present, each with the y coordinate
 * (the topmost node in that generation) to draw its "Nth Generation" label
 * against.
 */
function computeGenerationRows(nodes, generationById) {
    const minYByGeneration = new Map();
    for (const node of nodes) {
        const generation = generationById.get(node.data.id) ?? 1;
        const currentMin = minYByGeneration.get(generation);
        if (currentMin === undefined || node.y < currentMin) {
            minYByGeneration.set(generation, node.y);
        }
    }

    return [...minYByGeneration.entries()]
        .map(([generation, y]) => ({ generation, y }))
        .sort((a, b) => a.generation - b.generation);
}

/**
 * Centers every sibling group directly beneath its parents, with the siblings
 * spread evenly to either side — half to the left of the parents' midpoint,
 * half to the right — which is what a reader expects from a family chart.
 * Sugiyama on its own only minimizes edge crossings, so a lone child can end
 * up far off to one side of the parents it descends from.
 *
 * Works generation by generation, top down, so each row is placed against
 * parent coordinates that are already final. Within a row, a married couple
 * moves as a single unit (keeping them adjacent), and groups are swept
 * left-to-right so a group is nudged over rather than allowed to land on top
 * of the one before it — centering is the goal, but never at the cost of an
 * overlap.
 */
function centerChildrenUnderParents(nodes, links, spouseLinks, generationById) {
    const familyKey = buildFamilyKeys(links);

    const parentsByChild = new Map();
    for (const link of links) {
        const list = parentsByChild.get(link.target.data.id) ?? [];
        list.push(link.source);
        parentsByChild.set(link.target.data.id, list);
    }

    const spouseByNodeId = new Map();
    for (const { source, target } of spouseLinks) {
        spouseByNodeId.set(source.data.id, target);
        spouseByNodeId.set(target.data.id, source);
    }

    const unitWidth = (members) => members.reduce(
        (total, member, i) => total + estimateBoxWidth(member.data.name) + (i > 0 ? SPOUSE_MARGIN : 0),
        0
    );

    const byGeneration = new Map();
    for (const node of nodes) {
        const generation = generationById.get(node.data.id) ?? 1;
        if (!byGeneration.has(generation)) byGeneration.set(generation, []);
        byGeneration.get(generation).push(node);
    }

    for (const generation of [...byGeneration.keys()].sort((a, b) => a - b)) {
        const rowNodes = byGeneration.get(generation);

        // Everyone in a generation shares one row. Sugiyama can leave members
        // of the same layer a few pixels apart, and the married-couple offset
        // only applies to some of them, which reads as a ragged line of cards
        // — so snap the whole generation to its lowest point (keeping the
        // clearance that offset was there to provide).
        const rowY = Math.max(...rowNodes.map((n) => n.y));
        for (const node of rowNodes) node.y = rowY;

        // Build this row's units: a couple counts as one unit so centering
        // can't split a husband and wife apart.
        const claimed = new Set();
        const units = [];
        for (const node of rowNodes.slice().sort((a, b) => a.x - b.x)) {
            if (claimed.has(node.data.id)) continue;
            claimed.add(node.data.id);

            const spouse = spouseByNodeId.get(node.data.id);
            const members = spouse && !claimed.has(spouse.data.id) && rowNodes.includes(spouse)
                ? (claimed.add(spouse.data.id), [node, spouse].sort((a, b) => a.x - b.x))
                : [node];

            units.push({ members, width: unitWidth(members) });
        }

        // Group units by the family they descend from, so true siblings share
        // one centering target. Uses the same family key as the birth-date
        // ordering, so the two can't disagree about who counts as siblings —
        // otherwise one of them spreads a half-entered family across two
        // groups and undoes the other's work. A unit whose members have no
        // parents in the tree keeps its current center.
        const groups = new Map();
        for (const unit of units) {
            const memberKeys = unit.members.map((m) => familyKey(m.data.id)).filter(Boolean);
            const key = memberKeys.length
                ? [...new Set(memberKeys)].join('+')
                : `unparented:${unit.members[0].data.id}`;

            if (!groups.has(key)) groups.set(key, { units: [], parents: [] });
            const group = groups.get(key);
            group.units.push(unit);

            // Every parent behind the group, deduplicated — so a family that
            // was only half linked up still centers on the whole couple rather
            // than on whichever parent the first child happened to be tied to.
            for (const parent of unit.members.flatMap((m) => parentsByChild.get(m.data.id) ?? [])) {
                if (!group.parents.includes(parent)) group.parents.push(parent);
            }
        }

        const placements = [];
        for (const group of groups.values()) {
            const totalWidth = group.units.reduce((sum, u) => sum + u.width, 0)
                + SIBLING_GAP * Math.max(0, group.units.length - 1);

            const currentCenter = (Math.min(...group.units.map((u) => u.members[0].x - u.width / 2))
                + Math.max(...group.units.map((u) => u.members[u.members.length - 1].x + u.width / 2))) / 2;

            const desiredCenter = group.parents.length
                ? group.parents.reduce((sum, p) => sum + p.x, 0) / group.parents.length
                : currentCenter;

            placements.push({ units: group.units, totalWidth, desiredCenter });
        }

        // Sweep left to right: each group wants to sit centered on its
        // parents, but never starts before the previous group ended.
        placements.sort((a, b) => a.desiredCenter - b.desiredCenter);

        let cursor = -Infinity;
        for (const placement of placements) {
            let start = placement.desiredCenter - placement.totalWidth / 2;
            if (start < cursor) start = cursor;

            let x = start;
            for (const unit of placement.units) {
                unit.members.forEach((member, i) => {
                    const memberWidth = estimateBoxWidth(member.data.name);
                    member.x = x + memberWidth / 2;
                    x += memberWidth;
                    if (i < unit.members.length - 1) x += SPOUSE_MARGIN;
                });
                x += SIBLING_GAP;
            }

            cursor = start + placement.totalWidth + SIBLING_GAP;
        }
    }

    // Centering can push a group left of the origin — shift everyone back so
    // nothing sits at a negative coordinate.
    const minLeft = Math.min(...nodes.map((n) => n.x - estimateBoxWidth(n.data.name) / 2));
    if (minLeft < 0) {
        for (const node of nodes) node.x -= minLeft;
    }
}

/**
 * Two adjustments for married couples, applied unconditionally — a couple
 * stays adjacent whether or not they're independently "connected" elsewhere
 * in the DAG (e.g. once they have a shared child, sugiyama's own crossing-
 * minimization usually keeps them close, but doesn't guarantee it):
 *  - Their row sits a little lower than an unmarried sibling's would, so the
 *    spouse connector has clear space and doesn't crowd the incoming line
 *    from their parents.
 *  - Whichever side sugiyama placed further left is kept as-is; the other
 *    is snapped directly beside it, so a couple never ends up apart.
 */
function positionSpouses(nodes, links, spouseLinks) {
    const offsetApplied = new Set();
    const lowerIfNotAlready = (node) => {
        if (offsetApplied.has(node.data.id)) return;
        offsetApplied.add(node.data.id);
        node.y += MARRIED_Y_OFFSET;
    };

    for (const { source, target } of spouseLinks) {
        lowerIfNotAlready(source);
        lowerIfNotAlready(target);

        const [anchor, spouse] = source.x <= target.x ? [source, target] : [target, source];
        placeBeside(nodes, anchor, spouse);
    }
}

/**
 * Slots `spouse` in immediately to the right of `anchor`, far enough away
 * that their (possibly wide, name-dependent) boxes can't overlap. Anything
 * else already sitting at or past that x position, in roughly the same row,
 * is pushed further right first — the same way inserting into a sorted list
 * shifts everything after the insertion point — so the new box never lands
 * on top of one that's already there. A no-op if they're already correctly
 * placed (the common case once a couple has shared children).
 */
function placeBeside(nodes, anchor, spouse) {
    const gap = estimateBoxWidth(anchor.data.name) / 2 + estimateBoxWidth(spouse.data.name) / 2 + SPOUSE_MARGIN;
    const targetX = anchor.x + gap;

    if (Math.abs(spouse.x - targetX) < 2 && Math.abs(spouse.y - anchor.y) < 2) {
        return;
    }

    for (const node of nodes) {
        if (node === anchor || node === spouse) continue;
        if (Math.abs(node.y - anchor.y) < CARD.height && node.x > anchor.x) {
            node.x += gap;
        }
    }

    spouse.y = anchor.y;
    spouse.x = targetX;
}

/**
 * Reorders each sibling group (children sharing the exact same set of
 * parents) left-to-right by date of birth, oldest first — sugiyama's own
 * crossing-minimization only cares about edge crossings, not birth order.
 * Only the x positions already assigned to that group's slots are permuted
 * among themselves, so overall spacing/centering from the layout is kept.
 */
function reorderSiblingsByBirthDate(nodes, links) {
    const familyKey = buildFamilyKeys(links);

    const siblingGroups = new Map();
    for (const node of nodes) {
        const key = familyKey(node.data.id);
        if (key === null) continue;

        const group = siblingGroups.get(key) ?? [];
        group.push(node);
        siblingGroups.set(key, group);
    }

    const byBirthDateAsc = (a, b) => {
        if (a.data.birth_date && b.data.birth_date) return a.data.birth_date.localeCompare(b.data.birth_date);
        if (a.data.birth_date) return -1;
        if (b.data.birth_date) return 1;
        return 0;
    };

    for (const group of siblingGroups.values()) {
        if (group.length < 2) continue;

        const xSlots = group.map((n) => n.x).sort((a, b) => a - b);
        const oldestFirst = [...group].sort(byBirthDateAsc);
        oldestFirst.forEach((node, i) => {
            node.x = xSlots[i];
        });
    }
}

/**
 * Works out which family each child belongs to, and returns a lookup from a
 * person's id to their family's key (null for anyone with no parents in the
 * records).
 *
 * Keying straight off the exact set of parents recorded splits one family in
 * two the moment the records are half-entered: a child linked to both parents
 * and their sibling linked only to the father come out as unrelated groups,
 * which are then ordered and centered independently of each other. That is
 * what leaves a branch reading as loose individuals rather than one set of
 * brothers and sisters, and what puts them out of age order.
 *
 * So a parent set that is contained in exactly one fuller set is folded into
 * it. "Exactly one" is the important part: where a father has two wives, a
 * father-only child could belong to either family, and guessing would be worse
 * than leaving them in a group of their own.
 */
function buildFamilyKeys(links) {
    const parentIdsByChild = new Map();
    for (const link of links) {
        const childId = link.target.data.id;
        const list = parentIdsByChild.get(childId) ?? [];
        list.push(link.source.data.id);
        parentIdsByChild.set(childId, list);
    }

    const keyOf = (ids) => [...new Set(ids)].sort().join(',');

    // Every distinct parent set the records actually contain.
    const parentSets = new Map();
    for (const ids of parentIdsByChild.values()) {
        const unique = [...new Set(ids)];
        parentSets.set(keyOf(unique), unique);
    }

    const canonical = new Map();
    for (const [key, ids] of parentSets) {
        const fuller = [...parentSets.entries()].filter(([otherKey, otherIds]) =>
            otherKey !== key
            && ids.length < otherIds.length
            && ids.every((id) => otherIds.includes(id))
        );

        canonical.set(key, fuller.length === 1 ? fuller[0][0] : key);
    }

    return (childId) => {
        const parents = parentIdsByChild.get(childId);
        if (!parents || parents.length === 0) return null;

        const key = keyOf(parents);
        return canonical.get(key) ?? key;
    };
}

/**
 * When a child has two parents who are themselves a spouse pair, produces
 * one link from the midpoint of their couple line down to the child instead
 * of two separate lines converging on the same point — the usual genealogy-
 * chart convention. Anyone else (a single parent, or two parents who aren't
 * a couple) keeps their own individual line. Shared by both the 2D and 3D
 * renderers so the drop-point math only lives in one place.
 */
function buildRenderLinks(links, spouseLinks, relationshipType) {
    const spousePairs = new Set(
        spouseLinks.map((s) => [s.source.data.id, s.target.data.id].sort((a, b) => a - b).join('-'))
    );

    const isNonBiological = (link) => {
        const type = relationshipType.get(`${link.source.data.id}-${link.target.data.id}`);
        return Boolean(type && type !== 'biological');
    };

    const byChild = new Map();
    for (const link of links) {
        const childId = link.target.data.id;
        if (!byChild.has(childId)) byChild.set(childId, []);
        byChild.get(childId).push(link);
    }

    const renderLinks = [];
    for (const parentLinks of byChild.values()) {
        if (parentLinks.length === 2) {
            const [a, b] = parentLinks;
            const pairKey = [a.source.data.id, b.source.data.id].sort((x, y) => x - y).join('-');

            if (spousePairs.has(pairKey)) {
                renderLinks.push({
                    source: { x: (a.source.x + b.source.x) / 2, y: (a.source.y + b.source.y) / 2 },
                    target: a.target,
                    points: [],
                    parentIds: [a.source.data.id, b.source.data.id],
                    dashed: isNonBiological(a) || isNonBiological(b),
                });
                continue;
            }
        }

        for (const link of parentLinks) {
            renderLinks.push({
                source: link.source,
                target: link.target,
                points: link.points,
                parentIds: [link.source.data.id],
                dashed: isNonBiological(link),
            });
        }
    }

    return renderLinks;
}
