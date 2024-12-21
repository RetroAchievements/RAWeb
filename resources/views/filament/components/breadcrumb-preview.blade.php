<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="fi-in-text text-neutral-950 dark:text-white">
        {{ $getValue() }}
    </div>
</x-dynamic-component>
