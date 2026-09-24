<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="turbo-cache-control" content="no-cache">

        <title>{{ config('app.name', 'Project Management') }}</title>

        <!-- Apply the saved light/dark preference before first paint so pages never flash the wrong theme. -->
        <script>
            (function () {
                try {
                    document.documentElement.classList.toggle('dark', localStorage.getItem('bmpc-theme') === 'dark');
                } catch (e) {}
            })();
        </script>

        <link rel="icon" type="image/png" href="{{ route('branding.logo') }}">
        <link rel="apple-touch-icon" href="{{ route('branding.logo') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Icons & charts -->
        <script src="https://cdn.jsdelivr.net/npm/lucide@0.462.0/dist/umd/lucide.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" defer></script>

        <!-- Turbo Drive: intercepts internal link clicks/form submits and swaps
             the page via fetch instead of a full browser reload, so navigating
             between modules feels instant. Fragments already handled by our own
             ajaxRegions() pagination/filter system opt out via data-turbo="false"
             so the two mechanisms never fight over the same click. -->
        <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.13/dist/turbo.es2017-umd.js" defer></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="{ sidebarOpen: false }">
        <x-login-splash />
        <x-logout-splash />

        <div class="min-h-screen bg-slate-50">
            <!-- Mobile backdrop -->
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-smooth duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-smooth duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-[2px] lg:hidden"
                @click="sidebarOpen = false"
                style="display: none;"
            ></div>

            <!-- Sidebar: always fixed to the viewport, scrolls independently of the page -->
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col border-r border-slate-200 bg-white shadow-xl transition-transform duration-300 ease-smooth lg:translate-x-0 lg:shadow-none"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                @include('layouts.partials.sidebar')
            </aside>

            <!-- Main column, offset past the fixed sidebar on large screens -->
            <div class="flex min-h-screen min-w-0 flex-col lg:pl-64">
                @include('layouts.partials.topbar')

                @isset($header)
                    <header class="border-b border-slate-200 bg-white">
                        <div class="px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1 animate-fade-in">
                    @yield('content')
                    {{ $slot ?? '' }}
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('turbo:load', () => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>

        @stack('scripts')
    </body>
</html>
