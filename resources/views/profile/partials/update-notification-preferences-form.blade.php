@php $types = \App\Services\NotificationPreferences::forUser($user); @endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Notifications</h2>
        <p class="mt-1 text-sm text-gray-600">Choose which notifications appear under the bell. Required ones can't be turned off.</p>
    </header>

    <form method="post" action="{{ route('profile.notifications') }}" class="mt-6 space-y-4">
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

        <div class="flex items-center gap-4">
            <x-primary-button>Save</x-primary-button>

            @if (session('status') === 'notifications-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-gray-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
