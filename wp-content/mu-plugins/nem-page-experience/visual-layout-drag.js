(function (root) {
    'use strict';
    function edgeForPoint(rect, x, y) {
        if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom || !rect.width || !rect.height) return '';
        const distances = { left: (x - rect.left) / rect.width, right: (rect.right - x) / rect.width, top: (y - rect.top) / rect.height, bottom: (rect.bottom - y) / rect.height };
        return Object.keys(distances).reduce((best, key) => distances[key] < distances[best] ? key : best, 'left');
    }
    function bind(options) {
        const { doc, groups, layouts, onChange } = options;
        if (!Object.keys(groups).length) return;
        let active = null;
        const guide = doc.createElement('div'); guide.className = 'nve-drop-guide'; guide.hidden = true;
        const label = doc.createElement('span'); guide.appendChild(label); doc.body.appendChild(guide);
        const ghost = doc.createElement('div'); ghost.className = 'nve-layout-ghost'; ghost.hidden = true; doc.body.appendChild(ghost);
        const roots = key => Array.from(doc.querySelectorAll('[data-nve-group]')).filter(el => el.dataset.nveGroup === key);
        const pieces = container => Array.from(container.children).filter(el => el.hasAttribute('data-nve-piece'));
        function apply(key) {
            const layout = layouts[key]; if (!layout) return;
            roots(key).forEach(container => {
                container.dataset.nveFlow = layout.direction;
                if (key === 'product_details') {
                    container.classList.toggle('npe-gallery-right', layout.direction === 'row' && layout.order[0] === 'summary');
                    container.classList.toggle('npe-gallery-col', layout.direction === 'column');
                }
                layout.order.forEach(item => { const el = pieces(container).find(piece => piece.dataset.nvePiece === item); if (el) container.appendChild(el); });
            });
        }
        function reset() {
            const old = active; active = null;
            guide.hidden = true; ghost.hidden = true; doc.body.classList.remove('nve-edge-dragging');
            if (old?.capture?.hasPointerCapture(old.pointerId)) old.capture.releasePointerCapture(old.pointerId);
        }
        function commit(key, source, target, edge) {
            const first = edge === 'left' || edge === 'top';
            const order = first ? [source, target] : [target, source];
            const direction = edge === 'left' || edge === 'right' ? 'row' : 'column';
            layouts[key] = { order, direction }; apply(key); onChange(key, layouts[key]);
        }
        function start(event, container, piece, handle) {
            if (event.button !== 0 || active) return;
            event.preventDefault(); event.stopPropagation();
            active = { container, piece, key: container.dataset.nveGroup, capture: handle, pointerId: event.pointerId, x: event.clientX, y: event.clientY, dragging: false, target: null, edge: '' };
            handle.setPointerCapture(event.pointerId);
        }
        Object.keys(groups).forEach(key => roots(key).forEach(container => {
            pieces(container).forEach(piece => {
                const item = piece.dataset.nvePiece;
                if (!groups[key].items[item]) return;
                const handle = doc.createElement('span'); handle.className = 'nve-edge-handle'; handle.tabIndex = 0; handle.setAttribute('role', 'button');
                handle.textContent = '⠿ ' + groups[key].items[item]; handle.setAttribute('aria-label', 'Kéo ' + groups[key].items[item] + ' sang trái, phải, lên hoặc xuống');
                piece.classList.add('nve-edge-piece'); piece.prepend(handle);
                handle.addEventListener('pointerdown', event => start(event, container, piece, handle));
                handle.addEventListener('keydown', event => {
                    const edge = { ArrowLeft: 'left', ArrowRight: 'right', ArrowUp: 'top', ArrowDown: 'bottom' }[event.key];
                    const other = pieces(container).find(el => el !== piece);
                    if (!edge || !other) return;
                    event.preventDefault(); event.stopPropagation(); commit(key, item, other.dataset.nvePiece, edge); handle.focus();
                });
                piece.querySelectorAll('img').forEach(img => {
                    img.draggable = false;
                    img.addEventListener('pointerdown', event => start(event, container, piece, img));
                    img.addEventListener('dragstart', event => event.preventDefault());
                });
            });
            apply(key);
        }));
        doc.addEventListener('pointermove', event => {
            if (!active || active.pointerId !== event.pointerId) return;
            if (!active.dragging && Math.hypot(event.clientX - active.x, event.clientY - active.y) < 8) return;
            active.dragging = true; event.preventDefault();
            doc.body.classList.add('nve-edge-dragging');
            ghost.hidden = false; ghost.textContent = groups[active.key].items[active.piece.dataset.nvePiece];
            ghost.style.left = (event.clientX + 14) + 'px'; ghost.style.top = (event.clientY + 14) + 'px';
            const hit = doc.elementFromPoint(event.clientX, event.clientY);
            const target = pieces(active.container).find(el => el !== active.piece && el.contains(hit));
            active.target = target || null; active.edge = ''; guide.hidden = true;
            if (!target) return;
            const rect = target.getBoundingClientRect(); const edge = edgeForPoint(rect, event.clientX, event.clientY);
            if (!edge) return;
            active.edge = edge; guide.hidden = false; guide.dataset.edge = edge;
            guide.style.left = (edge === 'right' ? rect.right - 6 : rect.left) + 'px';
            guide.style.top = (edge === 'bottom' ? rect.bottom - 6 : rect.top) + 'px';
            guide.style.width = (edge === 'left' || edge === 'right' ? 6 : rect.width) + 'px';
            guide.style.height = (edge === 'top' || edge === 'bottom' ? 6 : rect.height) + 'px';
            label.textContent = 'Thả ' + groups[active.key].items[active.piece.dataset.nvePiece] + ' ' + ({ left: 'bên trái', right: 'bên phải', top: 'phía trên', bottom: 'phía dưới' })[edge];
        }, { passive: false });
        doc.addEventListener('pointerup', event => {
            if (!active || active.pointerId !== event.pointerId) return;
            const old = active;
            if (old.dragging && old.target && old.edge) commit(old.key, old.piece.dataset.nvePiece, old.target.dataset.nvePiece, old.edge);
            reset();
        });
        doc.addEventListener('pointercancel', reset);
        doc.addEventListener('keydown', event => { if (event.key === 'Escape' && active) { event.preventDefault(); reset(); } });
        doc.defaultView.addEventListener('blur', reset);
    }
    const api = { bind, edgeForPoint };
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.NPELayoutDrag = api;
})(globalThis);
