<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

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
