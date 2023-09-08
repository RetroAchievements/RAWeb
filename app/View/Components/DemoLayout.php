<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;

class DemoLayout extends AppLayout
{
    public function render(): View
    {
        return view('layouts.demo');
    }
}
