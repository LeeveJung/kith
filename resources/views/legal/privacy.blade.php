{{-- The privacy policy is legal prose, so it is kept as one text per language instead of single translation keys. --}}
<x-layouts.legal :title="__('Privacy policy')">
    @include(app()->getLocale() === 'de' ? 'legal.privacy.de' : 'legal.privacy.en')
</x-layouts.legal>
