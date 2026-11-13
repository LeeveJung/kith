@php($operator = config('kith.operator'))

<x-layouts.legal :title="__('Imprint')">
    <h2>{{ __('Information according to § 5 DDG') }}</h2>
    <x-operator-address />

    @if (filled($operator['email']) || filled($operator['phone']))
        <h2>{{ __('Contact') }}</h2>
        <p>
            @if (filled($operator['email']))
                {{ __('Email') }}: <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a><br>
            @endif
            @if (filled($operator['phone']))
                {{ __('Phone') }}: {{ $operator['phone'] }}
            @endif
        </p>
    @endif

    <h2>{{ __('A private, non-commercial project') }}</h2>
    <p>{{ __('kith is a free hobby project. There is no subscription and no paid tier; donations are voluntary and do not unlock any features.') }}</p>

    <h2>{{ __('Consumer dispute resolution') }}</h2>
    <p>{{ __('We are neither willing nor obliged to take part in dispute resolution proceedings before a consumer arbitration board.') }}</p>

    <h2>{{ __('Content of breeder pages') }}</h2>
    <p>{{ __('The public breeder pages show information that the respective breeders enter themselves. The breeders are responsible for this content. If you notice anything unlawful, please let us know and we will remove it promptly.') }}</p>
</x-layouts.legal>
