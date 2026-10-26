<x-guest-layout>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900">Set your new password</h1>
        <p class="mt-1.5 text-sm text-slate-500">
            Your administrator reset your password, {{ auth()->user()->name }}. Choose a new one to continue. You'll use it from now on.
        </p>
    </div>

    <form method="POST" action="{{ route('password.setup.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" value="New password" />
            <div class="mt-1.5">
                <x-password-input id="password" name="password" required autofocus autocomplete="new-password" />
            </div>
            <p class="mt-1.5 text-xs text-slate-500">At least 8 characters.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm new password" />
            <div class="mt-1.5">
                <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
            </div>
        </div>

        <x-primary-button class="w-full justify-center">Save and continue</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs font-medium text-slate-500 hover:text-slate-800">Sign out</button>
    </form>
</x-guest-layout>
