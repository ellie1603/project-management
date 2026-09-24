@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Administration"
        title="System Settings"
        subtitle="Organization-wide defaults used across budgeting, reporting, and project registration."
        class="animate-rise-in"
    />

    <x-flash-toast />
    <x-flash-toast :message="session('error')" type="error" />

    <form method="POST" action="{{ route('settings.update') }}" class="animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 80ms">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="font-display text-[15px] font-semibold text-slate-900">General Settings</h2>
                <p class="text-xs text-slate-500">Organization identity, currency, and warning thresholds.</p>
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:p-7 md:grid-cols-2">
            <div>
                <label for="setting-organization-name" class="mb-1.5 block text-sm font-medium text-slate-700">Organization Name</label>
                <input id="setting-organization-name" name="organization_name" value="{{ old('organization_name', $settings['organization_name']->value ?? 'BMPC') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
            </div>
            <div>
                <label for="setting-currency" class="mb-1.5 block text-sm font-medium text-slate-700">Currency</label>
                <input id="setting-currency" name="currency" value="{{ old('currency', $settings['currency']->value ?? 'PHP') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
            </div>
            <div>
                <label for="setting-project-registration-threshold" class="mb-1.5 block text-sm font-medium text-slate-700">Project Registration Threshold</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">₱</span>
                    <input id="setting-project-registration-threshold" type="number" step="0.01" name="project_registration_threshold" value="{{ old('project_registration_threshold', $settings['project_registration_threshold']->value ?? 50000) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">Used to flag projects meeting the organization's procurement threshold.</p>
            </div>
            <div>
                <label for="setting-budget-warning-threshold" class="mb-1.5 block text-sm font-medium text-slate-700">Budget Warning Threshold</label>
                <div class="relative">
                    <input id="setting-budget-warning-threshold" type="number" step="1" min="1" max="100" name="budget_warning_threshold_percent" value="{{ old('budget_warning_threshold_percent', $settings['budget_warning_threshold_percent']->value ?? 80) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2 pl-3 pr-8 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                    <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">Projects are flagged "Approaching Budget Limit" past this utilization.</p>
            </div>
        </div>

        <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-7">
            <button type="submit" class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated active:translate-y-0">
                <i data-lucide="check" class="h-4 w-4"></i>
                Save Settings
            </button>
        </div>
    </form>

    <section class="animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 130ms">
        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                <i data-lucide="tag" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="font-display text-[15px] font-semibold text-slate-900">Project Categories</h2>
                <p class="text-xs text-slate-500">Used when registering and filtering projects.</p>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($categories as $category)
                <div class="px-6 py-4 sm:px-7">
                    <form method="POST" action="{{ route('settings.categories.update', $category) }}" class="flex flex-wrap items-center gap-3">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $category->name }}" aria-label="Category name" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100 sm:w-44">
                        <input name="description" value="{{ $category->description }}" placeholder="Description" aria-label="Category description" class="min-w-[10rem] flex-1 rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                        <select name="status" aria-label="Category status" class="rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                            <option value="active" @selected($category->status === 'active')>Active</option>
                            <option value="inactive" @selected($category->status === 'inactive')>Inactive</option>
                        </select>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 whitespace-nowrap">{{ $category->projects_count }} project{{ $category->projects_count === 1 ? '' : 's' }}</span>
                        <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Save</button>
                    </form>
                    <form method="POST" action="{{ route('settings.categories.destroy', $category) }}" class="mt-2" onsubmit="return confirm('Remove this category?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-red-500 transition-colors duration-150 hover:text-red-700">
                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                            Remove category
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">No categories yet.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('settings.categories.store') }}" class="flex flex-wrap items-center gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-7">
            @csrf
            <input name="name" placeholder="New category name" aria-label="New category name" class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:w-48" required>
            <input name="description" placeholder="Description" aria-label="New category description" class="min-w-[10rem] flex-1 rounded-xl border-slate-200 bg-white text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            <input type="hidden" name="status" value="active">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated">
                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                Add Category
            </button>
        </form>
    </section>
</div>
@endsection
