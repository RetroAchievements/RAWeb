@props([
    'kind' => '',
    'isPreChecked' => false,
])

<label class="flex items-center gap-x-1 cursor-pointer lg:active:scale-95 lg:transition-transform whitespace-nowrap">
    <input
        class="cursor-pointer"
        type="checkbox"
        autocomplete="off"
        onchange="handleFilterChanged(event, '{{ $kind }}')"
        @if ($isPreChecked) checked @endif
    >
        {{ $slot }}
    </input>
</label>