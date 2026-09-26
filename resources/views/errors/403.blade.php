<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied | {{ config('app.name', 'BMPC') }}</title>
    <link rel="icon" type="image/png" href="{{ route('branding.logo') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-6 py-12">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">403</p>
            <h1 class="mt-3 text-2xl font-semibold">Access denied</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">You do not have permission to access this resource.</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-6 inline-flex rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Return to BMPC</a>
        </section>
    </main>
</body>
</html>
