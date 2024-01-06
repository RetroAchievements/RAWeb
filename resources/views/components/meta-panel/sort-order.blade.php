@props([
    'selectedSortOrder' => null,
    'availableSorts' => [],
])

<script>
function handleSortOrderChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter(
        ['sort'],
        [newQueryParamValue],
    );
}
</script>

<label class="text-xs font-bold" for="sort-order-field">Sort by</label>
<select
    id="sort-order-field"
    class="w-full sm:max-w-[240px]"
    onchange="handleSortOrderChanged(event)"
    autocomplete="off"
>
@foreach ($availableSorts as $key => $text)
    <option value="{{ $key }}" @if ($selectedSortOrder === $key) selected @endif>{{ $text }}</option>
@endforeach
</select>