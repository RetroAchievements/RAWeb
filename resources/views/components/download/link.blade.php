<p class="embedded mb-2 text-right whitespace-nowrap">
    <x-link link="{{ $url }}">{{ $label }}</x-link>
    @if (isset($windows) && $windows)
        <br>
        <small>Windows</small>
    @endif
</p>
