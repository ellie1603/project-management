{{--
    Shared error page. Kept free of database and session lookups so it still
    renders when those are what failed (500, 503).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name', 'Project Management') }}</title>
    <link rel="icon" type="image/png" href="{{ url('/branding/logo') }}">
    <script>
        try { document.documentElement.classList.toggle('dark', localStorage.getItem('bmpc-theme') === 'dark'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-6 py-12">
        <section class="w-full rounded-2xl glass p-8 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">@yield('code')</p>
            <h1 class="mt-3 text-2xl font-semibold">@yield('title')</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">@yield('message')</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                @yield('actions')
            </div>
        </section>
    </main>
</body>
</html>
