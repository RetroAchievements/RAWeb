@props([
    'variant' => 'base', // 'base' | 'no-background'
])

<div class="w-full h-full flex flex-col gap-y-2 items-center justify-center py-8 rounded {{ $variant === 'base' ? 'bg-embed' : '' }}">
    <img src="/assets/images/cheevo/confused.webp" alt="empty state">
    <p>{{ $slot }}</p>
</div>
