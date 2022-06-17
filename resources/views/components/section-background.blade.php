<div class="{{ ($border ?? true) ? 'border-b' : '' }}"
     style="border-width:2px!important;position:absolute;bottom:{{ $bottom ?? 0 }}px;top:0;left:0;right:0;z-index:{{ $zIndex ?? -1 }}"
>
    @if($image ?? null)
        <x-background :image="$image" />
    @endif
    @if($scanlines ?? true)
        <x-background-texture :image="'assets/images/textures/scanlines.webp'" />
    @endif
</div>
