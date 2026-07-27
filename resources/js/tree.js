import { render2d } from './tree/render2d';

document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('tree-canvas');
    if (!container) return;

    const statusEl = document.getElementById('tree-status');
    const toggleBtn = document.getElementById('tree-toggle-3d');
    const resetBtn = document.getElementById('tree-reset');
    const focusBar = document.getElementById('tree-focus');
    const focusLabel = document.getElementById('tree-focus-label');
    const focusClearBtn = document.getElementById('tree-focus-clear');
    const editable = window.IS_SUPER_ADMIN === true;

    let treeData;
    // The data actually on screen — the whole tree, or one couple's branch of
    // it after clicking their marriage line.
    let viewData;
    let focused = false;
    let mode = '2d';
    let unmount3d = null;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    async function fetchTreeData() {
        const response = await fetch(`${window.APP_URL}/tree/data`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`Request failed: ${response.status}`);
        return response.json();
    }

    async function reload() {
        treeData = await fetchTreeData();
        showFullTree();
    }

    /**
     * Just one couple and everyone descended from them — the branch of the
     * family that starts with that marriage. Their own parents and siblings
     * are left out; that's the point of focusing. Spouses who married into
     * the branch come along so no couple is shown split in half.
     */
    function coupleSubtree(data, coupleIds) {
        const childIdsByParent = new Map();
        for (const edge of data.edges) {
            if (edge.type !== 'parent') continue;
            if (!childIdsByParent.has(edge.source)) childIdsByParent.set(edge.source, []);
            childIdsByParent.get(edge.source).push(edge.target);
        }

        const keep = new Set(coupleIds);
        const queue = [...coupleIds];
        while (queue.length) {
            const id = queue.shift();
            for (const childId of childIdsByParent.get(id) ?? []) {
                if (keep.has(childId)) continue;
                keep.add(childId);
                queue.push(childId);
            }
        }

        for (const edge of data.edges) {
            if (edge.type !== 'spouse') continue;
            if (keep.has(edge.source)) keep.add(edge.target);
            else if (keep.has(edge.target)) keep.add(edge.source);
        }

        return {
            nodes: data.nodes.filter((n) => keep.has(n.id)),
            // Both ends must survive the filter, or the layout would be handed
            // an edge pointing at somebody who isn't in the tree any more.
            edges: data.edges.filter((e) => keep.has(e.source) && keep.has(e.target)),
        };
    }

    function showFullTree() {
        focused = false;
        viewData = treeData;
        focusBar?.classList.add('hidden');
        goTo2d(viewData);
    }

    function onSelectCouple(personA, personB) {
        // The layout stringifies ids for d3-dag; the raw tree data keys off
        // numbers, so come back to numbers before matching against it.
        const subtree = coupleSubtree(treeData, [Number(personA.id), Number(personB.id)]);
        focused = true;
        viewData = subtree;

        if (focusLabel) focusLabel.textContent = `${personA.name} & ${personB.name}`;
        focusBar?.classList.remove('hidden');
        goTo2d(viewData);
    }

    async function onReparent(childId, parentId) {
        const response = await fetch(`${window.APP_URL}/admin/relationships`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ child_id: childId, parent_id: parentId }),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => null);
            alert(body?.message ?? 'Could not link these two people.');
            return;
        }

        await reload();
    }

    // Dragging a node onto empty space just rearranges that spot — save it
    // so it's still there next time the tree loads. No reload needed; it's
    // already visually where it was dropped.
    function onReposition(personId, x, y) {
        fetch(`${window.APP_URL}/admin/tree/positions/${personId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ x: Math.round(x), y: Math.round(y) }),
        });
    }

    async function onResetTree() {
        if (!confirm('Reset everyone to the automatic tree layout? This only affects arrangement, not any family data.')) return;

        await fetch(`${window.APP_URL}/admin/tree/positions/reset`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });

        await reload();
    }

    let teardown2d = null;

    const goTo2d = (data) => {
        unmount3d?.();
        unmount3d = null;
        // Stops the previous view's wind timers and leaf canvas before the
        // container is emptied out from under them.
        teardown2d?.();
        // Editing is off while focused on one couple: coordinates there are
        // relative to a layout of that branch alone, so saving them would
        // scramble everyone's position back on the full tree.
        const view = render2d(container, data, {
            onSelectNode,
            onSelectCouple,
            editable: editable && !focused,
            // Anyone may slide a card along its generation to read the chart
            // more comfortably; only a Super Admin's move is saved for
            // everyone else (see render2d).
            draggable: true,
            onReparent,
            onReposition,
        });
        teardown2d = view?.destroy ?? null;
        mode = '2d';
        if (toggleBtn) toggleBtn.textContent = 'Showcase in 3D';
    };

    const goTo3d = async (data) => {
        toggleBtn.disabled = true;
        toggleBtn.textContent = 'Loading 3D…';
        teardown2d?.();
        teardown2d = null;
        // Dynamic import: Three.js only ever downloads once someone actually
        // asks for the 3D view, keeping it off the 2D-only visitor's bundle.
        const { render3d } = await import('./tree/render3d');
        unmount3d = await render3d(container, data, { onSelectNode });
        mode = '3d';
        toggleBtn.disabled = false;
        toggleBtn.textContent = 'Back to 2D';
    };

    function onSelectNode(node) {
        window.location.href = `${window.APP_URL}/people/${node.id}`;
    }

    try {
        treeData = await fetchTreeData();

        if (treeData.nodes.length === 0) {
            statusEl.textContent = 'No one is in the tree yet.';
            toggleBtn?.remove();
            return;
        }

        statusEl.remove();
        showFullTree();

        toggleBtn?.addEventListener('click', () => {
            if (mode === '2d') {
                goTo3d(viewData);
            } else {
                goTo2d(viewData);
            }
        });

        resetBtn?.addEventListener('click', onResetTree);
        focusClearBtn?.addEventListener('click', showFullTree);
    } catch (error) {
        console.error(error);
        statusEl.textContent = 'Could not load the family tree.';
        toggleBtn?.remove();
    }
});
