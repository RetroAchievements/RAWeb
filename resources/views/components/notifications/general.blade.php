@props([
    'trigger' => '',
    'count' => null, //int
    'notifications' => null, //Collection
    'link' => '',
    'class' => '',
    'title' => '',
])

<x-nav-dropdown
    :class="$class ?? ''"
    dropdown-class="dropdown-menu-right dropdown-items-right"
    :title="__res('notification')"
>
    <x-slot name="trigger">
        <x-fas-bell />
        @if($count ?? 0)
            <div class="text-danger absolute translate-x-3 -translate-y-1 text-[8px]">
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
        <div class="flex whitespace-nowrap text-muted px-3 py-2">{{ __('No new notifications') }}</div>
    @endif
    {{--<x-dropdown-item>--}}
    {{--<a class="nav-link" href="{{ route('notification.index') }}" title="{{ __res('notification') }}" data-toggle="tooltip">--}}
    {{-- All notifications --}}
    {{--</a>--}}
    {{--</x-dropdown-item>--}}
</x-nav-dropdown>
