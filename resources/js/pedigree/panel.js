/**
 * Profile of a single animal: photos, facts, parents, litter mates, own litters
 * and a three-generation ancestry. Links inside use data-goto="<animal id>".
 */

const escape = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);

const sexSymbol = (sex) => ({ female: '♀', male: '♂' })[sex] ?? '';

function swatch(animal, size = 'size-3') {
    const color = animal?.color?.hex ?? '#d9d3c7';

    return `<span class="${size} inline-block shrink-0 rounded-full ring-1 ring-black/10" style="background:${escape(color)}"></span>`;
}

function chip(animal) {
    return `<button type="button" data-goto="${animal.id}" class="inline-flex items-center gap-1.5 rounded-full border border-paper-deep bg-white px-2.5 py-1 text-sm transition hover:border-kith-300 hover:bg-kith-50">
        ${swatch(animal)}<span>${escape(animal.name)}</span><span class="text-ink-soft">${sexSymbol(animal.sex)}</span>
    </button>`;
}

function section(title, body) {
    return `<section class="flex flex-col gap-2.5 border-t border-paper-deep px-6 py-5">
        <h3 class="text-xs font-medium tracking-wider text-ink-soft uppercase">${escape(title)}</h3>
        ${body}
    </section>`;
}

function parentsOf(model, id) {
    const animal = model.animals.get(id);

    return [animal?.motherId ?? null, animal?.fatherId ?? null];
}

function ancestry(model, id, labels) {
    const generations = [];
    let current = [id];

    for (let depth = 0; depth < 3; depth++) {
        current = current.flatMap((animalId) => (animalId === null ? [null, null] : parentsOf(model, animalId)));
        generations.push(current);
    }

    if (generations[0].every((animalId) => animalId === null)) {
        return '';
    }

    const spans = ['row-span-4', 'row-span-2', 'row-span-1'];
    const columns = generations
        .map((ids, depth) =>
            ids
                .map((animalId, index) => {
                    const animal = animalId === null ? null : model.animals.get(animalId);
                    const style = `grid-column:${depth + 1};grid-row:${index * 2 ** (2 - depth) + 1} / span ${2 ** (2 - depth)}`;

                    if (!animal) {
                        return `<div style="${style}" class="flex items-center rounded-lg border border-dashed border-paper-deep px-2 text-xs text-ink-soft/60">${escape(labels.unknown)}</div>`;
                    }

                    return `<button type="button" data-goto="${animal.id}" style="${style}" class="${spans[depth]} flex min-w-0 items-center gap-1.5 rounded-lg bg-paper px-2 py-1.5 text-left text-xs transition hover:bg-kith-50">
                        ${swatch(animal, 'size-2.5')}<span class="truncate">${escape(animal.name)}</span>
                    </button>`;
                })
                .join(''),
        )
        .join('');

    const headings = labels.generations
        .map((title) => `<div class="text-[0.65rem] tracking-wider text-ink-soft uppercase">${escape(title)}</div>`)
        .join('');

    return section(
        labels.ancestry,
        `<div class="grid grid-cols-3 gap-1">${headings}</div>
         <div class="grid grid-cols-3 gap-1" style="grid-template-rows:repeat(8,minmax(1.75rem,auto))">${columns}</div>`,
    );
}

