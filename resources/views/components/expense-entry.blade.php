{{--
    Amount and reference for one recorded expense, with its reversal state.
    Reversals show as negative amounts; reversed originals stay visible, marked
    "Reversed". Users who record expenses get a Reverse action with a reason.
--}}
@props(['expense', 'part'])

@php
    $amount = (float) $expense->amount;
    $peso = ($amount < 0 ? '-' : '').'₱'.number_format(abs($amount), 2);
    $isReversal = $expense->isReversal();
    $isReversed = $expense->isReversed();
@endphp

@if ($part === 'amount')
    <span class="whitespace-nowrap font-medium {{ $isReversal ? 'text-red-600' : ($isReversed ? 'text-slate-400 line-through' : 'text-slate-900') }}">{{ $peso }}</span>
    @if ($isReversed)
        <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-500">Reversed</span>
    @endif
@elseif ($part === 'reference')
    {{ $expense->reference_number ?? '—' }}
    @if ($isReversal)
        <p class="mt-0.5 text-xs text-slate-500">Reversal of {{ $expense->reverses?->reference_number ?? 'expense #'.$expense->reverses_expense_id }}@if ($expense->remarks): {{ $expense->remarks }}@endif</p>
    @elseif ($expense->description)
        <p class="mt-0.5 text-xs text-slate-400">{{ $expense->description }}</p>
    @endif
@elseif ($part === 'action')
    @if (! $isReversal && ! $isReversed && auth()->user()->can('create', App\Models\ProjectExpense::class))
        @php $modal = 'reverse-expense-'.$expense->id; @endphp
        <button type="button" @click="$dispatch('open-modal', '{{ $modal }}')" class="text-xs font-medium text-slate-500 underline hover:text-red-600">Reverse</button>
        <x-modal :name="$modal" max-width="md">
            <form method="POST" action="{{ route('expenses.reverse', $expense) }}" class="px-6 pb-6 pt-7 text-left">
                @csrf
                <h2 class="font-display text-lg font-semibold text-slate-900">Reverse this expense?</h2>
                <p class="mt-1 text-sm text-slate-500">
                    A matching negative entry of {{ '-₱'.number_format(abs($amount), 2) }} is added so the two cancel out.
                    Nothing is deleted; both stay in the history. Record the correct expense separately if needed.
                </p>
                <label for="reverse-reason-{{ $expense->id }}" class="mt-4 block text-sm font-medium text-slate-700">Reason</label>
                <textarea id="reverse-reason-{{ $expense->id }}" name="reason" rows="2" required maxlength="500" placeholder="e.g. Wrong amount encoded" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Reverse Expense</button>
                </div>
            </form>
        </x-modal>
    @endif
@endif
