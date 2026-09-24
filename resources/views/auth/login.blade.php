<x-guest-layout>
    <x-flash-toast />

    <div class="mb-8">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900">Welcome back</h1>
        <p class="mt-1.5 text-sm text-slate-500">Sign in to continue to your workspace.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative mt-1.5">
                <i data-lucide="mail" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <x-text-input id="email" class="w-full pl-10" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@bmpc.coop" />
            </div>
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="mt-1.5">
                <x-password-input id="password" name="password" required autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <div class="mt-2 flex justify-end">
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-slate-500 transition-colors duration-150 hover:text-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
        </div>

        <label for="remember_me" class="flex select-none items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-600 shadow-sm transition-colors duration-150 focus:ring-brand-500" name="remember">
            <span class="text-sm text-slate-600">{{ __('Keep me signed in') }}</span>
        </label>

        <x-primary-button class="w-full justify-center py-2.5">
            {{ __('Log in') }}
            <i data-lucide="arrow-right" class="h-4 w-4"></i>
        </x-primary-button>
    </form>
</x-guest-layout>
