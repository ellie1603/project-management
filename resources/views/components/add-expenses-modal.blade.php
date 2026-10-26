{{-- Open with $dispatch('open-modal', 'add-expenses'). Logic lives in resources/js/expense-batch.js. --}}
@php
    $config = [
        'projects' => $projects,
        'today' => now()->toDateString(),
        'maxRows' => $maxRows,
        'defaultProjectId' => $defaultProjectId,
        'saveUrl' => route('budget.expenses.batch.store'),
        'returnUrl' => url()->current(),
        'csrf' => csrf_token(),
        'draftKey' => 'bmpc-expense-draft-'.auth()->id(),
    ];
    $input = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100';
    $label = 'mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500';
@endphp

<x-modal name="add-expenses" :show="$open" max-width="3xl">
    {{-- Fixed header and footer with the list scrolling between them, so Save is always in reach. --}}
    <form
        x-data="expenseBatch(@js($config))"
        @submit.prevent="save()"
        novalidate
        class="flex max-h-[calc(100dvh-2rem)] flex-col"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 pb-4 pt-5 sm:px-6">
            <div>
                <h2 class="font-display text-lg font-semibold text-slate-900">Add Expenses</h2>
                <p class="mt-0.5 text-sm text-slate-500">Enter the total amount and date of each expense. Press Enter for the next one; it keeps the same project and date.</p>
            </div>
            <button type="button" x-on:click="$dispatch('close')" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-5 py-4 sm:px-6">
            <div x-show="restored" class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800" style="display: none;">
                <p>Your unsaved expenses from last time are back.</p>
                <button type="button" @click="startOver()" class="font-semibold underline">Start over</button>
            </div>

            <div class="space-y-3">
                <template x-for="row in rows" :key="row.key">
                    <div
                        :data-row="row.key"
                        class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-soft ring-1"
                        :class="Object.keys(row.errors).length ? 'ring-red-300' : 'ring-slate-900/5'"
                        @keydown.enter="if ($event.target.tagName === 'INPUT') { $event.preventDefault(); nextRow(row); }"
                    >
                        <div class="grid min-w-0 flex-1 grid-cols-2 gap-x-3 gap-y-2.5 sm:grid-cols-12">
                            <div class="col-span-2 sm:col-span-12">
                                <label :for="'project-' + row.key" class="{{ $label }}">Project</label>
                                <select :id="'project-' + row.key" x-model="row.project_id" @change="edited(row, 'project_id')" class="{{ $input }}" :class="row.errors.project_id && '!border-red-400'">
                                    <option value="">Choose a project</option>
                                    <template x-for="project in projects" :key="project.id">
                                        <option :value="String(project.id)" x-text="project.code + ' · ' + project.title" :selected="String(project.id) === String(row.project_id)"></option>
                                    </template>
                                </select>
                                <p x-show="row.errors.project_id" x-text="row.errors.project_id" class="mt-1 text-xs text-red-600"></p>
                            </div>
                            <div class="col-span-1 sm:col-span-3">
                                <label :for="'amount-' + row.key" class="{{ $label }}">Total Amount (₱)</label>
                                <input :id="'amount-' + row.key" data-field="amount" type="number" inputmode="decimal" step="0.01" min="0" placeholder="0.00" x-model="row.amount" @input="edited(row, 'amount')" class="{{ $input }} text-right tabular-nums" :class="row.errors.amount && '!border-red-400'">
                                <p x-show="row.errors.amount" x-text="row.errors.amount" class="mt-1 text-xs text-red-600"></p>
                            </div>
                            <div class="col-span-1 sm:col-span-4">
                                <label :for="'date-' + row.key" class="{{ $label }}">Date</label>
                                <input :id="'date-' + row.key" type="date" :max="today" x-model="row.expense_date" @input="edited(row, 'expense_date')" class="{{ $input }}" :class="row.errors.expense_date && '!border-red-400'">
                                <p x-show="row.errors.expense_date" x-text="row.errors.expense_date" class="mt-1 text-xs text-red-600"></p>
                            </div>
                            <div class="col-span-2 sm:col-span-5">
                                <label :for="'reference-' + row.key" class="{{ $label }}">Reference No. or Person <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                                <input :id="'reference-' + row.key" placeholder="OR / voucher no. or name" x-model="row.reference_number" @input="edited(row, 'reference_number')" class="{{ $input }}" :class="row.errors.reference_number && '!border-red-400'">
                                <p x-show="row.errors.reference_number" x-text="row.errors.reference_number" class="mt-1 text-xs text-red-600"></p>
                            </div>
                        </div>

                        <button type="button" @click="removeRow(row)" class="mt-5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors duration-150 hover:bg-red-50 hover:text-red-600" aria-label="Remove this expense" title="Remove">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <button type="button" @click="addRow()" class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-white py-3 text-sm font-semibold text-slate-500 transition-colors duration-150 hover:border-slate-300 hover:text-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Add another expense
            </button>
        </div>

        <div class="border-t border-slate-100 bg-white px-5 py-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-900" x-text="filledRows.length"></span>
                        <span x-text="filledRows.length === 1 ? 'expense' : 'expenses'"></span>
                        &middot;
                        <span class="font-display text-lg font-semibold tabular-nums text-slate-900" x-text="peso(grandTotal)"></span>
                    </p>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                        <template x-for="entry in projectTotals" :key="entry.project.id">
                            <p class="text-xs" :class="entry.after < 0 ? 'font-semibold text-red-600' : 'text-slate-500'">
                                <span x-text="entry.project.title"></span>:
                                <span class="tabular-nums" x-text="peso(entry.total)"></span>,
                                <span x-text="entry.after < 0 ? 'over budget by ' + peso(-entry.after) : peso(entry.after) + ' left'"></span>
                            </p>
                        </template>
                    </div>
                    <p x-show="formError" x-text="formError" class="mt-1 text-sm font-medium text-red-600" role="alert" style="display: none;"></p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" style="display: none;"></span>
                        <span x-text="saving ? 'Saving...' : (filledRows.length > 1 ? 'Save ' + filledRows.length + ' Expenses' : 'Save Expense')"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-modal>
