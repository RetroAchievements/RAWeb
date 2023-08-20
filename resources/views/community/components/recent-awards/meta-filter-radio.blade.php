@props([
    'name' => '',
    'selectedValue' => '',
    'value' => '',
])

<label class="transition lg:active:scale-95 cursor-pointer flex items-center gap-x-1 text-xs">
    <input 
        type="radio"
        class="cursor-pointer"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $selectedValue == $value ? 'checked' : '' }}
        onchange="handleUsersChanged(event)"
    >
    
    <span class="whitespace-nowrap">{{ $slot }}</span>
</label>