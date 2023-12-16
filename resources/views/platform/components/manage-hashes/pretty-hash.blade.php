@props([
    'hash' => '12345678901234567890123456789012',
])

<p class="font-mono text-neutral-200 light:text-neutral-700 cursor-help" title="{{ $hash }}">
    {{ substr($hash, 0, 4) . '...' . substr($hash, -4) }}
</p>
