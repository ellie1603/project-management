{{-- Controls for the Alpine `pager` component; must sit inside the x-data="pager()" wrapper. --}}
<div x-show="pages > 1" style="display: none;" {{ $attributes->merge(['class' => 'flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row']) }}>
    <p class="text-sm text-slate-500">
        Showing <span class="font-semibold text-slate-800" x-text="from"></span>
        to <span class="font-semibold text-slate-800" x-text="to"></span>
        of <span class="font-semibold text-slate-800" x-text="total"></span>
    </p>

    <div class="flex items-center gap-1.5">
        <button type="button" @click="go(page - 1)" :disabled="page === 1" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-white" aria-label="Previous page">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>

        <template x-for="number in pages" :key="number">
            <button
                type="button"
                @click="go(number)"
                :aria-current="number === page ? 'page' : null"
                :class="number === page ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-slate-900'"
                class="hidden h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors duration-150 sm:inline-flex"
                x-text="number"
            ></button>
        </template>
        <span class="inline-flex h-9 items-center px-2 text-sm font-medium text-slate-600 sm:hidden" x-text="page + ' / ' + pages"></span>

        <button type="button" @click="go(page + 1)" :disabled="page === pages" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-white" aria-label="Next page">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>
</div>
