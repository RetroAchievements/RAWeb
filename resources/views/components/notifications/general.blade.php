@props([
    'trigger' => '',
    'count' => 0,
    'notifications' => null, // Collection
    'link' => '', // FIXME unused?
    'class' => '',
    'title' => '', // FIXME unused?
])

<x-nav-dropdown
    :class="$class"
    dropdown-class="dropdown-menu-right dropdown-items-right"
    :title="__res('notification')"
>
    <x-slot name="trigger">
        <x-fas-bell />
        @if ($count)
            <div class="text-danger absolute translate-x-3 -translate-y-1 text-[8px]">
                <x-fas-circle />
            </div> 
        @endif
    </x-slot>

    @if ($notifications->isNotEmpty())
        @foreach ($notifications as $notification)
            <x-dropdown-item :href="$notification['link']" :class="$notification['class'] ?? ''">
                {{ $notification['title'] }}
            </x-dropdown-item>
        @endforeach
    @else
        <div class="flex whitespace-nowrap text-muted px-3 py-2">{{ __('No new notifications') }}</div>
    @endif
</x-nav-dropdown>
