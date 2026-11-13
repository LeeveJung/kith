{{-- Imprint, privacy policy and cookie settings; data-privacy-url is read by resources/js/consent.js. --}}
<nav
    aria-label="{{ __('Legal') }}"
    data-privacy-url="{{ route('privacy') }}"
    {{ $attributes->class('flex flex-wrap items-center gap-x-4 gap-y-1') }}
>
    <a href="{{ route('imprint') }}" class="transition hover:text-kith-900">{{ __('Imprint') }}</a>
    <a href="{{ route('privacy') }}" class="transition hover:text-kith-900">{{ __('Privacy policy') }}</a>
    <button type="button" data-consent-settings class="cursor-pointer transition hover:text-kith-900">{{ __('Cookie settings') }}</button>
</nav>
