@php
    $operator = config('kith.operator');
    $addressLines = array_filter([$operator['name'], $operator['street'], $operator['city'], $operator['country']]);
@endphp

@if (blank($operator['name']))
    <p class="rounded-xl border border-tan bg-white px-4 py-3 text-ink">{{ __('The operator details have not been configured yet.') }}</p>
@else
    <address class="not-italic">
        {!! collect($addressLines)->map(fn (string $line) => e($line))->implode('<br>') !!}
    </address>
@endif
