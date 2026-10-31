<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Project Management') }}</title>

        <link rel="icon" type="image/png" href="{{ route('branding.logo') }}">
        @include('layouts.partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <script src="https://cdn.jsdelivr.net/npm/lucide@0.462.0/dist/umd/lucide.js" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-50 font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen">
            <!-- Brand panel — hidden on small screens, this is what carries the "enterprise" first impression -->
            <div class="relative hidden w-[44%] shrink-0 overflow-hidden border-r border-brand-100 bg-brand-50 lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                <a href="/" class="relative z-10 flex w-fit animate-rise-in items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                    <x-application-logo class="h-14 w-14 rounded-full bg-white p-2 shadow-soft" />
                    <span class="font-display text-xl font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Project Management') }}</span>
                </a>

                <div class="relative z-10 max-w-md animate-rise-in" style="animation-delay: 40ms">
                    <h1 class="font-display text-[2rem] font-semibold leading-[1.15] tracking-tight text-slate-900">
                        Every approved project,<br>tracked with clarity.
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-slate-500">
                        Budgets, timelines, documents, and progress for Barbaza Multi-Purpose Cooperative — all in one place, from registration to completion.
                    </p>

                    <ul class="mt-9 space-y-4">
                        @foreach ([
                            ['icon' => 'wallet', 'text' => 'Real-time budget monitoring and utilization'],
                            ['icon' => 'calendar-clock', 'text' => 'Automatic timeline and delay tracking'],
                            ['icon' => 'shield-check', 'text' => 'Role-based access with a full audit trail'],
                        ] as $i => $feature)
                            <li class="flex items-center gap-3 animate-rise-in" style="animation-delay: {{ intdiv(120 + $i * 60, 2) }}ms">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg chip-ink">
                                    <i data-lucide="{{ $feature['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="text-sm text-slate-600">{{ $feature['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative z-10 animate-rise-in text-xs text-slate-400" style="animation-delay: 150ms">
                    &copy; {{ now()->year }} {{ config('app.name', 'Project Management') }}. All rights reserved.
                </p>
            </div>

            <!-- Form panel -->
            <div class="auth-panel-in flex w-full flex-1 flex-col items-center justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-24">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex animate-rise-in flex-col items-center gap-2 text-center lg:hidden">
                        <a href="/" class="rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                            <x-application-logo class="h-16 w-16" />
                        </a>
                        <p class="font-display text-base font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Project Management') }}</p>
                    </div>

                    <div class="animate-rise-in" style="animation-delay: 30ms">
                        {{ $slot }}
                    </div>

                    <p class="mt-10 animate-rise-in text-center text-xs text-slate-400 lg:hidden" style="animation-delay: 70ms">
                        &copy; {{ now()->year }} {{ config('app.name', 'Project Management') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
