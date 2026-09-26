@php
    $user = $user ?? null;
    $contractor = $user->contractor ?? null;
@endphp

<div
    x-data="{
        role: '{{ old('role', $user->role ?? 'project_personnel') }}',
        positionType: '{{ old('position_type', $user->position_type ?? '') }}',
    }"
>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="user-name" class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
            <input id="user-name" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
        </div>

        <div>
            <label for="user-email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
            <input id="user-email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
        </div>

        <div>
            <label for="user-password" class="mb-1.5 block text-sm font-medium text-slate-700">Password {{ $user ? '(leave blank to keep current)' : '' }}</label>
            <x-password-input id="user-password" name="password" class="rounded-xl border-slate-200 bg-slate-50/60 focus:border-brand-400 focus:bg-white focus:ring-brand-100" :required="! $user" autocomplete="new-password" />
        </div>

        <div>
            <label for="user-role" class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
            <select id="user-role" name="role" x-model="role" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                <option value="admin">Admin / CEO</option>
                <option value="project_personnel">Project Personnel</option>
                <option value="finance_accounting">Finance & Accounting</option>
            </select>
        </div>

        <div x-show="role === 'project_personnel'" style="display: none;">
            <label for="user-position-type" class="mb-1.5 block text-sm font-medium text-slate-700">Position Type</label>
            <select id="user-position-type" name="position_type" x-model="positionType" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                <option value="">Select position</option>
                @foreach (\App\Models\ProjectAssignment::POSITION_TYPES as $position)
                    <option value="{{ $position }}">{{ $position }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div x-show="role === 'project_personnel' && positionType === 'Contractor'" style="display: none;" class="mt-6 border-t border-slate-100 pt-5">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            <i data-lucide="hard-hat" class="h-3.5 w-3.5"></i>
            Contractor / Provider Profile
        </div>
        <div class="mt-4">
            @include('users._contractor-fields')
        </div>
    </div>
</div>
