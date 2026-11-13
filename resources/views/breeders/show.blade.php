@php
    $labels = [
        'female' => __('Female'),
        'male' => __('Male'),
        'unknownSex' => __('Unknown'),
        'parents' => __('Parents'),
        'mother' => __('Mother'),
        'father' => __('Father'),
        'littermates' => __('Litter mates'),
        'ownLitters' => __('Own litters'),
        'ancestry' => __('Ancestry'),
        'unknown' => __('Unknown'),
        'born' => __('Born'),
        'color' => __('Color'),
        'breed' => __('Breed'),
        'origin' => __('Origin'),
        'litter' => __('Litter :letter'),
        'with' => __('with :name'),
        'showInTree' => __('Show in pedigree'),
        'today' => __('Today'),
        'close' => __('Close'),
        'noResults' => __('No animal found'),
        'play' => __('Play'),
        'pause' => __('Pause'),
        'photo' => __('Photo of :name'),
        'generations' => [__('Parents'), __('Grandparents'), __('Great-grandparents')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#173b2b">
        <meta name="robots" content="noindex">

        <title>{{ $breeder->name }} — kith</title>

        <link rel="icon" type="image/webp" href="{{ asset('images/kith-logo.webp') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pedigree/index.js'])
        <x-matomo />
    </head>
    <body class="flex min-h-dvh flex-col bg-paper font-sans text-ink antialiased">
        <header class="border-b border-paper-deep bg-paper">
            <div class="flex flex-col gap-1 px-4 pt-5 pb-4 sm:gap-4 sm:px-8 sm:pt-7 sm:pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-1">
                    <h1 class="font-serif text-3xl leading-none text-kith-900 sm:text-5xl">{{ $breeder->name }}</h1>
                    @if ($stats !== null)
                        <p class="text-ink-soft">
                            {{ implode(' · ', array_filter([
                                implode(', ', $stats['breeds']) ?: ($speciesOptions[$speciesId] ?? null),
                                $stats['since'] !== null ? __('Breeding since :year', ['year' => $stats['since']]) : null,
                            ])) }}
                        </p>
                    @endif
                </div>

                @if ($stats !== null)
                    <p class="text-sm text-ink-soft sm:hidden">{{ implode(' · ', [$stats['litters'].' '.__('Litters'), $stats['dams'].' '.__('Dams'), $stats['animals'].' '.__('Animals')]) }}</p>
                    <dl class="hidden gap-6 text-sm sm:flex">
                        @foreach ([[__('Litters'), $stats['litters']], [__('Dams'), $stats['dams']], [__('Animals'), $stats['animals']]] as [$label, $value])
                            <div class="flex flex-col">
                                <dt class="text-ink-soft">{{ $label }}</dt>
                                <dd class="font-serif text-3xl leading-none text-kith-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            @if (count($speciesOptions) > 1)
                <nav class="flex gap-1 px-4 pb-3 sm:px-8" aria-label="{{ __('Species') }}">
                    @foreach ($speciesOptions as $id => $name)
                        <a
                            href="{{ route('breeders.show', ['breeder' => $breeder, 'species' => $id]) }}"
                            @class([
                                'rounded-full px-3 py-1 text-sm transition',
                                'bg-kith-900 text-white' => $id === $speciesId,
                                'text-ink-soft hover:bg-white hover:text-kith-900' => $id !== $speciesId,
                            ])
                        >{{ $name }}</a>
                    @endforeach
                </nav>
            @endif
        </header>

        @if ($pedigree === null)
            <main class="flex flex-1 items-center justify-center px-4 py-16 text-center">
                <p class="max-w-md text-ink-soft">{{ __('This breeder has not recorded any litters yet.') }}</p>
            </main>
        @else
            <main class="relative flex min-h-[32rem] flex-1 flex-col" data-pedigree>
                <script type="application/json" data-pedigree-data>@json(['pedigree' => $pedigree, 'labels' => $labels])</script>

                {{-- Toolbar --}}
                <div class="z-20 flex flex-wrap items-center gap-2 border-b border-paper-deep bg-paper/90 px-4 py-2 backdrop-blur sm:px-8">
                    <div class="relative w-full sm:w-64">
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-soft" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                        <input
                            type="search"
                            data-search
                            placeholder="{{ __('Find an animal…') }}"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="pedigree-search-results"
                            class="w-full rounded-full border border-paper-deep bg-white py-1.5 pr-3 pl-9 text-sm outline-none transition focus:border-kith-300 focus:ring-2 focus:ring-kith-100"
                        >
                        <ul id="pedigree-search-results" data-search-results role="listbox" class="absolute top-full right-0 left-0 z-30 mt-1 hidden max-h-72 overflow-auto rounded-xl border border-paper-deep bg-white p-1 text-sm shadow-xl shadow-kith-900/10"></ul>
                    </div>

                    <div class="flex items-center gap-1" role="group" aria-label="{{ __('Filter') }}">
                        <button type="button" data-filter-sex="female" aria-pressed="false" class="pedigree-chip" aria-label="{{ __('Female') }}">♀<span class="hidden sm:inline"> {{ __('Female') }}</span></button>
                        <button type="button" data-filter-sex="male" aria-pressed="false" class="pedigree-chip" aria-label="{{ __('Male') }}">♂<span class="hidden sm:inline"> {{ __('Male') }}</span></button>
                        <select data-filter-color class="pedigree-chip cursor-pointer appearance-none pr-3" aria-label="{{ __('Color') }}">
                            <option value="">{{ __('All colors') }}</option>
                        </select>
                    </div>

                    <div class="ml-auto flex items-center gap-1">
                        <button type="button" data-zoom="out" class="pedigree-icon-btn max-sm:hidden" aria-label="{{ __('Zoom out') }}">−</button>
                        <button type="button" data-zoom="fit" class="pedigree-icon-btn px-3 text-xs" aria-label="{{ __('Show all') }}">{{ __('Show all') }}</button>
                        <button type="button" data-zoom="in" class="pedigree-icon-btn max-sm:hidden" aria-label="{{ __('Zoom in') }}">+</button>
                    </div>
                </div>

                {{-- Stage --}}
                <div class="relative flex-1 overflow-hidden" data-stage>
                    <svg class="absolute inset-0 size-full touch-none select-none" data-canvas role="img" aria-label="{{ __('Pedigree of :name', ['name' => $breeder->name]) }}"></svg>
                    <div class="pointer-events-none absolute inset-0" data-overlay></div>

                    <div data-tooltip class="pointer-events-none absolute z-20 hidden rounded-lg bg-kith-950 px-2.5 py-1.5 text-xs text-white shadow-lg"></div>

                    {{-- Time scrubber --}}
                    <div class="absolute right-4 bottom-4 left-4 z-10 mx-auto flex max-w-xl items-center gap-3 rounded-full border border-paper-deep bg-white/90 py-1.5 pr-4 pl-1.5 shadow-lg shadow-kith-900/5 backdrop-blur">
                        <button type="button" data-play class="flex size-8 shrink-0 items-center justify-center rounded-full bg-kith-900 text-white transition hover:bg-kith-800" aria-label="{{ __('Play') }}">
                            <svg data-play-icon class="size-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M4 2.5v11a.5.5 0 0 0 .76.43l9-5.5a.5.5 0 0 0 0-.86l-9-5.5A.5.5 0 0 0 4 2.5Z"/></svg>
                            <svg data-pause-icon class="hidden size-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M4 2h3v12H4zM9 2h3v12H9z"/></svg>
                        </button>
                        <input type="range" data-scrubber min="0" max="1000" value="1000" step="1" class="pedigree-range flex-1" aria-label="{{ __('Time') }}">
                        <span data-scrubber-label class="w-10 text-right font-serif text-lg leading-none text-kith-900 tabular-nums"></span>
                    </div>
                </div>

                {{-- Profile panel --}}
                <aside
                    data-panel
                    aria-hidden="true"
                    class="fixed inset-x-0 bottom-0 z-40 flex max-h-[80dvh] translate-y-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl shadow-kith-950/20 transition-transform duration-300 ease-out md:inset-x-auto md:top-0 md:right-0 md:max-h-none md:h-dvh md:w-[26rem] md:translate-x-full md:translate-y-0 md:rounded-none md:rounded-l-3xl"
                >
                    <button type="button" data-panel-close class="absolute top-3 right-3 z-10 flex size-9 items-center justify-center rounded-full bg-white/90 text-ink shadow transition hover:bg-kith-50" aria-label="{{ __('Close') }}">
                        <svg class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg>
                    </button>
                    <div data-panel-body class="flex-1 overflow-y-auto overscroll-contain"></div>
                </aside>
            </main>
        @endif

        <footer class="border-t border-paper-deep px-4 py-3 text-xs text-ink-soft sm:px-8">
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                <p class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                    <span>powered by</span>
                    <a href="{{ route('home') }}" aria-label="kith" class="opacity-80 transition hover:opacity-100">
                        <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-3.5 w-auto">
                    </a>
                    <span aria-hidden="true">–</span>
                    <a href="{{ config('kith.donation_url') }}" target="_blank" rel="noopener" class="underline decoration-paper-deep underline-offset-2 transition hover:text-kith-900 hover:decoration-kith-300">
                        {{ __('If you like this app, consider buying me a coffee') }}
                    </a>
                </p>

                <x-legal-links class="sm:ml-auto" />

                <div class="flex items-center gap-1 font-medium">
                    @foreach (config('app.available_locales') as $code => $language)
                        <a
                            href="{{ route('locale.switch', $code) }}"
                            @class([
                                'rounded-full px-2 py-0.5 uppercase transition',
                                'bg-kith-900 text-white' => app()->getLocale() === $code,
                                'hover:text-kith-900' => app()->getLocale() !== $code,
                            ])
                            title="{{ $language }}"
                            hreflang="{{ $code }}"
                        >{{ $code }}</a>
                    @endforeach
                </div>
            </div>
        </footer>
    </body>
</html>
