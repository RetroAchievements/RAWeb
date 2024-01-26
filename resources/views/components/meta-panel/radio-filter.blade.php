@props([
    'allFilterOptions' => [],
    'kind' => '',
    'label' => '',
    'options' => [], // 'key' => 'Label'
])

<?php
$selectedValue = $allFilterOptions[$kind] ?? null;
?>

<script>
function handleRadioFilterChanged(event) {
    const kind = '{{ $kind }}';

    const newQueryParamValue = event.target.value;
    window.updateUrlParameter(
        [`filter[${kind}]`],
        [newQueryParamValue],
    );
}
</script>

<label class="text-xs font-bold" for="{{ $kind }}-filter-radio">{{ $label }}</label>
<div class="space-x-4 flex" id="{{ $kind }}-filter-radio">
    @foreach ($options as $optionValue => $optionLabel)
        <label class="transition-transform lg:active:scale-95 cursor-pointer flex items-center gap-x-1 text-xs sm:-mt-6">
            <input
                type="radio"
                class="cursor-pointer"
                name="radio-{{ $kind }}"
                value="{{ $optionValue }}"
                onchange="handleRadioFilterChanged(event)"
                autocomplete="off"
                @if ($selectedValue === $optionValue) checked @endif
            >
            {{ $optionLabel }}
        </label>
    @endforeach
</div>
