{{-- Budget check before a decision: what the project would have left if this pending request were approved. --}}
@props(['request', 'remaining'])

@if ($request->status === 'Pending')
    @php $after = (float) $remaining - (float) $request->amount; @endphp
    <p class="mt-0.5 whitespace-nowrap text-xs {{ $after < 0 ? 'font-semibold text-red-600' : 'text-slate-500' }}">
        {{ $after < 0 ? 'Over budget by ₱'.number_format(abs($after), 2) : '₱'.number_format($after, 2).' left after approval' }}
    </p>
@endif
