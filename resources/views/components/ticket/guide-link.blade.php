@props([
    'href' => '', // string
    'buttonText' => '', // string
])

<div class="relative w-full p-2 bg-embed rounded mt-2">
<div class="flex w-full justify-between gap-4">
        <div class="flex items-center">
            {{ $slot }}
        </div>
        <div class="flex min-w-[140px] justify-end">
            <a class="btn flex items-center whitespace-normal text-center" href="{{ $href }}">{{ $buttonText }}</a>
        </div>
    </div>
</div>