@props([
    'headingLabel' => '',
    'value' => 0,
])

<div class="rounded border border-embed-highlight bg-embed p-4">
    <p role="heading" aria-level="2">{{ $headingLabel }}</p>
    <p class="text-lg">{{ localized_number($value) }}</p>
</div>
