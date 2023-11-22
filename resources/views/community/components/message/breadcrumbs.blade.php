@props([
    'targetUsername' => '',
    'currentPage' => '',
])

<div class='navpath'>
    <a href='{{ route('message.inbox') }}'>Messages</a>
    &raquo;
    <span class="font-bold">{{ $currentPage }}</span>
</div>
