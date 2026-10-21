@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-flash-toast />
    <x-flash-toast type="error" />

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">System</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Roles &amp; Permissions</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
            Choose what each role can do. Changes apply to everyone in that role as soon as you save.
            Your System Administrator account always has full access.
        </p>
    </div>

    @error('permissions')
        <div class="mb-4 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>{{ $message }}</p>
        </div>
    @enderror

    <form method="POST" action="{{ route('system.permissions.update') }}" x-data="{ dirty: false }" @change="dirty = true">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            @foreach ($catalog as $group => $permissions)
                <section class="overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-slate-900/5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="px-5 py-3 text-left text-sm font-semibold text-slate-900">{{ $group }}</th>
                                    @foreach ($roles as $roleLabel)
                                        <th class="w-36 px-3 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $roleLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($permissions as $key => $permission)
                                    <tr>
                                        <td class="px-5 py-3.5">
                                            <p class="text-sm font-medium text-slate-900">{{ $permission['label'] }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $permission['description'] }}</p>
                                        </td>
                                        @foreach ($roles as $role => $roleLabel)
                                            <td class="px-3 py-3.5 text-center">
                                                @if (in_array($role, $permission['roles'], true))
                                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                                        <input
                                                            type="checkbox"
                                                            name="permissions[{{ $role }}][]"
                                                            value="{{ $key }}"
                                                            @checked(in_array($key, old("permissions.{$role}", $granted[$role] ?? []), true))
                                                            class="h-5 w-5 rounded-md border-slate-300 text-brand-600 focus:ring-brand-500"
                                                            aria-label="{{ $permission['label'] }} for {{ $roleLabel }}"
                                                        >
                                                    </label>
                                                @else
                                                    <span class="inline-flex items-center text-slate-300" title="Not available to {{ $roleLabel }} under BMPC's rules">
                                                        <i data-lucide="lock" class="h-4 w-4"></i>
                                                        <span class="sr-only">Not available to {{ $roleLabel }}</span>
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <p class="mt-4 flex items-center gap-2 text-xs text-slate-500">
            <i data-lucide="lock" class="h-3.5 w-3.5"></i>
            Locked boxes protect BMPC's rules, for example only the Admin/CEO approves budget requests and project personnel only see projects assigned to them.
        </p>

        <div class="sticky bottom-0 z-10 -mx-4 mt-6 border-t border-slate-200 bg-slate-50/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border sm:bg-white/95 sm:px-5 sm:shadow-elevated">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500" x-text="dirty ? 'You have unsaved changes.' : 'Every change is recorded in the audit log.'"></p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('system.permissions') }}" x-show="dirty" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50" style="display: none;">Undo changes</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition-transform duration-200 ease-elegant hover:-translate-y-0.5 hover:bg-accent-600">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        Save Permissions
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
