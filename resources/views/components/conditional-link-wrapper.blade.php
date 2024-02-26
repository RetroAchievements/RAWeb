{{--
    Conditionally wrap any element with an anchor tag, if `href` is truthy.
    The wrapper can also optionally be given some classnames.
--}}

@props([
    'class' => null, // ?string
    'href' => null, // ?string
])

@if ($href)
    <a href="{{ $href }}" @if ($class) class="{{ $class }}" @endif>{{ $slot }}</a>
@else
    {{ $slot }}
@endif
