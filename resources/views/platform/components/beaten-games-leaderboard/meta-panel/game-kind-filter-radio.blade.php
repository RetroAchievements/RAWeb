@props([
    'selectedValue' => '',
    'value' => '',
])

<label class="transition lg:active:scale-95 cursor-pointer flex items-center gap-x-1 text-xs">
    <input
        type="radio"
        class="cursor-pointer"
        name="game-kind"
        value="{{ $value }}"
        {{ $selectedValue == $value ? 'checked' : '' }}
        @change="handleGameKindsChanged"
    >
    {{ $slot }}
</label>
