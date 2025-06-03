@use('App\Http\Actions\DetectUserPlatformAction')

@php
    $userPlatform = (new DetectUserPlatformAction())->execute();
    $metaKey = in_array($userPlatform?->value, ['macOS', 'iOS']) ? '⌘' : 'Ctrl';
@endphp

<button
    @class([
        'flex h-9 items-center justify-between gap-2 rounded-md bg-neutral-800/40 px-3 py-1 text-sm',
        'text-neutral-400/50 transition-all hover:bg-neutral-800/60 hover:text-neutral-400',
        'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-neutral-600',
        'light:bg-white light:text-neutral-600 light:hover:bg-neutral-50 light:hover:text-neutral-900',
    ])
    onclick="window.openGlobalSearch ? window.openGlobalSearch() : window.dispatchEvent(new CustomEvent('open-global-search'))"
    type="button"
>
    <span class="flex items-center gap-2">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <span>Search...</span>
    </span>

    <kbd
        @class([
            'pointer-events-none hidden h-5 select-none items-center gap-1 rounded border border-neutral-700',
            'bg-neutral-800 px-1.5 font-mono text-[10px] font-medium text-neutral-400',
            'light:border-neutral-300 light:bg-white light:text-neutral-600',
            'sm:flex',
        ])
    >
        <span @if ($metaKey === '⌘') class="text-[16px] mt-0.5" @endif>{{ $metaKey }}</span>K
    </kbd>
</button>
