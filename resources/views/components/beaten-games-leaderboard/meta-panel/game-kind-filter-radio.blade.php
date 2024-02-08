@props([
    'disabled' => false,
    'selectedValue' => '',
    'value' => '',
])

<label class="@if (!$disabled) transition lg:active:scale-95 cursor-pointer @endif flex items-center gap-x-1 text-xs">
    <input
        type="radio"
        @if ($disabled) disabled @endif
        @if (!$disabled) class="cursor-pointer" @endif
        name="game-kind"
        value="{{ $value }}"
        {{ $selectedValue == $value ? 'checked' : '' }}
        @change="handleGameKindsChanged"
    >
    {{ $slot }}
</label>
