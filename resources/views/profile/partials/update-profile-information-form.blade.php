<header class="flex items-start gap-3">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink"><i data-lucide="user-round" class="h-4 w-4"></i></span>
    <div>
        <h2 class="font-display text-base font-semibold text-slate-900">Account Details</h2>
        <p class="mt-0.5 text-sm text-slate-500">Your name as others see it, and the email you sign in with.</p>
    </div>
</header>

<form method="post" action="{{ route('profile.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
    @csrf
    @method('patch')

    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
        <p class="mt-1.5 text-xs text-slate-500">Changing it changes the email you sign in with.</p>
    </div>

    <div class="sm:col-span-2">
        <x-primary-button>Save Details</x-primary-button>
    </div>
</form>
