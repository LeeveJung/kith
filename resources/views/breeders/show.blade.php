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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        <main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center gap-6 px-4 py-16 text-center sm:px-6">
            <a href="{{ route('home') }}" aria-label="kith">
                <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-7 w-auto">
            </a>

            <h1 class="font-serif text-5xl text-kith-900 sm:text-6xl">{{ $breeder->name }}</h1>

            <p class="max-w-md text-ink-soft">
                {{ __('The public pedigree of this breeder is coming soon.') }}
            </p>
        </main>
    </body>
</html>
