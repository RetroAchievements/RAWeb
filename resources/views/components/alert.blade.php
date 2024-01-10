{{-- A Blade variation of https://ui.shadcn.com/docs/components/alert --}}

@props([
    'variant' => 'base', // 'base' | 'destructive',
    'title' => 'Title',
    'description' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Alias, dignissimos?',
])

<div
    role="alert"
    class="flex gap-x-4 w-full rounded border-2 p-4 {{ $variant === 'destructive' ? 'border-red-500 text-red-400 bg-red-950 light:bg-red-100' : 'border-text bg-embed' }}"
>
    <div class="text-2xl leading-none -mt-0.5">
        @if ($variant === 'base')
            <x-fas-exclamation-circle />
        @elseif ($variant === 'destructive')
            <x-fas-exclamation-triangle />
        @endif
    </div>

    <div class="flex flex-col gap-y-1">
        <p class="font-bold leading-none tracking-tight">
            {{ $title }}
        </p>

        <p>{{ $description }}</p>
    </div>
</div>
