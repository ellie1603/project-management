@props(['alt' => config('app.name', 'BMPC')])

<img
    src="{{ route('branding.logo') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'object-contain']) }}
>
