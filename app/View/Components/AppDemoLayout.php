<?php

namespace App\View\Components;

use Illuminate\View\View;

class AppDemoLayout extends AppLayout
{
    public function render(): View
    {
        return view('layouts.app-demo');
    }
}
