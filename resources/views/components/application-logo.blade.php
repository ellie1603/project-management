@props(['alt' => config('app.name', 'Project Management')])

<img
    src="{{ route('branding.logo') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'object-contain']) }}
>
