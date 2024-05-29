@props([
    'currentPageLabel' => '',
])

<div class="navpath">
    @if (!$currentPageLabel)
        <span class="font-bold">Forum Index</span>
    @else
        <a href="/forum.php">Forum Index</a>
        &raquo;
        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
