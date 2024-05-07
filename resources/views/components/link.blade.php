@props([
    'active' => null, // ?bool
    'class' => '',
    'external' => null, // ?bool
    'href' => null, // ?string
    'target' => '_self',
    'title' => ''
])

@php
    use Illuminate\Support\Str;

    $external ??= !Str::startsWith($href ?? null, config('app.url'));
    $active ??= Str::startsWith(url()->current(), $href ?? null);
@endphp

<a
    class="{{ $class }} {{ ($active) ? 'active' : '' }}"
    target="{{ $target }}"
    href="{{ $href }}"
    {!! ($external) ? 'rel="noopener"' : '' !!}
    title="{{ $title }}"
>
    {{ $slot }}

    @if ($external)
        <x-fas-external-link-alt />
    @endif
</a>

