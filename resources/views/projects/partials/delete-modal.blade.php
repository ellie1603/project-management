{{-- Admin-only. The project code must be retyped before the delete button unlocks; the server checks it again. --}}
<x-modal name="delete-project" :show="$errors->projectDeletion->isNotEmpty()" max-width="md" focusable>
    <form method="POST" action="{{ route('projects.destroy', $project) }}" class="px-6 pb-6 pt-7" x-data="{ typed: '' }">
        @csrf
        @method('DELETE')

        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                <i data-lucide="triangle-alert" class="h-5 w-5"></i>
            </span>
            <div>
                <h2 class="font-display text-lg font-semibold text-slate-900">Delete this project?</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span class="font-medium text-slate-700">{{ $project->title }}</span> and everything in it will be permanently deleted:
                    progress updates, photos, documents, expenses, budget requests, assignments, and saved reports.
                </p>
                <p class="mt-2 text-sm font-medium text-red-600">This cannot be undone.</p>
                <p class="mt-1 text-sm text-slate-500">Only a record of the deletion is kept in the audit log, under your name.</p>
            </div>
        </div>

        <div class="mt-5">
            <label for="confirm_code" class="block text-sm font-medium text-slate-700">
                Type <span class="font-mono font-semibold text-slate-900">{{ $project->project_code }}</span> to confirm
            </label>
            <input
                id="confirm_code"
                name="confirm_code"
                type="text"
                x-model="typed"
                autocomplete="off"
                spellcheck="false"
                class="mt-1.5 block w-full rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-red-400 focus:ring-red-400"
            >
            <x-input-error :messages="$errors->projectDeletion->get('confirm_code')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
            <button
                type="submit"
                :disabled="typed.trim() !== @js($project->project_code)"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-red-600"
            >
                <i data-lucide="trash-2" class="h-4 w-4"></i>
                Delete Project
            </button>
        </div>
    </form>
</x-modal>
