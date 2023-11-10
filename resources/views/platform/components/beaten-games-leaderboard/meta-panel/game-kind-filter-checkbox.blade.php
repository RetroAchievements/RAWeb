@props([
    'kind' => '',
    'isPreChecked' => false,
])

<label class="flex items-center gap-x-1 cursor-pointer lg:active:scale-95 lg:transition-transform">
    <input
        class="cursor-pointer"
        type="checkbox"
        autocomplete="off"
        @change="handleGameKindsChanged($event, '{{ $kind }}')"
        @if ($isPreChecked) checked @endif
    >
        {{ $slot }}
    </input>
</label>