export function renderPanel(model, id, labels) {
    const animal = model.animals.get(id);
    const mother = model.animals.get(animal.motherId);
    const father = model.animals.get(animal.fatherId);
    const birthLitter = animal.litterId ? model.litterById.get(animal.litterId) : null;
    const siblings = birthLitter ? birthLitter.offspring.filter((other) => other !== id).map((other) => model.animals.get(other)) : [];
    const ownLitters = model.litters.filter((litter) => litter.motherId === id || litter.fatherId === id);

    const photos = animal.photos.length
        ? `<div class="relative aspect-[4/3] bg-paper-deep">
                <img data-main-photo src="${escape(animal.photos[0])}" alt="${escape(labels.photo.replace(':name', animal.name))}" class="size-full object-cover">
                ${
                    animal.photos.length > 1
                        ? `<div class="absolute right-0 bottom-0 left-0 flex gap-1.5 bg-gradient-to-t from-black/50 to-transparent p-3 pt-8">
                            ${animal.photos.map((url, index) => `<button type="button" data-photo="${escape(url)}" class="size-11 overflow-hidden rounded-lg ring-2 ${index === 0 ? 'ring-white' : 'ring-white/30'} transition hover:ring-white"><img src="${escape(url)}" alt="" class="size-full object-cover"></button>`).join('')}
                        </div>`
                        : ''
                }
            </div>`
        : `<div class="flex aspect-[5/2] items-end p-6" style="background:${escape(animal.color?.hex ?? '#ece7dd')}">
                <span class="font-serif text-8xl leading-none text-white/70 mix-blend-overlay">${escape(animal.name.slice(0, 1))}</span>
            </div>`;

    const facts = [
        [labels.born, animal.born ?? birthLitter?.born],
        [labels.color, animal.color ? `<span class="inline-flex items-center gap-1.5">${swatch(animal)}${escape(animal.color.name)}</span>` : null, true],
        [labels.breed, animal.breed],
        [labels.origin, animal.origin],
    ]
        .filter(([, value]) => value)
        .map(
            ([label, value, isHtml]) => `<div class="flex flex-col gap-0.5">
                <dt class="text-xs text-ink-soft">${escape(label)}</dt>
                <dd class="text-sm">${isHtml ? value : escape(value)}</dd>
            </div>`,
        )
        .join('');

    const sexLabel = { female: labels.female, male: labels.male }[animal.sex] ?? labels.unknownSex;

    const parentCard = (parent, label) =>
        parent
            ? `<button type="button" data-goto="${parent.id}" class="flex flex-1 items-center gap-2.5 rounded-xl border border-paper-deep p-2.5 text-left transition hover:border-kith-300 hover:bg-kith-50">
                    ${swatch(parent, 'size-7')}
                    <span class="flex min-w-0 flex-col"><span class="text-xs text-ink-soft">${escape(label)}</span><span class="truncate font-medium">${escape(parent.name)}</span></span>
               </button>`
            : '';

    const parents = mother || father ? section(labels.parents, `<div class="flex gap-2">${parentCard(mother, labels.mother)}${parentCard(father, labels.father)}</div>`) : '';

    const littermates = siblings.length ? section(labels.littermates, `<div class="flex flex-wrap gap-1.5">${siblings.map(chip).join('')}</div>`) : '';

    const litters = ownLitters.length
        ? section(
              labels.ownLitters,
              ownLitters
                  .map((litter) => {
                      const partner = model.animals.get(litter.motherId === id ? litter.fatherId : litter.motherId);
                      const offspring = litter.offspring.map((offspringId) => model.animals.get(offspringId));

                      return `<div class="flex flex-col gap-2">
                        <p class="text-sm"><span class="font-semibold text-kith-900">${escape(labels.litter.replace(':letter', litter.letter))}</span>
                            <span class="text-ink-soft">· ${escape(litter.born)}${partner ? ` · ${escape(labels.with.replace(':name', ''))}</span><button type="button" data-goto="${partner.id}" class="font-medium underline decoration-kith-200 underline-offset-2 hover:decoration-kith-900">${escape(partner.name)}</button>` : '</span>'}
                        </p>
                        <div class="flex flex-wrap gap-1.5">${offspring.map(chip).join('')}</div>
                    </div>`;
                  })
                  .join(''),
          )
        : '';

    return `${photos}
        <div class="flex flex-col gap-4 px-6 pt-5 pb-5">
            <div class="flex flex-col gap-1">
                <p class="text-xs font-medium tracking-wider text-ink-soft uppercase">${escape(sexLabel)} ${sexSymbol(animal.sex)}</p>
                <h2 class="font-serif text-4xl leading-tight text-kith-900">${escape(animal.name)}</h2>
                ${animal.callName ? `<p class="text-ink-soft">„${escape(animal.callName)}“</p>` : ''}
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">${facts}</dl>
            <button type="button" data-locate="${animal.id}" class="inline-flex items-center gap-2 self-start rounded-full bg-kith-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-kith-800">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd"/></svg>
                ${escape(labels.showInTree)}
            </button>
        </div>
        ${parents}${littermates}${litters}${ancestry(model, id, labels)}
        <div class="h-8"></div>`;
}
