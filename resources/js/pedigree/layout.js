/**
 * Turns the pedigree data into world coordinates.
 *
 * Time runs along one axis, every dam has her own lane on the other.
 * Horizontal ("h"): time left → right, lanes stacked top → bottom.
 * Vertical ("v", phones): time top → bottom, lanes side by side.
 */

export const YEAR_MS = 365.25 * 24 * 3600 * 1000;
export const ROW = 25;

/**
 * Indexes the raw data and assigns every litter a point in time.
 * Litters without a birth date are estimated from the birth of their parents
 * (one year later) or placed a year before the next dated litter of the dam.
 */
export function buildModel(data) {
    const animals = new Map(Object.values(data.animals).map((animal) => [animal.id, { ...animal }]));
    const litters = data.litters.map((litter) => ({ ...litter, estimated: litter.date === null }));
    const litterById = new Map(litters.map((litter) => [litter.id, litter]));

    for (const animal of animals.values()) {
        const birthLitter = animal.litterId ? litterById.get(animal.litterId) : null;
        animal.motherId = birthLitter?.motherId ?? null;
        animal.fatherId = birthLitter?.fatherId ?? null;
    }

    for (const litter of litters) {
        if (litter.date) {
            litter.ms = Date.parse(litter.date);
        }
    }

    const birthMs = (animalId) => {
        const litterId = animals.get(animalId)?.litterId;

        return litterId ? litterById.get(litterId)?.ms : undefined;
    };

    for (const litter of litters) {
        if (litter.ms !== undefined) {
            continue;
        }

        const afterParents = [litter.motherId, litter.fatherId]
            .map(birthMs)
            .filter((ms) => ms !== undefined)
            .map((ms) => ms + YEAR_MS);

        const datedOfDam = litters
            .filter((other) => other.motherId === litter.motherId && other !== litter && other.ms !== undefined)
            .map((other) => other.ms);

        const known = litters.filter((other) => other.ms !== undefined).map((other) => other.ms);

        if (afterParents.length) {
            litter.ms = Math.max(...afterParents);
        } else if (datedOfDam.length) {
            litter.ms = Math.min(...datedOfDam) - YEAR_MS;
        } else {
            litter.ms = known.length ? Math.min(...known) - YEAR_MS / 2 : Date.now();
        }
    }

    const fathers = new Set(litters.map((litter) => litter.fatherId));
    const mothers = new Set(litters.map((litter) => litter.motherId));

    for (const animal of animals.values()) {
        animal.isBreeding = fathers.has(animal.id) || mothers.has(animal.id);
    }

    return {
        animals,
        litters,
        litterById,
        lanes: data.lanes.filter((id) => animals.has(id)),
        relations: data.relations,
    };
}

/**
 * @param {ReturnType<typeof buildModel>} model
 * @param {'h'|'v'} orientation
 */
