@props([
    'currentPage' => '',
])

<div class='navpath'>
    @if (empty($currentPage))
        <span class="font-bold">Messages</span>
    @else
        <a href='{{ route("message-thread.index") }}'>Messages</a>
        &raquo;
        <span class="font-bold">{{ $currentPage }}</span>
    @endif
</div>
