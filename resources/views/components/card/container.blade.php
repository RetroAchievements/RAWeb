@props([
    'imgSrc' => '',
    'imgKind' => 'user',
])

<div class="bg-box-bg text-menu-link rounded border border-embed-highlight leading-normal p-2 w-[400px] overflow-hidden">
    <div class="flex gap-x-3">
        <div class="flex items-center flex-col gap-y-2">
            @if($imgSrc)
                <img 
                    src="{{ $imgSrc }}"
                    class="rounded-sm {{ $imgKind === 'user' ? 'w-32 h-32 min-w-[128px] max-h-[128px]' : 'w-24 h-24 min-w-[96px] max-h-[96px]' }}"
                    width="{{ $imgKind === 'user' ? 128 : 96 }}"
                    height="{{ $imgKind === 'user' ? 128 : 96 }}"
                >
            @endif
        </div>

        <div class="w-full">
            {{ $slot }}
        </div>
    </div>
</div>