export function computeLayout(model, orientation) {
    const horizontal = orientation === 'h';
    const pxPerYear = horizontal ? 320 : 320;
    const gap = 18;

    const allMs = model.litters.map((litter) => litter.ms);
    const startYear = new Date(Math.min(...allMs) - YEAR_MS).getFullYear();
    const t0 = Date.UTC(startYear, 0, 1);
    const toPos = (ms) => ((ms - t0) / YEAR_MS) * pxPerYear;
    const fromPos = (pos) => t0 + (pos / pxPerYear) * YEAR_MS;

    /** World point from time position and cross-axis coordinate. */
    const point = (t, c) => (horizontal ? { x: t, y: c } : { x: c, y: t });

    const littersOfDam = new Map();
    for (const litter of [...model.litters].sort((a, b) => a.ms - b.ms)) {
        if (!littersOfDam.has(litter.motherId)) {
            littersOfDam.set(litter.motherId, []);
        }
        littersOfDam.get(litter.motherId).push(litter);
    }

    const extentOf = (litter) => (horizontal ? 185 : 62 + litter.offspring.length * ROW + 32);
    const nameWidth = (name) => name.length * 8.4;

    const kittenPos = new Map();
    const placedLitters = [];
    const lanes = [];
    const connectors = [];
    const anchors = new Map();
    let cursor = 24;

    for (const queenId of model.lanes) {
        const queen = model.animals.get(queenId);
        const ownLitters = littersOfDam.get(queenId) ?? [];
        const maxOffspring = Math.max(0, ...ownLitters.map((litter) => litter.offspring.length));

        const bandSize = horizontal ? 62 + 42 + maxOffspring * ROW + 36 : 235;
        const c = horizontal ? cursor + 62 : cursor + 34;

        let start;
        let startMs;
        const birth = kittenPos.get(queenId);

        if (birth) {
            start = horizontal ? birth.x + 58 : birth.y + 54;
            startMs = birth.ms;

            const path = horizontal
                ? `M ${birth.x - 5} ${birth.y} H ${birth.x - 12} V ${c - 12} Q ${birth.x - 12} ${c} ${birth.x} ${c} H ${start - 28}`
                : `M ${birth.x + 16 + nameWidth(queen.name) + 6} ${birth.y} H ${c - 12} Q ${c} ${birth.y} ${c} ${birth.y + 12} V ${start - 28}`;

            connectors.push({ daughterId: queenId, path, ms: birth.ms });
        } else {
            const first = ownLitters[0];
            start = (first ? toPos(first.ms) : 0) - (horizontal ? 130 : 90);
            startMs = first ? first.ms - YEAR_MS / 2 : t0;
        }

        anchors.set(queenId, point(start, c));

        let previousEnd = start + (horizontal ? 150 : 84);

        for (const litter of ownLitters) {
            const t = Math.max(toPos(litter.ms), previousEnd);
            previousEnd = t + extentOf(litter) + gap;

            const node = point(t, c);
            const kittens = litter.offspring.map((id, index) => {
                const position = horizontal ? { x: t, y: c + 44 + index * ROW } : { x: c, y: t + 60 + index * ROW };
                const kitten = { id, ...position, ms: litter.ms };
                kittenPos.set(id, kitten);
                anchors.set(id, anchors.get(id) ?? position);

                return kitten;
            });

            placedLitters.push({
                litter,
                t,
                node,
                meta: horizontal ? { x: t - 15, y: c - 54 } : { x: c + 26, y: t - 11 },
                sire: horizontal ? { x: t - 15, y: c - 31 } : { x: c + 26, y: t + 13 },
                kittens,
            });
        }

        lanes.push({
            queenId,
            c,
            start,
            startMs,
            end: previousEnd - gap + 20,
            bandStart: cursor,
            bandSize,
        });

        cursor += bandSize;
    }

    // Sires without a lane or birth position are anchored at their first litter.
    for (const placed of placedLitters) {
        if (!anchors.has(placed.litter.fatherId)) {
            anchors.set(placed.litter.fatherId, placed.sire);
        }
    }

    // A queen is shown by her avatar rather than her dot in her mother's litter.
    for (const lane of lanes) {
        anchors.set(lane.queenId, point(lane.start, lane.c));
    }

    const timeMin = Math.min(...lanes.map((lane) => lane.start)) - 60;
    const timeMax = Math.max(...lanes.map((lane) => lane.end)) + 60;

    const ticks = [];
    for (let year = startYear; ; year++) {
        const pos = toPos(Date.UTC(year, 0, 1));
        if (pos > timeMax) {
            break;
        }
        if (pos >= timeMin) {
            ticks.push({ year, pos });
        }
    }

    const bounds = horizontal
        ? { x: timeMin, y: 0, width: timeMax - timeMin, height: cursor + 24 }
        : { x: 0, y: timeMin, width: cursor + 24, height: timeMax - timeMin };

    return {
        orientation,
        horizontal,
        lanes,
        litters: placedLitters,
        connectors,
        anchors,
        ticks,
        bounds,
        crossSize: cursor + 24,
        todayPos: toPos(Date.now()),
        timeMin,
        timeMax,
        point,
        toPos,
        fromPos,
        extentOf,
    };
}
