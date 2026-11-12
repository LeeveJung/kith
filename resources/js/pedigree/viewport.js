/**
 * Pan and zoom for an SVG group: mouse wheel, drag, pinch, buttons and animated camera moves.
 * Clicks that end a drag are swallowed so dragging never opens a profile by accident.
 */

const MIN_SCALE = 0.2;
const MAX_SCALE = 3;

export function createViewport(svg, onChange) {
    const state = { k: 1, tx: 0, ty: 0 };
    let world = null;
    const pointers = new Map();
    let gesture = null;
    let animation = null;
    let suppressClick = false;

    const clampScale = (k) => Math.min(MAX_SCALE, Math.max(MIN_SCALE, k));

    const apply = () => {
        world?.setAttribute('transform', `translate(${state.tx} ${state.ty}) scale(${state.k})`);
        onChange({ ...state });
    };

    const local = (event) => {
        const rect = svg.getBoundingClientRect();

        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const stop = () => {
        if (animation) {
            cancelAnimationFrame(animation);
            animation = null;
        }
    };

    function zoomAt(x, y, factor) {
        const k = clampScale(state.k * factor);
        state.tx = x - ((x - state.tx) * k) / state.k;
        state.ty = y - ((y - state.ty) * k) / state.k;
        state.k = k;
        apply();
    }

    function animateTo(target, duration = 650) {
        stop();
        const from = { ...state };
        const startedAt = performance.now();
        const ease = (t) => 1 - Math.pow(1 - t, 3);

        const step = (now) => {
            const progress = Math.min(1, (now - startedAt) / duration);
            const e = ease(progress);
            state.k = from.k + (target.k - from.k) * e;
            state.tx = from.tx + (target.tx - from.tx) * e;
            state.ty = from.ty + (target.ty - from.ty) * e;
            apply();
            animation = progress < 1 ? requestAnimationFrame(step) : null;
        };

        animation = requestAnimationFrame(step);
    }

    function size() {
        const rect = svg.getBoundingClientRect();

        return { width: rect.width, height: rect.height };
    }

    /**
     * Fits the given world rectangle into the view. With minScale the content
     * may overflow; it is then aligned to its start (left or top).
     */
    function fit(bounds, { padding = 48, minScale = MIN_SCALE, maxScale = 1.1, animate = true, insets = {} } = {}) {
        const view = size();
        const left = insets.left ?? 0;
        const top = insets.top ?? 0;
        const bottom = insets.bottom ?? 0;
        const availableWidth = view.width - left - padding * 2;
        const availableHeight = view.height - top - bottom - padding * 2;
        const k = clampScale(Math.min(maxScale, Math.max(minScale, Math.min(availableWidth / bounds.width, availableHeight / bounds.height))));

        const fitsX = bounds.width * k <= availableWidth;
        const fitsY = bounds.height * k <= availableHeight;

        const target = {
            k,
            tx: fitsX ? left + padding + (availableWidth - bounds.width * k) / 2 - bounds.x * k : left + padding - bounds.x * k,
            ty: fitsY ? top + padding + (availableHeight - bounds.height * k) / 2 - bounds.y * k : top + padding - bounds.y * k,
        };

        animate ? animateTo(target) : (Object.assign(state, target), apply());
    }

    /** Moves the camera so the world point sits at the given share of the view. */
    function focus(point, { k = Math.max(state.k, 1), anchorX = 0.5, anchorY = 0.5 } = {}) {
        const view = size();
        animateTo({ k, tx: view.width * anchorX - point.x * k, ty: view.height * anchorY - point.y * k });
    }

    function zoomBy(factor) {
        const view = size();
        const k = clampScale(state.k * factor);
        const cx = view.width / 2;
        const cy = view.height / 2;
        animateTo({ k, tx: cx - ((cx - state.tx) * k) / state.k, ty: cy - ((cy - state.ty) * k) / state.k }, 300);
    }

    svg.addEventListener(
        'wheel',
        (event) => {
            event.preventDefault();
            stop();
            const p = local(event);

            // Trackpad two-finger scrolling pans, pinch (ctrlKey) and mouse wheel zoom.
            if (!event.ctrlKey && event.deltaMode === 0 && Math.abs(event.deltaX) > 0) {
                state.tx -= event.deltaX;
                state.ty -= event.deltaY;
                apply();

                return;
            }

            zoomAt(p.x, p.y, Math.exp(-event.deltaY * (event.ctrlKey ? 0.01 : 0.0015)));
        },
        { passive: false },
    );

    svg.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 && event.pointerType === 'mouse') {
            return;
        }

        stop();
        pointers.set(event.pointerId, local(event));
        gesture = { moved: false, last: local(event), distance: null };
    });

    window.addEventListener('pointermove', (event) => {
        if (!pointers.has(event.pointerId) || !gesture) {
            return;
        }

        const p = local(event);
        pointers.set(event.pointerId, p);

        if (pointers.size === 2) {
            const [a, b] = [...pointers.values()];
            const distance = Math.hypot(a.x - b.x, a.y - b.y);
            const center = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };

            if (gesture.distance) {
                zoomAt(center.x, center.y, distance / gesture.distance);
                state.tx += center.x - gesture.last.x;
                state.ty += center.y - gesture.last.y;
                apply();
            }

            gesture.distance = distance;
            gesture.last = center;
            gesture.moved = true;

            return;
        }

        const dx = p.x - gesture.last.x;
        const dy = p.y - gesture.last.y;

        if (!gesture.moved && Math.hypot(dx, dy) < 4) {
            return;
        }

        gesture.moved = true;
        svg.classList.add('cursor-grabbing');
        state.tx += dx;
        state.ty += dy;
        gesture.last = p;
        apply();
    });

    const release = (event) => {
        if (!pointers.has(event.pointerId)) {
            return;
        }

        pointers.delete(event.pointerId);

        if (pointers.size === 1 && gesture) {
            gesture.last = [...pointers.values()][0];
            gesture.distance = null;

            return;
        }

        if (pointers.size === 0) {
            suppressClick = gesture?.moved ?? false;
            gesture = null;
            svg.classList.remove('cursor-grabbing');
        }
    };

    window.addEventListener('pointerup', release);
    window.addEventListener('pointercancel', release);

    svg.addEventListener(
        'click',
        (event) => {
            if (suppressClick) {
                event.stopPropagation();
                event.preventDefault();
                suppressClick = false;
            }
        },
        true,
    );

    svg.addEventListener('dblclick', (event) => {
        const p = local(event);
        stop();
        zoomAt(p.x, p.y, 1.6);
    });

    /** Switches to a newly rendered world group (after a re-layout). */
    function setWorld(group) {
        stop();
        world = group;
    }

    return { state, fit, focus, zoomBy, setWorld };
}
