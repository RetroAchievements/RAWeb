<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @php
        $state = $getState();
    @endphp

    @if ($state)
        <textarea
            disabled
            readonly
            rows="10"
            class="p-2 w-full font-mono text-sm border border-neutral-300 dark:border-neutral-600 bg-neutral-50 dark:bg-neutral-900 rounded-md"
        >{{ $state }}</textarea>
    @else
        <p class="text-neutral-500 dark:text-neutral-400 text-sm">No Rich Presence script defined.</p>
    @endif
</x-dynamic-component>
