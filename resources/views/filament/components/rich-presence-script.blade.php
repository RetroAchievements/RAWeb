@php
    $state = $getState();
@endphp

<div>
    @if ($state)
        <textarea 
            disabled 
            readonly
            rows="10"
            class="w-full font-mono text-sm bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 rounded-md"
        >{{ $state }}</textarea>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-sm">No Rich Presence script defined.</p>
    @endif
</div>
