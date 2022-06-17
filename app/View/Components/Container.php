<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Container extends Component
{
    public function __construct(
        public bool $fluid = false,
    ) {
    }

    public function render(): View
    {
        return view('layouts.components.container');
    }
}
