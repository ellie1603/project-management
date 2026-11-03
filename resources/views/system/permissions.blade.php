@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-flash-toast />
    <x-flash-toast type="error" />

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">System</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Roles &amp; Permissions</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
            Choose what each role can do. Changes apply to everyone in that role as soon as you save, and every change is recorded in the audit log.
        </p>
    </div>

    <section class="overflow-hidden rounded-3xl glass">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Name</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Permissions</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Users</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="transition-colors duration-150 hover:bg-slate-50/80">
                        <td class="px-5 py-4 text-sm font-medium text-slate-900">System Administrator</td>
                        <td class="px-4 py-4 text-sm text-slate-700">All</td>
                        <td class="px-4 py-4 text-sm tabular-nums text-slate-700">{{ $systemAdminCount }}</td>
                        <td class="px-5 py-4"></td>
                    </tr>
                    @foreach ($roles as $role => $roleLabel)
                        <tr class="transition-colors duration-150 hover:bg-slate-50/80">
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $roleLabel }}</td>
                            <td class="px-4 py-4 text-sm tabular-nums text-slate-700">{{ count($granted[$role] ?? []) }}</td>
                            <td class="px-4 py-4 text-sm tabular-nums text-slate-700">{{ $userCounts[$role] }}</td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" @click="$dispatch('open-modal', 'edit-role-{{ $role }}')" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition-colors duration-150 hover:text-brand-700">
                                    <i data-lucide="square-pen" class="h-4 w-4"></i>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-3.5">
            <p class="text-sm text-slate-500">Showing 1 to {{ count($roles) + 1 }} of {{ count($roles) + 1 }} results</p>
        </div>
    </section>

    @foreach ($roles as $role => $roleLabel)
        @php
            $reopen = $errors->has('permissions') && old('_role') === $role;
            $checked = $reopen ? old('permissions', []) : ($granted[$role] ?? []);
            $grantableCount = collect($catalog)->flatten(1)->filter(fn ($permission) => in_array($role, $permission['roles'], true))->count();
        @endphp

        <x-modal :name="'edit-role-'.$role" :show="$reopen" max-width="2xl">
            <form
                method="POST"
                action="{{ route('system.permissions.update', $role) }}"
                class="flex max-h-[85vh] flex-col"
                x-data
                x-on:open-modal.window="$event.detail === 'edit-role-{{ $role }}' && $el.reset()"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="_role" value="{{ $role }}">

                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl chip-ink">
                            <i data-lucide="shield-check" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="font-display text-lg font-semibold text-slate-900">Edit {{ $roleLabel }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $grantableCount }} permissions can be given to this role.</p>
                        </div>
                    </div>
                    <button type="button" @click="$dispatch('close-modal', 'edit-role-{{ $role }}')" class="shrink-0 rounded-lg p-1.5 text-slate-400 outline-none transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-brand-200" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    @if ($reopen)
                        <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                            <p>{{ $errors->first('permissions') }}</p>
                        </div>
                    @endif

                    @foreach ($catalog as $group => $permissions)
                        <fieldset>
                            <legend class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $group }}</legend>
                            <div class="mt-2 divide-y divide-slate-100 rounded-2xl ring-1 ring-slate-200/70">
                                @foreach ($permissions as $key => $permission)
                                    @if (in_array($role, $permission['roles'], true))
                                        <label class="flex cursor-pointer items-start gap-3 px-4 py-3 transition-colors duration-150 hover:bg-slate-50/80">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $key }}"
                                                @checked(in_array($key, $checked, true))
                                                class="mt-0.5 h-5 w-5 shrink-0 rounded-md border-slate-300 text-brand-600 focus:ring-brand-500"
                                            >
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-slate-900">{{ $permission['label'] }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">{{ $permission['description'] }}</span>
                                            </span>
                                        </label>
                                    @else
                                        <div class="flex items-start gap-3 px-4 py-3" title="Not available to {{ $roleLabel }} under BMPC's rules">
                                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center text-slate-300">
                                                <i data-lucide="lock" class="h-4 w-4"></i>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-slate-400">{{ $permission['label'] }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-400">Not available to {{ $roleLabel }}.</span>
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <p class="flex items-center gap-2 text-xs text-slate-500">
                        <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0"></i>
                        Locked permissions protect BMPC's rules, for example only the CEO approves budget requests and project personnel only see projects assigned to them.
                    </p>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                    <button type="button" @click="$dispatch('close-modal', 'edit-role-{{ $role }}')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-brand-700 hover:shadow-elevated active:translate-y-0">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        Save Permissions
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach
</div>
@endsection
