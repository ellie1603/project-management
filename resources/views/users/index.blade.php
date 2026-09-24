@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{}">
    <x-flash-toast />

    <x-page-hero
        eyebrow="Administration"
        title="Users"
        subtitle="Manage login accounts for Admin, Finance &amp; Accounting, and Project Personnel — including contractor/provider profiles."
        class="animate-rise-in"
    >
        <x-slot name="aside">
            <button
                type="button"
                x-on:click="$dispatch('open-modal', 'create-user')"
                class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-glow transition-transform duration-200 ease-elegant hover:-translate-y-0.5"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                New User
            </button>
        </x-slot>
    </x-page-hero>

    <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-3 shadow-soft ring-1 ring-slate-900/5" data-ajax-form="ajax-results" data-turbo="false">
        <div class="relative min-w-[16rem] flex-1">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search name or email"
                aria-label="Search name or email"
                class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100"
            >
        </div>

        <select name="role" aria-label="Filter by role" class="rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-9 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            <option value="">All Roles</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin / CEO</option>
            <option value="project_personnel" @selected(request('role') === 'project_personnel')>Project Personnel</option>
            <option value="finance_accounting" @selected(request('role') === 'finance_accounting')>Finance &amp; Accounting</option>
        </select>

        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated active:translate-y-0">
            <i data-lucide="filter" class="h-4 w-4"></i>
            Filter
        </button>

        @if (request()->hasAny(['search', 'role']))
            <a href="{{ route('users.index') }}" data-turbo="false" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-800">
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                Clear
            </a>
        @endif
    </form>

    <div id="ajax-results" data-ajax-region class="mt-5">
        @include('users.partials.results')
    </div>

    <x-modal name="create-user" :show="$errors->isNotEmpty()" max-width="lg">
        <form method="POST" action="{{ route('users.store') }}" class="flex max-h-[85vh] flex-col">
            @csrf

            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                        <i data-lucide="user-plus" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">New User Account</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Create a login for an Admin, Project Personnel, or Finance user.</p>
                    </div>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="shrink-0 rounded-lg p-1.5 text-slate-400 outline-none transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-brand-200" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                @include('users._form')
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated active:translate-y-0">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    Create User
                </button>
            </div>
        </form>
    </x-modal>
</div>

@push('scripts')
<script>
    (function openEditModalFromQuery() {
        const run = () => {
            const editId = new URLSearchParams(window.location.search).get('edit');

            if (editId) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: `edit-user-${editId}` }));
            }
        };

        document.addEventListener('DOMContentLoaded', run);
        document.addEventListener('turbo:load', run);
    })();
</script>
@endpush
@endsection
