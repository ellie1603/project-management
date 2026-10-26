@php $types = \App\Services\NotificationPreferences::forUser($user); @endphp

<div>
    <header class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink"><i data-lucide="bell" class="h-4 w-4"></i></span>
        <div>
            <h2 class="font-display text-base font-semibold text-slate-900">Notifications</h2>
            <p class="mt-0.5 text-sm text-slate-500">Choose which notifications appear under the bell. Required ones can't be turned off.</p>
        </div>
    </header>

    <form method="post" action="{{ route('profile.notifications') }}" class="mt-5 space-y-4">
        @csrf
        @method('patch')

        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
            @foreach ($types as $key => $type)
                <label class="flex items-start gap-3 px-4 py-3 {{ $type['required'] ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-slate-50' }}">
                    <input
                        type="checkbox"
                        name="enabled[]"
                        value="{{ $key }}"
                        @checked($type['required'] || \App\Services\NotificationPreferences::wants($user, $type['classes'][0]))
                        @disabled($type['required'])
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    >
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-slate-900">
                            {{ $type['label'] }}
                            @if ($type['required'])
                                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-500">Required</span>
                            @endif
                        </span>
                        <span class="block text-xs text-slate-500">{{ $type['description'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <x-primary-button>Save Notifications</x-primary-button>
    </form>
</div>
