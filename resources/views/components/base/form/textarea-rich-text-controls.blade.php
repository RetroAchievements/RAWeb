@props([
    'id' => null,
])

<?php
// TODO add markdown controls as soon as markdown is supported in addition to shortcode tags
if(!$id) {
    throw new Exception('Missing id attribute');
}
?>

<div class="flex flex-wrap gap-1 mb-2">
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[b]', '[/b]')"
            {{-- x-tooltip="{{ __('Bold') }}" --}} title="{{ __('Bold') }}">
        <x-fas-bold />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[i]', '[/i]')"
            {{-- x-tooltip="{{ __('Italic') }}" --}} title="{{ __('Italic') }}">
        <x-fas-italic />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[u]', '[/u]')"
            {{-- x-tooltip="{{ __('Underline') }}" --}} title="{{ __('Underline') }}">
        <x-fas-underline />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[s]', '[/s]')"
            {{-- x-tooltip="tooltip" --}} title="{{ __('Strikethrough') }}">
        <x-fas-strikethrough />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[code]', '[/code]')"
            {{-- x-tooltip="tooltip" --}} title="{{ __('Code') }}">
        <x-fas-code />
    </button>
     <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[spoiler]', '[/spoiler]')"
            {{-- x-tooltip="tooltip" --}} title="{{ __('Spoiler') }}">
        Spoiler
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[img=', ']')"
            {{-- x-tooltip="tooltip" --}} title="{{ __('Image') }}">
        <x-fas-image />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[url=', ']Link[/url]')"
            {{-- x-tooltip="tooltip" --}} title="{{ __('Link') }}">
        <x-fas-link />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[ach=', ']')"
            {{-- x-tooltip="{{ __res('achievement', 1) }}" --}} title="{{ __res('achievement', 1) }}">
        <x-fas-trophy />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[game=', ']')"
            {{-- x-tooltip="{{ __res('game', 1) }}" --}} title="{{ __res('game', 1) }}">
        <x-fas-gamepad />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[user=', ']')"
            {{-- x-tooltip="{{ __res('user', 1) }}" --}} title="{{ __res('user', 1) }}">
        <x-fas-user />
    </button>
    <button type="button" class="btn" onclick="injectShortcode('{{ $id }}', '[ticket=', ']')"
            {{-- x-tooltip="{{ __res('ticket', 1) }}" --}} title="{{ __res('ticket', 1) }}">
        <x-fas-ticket />
    </button>
</div>
