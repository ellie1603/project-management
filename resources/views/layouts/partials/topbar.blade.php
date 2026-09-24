<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/75 px-4 shadow-[0_1px_0_rgba(15,23,42,0.03)] backdrop-blur-md sm:px-6 lg:px-8">
    <button
        type="button"
        class="rounded-lg p-1.5 text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900 lg:hidden"
        @click="sidebarOpen = ! sidebarOpen"
        aria-label="Toggle navigation"
    >
        <i data-lucide="menu" class="h-6 w-6"></i>
    </button>

    <div class="hidden min-w-0 flex-1 flex-col justify-center sm:flex" x-data="{ now: '' }" x-init="
        const format = () => now = new Date().toLocaleString('en-US', { weekday: 'long', hour: 'numeric', minute: '2-digit' });
        format();
        setInterval(format, 30000);
    ">
        <p class="truncate text-sm font-medium text-slate-700">
            {{ (fn () => match (true) {
                now()->hour < 12 => 'Good morning',
                now()->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            })() }}, {{ explode(' ', Auth::user()->name)[0] }}
        </p>
        <p class="truncate text-xs text-slate-400" x-text="now"></p>
    </div>

    <div class="ml-auto flex items-center gap-1.5">
        <button
            type="button"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            @click="dark = window.bmpcSetTheme(! dark)"
            class="relative flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900"
            :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
            :title="dark ? 'Light mode' : 'Dark mode'"
        >
            <span x-show="! dark" class="flex"><i data-lucide="moon" class="h-5 w-5"></i></span>
            <span x-show="dark" class="flex" style="display: none;"><i data-lucide="sun" class="h-5 w-5"></i></span>
        </button>

        <div
            class="relative"
            x-data="{
                open: false,
                loaded: false,
                html: '',
                toggle() {
                    this.open = ! this.open;
                    if (this.open && ! this.loaded) {
                        this.load();
                    }
                },
                load() {
                    fetch('{{ route('notifications.recent') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then((response) => response.text())
                        .then((html) => {
                            this.html = html;
                            this.loaded = true;
                            this.$nextTick(() => window.lucide && window.lucide.createIcons());
                        });
                },
                markAllRead() {
                    fetch('{{ route('notifications.read-all') }}', {
                        method: 'PATCH',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    }).then(() => { this.loaded = false; this.load(); document.getElementById('notification-badge')?.remove(); });
                },
            }"
            @click.outside="open = false"
        >
            <button type="button" @click="toggle()" class="relative rounded-lg p-2 text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900" aria-label="Notifications">
                <i data-lucide="bell" class="h-5 w-5"></i>
                @if (($unread = Auth::user()->unreadNotifications()->count()) > 0)
                    <span id="notification-badge" class="absolute right-1 top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white shadow-sm">
                        {{ $unread > 9 ? '9+' : $unread }}
                    </span>
                @endif
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-elegant duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute right-0 z-50 mt-2 w-80 rounded-2xl bg-white shadow-premium ring-1 ring-slate-900/5"
                style="display: none;"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">Notifications</p>
                    <button type="button" @click="markAllRead()" class="text-xs font-medium text-brand-600 transition-colors duration-150 hover:text-brand-700">Mark all as read</button>
                </div>
                <div class="max-h-96 overflow-y-auto" x-html="html"></div>
                <template x-if="!loaded">
                    <div class="space-y-4 px-4 py-4">
                        <template x-for="i in 3" :key="i">
                            <div class="flex items-start gap-3">
                                <div class="skeleton h-8 w-8 shrink-0 animate-shimmer rounded-full"></div>
                                <div class="flex-1 space-y-1.5 py-0.5">
                                    <div class="skeleton h-2.5 w-3/4 animate-shimmer rounded-full"></div>
                                    <div class="skeleton h-2.5 w-1/2 animate-shimmer rounded-full"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></div>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2.5 rounded-lg py-1.5 pl-1.5 pr-2 text-slate-600 transition-colors duration-150 hover:bg-slate-100">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="hidden text-left leading-tight md:block">
                        <p class="max-w-[8rem] truncate text-xs font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-slate-400">{{ Str::headline(Auth::user()->role) }}</p>
                    </div>
                    <i data-lucide="chevron-down" class="hidden h-3.5 w-3.5 shrink-0 text-slate-400 md:block"></i>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                <form id="logout-form-topbar" method="POST" action="{{ route('logout') }}" data-turbo="false">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('bmpc-logout', { detail: { formId: 'logout-form-topbar' } }));">
                        Log Out
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
