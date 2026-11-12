import { buildModel, computeLayout } from './layout.js';
import { renderPanel } from './panel.js';
import { clipLanes, render } from './render.js';
import { createViewport } from './viewport.js';

const root = document.querySelector('[data-pedigree]');

if (root) {
    boot(root);
}

function boot(root) {
    const { pedigree, labels } = JSON.parse(root.querySelector('[data-pedigree-data]').textContent);
    const model = buildModel(pedigree);

    const svg = root.querySelector('[data-canvas]');
    const overlay = root.querySelector('[data-overlay]');
    const stage = root.querySelector('[data-stage]');
    const tooltip = root.querySelector('[data-tooltip]');
    const panel = root.querySelector('[data-panel]');
    const panelBody = root.querySelector('[data-panel-body]');
    const search = root.querySelector('[data-search]');
    const results = root.querySelector('[data-search-results]');
    const colorFilter = root.querySelector('[data-filter-color]');
    const scrubber = root.querySelector('[data-scrubber]');
    const scrubberLabel = root.querySelector('[data-scrubber-label]');
    const playButton = root.querySelector('[data-play]');

    const portrait = window.matchMedia('(max-width: 767px)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let layout;
    let rendered;
    let viewport;
    let selectedId = null;
    let hoveredId = null;
    const filters = { sex: null, color: '' };

    const msRange = [Math.min(...model.litters.map((litter) => litter.ms)) - 1000 * 3600 * 24 * 180, Date.now()];
    let cursorMs = msRange[1];
    let playing = null;

    /* ---------- Overlay: time axis and sticky lane names ---------- */

    const axis = document.createElement('div');
    axis.className = 'pd-axis';
    const laneNames = document.createElement('div');
    overlay.append(axis, laneNames);

    function buildOverlay() {
        axis.className = `pd-axis ${layout.horizontal ? 'is-horizontal' : 'is-vertical'}`;
        const today = document.createElement('span');
        today.className = 'is-today';
        today.textContent = labels.today;
        today.dataset.pos = layout.todayPos;

        axis.replaceChildren(
            ...layout.ticks.map((tick) => {
                const label = document.createElement('span');
                label.textContent = tick.year;
                label.dataset.pos = tick.pos;

                return label;
            }),
            today,
        );

        laneNames.replaceChildren(
            ...layout.lanes.map((lane) => {
                const queen = model.animals.get(lane.queenId);
                const label = document.createElement('button');
                label.type = 'button';
                label.className = 'pd-sticky-name';
                label.dataset.goto = queen.id;
                label.dataset.c = lane.c;
                label.dataset.start = lane.start;
                label.innerHTML = `<span style="background:${queen.color?.hex ?? '#d9d3c7'}"></span>${escapeHtml(queen.name)}`;

                return label;
            }),
        );
    }

    function updateOverlay({ k, tx, ty }) {
        const horizontal = layout.horizontal;

        for (const label of axis.children) {
            const screen = Number(label.dataset.pos) * k + (horizontal ? tx : ty);
            const shift = label.classList.contains('is-today') ? (horizontal ? ' translateX(calc(-100% - 0.8rem))' : ' translateY(-100%)') : '';
            label.style.transform = (horizontal ? `translateX(${screen}px)` : `translateY(${screen}px)`) + shift;
        }

        for (const label of laneNames.children) {
            const cross = Number(label.dataset.c) * k + (horizontal ? ty : tx);
            const start = Number(label.dataset.start) * k + (horizontal ? tx : ty);
            const hidden = start > (horizontal ? 70 : 40) || cross < 0 || cross > (horizontal ? stage.clientHeight : stage.clientWidth);
            label.classList.toggle('is-hidden', hidden);
            label.style.transform = horizontal ? `translate(12px, ${cross - 12}px)` : `translate(${cross - 12}px, 34px)`;
        }

        root.classList.toggle('lod-far', k < 0.5);
    }

    /* ---------- Highlighting ---------- */

    function relatedSet(id) {
        const relation = model.relations[id] ?? { a: [], d: [] };

        return new Set([id, ...relation.a, ...relation.d]);
    }

    function applyHighlight() {
        const focusId = hoveredId ?? selectedId;
        root.classList.toggle('is-focusing', focusId !== null);

        if (focusId === null) {
            return;
        }

        const related = relatedSet(focusId);

        for (const node of svg.querySelectorAll('[data-animal]')) {
            const id = Number(node.dataset.animal);
            node.classList.toggle('is-lit', related.has(id));
            node.classList.toggle('is-focus', id === focusId);
        }

        for (const { head, offspring } of rendered.litterHeads) {
            head.classList.toggle('is-lit', offspring.some((id) => related.has(id)));
        }
    }

    /* ---------- Filters and time ---------- */

    function applyFilters() {
        for (const node of svg.querySelectorAll('.pd-kitten, .pd-avatar')) {
            const matches = (!filters.sex || node.dataset.sex === filters.sex) && (!filters.color || node.dataset.color === filters.color);
            node.classList.toggle('is-filtered', !matches);
        }
    }

    function applyTime() {
        for (const node of svg.querySelectorAll('[data-ms]')) {
            node.classList.toggle('is-future', Number(node.dataset.ms) > cursorMs);
        }

        clipLanes(rendered, layout, cursorMs);
        scrubber.value = Math.round(((cursorMs - msRange[0]) / (msRange[1] - msRange[0])) * 1000);
        scrubberLabel.textContent = new Date(cursorMs).getFullYear();
    }

    function setPlaying(isPlaying) {
        playButton.querySelector('[data-play-icon]').classList.toggle('hidden', isPlaying);
        playButton.querySelector('[data-pause-icon]').classList.toggle('hidden', !isPlaying);
        playButton.setAttribute('aria-label', isPlaying ? labels.pause : labels.play);

        if (!isPlaying) {
            cancelAnimationFrame(playing);
            playing = null;

            return;
        }

        if (cursorMs >= msRange[1]) {
            cursorMs = msRange[0];
        }

        viewport.fit(layout.bounds, fitOptions());
        const duration = 9000;
        let last = performance.now();

        const step = (now) => {
            cursorMs = Math.min(msRange[1], cursorMs + ((now - last) / duration) * (msRange[1] - msRange[0]));
            last = now;
            applyTime();
            playing = cursorMs < msRange[1] ? requestAnimationFrame(step) : (setPlaying(false), null);
        };

        playing = requestAnimationFrame(step);
    }

    /* ---------- Selection, panel and URL ---------- */

    function select(id, { locate = true } = {}) {
        if (!model.animals.has(id)) {
            return;
        }

        if (playing !== null || cursorMs < msRange[1]) {
            setPlaying(false);
            cursorMs = msRange[1];
            applyTime();
        }

        selectedId = id;
        panelBody.innerHTML = renderPanel(model, id, labels);
        panelBody.scrollTop = 0;
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        applyHighlight();

        const url = new URL(window.location.href);
        url.searchParams.set('animal', id);
        history.replaceState(null, '', url);

        if (locate) {
            locateAnimal(id);
        }
    }

    function closePanel() {
        selectedId = null;
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        applyHighlight();

        const url = new URL(window.location.href);
        url.searchParams.delete('animal');
        history.replaceState(null, '', url);
    }

    function locateAnimal(id) {
        const anchor = layout.anchors.get(id);

        if (!anchor) {
            return;
        }

        const panelOpen = panel.classList.contains('is-open');
        const wide = !portrait.matches;
        viewport.focus(anchor, {
            k: Math.max(viewport.state.k, 1),
            anchorX: panelOpen && wide ? (stage.clientWidth - 420) / 2 / stage.clientWidth : 0.5,
            anchorY: panelOpen && !wide ? 0.25 : 0.45,
        });
    }

    /* ---------- Search ---------- */

    let matches = [];
    let active = 0;

    function showResults() {
        const query = search.value.trim().toLowerCase();
        matches = query
            ? [...model.animals.values()]
                  .filter((animal) => animal.name.toLowerCase().includes(query) || animal.callName?.toLowerCase().includes(query))
                  .sort((a, b) => a.name.toLowerCase().indexOf(query) - b.name.toLowerCase().indexOf(query) || a.name.localeCompare(b.name))
                  .slice(0, 8)
            : [];
        active = 0;

        results.innerHTML = matches.length
            ? matches
                  .map(
                      (animal, index) => `<li role="option" data-goto="${animal.id}" aria-selected="${index === active}" class="pd-result">
                        <span class="size-3 rounded-full ring-1 ring-black/10" style="background:${animal.color?.hex ?? '#d9d3c7'}"></span>
                        <span class="flex-1">${escapeHtml(animal.name)}</span>
                        <span class="text-xs text-ink-soft">${escapeHtml(animal.origin)}</span>
                    </li>`,
                  )
                  .join('')
            : query
              ? `<li class="px-3 py-2 text-ink-soft">${escapeHtml(labels.noResults)}</li>`
              : '';

        results.classList.toggle('hidden', !query);
        search.setAttribute('aria-expanded', query ? 'true' : 'false');
    }

    function pick(id) {
        search.value = '';
        results.classList.add('hidden');
        search.blur();
        select(id);
    }

    search.addEventListener('input', showResults);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            active = (active + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % Math.max(matches.length, 1);
            results.querySelectorAll('[role=option]').forEach((option, index) => option.setAttribute('aria-selected', index === active));
        } else if (event.key === 'Enter' && matches[active]) {
            event.preventDefault();
            pick(matches[active].id);
        } else if (event.key === 'Escape') {
            search.value = '';
            showResults();
        }
    });
    search.addEventListener('blur', () => setTimeout(() => results.classList.add('hidden'), 150));
    results.addEventListener('mousedown', (event) => {
        const option = event.target.closest('[data-goto]');
        if (option) {
            event.preventDefault();
            pick(Number(option.dataset.goto));
        }
    });

    /* ---------- Events ---------- */

    svg.addEventListener('pointerover', (event) => {
        const target = event.target.closest('[data-animal]');

        if (event.pointerType !== 'mouse' || !target || target.classList.contains('pd-lane') || target.classList.contains('pd-connector')) {
            return;
        }

        hoveredId = Number(target.dataset.animal);
        applyHighlight();

        if (root.classList.contains('lod-far')) {
            const animal = model.animals.get(hoveredId);
            tooltip.textContent = [animal.name, animal.color?.name].filter(Boolean).join(' · ');
            tooltip.classList.remove('hidden');
        }
    });

    svg.addEventListener('pointerout', (event) => {
        const target = event.target.closest('[data-animal]');

        if (target && !target.contains(event.relatedTarget)) {
            hoveredId = null;
            tooltip.classList.add('hidden');
            applyHighlight();
        }
    });

    svg.addEventListener('pointermove', (event) => {
        if (!tooltip.classList.contains('hidden')) {
            const rect = stage.getBoundingClientRect();
            tooltip.style.left = `${event.clientX - rect.left + 14}px`;
            tooltip.style.top = `${event.clientY - rect.top + 14}px`;
        }
    });

    svg.addEventListener('click', (event) => {
        const target = event.target.closest('[data-animal]');

        if (target && !target.classList.contains('pd-lane') && !target.classList.contains('pd-connector')) {
            select(Number(target.dataset.animal), { locate: false });
        } else if (selectedId !== null) {
            closePanel();
        }
    });

    svg.addEventListener('keydown', (event) => {
        const target = event.target.closest?.('[data-animal]');

        if (target && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            select(Number(target.dataset.animal));
        }
    });

    root.addEventListener('click', (event) => {
        const goto = event.target.closest('[data-goto]');
        const locate = event.target.closest('[data-locate]');
        const photo = event.target.closest('[data-photo]');

        if (goto && !results.contains(goto)) {
            select(Number(goto.dataset.goto));
        } else if (locate) {
            locateAnimal(Number(locate.dataset.locate));
        } else if (photo) {
            panelBody.querySelector('[data-main-photo]').src = photo.dataset.photo;
            panelBody.querySelectorAll('[data-photo]').forEach((thumb) => {
                thumb.classList.toggle('ring-white', thumb === photo);
                thumb.classList.toggle('ring-white/30', thumb !== photo);
            });
        }
    });

    root.querySelector('[data-panel-close]').addEventListener('click', closePanel);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && selectedId !== null && document.activeElement !== search) {
            closePanel();
        }
    });

    root.querySelectorAll('[data-filter-sex]').forEach((button) => {
        button.addEventListener('click', () => {
            const sex = button.dataset.filterSex;
            filters.sex = filters.sex === sex ? null : sex;
            root.querySelectorAll('[data-filter-sex]').forEach((other) => other.setAttribute('aria-pressed', other.dataset.filterSex === filters.sex));
            applyFilters();
        });
    });

    const colors = [...new Set([...model.animals.values()].map((animal) => animal.color?.name).filter(Boolean))].sort((a, b) => a.localeCompare(b));
    for (const color of colors) {
        colorFilter.add(new Option(color, color));
    }
    colorFilter.addEventListener('change', () => {
        filters.color = colorFilter.value;
        colorFilter.setAttribute('aria-pressed', filters.color !== '');
        applyFilters();
    });

    root.querySelectorAll('[data-zoom]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.zoom;
            action === 'fit' ? viewport.fit(layout.bounds, { ...fitOptions(), minScale: 0.2, maxScale: 1 }) : viewport.zoomBy(action === 'in' ? 1.4 : 1 / 1.4);
        });
    });

    scrubber.addEventListener('input', () => {
        setPlaying(false);
        cursorMs = msRange[0] + (Number(scrubber.value) / 1000) * (msRange[1] - msRange[0]);
        applyTime();
    });

    playButton.addEventListener('click', () => setPlaying(playing === null));

    /* ---------- Build ---------- */

    function fitOptions() {
        return layout.horizontal
            ? { insets: { top: 28, bottom: 64 }, minScale: 1.2, maxScale: 1.2, padding: 32 }
            : { insets: { left: 36, top: 64, bottom: 64 }, minScale: 0.95, maxScale: 0.95, padding: 16 };
    }

    function build() {
        layout = computeLayout(model, portrait.matches ? 'v' : 'h');
        rendered = render(svg, model, layout, labels);
        buildOverlay();
        viewport.setWorld(rendered.world);
        viewport.fit(layout.bounds, { ...fitOptions(), animate: false });
        applyFilters();
        applyTime();
        applyHighlight();
    }

    viewport = createViewport(svg, updateOverlay);
    build();

    portrait.addEventListener('change', () => {
        build();
        if (selectedId !== null) {
            locateAnimal(selectedId);
        }
    });

    const initial = Number(new URL(window.location.href).searchParams.get('animal'));
    if (initial && model.animals.has(initial)) {
        select(initial);
    } else if (!reducedMotion.matches && sessionStorageGet('pd-intro') !== '1') {
        sessionStorageSet('pd-intro', '1');
        setPlaying(true);
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
}

function sessionStorageGet(key) {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function sessionStorageSet(key, value) {
    try {
        window.sessionStorage.setItem(key, value);
    } catch {
        // Storage may be unavailable (private mode); the intro simply plays again.
    }
}
