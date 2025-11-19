@props(['messages'])

@if ($messages)
    <p {{ $attributes->merge(['class' => 'text-center text-sm text-danger']) }}> {{ implode(' | ', $messages) }}</p>
@endif
