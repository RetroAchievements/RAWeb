@props([
    'imgSrc' => ''
])

<div class="bg-box-bg text-menu-link rounded border border-embed-highlight leading-normal p-2 w-[400px] overflow-hidden">
    <div class="flex gap-x-2">
        @if($imgSrc)
            <img src="{{ $imgSrc }}" class="w-32 h-32" width="128" height="128" style="image-rendering: pixelated;">
        @endif

        <div class="w-full">
            {{ $slot }}
        </div>
    </div>
</div>