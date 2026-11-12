/**
 * Draws the laid-out pedigree into the SVG world group.
 * Every interactive element carries data-animal (and data-ms for the time scrubber).
 */

const SVG_NS = 'http://www.w3.org/2000/svg';
const NEUTRAL = '#d9d3c7';

function el(tag, attributes = {}, parent = null, text = null) {
    const node = document.createElementNS(SVG_NS, tag);

    for (const [name, value] of Object.entries(attributes)) {
        if (value !== null && value !== undefined && value !== false) {
            node.setAttribute(name, value);
        }
    }

    if (text !== null) {
        node.textContent = text;
    }

    parent?.appendChild(node);

    return node;
}

const sexSymbol = (sex) => ({ female: '♀', male: '♂' })[sex] ?? '';

export function render(svg, model, layout, labels) {
    svg.replaceChildren();

    const defs = el('defs', {}, svg);
    const world = el('g', { class: 'pd-world' }, svg);
    const bands = el('g', {}, world);
    const grid = el('g', {}, world);
    const lanesLayer = el('g', {}, world);
    const connectorsLayer = el('g', {}, world);
    const littersLayer = el('g', {}, world);
    const avatarsLayer = el('g', {}, world);

    const { horizontal, point } = layout;
    const laneLines = new Map();
    const litterHeads = [];

    layout.lanes.forEach((lane, index) => {
        if (index % 2 === 0) {
            const band = horizontal
                ? { x: layout.timeMin - 4000, y: lane.bandStart, width: layout.timeMax - layout.timeMin + 8000, height: lane.bandSize }
                : { x: lane.bandStart, y: layout.timeMin - 4000, width: lane.bandSize, height: layout.timeMax - layout.timeMin + 8000 };
            el('rect', { ...band, class: 'pd-band' }, bands);
        }
    });

    for (const tick of layout.ticks) {
        const from = point(tick.pos, -4000);
        const to = point(tick.pos, layout.crossSize + 4000);
        el('line', { x1: from.x, y1: from.y, x2: to.x, y2: to.y, class: 'pd-grid' }, grid);
    }

    const todayFrom = point(layout.todayPos, -4000);
    const todayTo = point(layout.todayPos, layout.crossSize + 4000);
    el('line', { x1: todayFrom.x, y1: todayFrom.y, x2: todayTo.x, y2: todayTo.y, class: 'pd-today' }, grid);

    for (const lane of layout.lanes) {
        const from = point(lane.start, lane.c);
        const to = point(lane.end, lane.c);
        const line = el(
            'line',
            { x1: from.x, y1: from.y, x2: to.x, y2: to.y, class: 'pd-lane pd-dim', 'data-animal': lane.queenId, 'data-ms': lane.startMs },
            lanesLayer,
        );
        laneLines.set(lane.queenId, { line, lane });
    }

    for (const connector of layout.connectors) {
        el('path', { d: connector.path, class: 'pd-connector pd-dim', 'data-animal': connector.daughterId, 'data-ms': connector.ms }, connectorsLayer);
    }

    for (const placed of layout.litters) {
        const { litter, node, kittens } = placed;
        const group = el('g', { class: 'pd-litter', 'data-litter': litter.id, 'data-ms': litter.ms }, littersLayer);
        const head = el('g', { class: 'pd-dim pd-litter-head', 'data-offspring': litter.offspring.join(' ') }, group);

        if (kittens.length) {
            const last = kittens[kittens.length - 1];
            el('line', { x1: node.x, y1: node.y, x2: last.x, y2: last.y, class: 'pd-stem' }, head);
        }

        el('circle', { cx: node.x, cy: node.y, r: 15, class: `pd-node${litter.estimated ? ' is-estimated' : ''}` }, head);
        el('text', { x: node.x, y: node.y, class: 'pd-node-letter' }, head, litter.letter);
        el('text', { x: placed.meta.x, y: placed.meta.y, class: 'pd-meta pd-halo' }, head, litter.born);

        const father = model.animals.get(litter.fatherId);
        if (father) {
            el(
                'text',
                { x: placed.sire.x, y: placed.sire.y, class: 'pd-sire pd-halo pd-dim', 'data-animal': father.id, tabindex: 0, role: 'button' },
                group,
                `♂ ${father.name}`,
            );
        }

        for (const kitten of kittens) {
            const animal = model.animals.get(kitten.id);
            const item = el(
                'g',
                {
                    class: `pd-kitten pd-dim${animal.isBreeding ? ' is-breeding' : ''}`,
                    'data-animal': animal.id,
                    'data-sex': animal.sex,
                    'data-color': animal.color?.name ?? '',
                    tabindex: 0,
                    role: 'button',
                },
                group,
            );
            el('rect', { x: kitten.x - 10, y: kitten.y - 12, width: 185, height: 24, class: 'pd-hit' }, item);
            el('circle', { cx: kitten.x, cy: kitten.y, r: animal.isBreeding ? 7.5 : 7, fill: animal.color?.hex ?? NEUTRAL }, item);
            el('text', { x: kitten.x + 16, y: kitten.y, class: 'pd-halo' }, item, `${animal.name} ${sexSymbol(animal.sex)}`.trim());
        }

        litterHeads.push({ head, offspring: litter.offspring });
    }

    for (const lane of layout.lanes) {
        const queen = model.animals.get(lane.queenId);
        const center = point(lane.start, lane.c);
        const group = el(
            'g',
            {
                class: 'pd-avatar pd-dim',
                'data-animal': queen.id,
                'data-sex': queen.sex,
                'data-color': queen.color?.name ?? '',
                'data-ms': lane.startMs,
                tabindex: 0,
                role: 'button',
                'aria-label': queen.name,
            },
            avatarsLayer,
        );

        el('circle', { cx: center.x, cy: center.y, r: 28, fill: queen.color?.hex ?? NEUTRAL, class: 'pd-avatar-fill' }, group);

        if (queen.photos.length) {
            const clipId = `pd-clip-${queen.id}`;
            const clip = el('clipPath', { id: clipId }, defs);
            el('circle', { cx: center.x, cy: center.y, r: 25.5 }, clip);
            el(
                'image',
                { href: queen.photos[0], x: center.x - 25.5, y: center.y - 25.5, width: 51, height: 51, 'clip-path': `url(#${clipId})`, preserveAspectRatio: 'xMidYMid slice' },
                group,
            );
        }

        el('circle', { cx: center.x, cy: center.y, r: 28, class: 'pd-avatar-ring' }, group);

        const name = horizontal ? { x: center.x, y: center.y + 50, anchor: 'middle' } : { x: center.x + 36, y: center.y, anchor: 'start' };
        el('text', { x: name.x, y: name.y, 'text-anchor': name.anchor, class: 'pd-avatar-name pd-halo' }, group, queen.name);
    }

    return { world, laneLines, litterHeads };
}

/**
 * Shortens every lane line to the litters that are visible at the given time.
 */
export function clipLanes(rendered, layout, cursorMs) {
    for (const { line, lane } of rendered.laneLines.values()) {
        const visible = layout.litters.filter((placed) => placed.litter.motherId === lane.queenId && placed.litter.ms <= cursorMs);
        const last = visible[visible.length - 1];
        const end = last ? last.t + layout.extentOf(last.litter) : lane.start + 30;
        const to = layout.point(Math.min(end, lane.end), lane.c);
        line.setAttribute('x2', to.x);
        line.setAttribute('y2', to.y);
    }
}
