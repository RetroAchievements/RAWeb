<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    public function __construct(
        public string $breakpoint = 'lg',
        public string $class = '',
        public bool $fluid = false,
    ) {
    }

    public function render(): View
    {
        return view('layouts.components.navbar');
    }
}
