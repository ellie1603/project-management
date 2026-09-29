@forelse ($notifications as $notification)
    @php
        $projectId = $notification->data['project_id'] ?? null;
        $isUnread = is_null($notification->read_at);
    @endphp
    <div
        data-notification-row
        @if ($isUnread) data-unread @endif
        class="group relative flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition-colors duration-150 last:border-b-0 hover:bg-slate-50 {{ $isUnread ? 'bg-brand-50/60' : '' }}"
    >
        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $isUnread ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400' }}" data-notification-icon>
            <i data-lucide="bell" class="h-3.5 w-3.5"></i>
        </div>
        <div class="min-w-0 flex-1">
            @if ($projectId)
                {{-- The stretched link makes the whole row clickable; opening it marks the notification read. --}}
                <a href="{{ route('notifications.open', $notification->id) }}" class="block text-sm text-slate-900 after:absolute after:inset-0 {{ $isUnread ? 'font-semibold' : 'font-medium' }}" data-notification-title>
                    {{ $notification->data['title'] ?? 'Notification' }}
                </a>
            @else
                <button type="button" onclick="window.markNotificationRead('{{ $notification->id }}', this)" class="block text-left text-sm text-slate-900 after:absolute after:inset-0 {{ $isUnread ? 'font-semibold' : 'font-medium' }}" data-notification-title>
                    {{ $notification->data['title'] ?? 'Notification' }}
                </button>
            @endif
            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
        </div>
        @if ($isUnread)
            <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-600" data-notification-dot aria-label="Unread"></span>
        @endif
    </div>
@empty
    <p class="px-4 py-10 text-center text-sm text-slate-400">No notifications yet.</p>
@endforelse
