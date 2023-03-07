<?php
$link ??= $model->permalink ?? null;
?>
<a class="btn {{ $class ?? null }}" href="{{ $link }}" onclick="clipboard('{{ $link }}');return false" data-toggle="tooltip" title="Permalink">
    {{ svg('fas-' . ($icon ?? 'link'), 'icon') }} {{ $slot ?? null }}
</a>
