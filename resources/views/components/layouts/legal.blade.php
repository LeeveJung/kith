@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="theme-color" content="#173b2b">

        <title>{{ $title }} — kith</title>

        <link rel="icon" type="image/webp" href="{{ asset('images/kith-logo.webp') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-matomo />
    </head>
    <body class="flex min-h-screen flex-col bg-paper font-sans text-ink antialiased">
        <header class="border-b border-paper-deep">
            <nav class="mx-auto flex max-w-3xl items-center justify-between gap-6 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" aria-label="kith">
                    <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-7 w-auto">
                </a>

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
            </nav>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-14 sm:px-6 md:py-20">
            <h1 class="font-serif text-5xl leading-tight text-kith-950">{{ $title }}</h1>

            <div class="legal-text mt-8">
                {{ $slot }}
            </div>
        </main>

        <x-site-footer />
    </body>
</html>
