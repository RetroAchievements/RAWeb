<?php

use function Laravel\Folio\{name};

name('demo.error');

?>

@php
    if (!view()->exists('errors.' . $code)) {
        abort(404);
    }
@endphp

@includeIf('errors.' . $code, ['exception' => new Exception('', $code)])
