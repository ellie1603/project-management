<header class="flex items-start gap-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink"><i data-lucide="key-round" class="h-4 w-4"></i></span>
    <div>
        <h2 class="font-display text-base font-semibold text-slate-900">Change Password</h2>
        <p class="mt-0.5 text-sm text-slate-500">Use at least 8 characters. Forgot your current one? Ask your administrator to reset it.</p>
    </div>
</header>

<form method="post" action="{{ route('password.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
    @csrf
    @method('put')

    <div class="sm:col-span-2 sm:max-w-[calc(50%-0.5rem)]">
        <x-input-label for="update_password_current_password" value="Current password" />
        <x-password-input id="update_password_current_password" name="current_password" class="mt-1.5" autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password" value="New password" />
        <x-password-input id="update_password_password" name="password" class="mt-1.5" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" value="Confirm new password" />
        <x-password-input id="update_password_password_confirmation" name="password_confirmation" class="mt-1.5" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-primary-button>Change Password</x-primary-button>
    </div>
</form>
