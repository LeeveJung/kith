@php
    use App\Enums\DatePrecision;
    use App\Enums\Sex;
    use App\Filament\App\Resources\Animals\AnimalResource;
    use App\Filament\App\Resources\Litters\LitterResource;

    $sexSymbol = fn ($animal): string => match ($animal->sex) {
        Sex::Female => '♀ ',
        Sex::Male => '♂ ',
        default => '',
    };
@endphp

<x-filament-panels::page>
    <style>
        .kith-pedigree a.kith-plain { color: inherit; text-decoration: none; }
        .kith-pedigree a.kith-plain:hover { color: var(--primary-600); text-decoration: underline; }
        .kith-pedigree [id^="animal-"] { scroll-margin-top: 6rem; }
        .kith-pedigree [id^="animal-"]:target { outline: 2px solid var(--primary-500); outline-offset: 2px; border-radius: .75rem; }
        .kith-pedigree .kith-muted { color: var(--gray-500); }
        .kith-pedigree table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .kith-pedigree th { text-align: left; font-weight: 500; padding: .5rem .75rem; color: var(--gray-500); }
        .kith-pedigree td { padding: .5rem .75rem; border-top: 1px solid var(--gray-200); }
        .dark .kith-pedigree td { border-color: var(--gray-800); }
        .kith-pedigree .kith-queen > .fi-section-header { align-items: center; background: #173b2b; border-radius: .75rem .75rem 0 0; }
        .kith-pedigree .kith-queen > .fi-section-header > * { align-self: center; }
        .kith-pedigree .kith-queen > .fi-section-header .fi-section-header-heading { color: #fff; font-size: 1.5rem; line-height: 2rem; }
        .kith-pedigree .kith-queen-edit { display: inline-block; padding: .375rem .875rem; border-radius: .5rem; background: #fff; color: #173b2b; font-size: .875rem; font-weight: 600; text-decoration: none; }
        .kith-pedigree .kith-queen-edit:hover { background: #e6eee8; }
        .kith-pedigree .kith-num { text-align: right; font-variant-numeric: tabular-nums; }
    </style>

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

        <div class="kith-pedigree" style="display: flex; flex-direction: column; gap: 2rem">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem">
                @if (count($this->speciesOptions) > 1)
                    <x-filament::input.wrapper style="max-width: 16rem">
                        <x-filament::input.select wire:model.live="speciesId">
                            @foreach ($this->speciesOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif

                <div class="kith-muted" style="display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .8125rem">
                    <x-filament::badge color="primary" size="sm">♀ {{ __('Name') }}</x-filament::badge>
                    {{ __('Breeding animal – click to jump to its litters') }}
                    <x-filament::badge color="gray" size="sm">♂ {{ __('Name') }}</x-filament::badge>
                    {{ __('Other offspring') }}
                </div>
            </div>

                    <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fill, minmax(min(22rem, 100%), 1fr))">
                        @foreach ($graph->lanes() as $lane)
                            @php($queen = $lane['queen'])
                            <x-filament::section
                                class="kith-queen"
                                :id="'animal-'.$queen->id"
                                :heading="$queen->name"
                            >
                                <x-slot name="afterHeader">
                                    <a class="kith-queen-edit" href="{{ AnimalResource::getUrl('edit', ['record' => $queen]) }}">
                                        {{ __('Edit') }}
                                    </a>
                                </x-slot>

                                <div style="display: flex; flex-direction: column; gap: 1.25rem">
                                    @foreach ($lane['litters'] as $litter)
                                        @php($father = $graph->animal($litter->father_id))
                                        @php($offspring = $graph->offspringOf($litter))
                                        <div style="display: flex; flex-direction: column; gap: .5rem; font-size: .875rem">
                                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: .375rem">
                                                <x-filament::link :href="LitterResource::getUrl('edit', ['record' => $litter])" weight="semibold">
                                                    {{ __('Litter :letter', ['letter' => $litter->letter ?? '#'.$litter->id]) }}
                                                </x-filament::link>
                                                <span class="kith-muted">·</span>
                                                <span class="kith-muted">{{ DatePrecision::describe($litter->born_on, $litter->born_on_precision) }}</span>
                                                <span class="kith-muted">·</span>
                                                <a class="kith-plain" href="#animal-{{ $father->id }}" title="{{ __('Sire') }}">♂ {{ $father->name }}</a>
                                                @if ($graph->isRelatedPairing($litter))
                                                    <x-filament::badge color="warning" size="sm">{{ __('Common ancestors') }}</x-filament::badge>
                                                @endif
                                            </div>

                                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: .375rem">
                                                @forelse ($offspring as $kitten)
                                                    @if ($graph->isBreedingAnimal($kitten))
                                                        <x-filament::badge tag="a" :href="'#animal-'.$kitten->id" color="primary" size="sm" :tooltip="__('Breeding animal – click to jump to its litters')">
                                                            {{ $sexSymbol($kitten) }}{{ $kitten->name }}
                                                        </x-filament::badge>
                                                    @else
                                                        <x-filament::badge tag="a" :href="AnimalResource::getUrl('edit', ['record' => $kitten])" color="gray" size="sm">
                                                            {{ $sexSymbol($kitten) }}{{ $kitten->name }}
                                                        </x-filament::badge>
                                                    @endif
                                                @empty
                                                    <span class="kith-muted">{{ __('No offspring recorded') }}</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-filament::section>
                        @endforeach
                    </div>

            <x-filament::section :heading="__('Sires')">
                <div style="overflow-x: auto; margin: -.5rem -.75rem">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Origin') }}</th>
                                <th class="kith-num">{{ __('Litters') }}</th>
                                <th class="kith-num">{{ __('Offspring') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($graph->sires() as $sire)
                                <tr id="animal-{{ $sire['animal']->id }}">
                                    <td style="white-space: nowrap">
                                        <x-filament::link :href="AnimalResource::getUrl('edit', ['record' => $sire['animal']])" size="sm">
                                            ♂ {{ $sire['animal']->name }}
                                        </x-filament::link>
                                    </td>
                                    <td class="kith-muted">{{ $graph->describeOrigin($sire['animal']) }}</td>
                                    <td class="kith-num">{{ $sire['litters'] }}</td>
                                    <td class="kith-num">{{ $sire['offspring'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
