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
function handleSelectFilterChanged(kind, event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter(
        [`filter[${kind}]`],
        [newQueryParamValue],
    );
}
</script>

<label class="text-xs font-bold" for="{{ $kind }}-filter-select">{{ $label }}</label>
<select
    id="{{ $kind }}-filter-select"
    class="w-full sm:max-w-[240px]"
    onchange="handleSelectFilterChanged('{{ $kind }}', event)"
    autocomplete="off"
>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @if ($selectedValue === $value) selected @endif>{{ $label }}</option>
    @endforeach
</select>
