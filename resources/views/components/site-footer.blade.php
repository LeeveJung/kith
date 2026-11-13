<footer class="border-t border-paper-deep">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-ink-soft sm:flex-row sm:px-6">
        <div class="flex flex-col items-center gap-2 sm:flex-row sm:gap-4">
            <img src="{{ asset('images/kith-logo-text.webp') }}" alt="kith" class="h-5 w-auto opacity-80">
            <span>© {{ now()->year }} kith · {{ __('Pedigree software for breeders') }}</span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
            <x-legal-links />
            <a href="{{ config('kith.donation_url') }}" target="_blank" rel="noopener" class="font-medium text-kith-700 transition hover:text-kith-900">♥ {{ __('Donate') }}</a>
        </div>
    </div>
</footer>
