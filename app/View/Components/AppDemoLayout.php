<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;

class AppDemoLayout extends AppLayout
{
    public function render(): View
    {
        return view('layouts.app-demo');
    }
}
