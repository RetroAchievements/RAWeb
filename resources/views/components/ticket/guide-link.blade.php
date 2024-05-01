@props([
    'link' => '', // string
    'text' => '', // string
])

<div class="relative w-full p-2 bg-embed rounded mt-2">
    <div class="flex flex-col sm:flex-row w-full justify-between gap-x-2">
        <div class="flex items-center">
            {{ $slot }}
        </div>
        <div class="flex items-center">
            <a class="btn" href="{{ $link }}">{{ $text }}</a>
        </div>
    </div>
</div>