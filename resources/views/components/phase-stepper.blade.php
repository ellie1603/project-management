@props(['phases'])

@php
    $phases = collect($phases)->values();
@endphp

<div class="overflow-x-auto pb-1">
    <div class="flex min-w-[520px] items-center sm:min-w-0">
        @foreach ($phases as $phase)
            @php
                $isCompleted = $phase->status === 'Completed';
                $isCurrent = $phase->status === 'In Progress';
            @endphp

            @if (! $loop->first)
                <div class="h-0.5 flex-1 {{ $phases[$loop->index - 1]->status === 'Completed' ? 'bg-slate-900' : 'bg-slate-200' }}"></div>
            @endif

            <div class="flex shrink-0 flex-col items-center gap-1.5">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-semibold transition-colors duration-200 {{ match (true) {
                        $isCompleted => 'border-brand-600 bg-brand-600 text-white',
                        $isCurrent => 'border-brand-600 bg-brand-50 text-brand-700',
                        default => 'border-slate-200 bg-white text-slate-400',
                    } }}"
                    title="{{ $phase->name }} — {{ $phase->status }}"
                >
                    @if ($isCompleted)
                        <i data-lucide="check" class="h-4 w-4"></i>
                    @else
                        {{ $phase->sequence }}
                    @endif
                </div>
                <span class="w-20 text-center text-[11px] font-medium leading-tight {{ $isCompleted || $isCurrent ? 'text-slate-900' : 'text-slate-400' }}">
                    {{ $phase->name }}
                </span>
            </div>
        @endforeach
    </div>
</div>
