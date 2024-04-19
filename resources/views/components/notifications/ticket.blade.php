<x-nav-dropdown
    :class="$class ?? ''"
    dropdown-class="dropdown-menu-right dropdown-items-right"
    :title="__res('notification')"
>
    <x-slot name="trigger">
        <x-fas-ticket />
        @if($count ?? 0)
            @switch($priority)
                @case(2)
                    @php($dot = 'dot-urgent')
                    @break
                @default
                    @php($dot = 'dot-warning')
            @endswitch
            <div class="{{ $dot }} absolute translate-x-3 -translate-y-1 text-[8px]">
                <x-fas-circle />
            </div>
        @endif
    </x-slot>
    @if($notifications->isNotEmpty())
        @foreach($notifications as $notification)
            <x-dropdown-item :link="$notification['link']" :class="$notification['class'] ?? ''">
                {{ $notification['title'] }}
            </x-dropdown-item>
        @endforeach
    @else
        <div class="flex whitespace-nowrap text-muted px-3 py-2">{{ __('No open tickets or requests') }}</div>
    @endif
</x-nav-dropdown>
