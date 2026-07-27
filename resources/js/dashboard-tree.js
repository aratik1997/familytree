import { render2d } from './tree/render2d';

// A small, static (no pan/zoom, no 3D toggle) green-themed preview of the
// family tree for the dashboard. It stays in normal document flow with a
// fixed pixel height (not vh) so it scrolls with the page like any other
// card instead of behaving oddly as viewport height changes.
document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('dashboard-tree-canvas');
    if (!container) return;

    const statusEl = document.getElementById('dashboard-tree-status');

    function onSelectNode(node) {
        window.location.href = `${window.APP_URL}/people/${node.id}`;
    }

    try {
        const response = await fetch(`${window.APP_URL}/tree/data`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`Request failed: ${response.status}`);

        const treeData = await response.json();

        if (treeData.nodes.length === 0) {
            if (statusEl) statusEl.textContent = 'No one is in the tree yet.';
            return;
        }

        statusEl?.remove();
        // No falling leaves in the preview — it is a thumbnail, not the
        // showpiece, and the tree page is already running its own canvas.
        render2d(container, treeData, { onSelectNode, showLeaves: false });
    } catch (error) {
        console.error(error);
        if (statusEl) statusEl.textContent = 'Could not load the family tree.';
    }
});
