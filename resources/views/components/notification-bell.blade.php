@php
    $unread = auth()->user()->unreadNotifications;
    $recent = auth()->user()->notifications()->latest()->take(8)->get();
@endphp

{{-- The dropdown passes its width through as a class, so it needs the full
     Tailwind name rather than just the number. --}}
<x-dropdown align="right" width="w-80" contentClasses="py-0">
    <x-slot name="trigger">
        <button type="button"
                class="relative inline-flex items-center justify-center rounded-control transition duration-micro ease-royal"
                style="width: 44px; height: 44px; color: var(--text-mid)"
                aria-label="{{ trans_choice('{0}Notifications, none unread|{1}Notifications, :count unread|[2,*]Notifications, :count unread', $unread->count(), ['count' => $unread->count()]) }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.7 21a2 2 0 0 1-3.4 0" />
            </svg>

            @if ($unread->isNotEmpty())
                {{-- Count, not just a dot: the number is the useful part. --}}
                <span class="numeric absolute flex items-center justify-center"
                      style="top: 4px; right: 2px; min-width: 17px; height: 17px; padding: 0 4px;
                             border-radius: 999px; background: var(--maroon-500); color: var(--text-hi);
                             font-size: 10px; line-height: 1">
                    {{ $unread->count() > 9 ? '9+' : $unread->count() }}
                </span>
            @endif
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="flex items-center justify-between px-4 py-2.5 hairline-b">
            <p class="eyebrow">{{ __('Notifications') }}</p>
            @if ($unread->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs underline underline-offset-2"
                            style="color: var(--text-mid)">{{ __('Mark all read') }}</button>
                </form>
            @endif
        </div>

        @forelse ($recent as $notification)
            <a href="{{ route('notifications.show', $notification->id) }}"
               class="dropdown-item block px-4 py-3 text-sm hairline-b"
               style="{{ $notification->read_at ? '' : 'background: color-mix(in srgb, var(--gold-500) 8%, transparent)' }}">
                <span class="block" style="color: var(--text-hi)">
                    {{ $notification->data['message'] ?? __('You have a new notification.') }}
                </span>
                <span class="numeric block text-xs mt-1" style="color: var(--text-low)">
                    {{ $notification->created_at->diffForHumans() }}
                </span>
            </a>
        @empty
            <p class="px-4 py-6 text-sm text-center" style="color: var(--text-low)">
                {{ __('Nothing to catch up on.') }}
            </p>
        @endforelse
    </x-slot>
</x-dropdown>
