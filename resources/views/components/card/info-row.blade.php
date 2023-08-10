@props(['label' => 'Label'])

<p>
    <span class="font-bold">{{ $label }}:</span>
    {{ $slot }}
</p>