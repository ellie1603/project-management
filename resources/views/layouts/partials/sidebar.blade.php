@php
    $user = Auth::user();

    $navLink = function (string $routeName, string $label, string $icon, ?string $activePattern = null) use ($user) {
        $pattern = $activePattern ?? $routeName.'*';
        $isActive = request()->routeIs($pattern);

        return [
            'href' => \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : '#',
            'label' => $label,
            'icon' => $icon,
            'active' => $isActive,
        ];
    };

    if ($user->isAdmin()) {
        $sections = [
            [
                'items' => [
                    $navLink('dashboard', 'Dashboard', 'layout-dashboard'),
                ],
            ],
            [
                'items' => [
                    $navLink('projects.index', 'Projects', 'folder'),
                    $navLink('budget.overview', 'Budget & Finance', 'wallet', 'budget.*'),
                    $navLink('documents.index', 'Documents', 'file-text'),
                    $navLink('reports.index', 'Reports', 'file-text', 'reports.*'),
                ],
            ],
            [
                'title' => 'Administration',
                'items' => [
                    $navLink('users.index', 'Users', 'users'),
                    $navLink('audit-logs.index', 'Audit Logs', 'clock'),
                    $navLink('settings.edit', 'Settings', 'settings'),
                ],
            ],
        ];
    } elseif ($user->isFinance()) {
        $sections = [
            [
                'items' => [
                    $navLink('dashboard', 'Dashboard', 'layout-dashboard'),
                    $navLink('projects.index', 'Projects', 'folder'),
                ],
            ],
            [
                'title' => 'Finance',
                'items' => [
                    $navLink('budget.overview', 'Budget Monitoring', 'wallet'),
                    $navLink('budget.requests', 'Budget Requests', 'hand-coins'),
                    $navLink('budget.expenses', 'Expenses', 'calculator'),
                ],
            ],
            [
                'items' => [
                    $navLink('reports.index', 'Reports', 'file-text', 'reports.*'),
                ],
            ],
        ];
    } else {
        $sections = [
            [
                'items' => [
                    $navLink('dashboard', 'Dashboard', 'layout-dashboard'),
                    $navLink('projects.index', 'My Projects', 'folder'),
                ],
            ],
        ];
    }
@endphp

<div class="flex h-full flex-col bg-white">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
            <x-application-logo class="h-9 w-9" />
            <span class="font-display text-base font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Project Management') }}</span>
        </a>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
        @foreach ($sections as $section)
            <div>
                @isset($section['title'])
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $section['title'] }}</p>
                @endisset
                <div class="mt-1.5 space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <a
                            href="{{ $item['href'] }}"
                            class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13.5px] font-medium transition-all duration-150 ease-smooth focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 {{ $item['active'] ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        >
                            <i data-lucide="{{ $item['icon'] }}" class="h-[17px] w-[17px] shrink-0 transition-colors duration-150 {{ $item['active'] ? 'text-white' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full chip-ink text-sm font-semibold text-white shadow-soft">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ ucwords(str_replace('_', ' ', $user->role)) }}</p>
            </div>
        </div>
        <form id="logout-form-sidebar" method="POST" action="{{ route('logout') }}" data-turbo="false" class="mt-2">
            @csrf
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('bmpc-logout', { detail: { formId: 'logout-form-sidebar' } }))"
                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-150 hover:bg-red-50 hover:text-red-700"
            >
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Log Out
            </button>
        </form>
    </div>
</div>
