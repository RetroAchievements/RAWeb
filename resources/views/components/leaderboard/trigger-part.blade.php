@props([
    'header' => '',
    'definition' => '',
    'groups' => [],
])

<div>
    <button id="devbox{{ $header }}Button" class="btn"
            onclick="toggleExpander('devbox{{ $header }}Button', 'devbox{{ $header }}Content');">{{ $header }} ▼</button>
    <div id="devbox{{ $header }}Content" class="hidden devboxcontainer">
        <li>Mem:</li>
        <code>{{ $definition }}</code>
        <li>Mem explained:</li>
        <x-trigger.viewer :groups="$groups" prefix="{{ strtolower($header) }}" />
    </div>
</div>
