@php
    $logoPath = base_path('resources/logo/logo.png');
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp

@if ($logoData)
    <img src="data:image/png;base64,{{ $logoData }}" alt="{{ config('app.name') }}">
@endif
