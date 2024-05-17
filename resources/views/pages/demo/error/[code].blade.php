<?php

use Illuminate\View\View;

use function Laravel\Folio\{name, render};

name('demo.error');

render(function (View $view, int $code) {
    if (!view()->exists('errors.' . $code)) {
        abort(404);
    }

    return $view;
});

?>

@includeIf('errors.' . $code, ['exception' => new Exception('', $code)])
