@php
    use App\Enums\DatePrecision;
    use App\Filament\App\Resources\Animals\AnimalResource;
    use App\Filament\App\Resources\Litters\LitterResource;
@endphp

<x-filament-panels::page>
    @if (count($this->speciesOptions) === 0)
        <x-filament::section>
            {{ __('There are no litters yet. Add animals and a litter to see the pedigree.') }}
            <x-slot name="footer">
                <x-filament::button tag="a" :href="LitterResource::getUrl('create')">
                    {{ __('Create litter') }}
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    @else
        @php($graph = $this->graph)

        @if (count($this->speciesOptions) > 1)
            <x-filament::input.wrapper style="max-width: 16rem">
                <x-filament::input.select wire:model.live="speciesId">
                    @foreach ($this->speciesOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @endif

        <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fill, minmax(min(22rem, 100%), 1fr)); align-items: start">
            @foreach ($graph->lanes() as $lane)
                @php($queen = $lane['queen'])
                <x-filament::section
                    :heading="$queen->name"
                    :description="$queen->litter_id !== null
                        ? __('Queen · from litter :letter', ['letter' => $graph->litter($queen->litter_id)?->letter ?? '#'.$queen->litter_id])
                        : $queen->origin->getLabel()"
                >
                    <x-slot name="afterHeader">
                        <x-filament::link :href="AnimalResource::getUrl('edit', ['record' => $queen])" size="sm">
                            {{ __('Edit') }}
                        </x-filament::link>
                    </x-slot>

                    <div style="display: flex; flex-direction: column; gap: 1rem">
                        @foreach ($lane['litters'] as $litter)
                            @php($father = $graph->animal($litter->father_id))
                            @php($offspring = $graph->offspringOf($litter))
                            <div style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem">
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: .5rem">
                                    <x-filament::link :href="LitterResource::getUrl('edit', ['record' => $litter])" weight="semibold">
                                        {{ __('Litter :letter', ['letter' => $litter->letter ?? '#'.$litter->id]) }}
                                    </x-filament::link>
                                    <span style="color: var(--gray-500)">
                                        {{ DatePrecision::describe($litter->born_on, $litter->born_on_precision) }}
                                    </span>
                                    @if ($graph->isRelatedPairing($litter))
                                        <x-filament::badge color="warning" size="sm">{{ __('Common ancestors') }}</x-filament::badge>
                                    @endif
                                </div>
                                <div>
                                    {{ __('Father') }}:
                                    <x-filament::link :href="AnimalResource::getUrl('edit', ['record' => $father])" size="sm">{{ $father->name }}</x-filament::link>
                                </div>
                                <div>
                                    {{ __(':count offspring', ['count' => $offspring->count()]) }}@if ($offspring->isNotEmpty()):@endif
                                    @foreach ($offspring as $kitten)
                                        <x-filament::link :href="AnimalResource::getUrl('edit', ['record' => $kitten])" size="sm">{{ $kitten->name }}</x-filament::link>@if (! $loop->last),@endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section :heading="__('Sires')">
            <div style="display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; font-size: .875rem">
                @foreach ($graph->sires() as $sire)
                    <span>
                        <x-filament::link :href="AnimalResource::getUrl('edit', ['record' => $sire['animal']])" size="sm">{{ $sire['animal']->name }}</x-filament::link>
                        <span style="color: var(--gray-500)">· {{ __(':count litters', ['count' => $sire['litters']]) }}</span>
                    </span>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
