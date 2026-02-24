@props(['size' => 'md'])

@php
    $classes = match ($size) {
        'sm' => 'size-8',
        'md' => 'size-10',
        'lg' => 'size-[72px]',
    };
@endphp

<img
    src="{{ asset('images/coach-marcus.svg') }}"
    alt="{{ __('Coach Marcus') }}"
    {{ $attributes->class([$classes, 'rounded-full bg-lime-600/10']) }}
/>
