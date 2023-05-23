@props([
    'imageIcon' => '#',
    'metaKind' => 'Game',
])

<div class="flex flex-col sm:flex-row sm:w-full gap-x-4 gap-y-2 items-center mb-4">
    <img 
        class="aspect-1 object-cover rounded-sm w-[96px] h-[96px]" 
        src="{{ $imageIcon }}"
        width="96" 
        height="96" 
        alt="{{ $metaKind }} icon"
    >

    <div class="flex flex-col w-full gap-1">
        {{ $slot }}
    </div>
</div>