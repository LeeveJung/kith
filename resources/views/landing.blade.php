@php
    $signInUrl = route('filament.app.auth.login');

    $features = [
        [
            'title' => __('The pedigree at a glance'),
            'text' => __('One lane per dam, litters from top to bottom. Hover any animal and its ancestors and descendants light up.'),
            'icon' => 'M4 6h4v4H4zM16 4h4v4h-4zM16 16h4v4h-4zM8 8h4v8h4M12 12h4',
        ],
        [
            'title' => __('Litters and offspring'),
            'text' => __('Record matings with litter letter and date — even if you only know the month. Add the young right inside the litter.'),
            'icon' => 'M12 4v16M4 12h16M7 7l10 10M17 7 7 17',
        ],
        [
            'title' => __('Spot common ancestors'),
            'text' => __('kith marks litters whose parents share ancestors, so related matings never go unnoticed.'),
            'icon' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 5v5m0 3h.01',
        ],
        [
            'title' => __('Made for every species'),
            'text' => __('Cats, dogs, horses and beyond. Use the shared breeds and colors or add your own.'),
            'icon' => 'M5 12a7 7 0 1 0 14 0 7 7 0 0 0-14 0Zm7-7v14M5 12h14',
        ],
        [
            'title' => __('Profiles with photos'),
            'text' => __('Photos, call names, parents and litter mates — every animal gets a profile worth showing.'),
            'icon' => 'M4 7h3l2-3h6l2 3h3v12H4zM12 10a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
        ],
        [
            'title' => __('Your breeding, your space'),
            'text' => __('Every breeder gets a private space for their data, shared only with the people they work with — in English or German.'),
            'icon' => 'M6 10V7a6 6 0 1 1 12 0v3M5 10h14v10H5z',
        ],
    ];

    $steps = [
        [__('Add your foundation animals'), __('Start with the dams and sires your breeding builds on — including partners from other breeders.')],
        [__('Record your litters'), __('Pick mother and father, add the young and their colors. Parents are linked automatically.')],
        [__('Watch the family grow'), __('Every litter extends the tree. Daughters become dams and get a lane of their own.')],
    ];

    $lanes = [
        ['name' => 'Luna', 'color' => '#24221F', 'litters' => [['A', '#A9A9A4', ['#A9A9A4', '#A59A92', '#B4B8BE', '#C2B8B2']], ['B', '#E6D5B8', ['#A9A9A4', '#A59A92', '#A9A9A4']]]],
        ['name' => 'Freya', 'color' => '#C2B8B2', 'litters' => [['D', '#B86B3C', ['#E6D5B8', '#E6D5B8', '#A9A9A4', '#A9A9A4', '#B4B8BE']], ['E', '#B86B3C', ['#B86B3C', '#E6D5B8', '#E6D5B8', '#E6D5B8', '#A9A9A4']]]],
        ['name' => 'Ada', 'color' => '#E6D5B8', 'litters' => [['F', '#A9A9A4', ['#E6D5B8', '#E6D5B8', '#B86B3C', '#4A3326']], ['H', '#A9A9A4', ['#F4F1EC', '#A59A92', '#2B2926']]]],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('kith is pedigree software for breeders of cats, dogs, horses and more: litters, bloodlines and profiles in one living family tree.') }}">
        <meta name="theme-color" content="#173b2b">

        <title>kith — {{ __('Pedigree software for breeders') }}</title>

        <link rel="icon" type="image/webp" href="{{ asset('images/kith-logo.webp') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        {{-- Navigation --}}
        <header class="sticky top-0 z-30 border-b border-paper-deep/80 bg-paper/85 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="kith">
                    <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-7 w-auto">
                </a>

                <div class="flex items-center gap-2 text-sm sm:gap-6">
                    <a href="#features" class="hidden text-ink-soft transition hover:text-kith-900 md:inline">{{ __('Features') }}</a>
                    <a href="#how-it-works" class="hidden text-ink-soft transition hover:text-kith-900 md:inline">{{ __('How it works') }}</a>

                    <div class="flex items-center rounded-full border border-paper-deep bg-white/60 p-0.5 text-xs font-medium">
                        @foreach (config('app.available_locales') as $code => $language)
                            <a
                                href="{{ route('locale.switch', $code) }}"
                                @class([
                                    'rounded-full px-2.5 py-1 uppercase transition',
                                    'bg-kith-900 text-white' => app()->getLocale() === $code,
                                    'text-ink-soft hover:text-kith-900' => app()->getLocale() !== $code,
                                ])
                                title="{{ $language }}"
                                hreflang="{{ $code }}"
                            >{{ $code }}</a>
                        @endforeach
                    </div>

                    <a href="{{ $signInUrl }}" class="rounded-full bg-kith-900 px-4 py-2 font-medium text-white transition hover:bg-kith-800">
                        {{ __('Sign in') }}
                    </a>
                </div>
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute -top-40 -right-40 size-144 rounded-full bg-kith-100/70 blur-3xl"></div>

                <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pt-16 pb-20 sm:px-6 md:grid-cols-[1.15fr_1fr] md:pt-24 md:pb-28">
                    <div class="flex flex-col gap-7">
                        <span class="w-fit rounded-full border border-kith-200 bg-white/70 px-3 py-1 text-xs font-medium tracking-[.16em] text-kith-700 uppercase">
                            {{ __('Pedigree software for breeders') }}
                        </span>

                        <h1 class="font-serif text-5xl leading-[1.02] text-kith-950 sm:text-6xl lg:text-7xl">
                            {{ __('Every litter.') }}<br>
                            {{ __('Every line.') }}<br>
                            <em class="text-kith-600">{{ __('One family tree.') }}</em>
                        </h1>

                        <p class="max-w-xl text-lg leading-relaxed text-ink-soft text-pretty">
                            {{ __('kith keeps your animals, litters and bloodlines in one place — for cats, dogs, horses and every breed you love. See relatives at a glance, notice shared ancestors and grow a pedigree that tells your story.') }}
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ $signInUrl }}" class="rounded-full bg-kith-900 px-6 py-3 font-medium text-white shadow-lg shadow-kith-900/20 transition hover:-translate-y-0.5 hover:bg-kith-800">
                                {{ __('Open your pedigree') }}
                            </a>
                            <a href="#how-it-works" class="rounded-full px-6 py-3 font-medium text-kith-900 ring-1 ring-kith-200 transition hover:bg-white hover:ring-kith-300">
                                {{ __('See how it works') }}
                            </a>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-sm md:max-w-md">
                        <div class="absolute inset-6 rounded-full bg-white shadow-[0_40px_80px_-30px_rgba(23,59,43,.35)]"></div>
                        <img
                            src="{{ asset('images/kith-logo.webp') }}"
                            alt="{{ __('kith logo: a family tree connecting a cat, a dog and a horse') }}"
                            class="relative w-full p-10"
                            width="774"
                            height="907"
                        >
                    </div>
                </div>
            </section>

            {{-- Meaning of the name --}}
            <section class="border-y border-paper-deep bg-white/50">
                <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-4 py-14 text-center sm:px-6">
                    <p class="font-serif text-3xl text-kith-950 sm:text-4xl">
                        kith <span class="text-kith-500">[kɪθ]</span>
                    </p>
                    <p class="max-w-2xl text-lg leading-relaxed text-ink-soft text-pretty">
                        {{ __('From Old English cȳþþ — your own people, the ones you know and belong to. As in “kith and kin”: the family you choose to build.') }}
                    </p>
                </div>
            </section>

            {{-- Features --}}
            <section id="features" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 md:py-28">
                <div class="flex max-w-2xl flex-col gap-4">
                    <span class="text-xs font-medium tracking-[.16em] text-kith-600 uppercase">{{ __('Features') }}</span>
                    <h2 class="font-serif text-4xl leading-tight text-kith-950 sm:text-5xl">{{ __('Everything a breeding program remembers.') }}</h2>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as $feature)
                        <article class="group flex flex-col gap-4 rounded-2xl border border-paper-deep bg-white p-6 transition hover:-translate-y-1 hover:border-kith-200 hover:shadow-xl hover:shadow-kith-900/5">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-kith-50 text-kith-800 ring-1 ring-kith-100 transition group-hover:bg-kith-900 group-hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">
                                    <path d="{{ $feature['icon'] }}" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-semibold text-kith-950">{{ $feature['title'] }}</h3>
                            <p class="leading-relaxed text-ink-soft">{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Pedigree preview --}}
            <section class="bg-kith-950 text-white">
                <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 md:py-28 lg:grid-cols-[1fr_1.4fr]">
                    <div class="flex flex-col gap-5">
                        <span class="text-xs font-medium tracking-[.16em] text-kith-300 uppercase">{{ __('Pedigree view') }}</span>
                        <h2 class="font-serif text-4xl leading-tight sm:text-5xl">{{ __('Litters instead of loose ends.') }}</h2>
                        <p class="text-lg leading-relaxed text-kith-200 text-pretty">
                            {{ __('Each dam has her own lane, her litters follow in order. When a daughter has her first litter, she gets a lane of her own — and the family grows visibly with every generation.') }}
                        </p>
                        <div class="flex gap-5 text-sm text-kith-200">
                            <span class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-tan"></span>{{ __('Ancestors') }}</span>
                            <span class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-kith-300"></span>{{ __('Descendants') }}</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-3xl bg-paper p-5 text-ink shadow-2xl shadow-black/30 sm:p-7" aria-hidden="true">
                        <div class="mb-5 flex items-baseline justify-between gap-4">
                            <div>
                                <div class="text-[10px] tracking-[.2em] text-ink-soft uppercase">{{ __('Your breeding') }}</div>
                                <div class="font-serif text-3xl leading-none">{{ __('Pedigree') }}</div>
                            </div>
                        </div>
                        <div class="grid min-w-120 grid-cols-3 gap-4">
                            @foreach ($lanes as $lane)
                                <div class="flex flex-col gap-3 border-l border-paper-deep pl-3">
                                    <div class="flex items-center gap-3 rounded-2xl border border-paper-deep bg-white p-3">
                                        <span class="size-9 shrink-0 rounded-full ring-1 ring-black/10" style="background: {{ $lane['color'] }}"></span>
                                        <span class="font-serif text-xl leading-none">{{ $lane['name'] }}</span>
                                    </div>
                                    @foreach ($lane['litters'] as [$letter, $sireColor, $kittens])
                                        <div class="flex flex-col gap-2 rounded-2xl border border-paper-deep bg-white p-3 transition hover:border-tan hover:shadow-md">
                                            <div class="flex items-center justify-between">
                                                <span class="font-serif text-lg leading-none">{{ __('Litter :letter', ['letter' => $letter]) }}</span>
                                                <span class="size-4 rounded-full ring-1 ring-black/10" style="background: {{ $sireColor }}"></span>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($kittens as $kittenColor)
                                                    <span class="size-5 rounded-full ring-2 ring-white transition hover:ring-kith-300" style="background: {{ $kittenColor }}"></span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="how-it-works" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 md:py-28">
                <div class="flex max-w-2xl flex-col gap-4">
                    <span class="text-xs font-medium tracking-[.16em] text-kith-600 uppercase">{{ __('How it works') }}</span>
                    <h2 class="font-serif text-4xl leading-tight text-kith-950 sm:text-5xl">{{ __('Three steps to a living pedigree.') }}</h2>
                </div>

                <ol class="mt-12 grid gap-8 md:grid-cols-3">
                    @foreach ($steps as $index => [$title, $text])
                        <li class="flex flex-col gap-3 border-t-2 border-kith-900 pt-6">
                            <span class="font-serif text-5xl leading-none text-kith-300">{{ $index + 1 }}</span>
                            <h3 class="text-lg font-semibold text-kith-950">{{ $title }}</h3>
                            <p class="leading-relaxed text-ink-soft">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Call to action --}}
            <section class="px-4 pb-20 sm:px-6 md:pb-28">
                <div class="relative mx-auto max-w-6xl overflow-hidden rounded-4xl bg-kith-900 px-6 py-16 text-center text-white sm:px-12">
                    <div class="pointer-events-none absolute -bottom-24 -left-24 size-80 rounded-full bg-kith-700/50 blur-3xl"></div>
                    <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-kith-600/40 blur-3xl"></div>

                    <div class="relative flex flex-col items-center gap-6">
                        <h2 class="max-w-2xl font-serif text-4xl leading-tight sm:text-5xl">{{ __('Give your bloodlines a home.') }}</h2>
                        <p class="max-w-xl text-lg text-kith-200">{{ __('Start with a single litter. kith grows with every generation you add.') }}</p>
                        <a href="{{ $signInUrl }}" class="rounded-full bg-white px-7 py-3 font-medium text-kith-900 transition hover:-translate-y-0.5 hover:bg-kith-50">
                            {{ __('Open your pedigree') }}
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-paper-deep">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-ink-soft sm:flex-row sm:px-6">
                <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-5 w-auto opacity-80">
                <span>© {{ now()->year }} kith · {{ __('Pedigree software for breeders') }}</span>
            </div>
        </footer>
    </body>
</html>
