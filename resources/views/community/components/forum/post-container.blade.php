@props([
    'commentId',
    'isHighlighted' => false,
])

<div class="relative">
    <div id="{{ $commentId }}" class="absolute left-0 h-px w-px" style="top: -64px;"></div>

    <div
        class='{{ $isHighlighted ? 'highlight' : '' }} relative w-[calc(100%+16px)] sm:w-full -mx-2 sm:mx-0 lg:flex rounded-lg mt-3 odd:bg-embed bg-embed-highlight px-1 pb-3 pt-2'
        style="word-break: break-word;"
    >
        {{ $slot }}
    </div>
</div>